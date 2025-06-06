<?php

session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

// Database connection
//require 'config.php';

// Fetch Notifications from the database
//$sql = "SELECT id, name, email, phone FROM Notifications";
//$result = $conn->query($sql);


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Notifications</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }
    body { background-color: #3b5870; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 20px; }
    .header { background-color: #2f4a5f; width: 100%; padding: 20px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
    .header h2 { color: #fff; font-size: 28px; }
    .nav-container { display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin: 20px 0; }
    .nav-button, .action-button, .modal-button { background-color: #f4a825; padding: 10px 20px; border-radius: 8px; color: #fff; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; cursor: pointer; }
    .nav-button:hover, .action-button:hover, .modal-button:hover { background-color: #ffcc00; transform: scale(1.05); }
    .button-container { display: flex; justify-content: center; align-items: center; gap: 10px; margin: 20px 0; flex-wrap: wrap; }
    .search-input { padding: 8px 12px; border-radius: 8px; border: none; width: 200px; }
    .Notification-section { width: 100%; max-width: 1000px; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; }
    .Notification-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .Notification-table th, .Notification-table td { border: 1px solid #ccc; padding: 12px; text-align: center; background: #fff; }
    .Notification-table th { background-color: #2f4a5f; color: #fff; }
     /* --- dropdown styles with animation and matching color --- */
     .dropdown {
      position: relative;
      display: inline-block;
    }
    .dropdown-toggle {
      background-color: #f4a825;
      color: #fff;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
    }
    .dropdown-toggle:hover {
      background-color: #ffcc00;
      transform: scale(1.05);
    }
    .dropdown-menu {
      display: none;
      position: absolute;
      top: 100%;
      left: 0;
      background-color: #f4a825;
      min-width: 200px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      border-radius: 8px;
      overflow: hidden;
      z-index: 100;
      animation: fadeInMenu 0.3s ease;
    }
    .dropdown-menu a {
      display: block;
      padding: 10px 15px;
      color: #fff;
      text-decoration: none;
      transition: background 0.2s;
    }

    .dropdown-menu a:hover {
      background-color: #ffcc00;
    }
   
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeInMenu { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>
  <div class="header">
    <h2>Notification</h2>
    <div class="nav-container">
        <a class="nav-button" href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a class="nav-button" href="truck.php"><i class="fas fa-truck"></i> Trucks</a>
        <a class="nav-button" href="driver.php"><i class="fas fa-user"></i> Drivers</a>

        <!-- Maintenance dropdown -->
          <div class="dropdown">
            <div class="dropdown-toggle"><i class="fas fa-wrench"></i> Maintenance <i class="fas fa-caret-down"></i></div>
            <div class="dropdown-menu">
            <a href="maintenance_log.php"><i class="fas fa-plus-circle"></i> Log Maintenance Schedule</a>
              <a href="maintenance.php"><i class="fas fa-history"></i> View Maintenance History</a>
            </div>
          </div>

        <a class="nav-button" href="reports.php"><i class="fas fa-bullhorn"></i> Announcements</a>
        <a class="nav-button" href="notification.php"><i class="fas fa-bell"></i> Notification</a>   
        <a class="nav-button" href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout </a>
      </div>
  </div>

 
  <script>
  // dropdown menue
    document.getElementById("myDropdown").style.display = "none";
    function openNav() {
        document.getElementById("myDropdown").style.display = "block";
    }
    function closeNav() {
        document.getElementById("myDropdown").style.display = "none";
    }
  </script>
</body>
</html>
