<?php
// This file can be included in header sections to show notification counters
// filename: notification_counter.php

function getUnreadNotificationCount($conn, $userId, $userRole) {
    // Count notifications directed to this user's role or specifically to them
    $query = "
        SELECT COUNT(*) as count 
        FROM reports r
        JOIN report_visibility v ON r.report_id = v.report_id
        LEFT JOIN notification_status ns ON r.report_id = ns.report_id AND ns.user_id = $userId
        WHERE (v.role = '$userRole' OR v.id = $userId)
        AND (ns.read_status IS NULL OR ns.read_status = 0)
    ";
    
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['count'];
    }
    return 0;
}

// Usage example:
// include 'notification_counter.php';
// $unreadCount = getUnreadNotificationCount($conn, $_SESSION['user_id'], $_SESSION['role']);
// Then use $unreadCount in your notification bell icon
?>

// JavaScript for notification badge
<script>
function updateNotificationCounter() {
    // You can use AJAX to periodically check for new notifications
    fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notification-badge');
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(error => console.error('Error fetching notification count:', error));
}

// Update initially
document.addEventListener('DOMContentLoaded', updateNotificationCounter);

// Update every minute
setInterval(updateNotificationCounter, 60000);
</script>

<style>
.notification-icon {
    position: relative;
    display: inline-flex;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #ff4d4d;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: none;
    justify-content: center;
    align-items: center;
    font-size: 12px;
    font-weight: bold;
}
</style>

<!-- Example HTML -->
<!--
<div class="notification-icon">
    <i class="fas fa-bell"></i>
    <span id="notification-badge" class="notification-badge">0</span>
</div>
-->