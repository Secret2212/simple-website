<?php
include 'db_connect/db_connect.php'; // Database connection

if(isset($_POST['add_driver'])) {
    // Get form data and sanitize
    $fullName = mysqli_real_escape_string($conn, $_POST['FullName']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $contactNumber = mysqli_real_escape_string($conn, $_POST['contact_number']);
    
    // SQL query to insert data
    $sql = "INSERT INTO drivers (FullName, address, contact_number) VALUES ('$fullName', '$address', '$contactNumber')";
    
    if ($conn->query($sql) === TRUE) {
        // Success: Redirect back to driver page
        header("Location: driver.php");
        exit();
    } else {
        // Error: Display error message
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// If not a POST request, redirect to driver page
if(!isset($_POST['add_driver'])) {
    header("Location: driver.php");
    exit();
}
?>