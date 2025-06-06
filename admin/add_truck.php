<?php
include 'db_connect/db_connect.php'; // Database connection

if(isset($_POST['add_truck'])) {
    // Get form data and sanitize
    $truck_type = mysqli_real_escape_string($conn, $_POST['truck_type']);
    $unit_number = mysqli_real_escape_string($conn, $_POST['unit_number']);
    $plate_number = mysqli_real_escape_string($conn, $_POST['plate_number']);
    
    // SQL query to insert data
    $sql = "INSERT INTO trucks (truck_type, unit_number, plate_number) VALUES ('$truck_type', '$unit_number', '$plate_number')";
    
    if ($conn->query($sql) === TRUE) {
        // Success: Redirect back to truck page
        header("Location: truck.php");
        exit();
    } else {
        // Error: Display error message
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// If not a POST request, redirect to truck page
if(!isset($_POST['add_truck'])) {
    header("Location: truck.php");
    exit();
}
?>