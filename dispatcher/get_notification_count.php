<?php
// Filename: get_notification_count.php
// This file serves as an AJAX endpoint to get the current notification count

include 'db_connect/db_connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    // Return 0 if not logged in
    echo json_encode(['count' => 0]);
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Query to count unread notifications
$query = "
    SELECT COUNT(*) as count 
    FROM reports r
    JOIN report_visibility v ON r.report_id = v.report_id
    LEFT JOIN notification_status ns ON r.report_id = ns.report_id AND ns.user_id = $userId
    WHERE (v.role = '$userRole' OR v.id = $userId)
    AND (ns.read_status IS NULL OR ns.read_status = 0)
";

$result = mysqli_query($conn, $query);
$count = 0;

if ($result && $row = mysqli_fetch_assoc($result)) {
    $count = (int)$row['count'];
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode(['count' => $count]);