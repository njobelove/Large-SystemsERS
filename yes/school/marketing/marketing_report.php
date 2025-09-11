<?php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

// Fetch monthly spend and revenue data
$monthlyData = [];
if ($conn->query("SHOW TABLES LIKE 'campaigns'")->num_rows && $conn->query("SHOW TABLES LIKE 'conversions'")->num_rows) {
    $sql = "SELECT 
                DATE_FORMAT(c.created_at, '%Y-%m') AS month,
                COALESCE(SUM(c.spend), 0) AS spend,
                COALESCE(SUM(cv.revenue), 0) AS revenue
            FROM campaigns c
            LEFT JOIN conversions cv ON c.id = cv.campaign_id
            GROUP BY DATE_FORMAT(c.created_at, '%Y-%m')
            ORDER BY month";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $monthlyData[] = $row;
        }
    }
}

// Total summary
$totalSpend = 0;
$totalRevenue = 0;
foreach ($monthlyData as $data) {
    $totalSpend += (float)$data['spend'];
    $totalRevenue += (float)$data['revenue'];
}
$netGain = $totalRevenue - $totalSpend;

// Fetch total leads and conversions for pie chart
$leadConversionData = [];
$totalLeads = 0;
$totalConversions = 0;
if ($conn->query("SHOW TABLES LIKE 'leads'")->num_rows) {
    $totalLeads = $conn->query("SELECT COUNT(*) AS count FROM leads")->fetch_assoc()['count'] ?? 0;
}
if ($conn->query("SHOW TABLES LIKE 'conversions'")->num_rows) {
    $totalConversions = $conn->query("SELECT COUNT(*) AS count FROM conversions")->fetch_assoc()['count'] ?? 0;
}
$leadConversionData = [
    ['label' => 'Leads', 'count' => $totalLeads],
    ['label' => 'Conversions', 'count' => $totalConversions]
];
?>
<?php
include '../include/finance_header.php';
?>

<h2>Marketing Report</h2>

<div class="summary-cards">
    <div class="card">
        <h3>Total Spend</h3>
        <div class="val">FCFA <?= number_format($totalSpend, 2) ?></div>
    </div>
    <div class="card">
        <h3>Total Revenue</h3>
        <div class="val">FCFA <?= number_format($totalRevenue, 2) ?></div>
    </div>
    <div class="card">
        <h3>Net Gain</h3>
        <div class="val">FCFA <?= number_format($netGain, 2) ?></div>
    </div>
</div>

<div class="chart-container">
    <h3>Monthly Spend and Revenue</h3>
    <canvas id="spendRevenueChart" width="250" height="125"></canvas>
</div>

<div class="chart-container">
    <h3>Leads vs Conversions</h3>
    <canvas id="leadStatusChart" width="120" height="60"></canvas>
    <div class="color-legend">
        <div class="legend-item"><span class="color-box" style="background-color: #FF6384;"></span> Leads</div>
        <div class="legend-item"><span class="color-box" style="background-color: #36A2EB;"></span> Conversions</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('spendRevenueChart').getContext('2d');
    const monthlyData = <?php echo json_encode($monthlyData); ?>;
    const labels = monthlyData.map(d => d.month);
    const spendData = monthlyData.map(d => parseFloat(d.spend));
    const revenueData = monthlyData.map(d => parseFloat(d.revenue));

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Spend (FCFA)',
                data: spendData,
                borderColor: '#d9534f',
                backgroundColor: 'rgba(217, 83, 79, 0.2)',
                fill: true
            }, {
                label: 'Revenue (FCFA)',
                data: revenueData,
                borderColor: '#5cb85c',
                backgroundColor: 'rgba(92, 184, 92, 0.2)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Pie chart for leads vs conversions
    const pieCtx = document.getElementById('leadStatusChart').getContext('2d');
    const leadConversionData = <?php echo json_encode($leadConversionData); ?>;
    const pieLabels = leadConversionData.map(d => d.label);
    const pieData = leadConversionData.map(d => parseInt(d.count));

    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB'
                ],
                hoverBackgroundColor: [
                    '#FF6384',
                    '#36A2EB'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return tooltipItem.label + ': ' + tooltipItem.raw;
                        }
                    }
                }
            }
        }
    });
</script>

<?php
include '../include/finance_footer.php';
?>
