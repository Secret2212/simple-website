<?php

session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

// Database connection
include 'db_connect/db_connect.php';

// ✅ Run this only when form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title'], $_POST['content'])) {
    $reportTitle = mysqli_real_escape_string($conn, $_POST['title']);
    $reportContent = mysqli_real_escape_string($conn, $_POST['content']);
    
    // Check if session has an ID
    if (isset($_SESSION['user_id'])) {
        $adminId = (int)$_SESSION['user_id']; // cast to int for safety
        
        // Insert the report
        $query = "INSERT INTO reports (title, content, created_by) VALUES ('$reportTitle', '$reportContent', $adminId)";
        if (mysqli_query($conn, $query)) {
            $reportId = mysqli_insert_id($conn);
            
            // Set visibility based on selected recipients
            if (isset($_POST['recipients']) && is_array($_POST['recipients']) && count($_POST['recipients']) > 0) {
                foreach ($_POST['recipients'] as $roleOrUser) {
                    if (strpos($roleOrUser, 'role_') === 0) {
                        $role = mysqli_real_escape_string($conn, substr($roleOrUser, 5)); // sanitize role string
                        mysqli_query($conn, "INSERT INTO report_visibility (report_id, role) VALUES ($reportId, '$role')");
                    } else {
                        $userId = (int)$roleOrUser;
                        mysqli_query($conn, "INSERT INTO report_visibility (report_id, user_id) VALUES ($reportId, $userId)");
                    }
                }
                echo "<script>alert('Report submitted successfully!'); window.location.href='reports.php';</script>";
                exit;
            } else {
                // No recipients selected, delete the inserted report to keep data clean
                mysqli_query($conn, "DELETE FROM reports WHERE report_id = $reportId");
                echo "<script>alert('Please select at least one recipient.');</script>";
            }
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        echo "Error: Session expired or user not logged in.";
    }
}

// Get search query if exists
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Fetch all reports (that the current user can see)
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $currentUserId = (int) $_SESSION['user_id'];
    $currentRoleEscaped = mysqli_real_escape_string($conn, $_SESSION['role']);

    $searchCondition = '';
    if (!empty($search)) {
        $searchCondition = "AND (r.title LIKE '%$search%' OR r.content LIKE '%$search%')";
    }

    $reportsQuery = "SELECT r.* FROM reports r
                    LEFT JOIN report_visibility rv ON r.report_id = rv.report_id
                    WHERE (r.created_by = $currentUserId 
                    OR rv.role = '$currentRoleEscaped'
                    OR rv.user_id = $currentUserId)
                    $searchCondition
                    GROUP BY r.report_id
                    ORDER BY r.created_at DESC";
    $reportsResult = mysqli_query($conn, $reportsQuery);
} else {
    // Fallback if session not set (shouldn't happen with proper auth)
    $searchCondition = '';
    if (!empty($search)) {
        $searchCondition = "WHERE title LIKE '%$search%' OR content LIKE '%$search%'";
    }
    $reportsResult = mysqli_query($conn, "SELECT * FROM reports $searchCondition ORDER BY created_at DESC");
}

// Fetch all users for the selection dropdown
$usersResult = mysqli_query($conn, "SELECT id, CONCAT(FirstName, ' ', LastName) AS fullname, role FROM users ORDER BY role, FirstName");

// Fetch all available roles
$rolesResult = mysqli_query($conn, "SELECT DISTINCT role FROM users ORDER BY role");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reports Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* --- Base styles --- */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }
    body { background-color: #3b5870; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 20px; }
    .header { background-color: #2f4a5f; width: 100%; padding: 20px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
    .header h2 { color: #fff; font-size: 28px; }
    .nav-container { display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin: 20px 0; }

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

    /* --- Dropdown styles --- */
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

    /* --- Reports container styles --- */
    .reports-container {
      display: flex;
      justify-content: center;
      gap: 30px;
      margin-top: 40px;
      flex-wrap: wrap;
      width: 100%;
      max-width: 1200px;
    }
    
    .card {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    
    .reports-table {
      width: 550px;
    }
    
    .reports-form {
      width: 400px;
    }
    
    h3 {
      margin-bottom: 15px;
      color: #2f4a5f;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    table thead tr {
      background-color: #f4a825;
      color: #fff;
    }
    
    table th, table td {
      padding: 10px;
      text-align: left;
    }
    
    table tr {
      border-bottom: 1px solid #ddd;
    }
    
    .select-container {
      margin-bottom: 15px;
      background: #f9f9f9;
      border: 1px solid #ddd;
      border-radius: 5px;
      padding: 10px;
    }
    
    .select-container h4 {
      margin-bottom: 8px;
      color: #2f4a5f;
      font-size: 16px;
    }
    
    .checkbox-group {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 5px;
    }
    
    .checkbox-item {
      display: flex;
      align-items: center;
      gap: 4px;
    }
    
    input[type="text"], textarea {
      padding: 10px;
      width: 100%;
      margin-bottom: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }
    
    textarea {
      height: 100px;
      resize: vertical;
    }
    
    button[type="submit"] {
      width: 100%;
      border: none;
    }
    
    .role-heading {
      width: 100%;
      font-weight: bold;
      margin-top: 8px;
      color: #2f4a5f;
    }
    
    /* Search bar styles */
    .search-container {
      margin-bottom: 15px;
    }
    
    .search-container input[type="text"] {
      width: 100%;
      margin-bottom: 0;
      padding-left: 30px;
      background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="%238a8a8a" d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>');
      background-repeat: no-repeat;
      background-position: 8px center;
    }
    
    /* --- Animations --- */
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeInMenu { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>
  <div class="header">
    <h2>Announcements </h2>
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

  <div class="reports-container">
    <!-- LEFT CARD: Reports Table -->
    <div class="card reports-table">
      <h3>View Announcements</h3>
      
      <!-- Search Bar -->
      <form method="GET" action="reports.php" class="search-container">
        <input type="text" name="search" placeholder="Search announcements..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
        <?php if(!empty($search)): ?>
          <a href="reports.php" style="position: absolute; margin-left: -25px; margin-top: 10px; color: #999; text-decoration: none;">×</a>
        <?php endif; ?>
      </form>
      
      <table>
        <thead>
          <tr>
            <th>Title</th>
            <th>Content</th>
            <th>Date</th>
            <th>Recipients</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($reportsResult) > 0): ?>
            <?php while ($report = mysqli_fetch_assoc($reportsResult)): ?>
              <tr>
                <td><?php echo htmlspecialchars($report['title']); ?></td>
                <td><?php echo htmlspecialchars($report['content']); ?></td>
                <td><?php echo date("M d, Y", strtotime($report['created_at'])); ?></td>
                <td>
                  <?php
                    // Get recipients for this report
                    $reportId = $report['report_id'];
                    $recipientsQuery = "SELECT rv.role, CONCAT(u.FirstName, ' ', u.LastName) AS name 
                   FROM report_visibility rv
                   LEFT JOIN users u ON rv.user_id = u.id
                   WHERE rv.report_id = $reportId";

                    $recipientsResult = mysqli_query($conn, $recipientsQuery);
                    
                    $recipients = [];
                    while ($recipient = mysqli_fetch_assoc($recipientsResult)) {
                      if (!empty($recipient['role'])) {
                        $recipients[] = ucfirst($recipient['role']) . "s";
                      } else if (!empty($recipient['name'])) {
                        $recipients[] = $recipient['name'];
                      }
                    }
                    echo implode(", ", $recipients);
                  ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="4">No reports found<?php echo !empty($search) ? ' matching "' . htmlspecialchars($search) . '"' : ''; ?>.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- RIGHT CARD: Report Form -->
    <div class="card reports-form">
      <h3>Create New Announcements</h3>
      <form method="POST" action="reports.php">
        <input type="text" name="title" placeholder="Report Title" required>
        <textarea name="content" placeholder="Report/Message Content" required></textarea>
        
        <div class="select-container">
          <h4>Select user:</h4>
          
          <!-- By Role Section -->
          <div>
            <strong>By Role:</strong>
            <div class="checkbox-group">
              <?php while ($role = mysqli_fetch_assoc($rolesResult)): ?>
                <div class="checkbox-item">
                  <input type="checkbox" name="recipients[]" value="role_<?php echo $role['role']; ?>" id="role_<?php echo $role['role']; ?>">
                  <label for="role_<?php echo $role['role']; ?>"><?php echo ucfirst($role['role']); ?>s</label>
                </div>
              <?php endwhile; ?>
            </div>
          </div>
          
          <!-- By Individual User Section -->
          <div style="margin-top: 15px;">
            <strong>By Individual User:</strong>
            <div class="checkbox-group">
              <?php 
                $currentRole = '';
                while ($user = mysqli_fetch_assoc($usersResult)):
                  if ($currentRole != $user['role']) {
                    $currentRole = $user['role'];
                    echo '<div class="role-heading">' . ucfirst($currentRole) . 's:</div>';
                  }
              ?>
                <div class="checkbox-item">
                  <input type="checkbox" name="recipients[]" value="<?php echo $user['id']; ?>" id="user_<?php echo $user['id']; ?>">
                  <label for="user_<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['fullname']); ?></label>
                </div>
              <?php endwhile; ?>
            </div>
          </div>
        </div>
        
        <button class="action-button" type="submit">Send Report/Message</button>
      </form>
    </div>
  </div>

  <script>
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