<?php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

$data = [];

// Get total invoice amount
$result = $conn->query("SELECT SUM(amount) as total_invoices FROM invoices");
$data['total_invoices'] = $result->fetch_assoc()['total_invoices'] ?? 0;

// Get total payments received
$result = $conn->query("SELECT SUM(amount) as total_payments FROM payments");
$data['total_payments'] = $result->fetch_assoc()['total_payments'] ?? 0;

// Calculate outstanding dues
$data['outstanding_dues'] = $data['total_invoices'] - $data['total_payments'];

header('Content-Type: application/json');
echo json_encode($data);
