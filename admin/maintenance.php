<?php
session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

include 'db_connect/db_connect.php';



// Fetch all maintenance records with truck details
$sql = "SELECT ms.*, 
        t.truck_type, t.unit_number, t.plate_number 
        FROM maintenance_schedule ms
        LEFT JOIN trucks t ON ms.truck_id = t.id
        ORDER BY ms.scheduled_date DESC, ms.scheduled_time DESC";
$result = $conn->query($sql);


// Status update handling
if(isset($_POST['update_status'])) {
  $update_id = intval($_POST['maintenance_id']);
  $new_status = $_POST['status'];
  $completion_date = null;
  
  // If status is completed, set completion date to current date
  if($new_status === 'Completed') {
    $completion_date = date('Y-m-d');
  }
  
  // Check if completion_date column exists, if not create it
  $check_column = $conn->query("SHOW COLUMNS FROM maintenance_schedule LIKE 'completion_date'");
  if($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE maintenance_schedule ADD COLUMN completion_date DATE NULL");
    $conn->query("ALTER TABLE maintenance_schedule ADD COLUMN status VARCHAR(20) DEFAULT 'Pending'");
  }
  
  $update_sql = "UPDATE maintenance_schedule SET status = ?, completion_date = ? WHERE id = ?";
  $stmt = $conn->prepare($update_sql);
  $stmt->bind_param('ssi', $new_status, $completion_date, $update_id);
  
  if($stmt->execute()) {
    header("Location: maintenance.php?updated=true");
    exit();
  }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Maintenance Records</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }
    body { background-color: #3b5870; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 20px; }
    .header { background-color: #2f4a5f; width: 100%; padding: 20px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
    .header h2 { color: #fff; font-size: 28px; }
    .nav-container { display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin: 20px 0; }
    .nav-button, .action-button, .modal-button { background-color: #f4a825; padding: 10px 20px; border-radius: 8px; color: #fff; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; cursor: pointer; border: none; }
    .nav-button:hover, .action-button:hover, .modal-button:hover { background-color: #ffcc00; transform: scale(1.05); }
    .button-container { display: flex; justify-content: center; align-items: center; gap: 10px; margin: 20px 0; flex-wrap: wrap; }
    .search-input { padding: 8px 12px; border-radius: 8px; border: none; width: 300px; }
    .maintenance-section { width: 100%; max-width: 1200px; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; color: #fff; }
    .maintenance-table { width: 100%; border-collapse: collapse; margin-top: 10px; overflow-x: auto; }
    .maintenance-table th, .maintenance-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    .maintenance-table th { background-color: #2f4a5f; color: #fff; position: sticky; top: 0; }
    .maintenance-table tr { background-color: rgba(255, 255, 255, 0.9); color: #333; }
    .maintenance-table tr:nth-child(even) { background-color: rgba(242, 242, 242, 0.9); }
    .maintenance-table tr:hover { background-color: rgba(221, 221, 221, 0.9); }
    
    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: bold;
      display: inline-block;
    }
    .status-pending { background-color: #f0ad4e; color: #fff; }
    .status-in-progress { background-color: #5bc0de; color: #fff; }
    .status-completed { background-color: #5cb85c; color: #fff; }
    .status-cancelled { background-color: #d9534f; color: #fff; }
    
    /* Action button styles */
    .table-actions { display: flex; gap: 5px; }
    .btn-edit { background-color: #5bc0de; }
    .btn-status { background-color: #5cb85c; }
    
    /* Dropdown styles */
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
    
    /* Modal styles */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.6);
      justify-content: center;
      align-items: center;
      z-index: 1000;
      animation: fadeIn 0.3s ease;
    }
    .modal {
      background: #fff;
      border-radius: 10px;
      width: 90%;
      max-width: 400px;
      padding: 20px;
      position: relative;
      animation: slideDown 0.3s ease;
    }
    .modal h3 {
      margin-bottom: 15px;
      color: #2f4a5f;
    }
    .modal-close {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 24px;
      cursor: pointer;
      color: #666;
    }
    .modal-form {
      display: flex;
      flex-direction: column;
    }
    .modal-form label {
      margin-top: 10px;
      color: #333;
    }
    .modal-form select {
      padding: 8px;
      margin-top: 5px;
      border-radius: 5px;
      border: 1px solid #ddd;
    }
    .modal-form button {
      margin-top: 20px;
    }
    
    .alert {
      padding: 10px 15px;
      border-radius: 5px;
      margin-bottom: 15px;
      font-weight: bold;
    }
    .alert-success {
      background-color: rgba(92, 184, 92, 0.2);
      color: #5cb85c;
    }
    .alert-info {
      background-color: rgba(91, 192, 222, 0.2);
      color: #5bc0de;
    }
    
    /* Animation keyframes */
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeInMenu { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Make tables responsive */
    @media (max-width: 992px) {
      .maintenance-section { overflow-x: auto; }
      .maintenance-table { min-width: 800px; }
    }
  </style>
</head>
<body>
  <div class="header">
    <h2>Maintenance Records</h2>
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

        <a class="nav-button" href="reports.php"><i class="fas fa-bullhorn"></i>Announcements</a>
 
        <a class="nav-button" href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout </a>
      </div>
  </div>

  <div class="button-container">
    <a href="maintenance_log.php" class="nav-button"><i class="fas fa-plus-circle"></i> Add New Maintenance</a>
    <input type="text" id="searchInput" class="search-input" placeholder="Search maintenance records...">
  </div>

  <div class="maintenance-section">
    
    <?php if(isset($_GET['updated']) && $_GET['updated'] == 'true'): ?>
    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i> Maintenance status has been updated successfully.
    </div>
    <?php endif; ?>
    
    <div style="overflow-x: auto;">
      <table class="maintenance-table" id="maintenanceTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Truck</th>
            <th>Description</th>
            <th>Scheduled Date</th>
            <th>Scheduled Time</th>
            <th>Status</th>
            <th>Completion Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
              // Set default status if not set
              $status = isset($row['status']) ? $row['status'] : 'Pending';
              
              // Define status class
              $statusClass = 'status-pending';
              if($status == 'In Progress') $statusClass = 'status-in-progress';
              if($status == 'Completed') $statusClass = 'status-completed';
              if($status == 'Cancelled') $statusClass = 'status-cancelled';
            ?>
              <tr>
                <td><?= htmlspecialchars($row['id']); ?></td>
                <td>
                  <?= htmlspecialchars($row['truck_type'] . ' - Unit: ' . $row['unit_number'] . ' - Plate: ' . $row['plate_number']); ?>
                </td>
                <td><?= htmlspecialchars($row['description']); ?></td>
                <td><?= htmlspecialchars(date('M d, Y', strtotime($row['scheduled_date']))); ?></td>
                <td><?= htmlspecialchars(date('h:i A', strtotime($row['scheduled_time']))); ?></td>
                <td>
                  <span class="status-badge <?= $statusClass ?>">
                    <?= htmlspecialchars($status); ?>
                  </span>
                </td>
                <td>
                  <?= isset($row['completion_date']) && $row['completion_date'] ? htmlspecialchars(date('M d, Y', strtotime($row['completion_date']))) : '-'; ?>
                </td>
                <td class="table-actions">
                  <button class="action-button btn-status" 
                          onclick="openStatusModal(<?= $row['id'] ?>, '<?= $status ?>')">
                    <i class="fas fa-tasks"></i>
                  </button>

                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: 20px;">No maintenance records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Status Update Modal -->
  <div class="modal-overlay" id="statusModal">
    <div class="modal">
      <span class="modal-close" onclick="closeStatusModal()">&times;</span>
      <h3>Update Maintenance Status</h3>
      <form class="modal-form" method="POST">
        <input type="hidden" id="maintenance_id" name="maintenance_id">
        
        <label for="status">Status:</label>
        <select id="status" name="status" required>
          <option value="Pending">Pending</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
          <option value="Cancelled">Cancelled</option>
        </select>
        
        <p style="margin-top:10px; font-size:12px; color:#666;">
          Note: Setting status to "Completed" will automatically set today as the completion date.
        </p>
        
        <button type="submit" name="update_status" class="action-button">
          <i class="fas fa-save"></i> Update Status
        </button>
      </form>
    </div>
  </div>

  <script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
      const searchTerm = this.value.toLowerCase();
      const table = document.getElementById('maintenanceTable');
      const rows = table.getElementsByTagName('tr');
      
      for (let i = 1; i < rows.length; i++) {
        const rowText = rows[i].textContent.toLowerCase();
        rows[i].style.display = rowText.includes(searchTerm) ? '' : 'none';
      }
    });
    
    // Status modal functions
    function openStatusModal(id, currentStatus) {
      document.getElementById('maintenance_id').value = id;
      document.getElementById('status').value = currentStatus;
      document.getElementById('statusModal').style.display = 'flex';
    }
    
    function closeStatusModal() {
      document.getElementById('statusModal').style.display = 'none';
    }
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
      const modal = document.getElementById('statusModal');
      if (event.target === modal) {
        closeStatusModal();
      }
    });
    
    // Toggle dropdown menu
    document.querySelectorAll('.dropdown-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const menu = btn.nextElementSibling;
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
      });
    });
    
    // Close dropdown if clicked outside
    window.addEventListener('click', e => {
      document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (!menu.parentElement.contains(e.target)) {
          menu.style.display = 'none';
        }
      });
    });
  </script>
</body>
</html>