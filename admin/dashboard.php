<?php
// dashboard.php - Fixed version with Monthly Maintenance Overview chart removed
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

// Database connection for initial load
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "truckingsystem";

// Initialize default values
$totalTrucks = 0;
$totalDrivers = 0;
$pendingMaintenance = 0;
$totalReports = 0;

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Get initial counts for page load with error handling
    try {
        $totalTrucks = (int)$pdo->query("SELECT COUNT(*) as count FROM trucks")->fetch()['count'];
    } catch (PDOException $e) {
        error_log("Error fetching trucks count: " . $e->getMessage());
    }

    try {
        $totalDrivers = (int)$pdo->query("SELECT COUNT(*) as count FROM drivers")->fetch()['count'];
    } catch (PDOException $e) {
        error_log("Error fetching drivers count: " . $e->getMessage());
    }

    try {
        $pendingMaintenance = (int)$pdo->query("SELECT COUNT(*) as count FROM maintenance_schedule WHERE status != 'completed' OR status IS NULL")->fetch()['count'];
    } catch (PDOException $e) {
        error_log("Error fetching maintenance count: " . $e->getMessage());
    }

    try {
        $totalReports = (int)$pdo->query("SELECT COUNT(*) as count FROM reports")->fetch()['count'];
    } catch (PDOException $e) {  
        error_log("Error fetching reports count: " . $e->getMessage());
    }

} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Trucking System</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,700">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
  <style>
    /* Base Styles */
    * { 
      margin: 0; 
      padding: 0; 
      box-sizing: border-box; 
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
    }
    
    body { 
      background-color: #3b5870; 
      min-height: 100vh; 
      display: flex; 
      flex-direction: column; 
      align-items: center; 
      padding: 20px; 
    }

    /* Header Styles */
    .header { 
      background-color: #2f4a5f; 
      width: 100%; 
      padding: 20px; 
      text-align: center; 
      box-shadow: 0 4px 12px rgba(0,0,0,0.2); 
      border-radius: 10px;
      margin-bottom: 20px;
    }
    
    .header h2 { 
      color: #fff; 
      font-size: 28px; 
      margin-bottom: 15px;
    }

    /* Navigation Styles */
    .nav-container { 
      display: flex; 
      justify-content: center; 
      flex-wrap: wrap; 
      gap: 15px; 
    }

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
      border: none;
      font-size: 14px;
    }
    
    .nav-button:hover, .action-button:hover, .modal-button:hover { 
      background-color: #ffcc00; 
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    /* Dropdown Styles */
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
      border: none;
      font-size: 14px;
    }
    
    .dropdown-toggle:hover {
      background-color: #ffcc00;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .dropdown-menu {
      display: none;
      position: absolute;
      top: 100%;
      left: 0;
      background-color: #f4a825;
      min-width: 220px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.2);
      border-radius: 8px;
      overflow: hidden;
      z-index: 1000;
      animation: fadeInMenu 0.3s ease;
    }
    
    .dropdown-menu a {
      display: block;
      padding: 12px 18px;
      color: #fff;
      text-decoration: none;
      transition: background 0.2s;
    }
    
    .dropdown-menu a:hover {
      background-color: #ffcc00;
    }

    /* Dashboard Container */
    .dashboard-container {
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Statistics Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 25px;
      margin-bottom: 35px;
    }

    .stat-card {
      background: linear-gradient(135deg, #fff, #f8f9fa);
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border-left: 5px solid #f4a825;
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 100px;
      height: 100px;
      background: linear-gradient(45deg, rgba(244, 168, 37, 0.1), transparent);
      border-radius: 50%;
      transform: translate(30px, -30px);
    }

    .stat-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }

    .stat-card .icon {
      width: 65px;
      height: 65px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 26px;
      color: #fff;
      margin-bottom: 20px;
    }

    .stat-card .icon.trucks { background: linear-gradient(135deg, #667eea, #764ba2); }
    .stat-card .icon.drivers { background: linear-gradient(135deg, #f093fb, #f5576c); }
    .stat-card .icon.maintenance { background: linear-gradient(135deg, #4facfe, #00f2fe); }
    .stat-card .icon.reports { background: linear-gradient(135deg, #43e97b, #38f9d7); }

    .stat-card .number {
      font-size: 42px;
      font-weight: bold;
      color: #2f4a5f;
      margin-bottom: 8px;
      position: relative;
      z-index: 1;
    }

    .stat-card .label {
      color: #6c757d;
      font-size: 15px;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 600;
    }

    /* Charts Grid - Updated to single column since maintenance chart is removed */
    .charts-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 30px;
      margin-bottom: 35px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }

    .chart-container {
      background: #fff;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .chart-container h3 {
      color: #2f4a5f;
      margin-bottom: 25px;
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
    }

    

    .activity-item {
      display: flex;
      align-items: center;
      gap: 18px;
      padding: 18px 0;
      border-bottom: 1px solid #e9ecef;
      transition: background-color 0.2s ease;
    }

    .activity-item:hover {
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 18px 15px;
      margin: 0 -15px;
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-icon {
      width: 45px;
      height: 45px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      color: #fff;
      flex-shrink: 0;
    }

    .activity-icon.maintenance { background: linear-gradient(135deg, #4facfe, #00f2fe); }
    .activity-icon.driver { background: linear-gradient(135deg, #f5576c, #f093fb); }
    .activity-icon.truck { background: linear-gradient(135deg, #667eea, #764ba2); }
    .activity-icon.report { background: linear-gradient(135deg, #43e97b, #38f9d7); }

    .activity-content {
      flex: 1;
    }

    .activity-title {
      font-weight: 600;
      color: #2f4a5f;
      margin-bottom: 6px;
      font-size: 15px;
    }

    .activity-time {
      font-size: 13px;
      color: #6c757d;
    }

    /* Status Overview */
    .status-overview {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 25px;
    }

    .status-card {
      background: #fff;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      text-align: center;
      transition: transform 0.3s ease;
    }

    .status-card:hover {
      transform: translateY(-5px);
    }

    .status-number {
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 12px;
    }

    .status-number.success { color: #28a745; }
    .status-number.warning { color: #ffc107; }
    .status-number.danger { color: #dc3545; }

    .status-card .label {
      color: #6c757d;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 600;
    }

    /* Loading State */
    .loading {
      opacity: 0.6;
      pointer-events: none;
    }

    

    /* Animations */
    @keyframes fadeIn { 
      from { opacity: 0; transform: translateY(20px); } 
      to { opacity: 1; transform: translateY(0); } 
    }
    
    @keyframes slideDown { 
      from { opacity: 0; transform: translateY(-20px); } 
      to { opacity: 1; transform: translateY(0); } 
    }
    
    @keyframes fadeInMenu { 
      from { opacity: 0; transform: translateY(-10px); } 
      to { opacity: 1; transform: translateY(0); } 
    }

    .fade-in {
      animation: fadeIn 0.5s ease;
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
      .charts-grid {
        grid-template-columns: 1fr;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }

      .nav-container {
        flex-direction: column;
        align-items: center;
      }

      .header {
        padding: 15px;
      }

      .header h2 {
        font-size: 24px;
      }

      .dashboard-container {
        padding: 0 10px;
      }
    }

    @media (max-width: 480px) {
      .stat-card {
        padding: 20px;
      }

      .chart-container {
        padding: 20px;
      }

      .activity-item {
        gap: 12px;
      }

      .activity-icon {
        width: 35px;
        height: 35px;
        font-size: 16px;
      }
    }
  </style>
</head>
<body>
  <div class="header">
    <h2><i class="fas fa-chart-line"></i> Dashboard</h2>
    <div class="nav-container">
        <a class="nav-button" href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a class="nav-button" href="truck.php"><i class="fas fa-truck"></i> Trucks</a>
        <a class="nav-button" href="driver.php"><i class="fas fa-user"></i> Drivers</a>

        <!-- Maintenance dropdown -->
        <div class="dropdown">
          <button class="dropdown-toggle" type="button">
            <i class="fas fa-wrench"></i> Maintenance <i class="fas fa-caret-down"></i>
          </button>
          <div class="dropdown-menu">
            <a href="maintenance_log.php"><i class="fas fa-plus-circle"></i> Log Maintenance Schedule</a>
            <a href="maintenance.php"><i class="fas fa-history"></i> View Maintenance History</a>
          </div>
        </div>

        <a class="nav-button" href="reports.php"><i class="fas fa-bullhorn"></i> Announcements</a>
        <a class="nav-button" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>

  <!-- Dashboard Content -->
  <div class="dashboard-container">
    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card fade-in">
        <div class="icon trucks">
          <i class="fas fa-truck"></i>
        </div>
        <div class="number" id="totalTrucks"><?php echo $totalTrucks; ?></div>
        <div class="label">Total Trucks</div>
      </div>

      <div class="stat-card fade-in">
        <div class="icon drivers">
          <i class="fas fa-users"></i>
        </div>
        <div class="number" id="totalDrivers"><?php echo $totalDrivers; ?></div>
        <div class="label">Active Drivers</div>
      </div>

      <div class="stat-card fade-in">
        <div class="icon maintenance">
          <i class="fas fa-wrench"></i>
        </div>
        <div class="number" id="pendingMaintenance"><?php echo $pendingMaintenance; ?></div>
        <div class="label">Pending Maintenance</div>
      </div>

      <div class="stat-card fade-in">
        <div class="icon reports">
          <i class="fas fa-bullhorn"></i>
        </div>
        <div class="number" id="totalReports"><?php echo $totalReports; ?></div>
        <div class="label">Total Announcements</div>
      </div>
    </div>

   
   
    

  <!-- Error Message Container -->
  <div id="errorContainer" style="display: none;"></div>

  <script>
// Simplified JavaScript with maintenance chart removed

let fleetChart;
let updateInterval = null;
let isUpdating = false;
let chartsInitialized = false;

document.addEventListener('DOMContentLoaded', function() {
  // Dropdown functionality
  document.querySelectorAll('.dropdown-toggle').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const menu = this.nextElementSibling;
      const isVisible = menu.style.display === 'block';
      
      document.querySelectorAll('.dropdown-menu').forEach(m => {
        m.style.display = 'none';
      });
      
      menu.style.display = isVisible ? 'none' : 'block';
    });
  });
  
  window.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
      document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.style.display = 'none';
      });
    }
  });

  // Initialize dashboard
  initializeDashboard();
});

function initializeDashboard() {
  try {
    console.log('Initializing dashboard...');
    
    // Initialize chart
    initializeCharts();
    
    // Load data once on page load
    updateDashboardData();
    
    console.log('Dashboard initialized successfully');
  } catch (error) {
    console.error('Error initializing dashboard:', error);
    showError('Failed to initialize dashboard. Please refresh the page.');
  }
}



function updateDashboardData() {
  if (isUpdating) {
    console.log('Update already in progress, skipping...');
    return;
  }

  isUpdating = true;
  console.log('Updating dashboard data...');
  
  fetch('get_dashboard_data.php', {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
    credentials: 'same-origin'
  })
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
  })
  .then(data => {
    if (!data.success) {
      throw new Error(data.error || 'Unknown error occurred');
    }

    // Update components (maintenance chart update removed)
    updateCountCards(data.counts);
    updateStatusCards(data.status);
    updateFleetChart(data.fleetChart);
   
    
    hideError();
    console.log('Dashboard updated successfully');
  })
  .catch(error => {
    console.error('Error fetching dashboard data:', error);
    showError('Failed to update dashboard data: ' + error.message);
  })
  .finally(() => {
    isUpdating = false;
  });
}

function updateFleetChart(fleetData) {
  if (!fleetChart || !fleetData || !chartsInitialized) return;

  try {
    if (fleetData.length > 0) {
      const labels = fleetData.map(item => item.truck_type || 'Unknown');
      const counts = fleetData.map(item => parseInt(item.count) || 0);
      const colors = [
        '#667eea', '#f5576c', '#4facfe', '#43e97b', '#ffc107',
        '#17a2b8', '#6f42c1', '#e83e8c', '#fd7e14', '#20c997'
      ];

      fleetChart.data.labels = labels;
      fleetChart.data.datasets[0].data = counts;
      fleetChart.data.datasets[0].backgroundColor = colors.slice(0, labels.length);
      fleetChart.data.datasets[0].hoverBackgroundColor = colors.slice(0, labels.length).map(color => color + 'CC');
    } else {
      fleetChart.data.labels = ['No Data'];
      fleetChart.data.datasets[0].data = [1];
      fleetChart.data.datasets[0].backgroundColor = ['#e9ecef'];
    }
    
    fleetChart.update('none');
    
  } catch (error) {
    console.error('Error updating fleet chart:', error);
  }
}

function updateCountCards(counts) {
  if (!counts) return;

  const cards = [
    { id: 'totalTrucks', value: counts.trucks },
    { id: 'totalDrivers', value: counts.drivers },
    { id: 'pendingMaintenance', value: counts.maintenance },
    { id: 'totalReports', value: counts.reports }
  ];

  cards.forEach(card => {
    const element = document.getElementById(card.id);
    if (element) {
      const newValue = parseInt(card.value) || 0;
      element.textContent = newValue;
    }
  });
}

function updateStatusCards(status) {
  if (!status) return;

  const statusCards = [
    { id: 'operationalTrucks', value: status.operational },
    { id: 'underMaintenance', value: status.maintenance },
    { id: 'outOfService', value: status.outOfService },
    { id: 'availableDrivers', value: status.availableDrivers }
  ];

  statusCards.forEach(card => {
    const element = document.getElementById(card.id);
    if (element) {
      const newValue = parseInt(card.value) || 0;
      element.textContent = newValue;
    }
  });
}

// Cleanup function
window.addEventListener('beforeunload', function() {
  if (updateInterval) {
    clearInterval(updateInterval);
    updateInterval = null;
  }
  
  if (fleetChart) {
    fleetChart.destroy();
    fleetChart = null;
  }
  
  chartsInitialized = false;
});



function hideError() {
  const errorContainer = document.getElementById('errorContainer');
  if (errorContainer) {
    errorContainer.style.display = 'none';
  }
}

function escapeHtml(unsafe) {
  if (typeof unsafe !== 'string') return '';
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}


  </script>
</body>
</html>