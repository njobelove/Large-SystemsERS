
<?php
// JSON summary for marketing analytics
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';
header('Content-Type: application/json');

try {
    $summary = [
        'campaigns' => ['count' => 0, 'spend' => 0.0],
        'leads' => ['count' => 0],
        'conversions' => ['count' => 0, 'revenue' => 0.0],
        'metrics' => ['conversion_rate' => 0.0, 'roi' => 0.0]
    ];

    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Fetch campaigns data
    if ($conn->query("SHOW TABLES LIKE 'campaigns'")->num_rows) {
        $stmt = $conn->prepare("SELECT COUNT(*) c, COALESCE(SUM(spend),0) s FROM campaigns");
        if (!$stmt) {
            throw new Exception('Failed to prepare campaigns query');
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $summary['campaigns']['count'] = (int)($row['c'] ?? 0);
        $summary['campaigns']['spend'] = (float)($row['s'] ?? 0.0);
        $stmt->close();
    }

    // Fetch leads data
    if ($conn->query("SHOW TABLES LIKE 'leads'")->num_rows) {
        $stmt = $conn->prepare("SELECT COUNT(*) c FROM leads");
        if (!$stmt) {
            throw new Exception('Failed to prepare leads query');
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $summary['leads']['count'] = (int)($row['c'] ?? 0);
        $stmt->close();
    }

    // Fetch conversions data
    if ($conn->query("SHOW TABLES LIKE 'conversions'")->num_rows) {
        $stmt = $conn->prepare("SELECT COUNT(*) c, COALESCE(SUM(revenue),0) r FROM conversions");
        if (!$stmt) {
            throw new Exception('Failed to prepare conversions query');
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $summary['conversions']['count'] = (int)($row['c'] ?? 0);
        $summary['conversions']['revenue'] = (float)($row['r'] ?? 0.0);
        $stmt->close();
    }

    // Calculate metrics
    $leads = $summary['leads']['count'];
    $convs = $summary['conversions']['count'];
    $spend = $summary['campaigns']['spend'];
    $revenue = $summary['conversions']['revenue'];

    $summary['metrics']['conversion_rate'] = $leads > 0 ? round(($convs / $leads) * 100, 2) : 0.0;
    $summary['metrics']['roi'] = $spend > 0 ? round((($revenue - $spend) / $spend) * 100, 2) : 0.0;

    echo json_encode($summary, JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to build marketing summary', 'details' => $e->getMessage()], JSON_PRETTY_PRINT);
}
