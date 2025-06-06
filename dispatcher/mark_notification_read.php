<?php
// Filename: mark_notification_read.php
// This file handles marking notifications as read

include 'db_connect/db_connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

// Check if report_id is provided
if (!isset($_POST['report_id']) || !is_numeric($_POST['report_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
    exit();
}

$reportId = (int)$_POST['report_id'];

// First check if a record exists
$checkQuery = "SELECT * FROM notification_status WHERE report_id = $reportId AND user_id = $userId";
$checkResult = mysqli_query($conn, $checkQuery);

if (mysqli_num_rows($checkResult) > 0) {
    // Update existing record
    $updateQuery = "UPDATE notification_status SET read_status = 1, read_at = NOW() WHERE report_id = $reportId AND user_id = $userId";
    $success = mysqli_query($conn, $updateQuery);
} else {
    // Insert new record
    $insertQuery = "INSERT INTO notification_status (report_id, user_id, read_status, read_at) VALUES ($reportId, $userId, 1, NOW())";
    $success = mysqli_query($conn, $insertQuery);
}

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}