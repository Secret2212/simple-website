<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || $_SESSION["role"] !== "dispatcher") {
    header("Location: ../index.php");
    exit();
}

include 'db_connect/db_connect.php';


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_delivery"])) {
    $productName = $_POST["product_name"];
    $driverId = $_POST["driver_id"];

    $sql = "INSERT INTO deliveries (product_name, driver_id, status, dispatch_date) 
            VALUES (?, ?, 'Pending', NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $productName, $driverId);

    if ($stmt->execute()) {
        echo "<script>alert('Delivery created successfully!'); window.location.href='delieveries.php';</script>";
    } else {
        echo "<div style='color:red;'>Error: " . htmlspecialchars($stmt->error) . "</div>";
    }

    $stmt->close();
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delivery Management System</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #2c4b63;
      color: white;
      margin: 0;
      padding: 0;
    }
    
    .header { background-color: #2f4a5f; width: 100%; padding: 20px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
    .header h2 { color: #fff; font-size: 28px; }
    .nav-container { display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin: 20px 0; }
    .nav-button { background-color: #f4a825; padding: 10px 20px; border-radius: 8px; color: #fff; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; cursor: pointer; }
    .nav-button:hover { background-color: #ffcc00; transform: scale(1.05); }
    
    .content-wrapper {
      display: flex;
      justify-content: space-between;
      margin: 20px;
      flex-wrap: wrap;
    }
    
    .card {
      background-color: #3a5d7a;
      align-items: center;
      width: 30%;
      border-radius: 10px;
      padding: 20px;
      margin: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      flex: 1;
      min-width: 300px;
    }
    
    .card h2 {
      margin-top: 0;
      border-bottom: 1px solid #4d7494;
      padding-bottom: 10px;
    }
    
    input[type="text"], input[type="number"], select {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border-radius: 5px;
      border: 1px solid #ccc;
      box-sizing: border-box;
    }
    
    button {
      background-color: #f9b233;
      color: white;
      border: none;
      padding: 10px 20px;
      margin: 10px 0;
      border-radius: 5px;
      cursor: pointer;
      width: 100%;
      font-weight: bold;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background-color: rgba(255, 255, 255, 0.1);
    }
    
    th {
      background-color: #f9b233;
      padding: 10px;
      text-align: left;
    }
    
    td {
      padding: 10px;
      border-bottom: 1px solid #4d7494;
    }
    
    tr:nth-child(even) {
      background-color: rgba(255, 255, 255, 0.05);
    }
    
    .validation-message {
      color: #f9b233;
      background-color: rgba(0, 0, 0, 0.2);
      padding: 5px 10px;
      border-radius: 3px;
      font-size: 12px;
      margin-top: -8px;
      display: inline-block;
    }
    
    .truck-icon {
      margin-right: 5px;
    }
    
    .truck-type {
      display: flex;
      align-items: center;
    }
    
    /* Responsive design */
     @media (max-width: 768px) {
      .nav-container {
        flex-direction: column;
        align-items: center;
      }
      
      .notifications-container {
        padding: 0 10px;
      }
      
      .stats-bar {
        flex-direction: column;
        gap: 10px;
        text-align: center;
      }
      
      .notification-header {
        flex-direction: column;
        gap: 10px;
      }
      
      .notification-status {
        margin-left: 0;
        align-items: flex-start;
      }
    } 
  </style>
</head>
<body>
 <div class="header">
    <h2>Notifications</h2>
    <div class="nav-container">
      <a class="nav-button" href="trucklocation.php"><i class="fas fa-truck"></i> Trucking Location</a>
      <a class="nav-button" href="delieveries.php"><i class="fas fa-user"></i> Manage Deliveries</a>
      <a class="nav-button" href="notification.php"><i class="fas fa-bell"></i> Notifications</a>
      <a class="nav-button" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>

<div class="content-wrapper">

  <!-- LEFT CARD: Show created deliveries -->
  <div class="card">
    <h2>Assigned Deliveries</h2>
    <table>
      <thead>
        <tr>
          <th>Delivery ID</th>
          <th>Product</th>
          <th>Driver</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $sql = "SELECT d.*, u.FirstName, u.LastName 
                  FROM deliveries d
                  JOIN users u ON d.driver_id = u.id
                  ORDER BY d.dispatch_date DESC";
          $result = $conn->query($sql);

          while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["delivery_id"] . "</td>";
            echo "<td>" . htmlspecialchars($row["product_name"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["FirstName"]) . " " . htmlspecialchars($row["LastName"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["status"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["dispatch_date"]) . "</td>";
            echo "</tr>";
          }
        ?>
      </tbody>
    </table>
  </div>

  <!-- RIGHT CARD: Create delivery -->
  <div class="card">
    <h2>Create New Delivery</h2>
    <form method="POST">
      <input type="text" name="product_name" placeholder="Product Name" required>

      <select name="truck_id" required>
  <option value="">Select Truck</option>
  <?php
    $truck_query = "SELECT id, truck_type, plate_number, unit_number FROM trucks";
    $trucks = $conn->query($truck_query);
    while ($truck = $trucks->fetch_assoc()) {
        $display = "{$truck['truck_type']} - {$truck['plate_number']} ({$truck['unit_number']})";
        echo "<option value='{$truck['id']}'>$display</option>";
    }
  ?>
</select>


      <!-- Driver dropdown -->
      <select name="driver_id" required>
        <option value="">Select Driver</option>
        <?php
          $driver_query = "SELECT id, FirstName, LastName FROM users WHERE role = 'driver'";
          $drivers = $conn->query($driver_query);
          while ($driver = $drivers->fetch_assoc()) {
              echo "<option value='{$driver['id']}'>{$driver['FirstName']} {$driver['LastName']}</option>";
          }
        ?>
      </select>

      <input class="nav-button" type="submit" name="create_delivery" value="Create Delivery">
    </form>
  </div>
</div>

  
</body>
</html>