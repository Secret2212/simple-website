<?php
include 'db_connect/db_conn.php'; // Database connection

if(isset($_POST['update_driver'])) {
    // Get form data and sanitize
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $fullName = mysqli_real_escape_string($conn, $_POST['FullName']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $contactNumber = mysqli_real_escape_string($conn, $_POST['contact_number']);
    
    // SQL query to update data
    $sql = "UPDATE drivers SET FullName='$fullName', address='$address', contact_number='$contactNumber' WHERE id='$id'";
    
    if ($conn->query($sql) === TRUE) {
        // Success: Redirect back to driver page
        header("Location: driver.php");
        exit();
    } else {
        // Error: Display error message
        echo "Error updating record: " . $conn->error;
    }
}

// If not a POST request, redirect to driver page
if(!isset($_POST['update_driver'])) {
    header("Location: driver.php");
    exit();
}
?>