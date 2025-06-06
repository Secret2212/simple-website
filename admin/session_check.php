<?php
// Common file to include in all protected pages
session_start();

// Function to check if user is logged in and has the correct role
function checkUserAccess($allowed_roles = []) {
    // Check if user is logged in
    if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
        header("Location: ../index.html");
        exit;
    }
    
    // If specific roles are required
    if (!empty($allowed_roles)) {
        if (!in_array($_SESSION["role"], $allowed_roles)) {
            // Redirect to appropriate dashboard based on role
            switch($_SESSION["role"]) {
                case "admin":
                    header("Location: ../admin/dashboard.php");
                    break;
                case "dispatcher":
                    header("Location: ../dispatcher/dashboard.php");
                    break;
                case "driver":
                    header("Location: ../driver/dashboard.php");
                    break;
                case "bookkeeper":
                    header("Location: ../bookkeeper/dashboard.php");
                    break;
                default:
                    header("Location: ../index.html");
                    break;
            }
            exit;
        }
    }
    
    return true;
}
?>