<?php
session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || $_SESSION["role"] !== "driver") {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delivery_id"])) {
    include 'db_connect/db_connect.php'; // Adjust path if needed

    $delivery_id = $_POST["delivery_id"];
    $sql = "UPDATE deliveries SET status = 'Delivered' WHERE delivery_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delivery_id);

    if ($stmt->execute()) {
        header("Location: delieverystatus.php?success=1");
    } else {
        echo "Error updating status: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
