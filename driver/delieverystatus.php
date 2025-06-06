<?php
session_start();

// Redirect if not logged in or wrong role
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || $_SESSION["role"] !== "driver") {
    header("Location: ../index.php");
    exit();
}

// Check if driver ID is set
if (!isset($_SESSION["user_id"])) {
    echo "Driver ID not found in session.";
    exit();
}

$driver_id = $_SESSION["user_id"];

// Include database connection
include 'db_connect/db_connect.php'; // Adjust path if needed

// Prepare query
$sql = "SELECT * FROM deliveries WHERE driver_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Delivery Status</title>
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
    .driver-section { width: 100%; max-width: 1000px; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; }
    .delivery-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .delivery-table th, .delivery-table td { border: 1px solid #ccc; padding: 12px; text-align: center; background: #fff; }
    .delivery-table th { background-color: #2f4a5f; color: #fff; }
    /* Modal styles */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; animation: fadeIn 0.5s ease; }
    .modal { background: #fff; border-radius: 10px; width: 90%; max-width: 400px; padding: 20px; animation: slideDown 0.5s ease; position: relative; }
    .modal h3 { margin-bottom: 15px; color: #2f4a5f; }
    .modal label { display: block; margin: 10px 0 5px; color: #333; }
    .modal input, .modal select { width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; }
    .modal-close { position: absolute; top: 10px; right: 10px; font-size: 18px; cursor: pointer; color: #666; }
    
    /* Frame styling */
    .frame {
      background-color: #2f4a5f;
      border-radius: 10px;
      padding: 20px;
      margin-top: 30px;
      width: 100%;
      max-width: 600px;
    }
    
    .frame-content {
      background-color: #fff;
      border-radius: 5px;
      padding: 20px;
    }
    
    .frame-title {
      margin-bottom: 15px;
      font-size: 18px;
      font-weight: bold;
    }
    
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>
<div class="header">
  <h2>Delivery Stat</h2>
  <div class="nav-container">
     <a class="nav-button" href="delieverystatus.php"><i class="fas fa-route"></i> Delivery Status</a>
    <a class="nav-button" href="notification.php"><i class="fas fa-bell"></i> Notifications</a>
    <a class="nav-button" href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>

<div class="frame">
  <div class="frame-content">
    <div class="frame-title">Delivery Status Update</div>
    
    <div class="search-container">
      <input type="text" class="search-input" placeholder="🔍">
    </div>
    
    <table class="delivery-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Product Type</th>
          <th>Status Update</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
       
        <?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row["delivery_id"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["product_name"]) . "</td>";
        echo "<td>";
if ($row["status"] !== "Delivered") {
    echo '<form method="POST" action="update_delivery_status.php" style="display:inline;">
            <input type="hidden" name="delivery_id" value="' . htmlspecialchars($row["delivery_id"]) . '">
            <button type="submit" class="action-button">Mark as Delivered</button>
          </form>';
} else {
    echo '<span style="color:green;font-weight:bold;">Delivered</span>';
}
echo "</td>";

        echo "<td>" . htmlspecialchars($row["dispatch_date"]) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='4' style='text-align: center;'>No deliveries found</td></tr>";
}
?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>