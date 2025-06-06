<?php
// Database connection
// require 'config.php';
include 'db_connect/db_connect.php';

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'An error occurred'
];

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $vendor = isset($_POST['vendor']) ? trim($_POST['vendor']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $amount = isset($_POST['amount']) ? filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT) : 0;
    $due_date = isset($_POST['due_date']) ? trim($_POST['due_date']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'Unpaid';
    
    // Validate required fields
    if (empty($vendor) || $amount <= 0 || empty($due_date)) {
        $response['message'] = 'Please fill all required fields with valid data';
    } else {
        // Format date for database
        $formatted_date = date('Y-m-d', strtotime($due_date));
        
        // If database connection is available
        
        // Prepare SQL statement
        $sql = "INSERT INTO finance_records (vendor, description, amount, due_date, status, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdss", $vendor, $description, $amount, $formatted_date, $status);
        
        // Execute query
        if ($stmt->execute()) {
            $response = [
                'status' => 'success',
                'message' => 'Financial record added successfully',
                'record_id' => $conn->insert_id
            ];
        } else {
            $response['message'] = 'Database error: ' . $conn->error;
        }
        
        $stmt->close();
        
        
        // For demonstration without database
        $response = [
            'status' => 'success',
            'message' => 'Financial record added successfully (simulation)',
            'record_id' => rand(10, 100)
        ];
    }
}

// Return JSON response if AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Redirect if normal form submission
$redirect_url = 'accounts_finance.php';
if ($response['status'] == 'success') {
    $redirect_url .= '?success=1&message=' . urlencode($response['message']);
} else {
    $redirect_url .= '?error=1&message=' . urlencode($response['message']);
}

header('Location: ' . $redirect_url);
exit;