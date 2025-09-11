<?php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

// Aggregate metrics for KPI cards
$totals = [
    'campaigns' => 0,
    'leads' => 0,
    'new_leads_30d' => 0,
    'conversions' => 0,
    'spend' => 0.0,
    'revenue' => 0.0,
    'conversion_rate' => 0.0,
    'roi' => 0.0,
];

if ($conn->query("SHOW TABLES LIKE 'campaigns'")->num_rows) {
    $row = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(spend),0) s FROM campaigns")->fetch_assoc();
    $totals['campaigns'] = (int)($row['c'] ?? 0);
    $totals['spend'] = (float)($row['s'] ?? 0);
}
if ($conn->query("SHOW TABLES LIKE 'leads'")->num_rows) {
    $row = $conn->query("SELECT COUNT(*) c FROM leads")->fetch_assoc();
    $totals['leads'] = (int)($row['c'] ?? 0);

    $row_new = $conn->query("SELECT COUNT(*) c FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc();
    $totals['new_leads_30d'] = (int)($row_new['c'] ?? 0);
}
if ($conn->query("SHOW TABLES LIKE 'conversions'")->num_rows) {
    $row = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(revenue),0) r FROM conversions")->fetch_assoc();
    $totals['conversions'] = (int)($row['c'] ?? 0);
    $totals['revenue'] = (float)($row['r'] ?? 0);
}
$totals['conversion_rate'] = $totals['leads'] > 0 ? round(($totals['conversions'] / $totals['leads']) * 100, 2) : 0.0;
$totals['roi'] = $totals['spend'] > 0 ? round((($totals['revenue'] - $totals['spend']) / $totals['spend']) * 100, 2) : 0.0;

// Top campaigns table (by revenue desc)
$topCampaigns = [];
if ($conn->query("SHOW TABLES LIKE 'campaigns'")->num_rows && $conn->query("SHOW TABLES LIKE 'leads'")->num_rows && $conn->query("SHOW TABLES LIKE 'conversions'")->num_rows) {
    $sql = "SELECT 
                c.id,
                c.name,
                c.spend,
                COALESCE(ls.leads_count, 0) AS leads_count,
                COALESCE(cs.conv_count, 0) AS conv_count,
                COALESCE(cs.revenue_sum, 0) AS revenue_sum,
                (CASE WHEN c.spend > 0 THEN ((COALESCE(cs.revenue_sum, 0) - c.spend) / c.spend) * 100 ELSE 0 END) AS roi,
                (CASE WHEN ls.leads_count > 0 THEN (COALESCE(cs.conv_count, 0) / ls.leads_count) * 100 ELSE 0 END) AS conv_rate
            FROM campaigns c
            LEFT JOIN (
                SELECT campaign_id, COUNT(id) AS leads_count 
                FROM leads 
                GROUP BY campaign_id
            ) AS ls ON c.id = ls.campaign_id
            LEFT JOIN (
                SELECT campaign_id, COUNT(id) AS conv_count, SUM(revenue) AS revenue_sum 
                FROM conversions 
                GROUP BY campaign_id
            ) AS cs ON c.id = cs.campaign_id
            ORDER BY revenue_sum DESC, c.id DESC
            LIMIT 10";
    
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $topCampaigns[] = $row;
        }
    }
}

// Find max value for chart scaling
$max_val = 0;
foreach ($topCampaigns as $c) {
    if ($c['revenue_sum'] > $max_val) $max_val = $c['revenue_sum'];
    if ($c['spend'] > $max_val) $max_val = $c['spend'];
}
$max_val = $max_val > 0 ? $max_val : 1; // Avoid division by zero

?>
<?php
include '../include/finance_header.php';
?>

<h2>Marketing Dashboard</h2>

<div class="cards">
    <div class="card"><h3>Total Campaigns</h3><div class="val"><?= (int)$totals['campaigns'] ?></div></div>
    <div class="card"><h3>Total Leads</h3><div class="val"><?= (int)$totals['leads'] ?></div></div>
    <div class="card"><h3>New Leads (30d)</h3><div class="val"><?= (int)$totals['new_leads_30d'] ?></div></div>
    <div class="card"><h3>Total Conversions</h3><div class="val"><?= (int)$totals['conversions'] ?></div></div>
    <div class="card"><h3>Total Spend</h3><div class="val">FCFA <?= number_format((float)$totals['spend'], 2) ?></div></div>
    <div class="card"><h3>Total Revenue</h3><div class="val">FCFA <?= number_format((float)$totals['revenue'], 2) ?></div></div>
    <div class="card"><h3>Conversion Rate</h3><div class="val"><?= number_format((float)$totals['conversion_rate'], 2) ?>%</div></div>
    <div class="card"><h3>ROI</h3><div class="val"><?= number_format((float)$totals['roi'], 2) ?>%</div></div>
</div>

<div class="actions">
    <a href="create_campaign.php" class="nav-link">Create Campaign</a>
    <a href="list_campaigns.php" class="nav-link">List Campaigns</a>
    <a href="create_lead.php" class="nav-link">Create Lead</a>
    <a href="list_leads.php" class="nav-link">List Leads</a>
    <a href="record_conversion.php" class="nav-link">Record Conversion</a>
    <a href="list_conversions.php" class="nav-link">List Conversions</a>
    <a href="marketing_report.php" class="nav-link">View Report</a>
</div>

<div class="chart-container">
    <h3>Campaign Performance (Revenue vs. Spend)</h3>
    <div class="legend">
        <span><span class="swatch" style="background:#5cb85c;"></span>Revenue</span>
        <span><span class="swatch" style="background:#d9534f;"></span>Spend</span>
    </div>
    <div class="chart">
        <?php foreach ($topCampaigns as $c): ?>
            <?php
                $rev_height = round(((float)$c['revenue_sum'] / $max_val) * 100);
                $spend_height = round(((float)$c['spend'] / $max_val) * 100);
            ?>
            <div class="bar-group" title="<?= htmlspecialchars($c['name']) ?>">
                <div class="bar revenue" style="height: <?= $rev_height ?>%;" title="Revenue: <?= number_format((float)$c['revenue_sum'], 2) ?>"></div>
                <div class="bar spend" style="height: <?= $spend_height ?>%;" title="Spend: <?= number_format((float)$c['spend'], 2) ?>"></div>
                <div class="label">ID: <?= (int)$c['id'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
include '../include/finance_footer.php';
?>
