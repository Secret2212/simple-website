<?php
session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || $_SESSION["role"] !== "driver") {
    header("Location: ../index.php");
    exit();
}

require_once "db_connect/db_connect.php";

// Get logged-in user's ID
$user_id = $_SESSION['user_id'];

// Handle marking notifications as read
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_read'])) {
    $notificationId = (int)$_POST['notification_id'];
    
    // Check if status record exists
    $checkQuery = "SELECT * FROM notification_status WHERE report_id = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $notificationId, $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // Update existing record
        $updateQuery = "UPDATE notification_status SET read_status = 1, read_at = CURRENT_TIMESTAMP WHERE report_id = ? AND user_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ii", $notificationId, $user_id);
        $updateStmt->execute();
    } else {
        // Insert new record
        $insertQuery = "INSERT INTO notification_status (report_id, user_id, read_status, read_at) VALUES (?, ?, 1, CURRENT_TIMESTAMP)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("ii", $notificationId, $user_id);
        $insertStmt->execute();
    }
    
    header("Location: notification.php");
    exit;
}

// Get search query if exists
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch notifications (reports) visible to this driver
$searchCondition = '';
$params = [$user_id];
$types = "i";

if (!empty($search)) {
    $searchCondition = "AND (r.title LIKE ? OR r.content LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

$sql = "SELECT r.report_id, r.title, r.content, r.created_at, 
               CONCAT(u.FirstName, ' ', u.LastName) AS sender,
               COALESCE(ns.read_status, 0) as is_read,
               ns.read_at
        FROM reports r
        LEFT JOIN report_visibility rv ON r.report_id = rv.report_id
        LEFT JOIN users u ON r.created_by = u.id
        LEFT JOIN notification_status ns ON r.report_id = ns.report_id AND ns.user_id = ?
        WHERE (rv.role = 'driver' OR rv.user_id = ?) 
        $searchCondition
        GROUP BY r.report_id
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$params = [$user_id, $user_id];
if (!empty($search)) {
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $stmt->bind_param("iiss", ...$params);
} else {
    $stmt->bind_param("ii", ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Count unread notifications
$unreadQuery = "SELECT COUNT(*) as unread_count 
               FROM reports r
               LEFT JOIN report_visibility rv ON r.report_id = rv.report_id
               LEFT JOIN notification_status ns ON r.report_id = ns.report_id AND ns.user_id = ?
               WHERE (rv.role = 'driver' OR rv.user_id = ?)
               AND COALESCE(ns.read_status, 0) = 0";
$unreadStmt = $conn->prepare($unreadQuery);
$unreadStmt->bind_param("ii", $user_id, $user_id);
$unreadStmt->execute();
$unreadResult = $unreadStmt->get_result();
$unreadCount = $unreadResult->fetch_assoc()['unread_count'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>drivers</title>
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


    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>
<div class="header">
  <h2>Notification</h2>
  <div class="nav-container">

     <a class="nav-button" href="delieverystatus.php"><i class="fas fa-route"></i> Delivery Status</a>
    <a class="nav-button" href="notification.php"><i class="fas fa-bell"></i> Notifications</a>
    <a class="nav-button" href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>

<div class="notifications-container">
  <div class="card">
    <!-- Stats Bar -->
    <div class="stats-bar">
      <div class="stats-item">
        <i class="fas fa-bell"></i>
        Total Notifications: <?php echo $result->num_rows; ?>
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
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($notification = $result->fetch_assoc()): ?>
        <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
          <div class="notification-header">
            <div>
              <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
              <div class="notification-meta">
                From: <?php echo htmlspecialchars($notification['sender'] ?? 'Admin'); ?> • 
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
</script>
</body>
</html>