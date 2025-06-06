<?php
// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'truckingsystem';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if this is a search request or a new record submission
    if (isset($_POST['search_description'])) {
        // Search functionality
        $search_description = $_POST['search_description'] ?? '';
    } else {
        // This is a new record submission
        $type = $_POST['type'];
        $description = $_POST['description'];
        $amount = floatval($_POST['amount']);

        // Use prepared statement for security
        $stmt = $conn->prepare("INSERT INTO finance_records (type, description, amount) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $type, $description, $amount);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch records based on search criteria
$sql = "SELECT * FROM finance_records";
$params = [];
$types = "";

// Check if search description is set
$search_description = isset($_POST['search_description']) ? $_POST['search_description'] : (isset($_GET['search_description']) ? $_GET['search_description'] : '');
if (!empty($search_description)) {
    $sql .= " WHERE description LIKE ?";
    $params[] = "%$search_description%";
    $types .= "s";
}

$sql .= " ORDER BY date DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$records = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bookkeeper</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Segoe UI", sans-serif;
    }
    body {
      background-color: #3b5870;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 20px;
    }
    .header {
      background-color: #2f4a5f;
      width: 100%;
      padding: 20px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    .header h2 {
      color: #fff;
      font-size: 28px;
      margin-bottom: 10px;
    }
    .nav-container {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 15px;
      margin: 20px 0;
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
    }
    .nav-button:hover, .action-button:hover, .modal-button:hover {
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
  </style>
</head>
<body>

<div class="header">
  <h2>Expense and Income</h2>
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

<br>

<div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; width: 100%; max-width: 1200px;">

  <!-- Transaction History Card -->
  <div style="flex: 1; min-width: 300px; background-color: rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 20px; backdrop-filter: blur(10px);">
    <h3 style="color: #fff;">Transaction History 
      <?php if (!empty($search_description)): ?>
        <span style="font-size: 0.8em; font-weight: normal;">
          <?= "Description: " . htmlspecialchars($search_description) ?>
        </span>
      <?php endif; ?>
    </h3>
    
    <!-- Integrated Search Form -->
    <form method="POST" style="display: flex; gap: 10px; margin: 15px 0; align-items: center;">
      <input type="text" name="search_description" value="<?= htmlspecialchars($search_description) ?>" 
             style="padding: 8px; border-radius: 5px; border: none; flex: 1; width: 100%;" 
             placeholder="Search by description">
      <?php if (!empty($search_description)): ?>
        <a href="expense.php" style="color: #f4a825; text-decoration: none;">
          <i class="fas fa-times"></i>
        </a>
      <?php endif; ?>
    </form>
    
    <?php if ($records->num_rows > 0): ?>
      <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <thead>
          <tr style="background-color: #f4a825; color: #fff;">
            <th style="padding: 10px;">Date</th>
            <th style="padding: 10px;">Type</th>
            <th style="padding: 10px;">Description</th>
            <th style="padding: 10px;">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $records->fetch_assoc()): ?>
            <tr style="background-color: rgba(255,255,255,0.3);">
              <td style="padding: 10px;"><?= htmlspecialchars($row['date']) ?></td>
              <td style="padding: 10px;"><?= htmlspecialchars($row['type']) ?></td>
              <td style="padding: 10px;"><?= htmlspecialchars($row['description']) ?></td>
              <td style="padding: 10px;"><?= number_format($row['amount'], 2) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="color: #fff; text-align: center; margin-top: 20px;">No transactions found <?= !empty($search_date) ? "for " . htmlspecialchars($search_date) : "" ?></p>
    <?php endif; ?>
  </div>

  <!-- Add Expense or Income Card -->
  <div style="flex: 1; min-width: 300px; background-color: rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 20px; backdrop-filter: blur(10px);">
    <h3 style="color: #fff;">Add Expense or Income</h3>
    <form method="POST" style="display: flex; flex-direction: column; gap: 10px;">
      <select name="type" required style="padding: 10px;">
        <option value="">Select Type</option>
        <option value="Income">Income</option>
        <option value="Expense">Expense</option>
      </select>
      <input type="text" name="description" placeholder="Description" required style="padding: 10px;">
      <input type="number" step="0.01" name="amount" placeholder="Amount" required style="padding: 10px;">
      <button type="submit" class="action-button">Add Record</button>
    </form>
  </div>

</div>

</body>
</html>