<?php
// Start session to maintain login state
session_start();

// Database connection parameters
$host = "localhost";
$dbname = "truckingsystem";
$username = "root"; // Replace with your actual database username
$password = ""; // Replace with your actual database password

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user input
    $email = $_POST["email"];
    $pass = $_POST["password"];  // This variable stores the password from the form
    $role = $_POST["role"];
    
    // Connect to database
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        // Set PDO to throw exceptions on error
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND role = :role");
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":role", $role);
        $stmt->execute();
        
        // Check if user exists
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password - FIXED: Use $pass instead of $password which is the database password
            if ($user["password"] == $pass) { // Note: In a production environment, use password_verify() with hashed passwords
                // Authentication successful
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["firstname"] = $user["FirstName"];
                $_SESSION["lastname"] = $user["LastName"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["logged_in"] = true;
                
                // Redirect based on role
                switch($role) {
                    case "admin":
                        header("Location: admin/dashboard.php");
                        break;
                    case "dispatcher":
                        header("Location: dispatcher/delieveries.php");
                        break;
                    case "driver":
                        header("Location: driver/delieverystatus.php");
                        break;
                    case "bookkeeper":
                        header("Location: bookkeeper/invoice_history.php");
                        break;
                    default:
                        header("Location: index.html");
                        break;
                }
                exit();
            } else {
                $error_message = "Invalid password!";
                // Redirect back to login page with error
                header("Location: index.php?error=invalid_password");
                exit();
            }
        } else {
            $error_message = "User not found with the provided email and role!";
            // Redirect back to login page with error
            header("Location: index.php?error=user_not_found");
            exit();
        }
    } catch(PDOException $e) {
        $error_message = "Database Error: " . $e->getMessage();
        // Redirect back to login page with error
        header("Location: index.php?error=database_error");
        exit();
    }
    
    // Close connection
    $conn = null;
}
?>