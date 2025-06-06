<?php
// Start session
session_start();

// Check if user is logged in and has bookkeeper role
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || $_SESSION["role"] !== "bookkeeper") {
    header("Location: ../index.php");
    exit();
}

// Database connection
include 'db_connect/db_connect.php';

// Handle marking notifications as read
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_read'])) {
    $notificationId = (int)$_POST['notification_id'];
    $userId = (int)$_SESSION['user_id'];
    
    // Check if status record exists
    $checkQuery = "SELECT * FROM notification_status WHERE report_id = $notificationId AND user_id = $userId";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        // Update existing record
        mysqli_query($conn, "UPDATE notification_status SET read_status = 1, read_at = CURRENT_TIMESTAMP WHERE report_id = $notificationId AND user_id = $userId");
    } else {
        // Insert new record
        mysqli_query($conn, "INSERT INTO notification_status (report_id, user_id, read_status, read_at) VALUES ($notificationId, $userId, 1, CURRENT_TIMESTAMP)");
    }
    
    header("Location: notification.php");
    exit;
}

// Get search query if exists
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Fetch notifications for current bookkeeper user
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $currentUserId = (int) $_SESSION['user_id'];
    $currentRoleEscaped = mysqli_real_escape_string($conn, $_SESSION['role']);

    $searchCondition = '';
    if (!empty($search)) {
        $searchCondition = "AND (r.title LIKE '%$search%' OR r.content LIKE '%$search%')";
    }

    // Get notifications visible to this bookkeeper (by role or specific user)
    $notificationsQuery = "SELECT r.*, 
                          COALESCE(ns.read_status, 0) as is_read,
                          ns.read_at,
                          CONCAT(u.FirstName, ' ', u.LastName) as sender_name
                          FROM reports r
                          LEFT JOIN report_visibility rv ON r.report_id = rv.report_id
                          LEFT JOIN notification_status ns ON r.report_id = ns.report_id AND ns.user_id = $currentUserId
                          LEFT JOIN users u ON r.created_by = u.id
                          WHERE (rv.role = '$currentRoleEscaped' OR rv.user_id = $currentUserId)
                          $searchCondition
                          GROUP BY r.report_id
                          ORDER BY r.created_at DESC";
    
    $notificationsResult = mysqli_query($conn, $notificationsQuery);
    
    // Count unread notifications
    $unreadQuery = "SELECT COUNT(*) as unread_count 
                   FROM reports r
                   LEFT JOIN report_visibility rv ON r.report_id = rv.report_id
                   LEFT JOIN notification_status ns ON r.report_id = ns.report_id AND ns.user_id = $currentUserId
                   WHERE (rv.role = '$currentRoleEscaped' OR rv.user_id = $currentUserId)
                   AND COALESCE(ns.read_status, 0) = 0";
    $unreadResult = mysqli_query($conn, $unreadQuery);
    $unreadCount = mysqli_fetch_assoc($unreadResult)['unread_count'];
} else {
    $notificationsResult = false;
    $unreadCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* --- existing styles --- */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }
    body { background-color: #3b5870; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 20px; }
   .header {
      background-color: #2f4a5f;
      width: 100%;
      padding: 20px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      margin-bottom: 20px;
    }
    .header h2 { color: #fff; font-size: 28px; margin-bottom: 10px; }
    .nav-container {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 20px;
    }
    .nav-button {
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
    .nav-button:hover {
      background-color: #ffcc00;
      transform: scale(1.05);
    }
    .dropdown {
      position: relative;
      display: inline-block;
    }
    .dropdown-content {
      display: none;
      position: absolute;
      top: 100%;
      left: 0;
      background-color: #f4a825;
      min-width: 200px;
      border-radius: 8px;
      overflow: hidden;
      z-index: 100;
      animation: fadeInMenu 0.3s ease;
    }
    .dropdown-content a {
      display: block;
      padding: 10px 15px;
      color: #fff;
      text-decoration: none;
      transition: background 0.2s;
    }
    .dropdown-content a:hover {
      background-color: #ffcc00;
    }
    .dropdown:hover .dropdown-content {
      display: block;
    }
    @keyframes fadeInMenu {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
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

    /* Notifications container */
    .notifications-container {
      width: 100%;
      max-width: 900px;
      margin-top: 20px;
    }
    
    .card {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      margin-bottom: 20px;
    }
    
    .stats-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    
    .stats-item {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #2f4a5f;
      font-weight: bold;
    }
    
    .unread-count {
      background-color: #e74c3c;
      color: white;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
    }
    
    .search-container {
      margin-bottom: 20px;
      position: relative;
    }
    
    .search-container input[type="text"] {
      width: 100%;
      padding: 12px 40px 12px 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 16px;
    }
    
    .search-clear {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #999;
      text-decoration: none;
      font-size: 18px;
    }
    
    .notification-item {
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      margin-bottom: 15px;
      transition: all 0.3s ease;
      overflow: hidden;
    }
    
    .notification-item.unread {
      border-left: 4px solid #f4a825;
      background-color: #fffbf0;
    }
    
    .notification-item.read {
      background-color: #f8f9fa;
      opacity: 0.8;
    }
    
    .notification-header {
      display: flex;
      justify-content: between;
      align-items: flex-start;
      padding: 15px;
      background: linear-gradient(135deg, #2f4a5f 0%, #3b5870 100%);
      color: white;
    }
    
    .notification-title {
      flex: 1;
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .notification-meta {
      font-size: 14px;
      opacity: 0.9;
    }
    
    .notification-status {
      margin-left: 15px;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 8px;
    }
    
    .status-badge {
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
    }
    
    .status-unread {
      background-color: #e74c3c;
      color: white;
    }
    
    .status-read {
      background-color: #27ae60;
      color: white;
    }
    
    .notification-content {
      padding: 15px;
      line-height: 1.6;
      color: #333;
    }
    
    .notification-actions {
      padding: 15px;
      border-top: 1px solid #e0e0e0;
      background-color: #f8f9fa;
      text-align: right;
    }
    
    .mark-read-btn {
      background-color: #27ae60;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }
    
    .mark-read-btn:hover {
      background-color: #229954;
    }
    
    .empty-state {
      text-align: center;
      padding: 40px;
      color: #666;
    }
    
    .empty-state i {
      font-size: 48px;
      margin-bottom: 15px;
      color: #ccc;
    }
    
    @keyframes fadeInMenu {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
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
    <div class="dropdown">
      <a class="nav-button" href="#"><i class="fas fa-file-invoice-dollar"></i> Accounting <i class="fas fa-caret-down"></i></a>
      <div class="dropdown-content">
        <a href="accounts_payable.php">Accounts Payable</a>
        <a href="invoice.php">Invoice Capture</a>
        <a href="invoice_history.php">Invoice History</a>
      </div>
    </div>
    <a class="nav-button" href="notification.php">Notification</a>
    <a class="nav-button" href="contract.php"><i class="fas fa-file-contract"></i> Contract</a>
    <a class="nav-button" href="expense.php"><i class="fas fa-coins"></i> Expense & Income</a>
    
        <a class="nav-button" href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>


<div class="notifications-container">
  <div class="card">
    <!-- Stats Bar -->
    <div class="stats-bar">
      <div class="stats-item">
        <i class="fas fa-bell"></i>
        Total Notifications: <?php echo mysqli_num_rows($notificationsResult); ?>
      </div>
      <div class="stats-item">
        <i class="fas fa-exclamation-circle"></i>
        Unread: <span class="unread-count"><?php echo $unreadCount; ?></span>
      </div>
    </div>

    <!-- Search Bar -->
    <form method="GET" action="notification.php" class="search-container">
      <input type="text" name="search" placeholder="Search notifications..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
      <?php if(!empty($search)): ?>
        <a href="notification.php" class="search-clear">×</a>
      <?php endif; ?>
    </form>

    <!-- Notifications List -->
    <?php if ($notificationsResult && mysqli_num_rows($notificationsResult) > 0): ?>
      <?php while ($notification = mysqli_fetch_assoc($notificationsResult)): ?>
        <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
          <div class="notification-header">
            <div>
              <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
              <div class="notification-meta">
                From: <?php echo htmlspecialchars($notification['sender_name']); ?> • 
                <?php echo date("M d, Y g:i A", strtotime($notification['created_at'])); ?>
              </div>
            </div>
            <div class="notification-status">
              <span class="status-badge <?php echo $notification['is_read'] ? 'status-read' : 'status-unread'; ?>">
                <?php echo $notification['is_read'] ? 'Read' : 'Unread'; ?>
              </span>
              <?php if ($notification['is_read'] && $notification['read_at']): ?>
                <small style="color: #ccc;">Read: <?php echo date("M d, g:i A", strtotime($notification['read_at'])); ?></small>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="notification-content">
            <?php echo nl2br(htmlspecialchars($notification['content'])); ?>
          </div>
          
          <?php if (!$notification['is_read']): ?>
            <div class="notification-actions">
              <form method="POST" action="notification.php" style="display: inline;">
                <input type="hidden" name="notification_id" value="<?php echo $notification['report_id']; ?>">
                <button type="submit" name="mark_read" class="mark-read-btn">
                  <i class="fas fa-check"></i> Mark as Read
                </button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-bell-slash"></i>
        <h3>No notifications found</h3>
        <p><?php echo !empty($search) ? 'No notifications match your search "' . htmlspecialchars($search) . '"' : 'You have no notifications at this time.'; ?></p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  // Auto-refresh page every 5 minutes to check for new notifications
  setTimeout(function() {
    window.location.reload();
  }, 300000); // 5 minutes

  // Close dropdown if clicked outside
  window.addEventListener('click', e => {
    document.querySelectorAll('.dropdown-content').forEach(menu => {
      if (!menu.parentElement.contains(e.target)) {
        menu.style.display = 'none';
      }
    });
  });
</script>
  <!-- your page content here -->

  
</body>
</html>
