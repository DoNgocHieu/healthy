<?php
require_once __DIR__ . '/../config/OrderAdmin.php';
header('Content-Type: application/json');

$orderAdmin = new OrderAdmin();
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$dateFrom = sprintf('%04d-%02d-01', $year, $month);
$dateTo = date('Y-m-t', strtotime($dateFrom));
$stats = $orderAdmin->getOrderStats($dateFrom, $dateTo);
echo json_encode($stats);
