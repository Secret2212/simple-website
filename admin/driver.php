<?php

session_start();
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

include 'db_connect/db_connect.php'; // Database connection



// Query to fetch all drivers
$sql = "SELECT * FROM drivers";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Drivers</title>
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
    .driver-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .driver-table th, .driver-table td { border: 1px solid #ccc; padding: 12px; text-align: center; background: #fff; }
    .driver-table th { background-color: #2f4a5f; color: #fff; }

    .nav-button, .action-button, .modal-button {
      background-color: #f4a825;
      padding: 10px 20px;
      border-radius: 8px;
      color: #fff;
      text-decoration: none;
      font-weight: bold;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    .nav-button:hover, .action-button:hover, .modal-button:hover { background-color: #ffcc00; transform: scale(1.05); }

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
    
    /* Modal styles */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; animation: fadeIn 0.5s ease; }
    .modal { background: #fff; border-radius: 10px; width: 90%; max-width: 400px; padding: 20px; animation: slideDown 0.5s ease; position: relative; }
    .modal h3 { margin-bottom: 15px; color: #2f4a5f; }
    .modal label { display: block; margin: 10px 0 5px; color: #333; }
    .modal input, .modal select { width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; }
    .modal-close { position: absolute; top: 10px; right: 10px; font-size: 18px; cursor: pointer; color: #666; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeInMenu { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>
  <div class="header">
    <h2>Drivers</h2>
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
  
        <a class="nav-button" href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout </a>
      </div>
  </div>

  <div class="button-container">
    <input type="text" id="searchInput" class="search-input" placeholder="Search drivers...">
    <button class="modal-button" data-target="#addModal"><i class="fas fa-user-plus"></i> Add Driver</button>
  </div>

  <div class="driver-section">
    <table class="driver-table" id="driverTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Full Name</th>
          <th>Address</th>
          <th>Contact Number</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['id']); ?></td>
              <td><?= htmlspecialchars($row['FullName']); ?></td>
              <td><?= htmlspecialchars($row['address']); ?></td>
              <td><?= htmlspecialchars($row['contact_number']); ?></td>
              <td>
                <button class="modal-button edit-btn" 
                  data-target="#editModal" 
                  data-id="<?= $row['id']; ?>"
                  data-fullname="<?= htmlspecialchars($row['FullName']); ?>"
                  data-address="<?= htmlspecialchars($row['address']); ?>"
                  data-contact="<?= htmlspecialchars($row['contact_number']); ?>"
                ><i class="fas fa-edit"></i> Edit</button>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5">No drivers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Add Driver Modal -->
  <div class="modal-overlay" id="addModal">
    <div class="modal">
      <span class="modal-close" data-target="#addModal">&times;</span>
      <h3>Add Driver</h3>
      <form method="POST" action="add_driver.php">

        <label for="FullName">Full Name</label>
        <input type="text" name="FullName" id="FullName" required>

        <label for="address">Address</label>
        <input type="text" name="address" id="address" required>

        <label for="contact_number">Contact Number</label>
        <input type="text" name="contact_number" id="contact_number" required>

        <button type="submit" name="add_driver" class="action-button">Save</button>
      </form>
    </div>
  </div>

  <!-- Edit Driver Modal -->
  <div class="modal-overlay" id="editModal">
    <div class="modal">
      <span class="modal-close" data-target="#editModal">&times;</span>
      <h3>Edit Driver</h3>
      <form method="POST" action="edit_driver.php">

        <input type="hidden" name="id" id="edit-id">

        <label for="edit-FullName">Full Name</label>
        <input type="text" name="FullName" id="edit-FullName" required>

        <label for="edit-address">Address</label>
        <input type="text" name="address" id="edit-address" required>

        <label for="edit-contact_number">Contact Number</label>
        <input type="text" name="contact_number" id="edit-contact_number" required>

        <button type="submit" name="update_driver" class="action-button">Update</button>
      </form>
    </div>
  </div>

  <script>
    // Search filter
    document.getElementById('searchInput').addEventListener('keyup', function() {
      const filter = this.value.toLowerCase();
      document.querySelectorAll('#driverTable tbody tr').forEach(function(row) {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
      });
    });
    
    // Modal toggling
    document.querySelectorAll('.modal-button').forEach(btn => {
      btn.addEventListener('click', () => {
        const target = document.querySelector(btn.getAttribute('data-target'));
        if (btn.classList.contains('edit-btn')) {
          document.getElementById('edit-id').value = btn.getAttribute('data-id');
          document.getElementById('edit-FullName').value = btn.getAttribute('data-fullname');
          document.getElementById('edit-address').value = btn.getAttribute('data-address');
          document.getElementById('edit-contact_number').value = btn.getAttribute('data-contact');
        }
        target.style.display = 'flex';
      });
    });
    
    document.querySelectorAll('.modal-close').forEach(span => {
      span.addEventListener('click', () => {
        document.querySelector(span.getAttribute('data-target')).style.display = 'none';
      });
    });

    // Toggle dropdown menu on click
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