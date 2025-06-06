<?php
// Database connection
include 'db_connect/db_connect.php';

// Initialize variables
$success_message = '';
$error_message = '';
$payable_id = '';
$title = '';
$description = '';
$amount = '';
$due_date = '';
$status = 'Unpaid';

// Handle form submission for adding/updating a payable
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Get form data
        $payable_id = isset($_POST['payable_id']) ? $_POST['payable_id'] : '';
        $title = $_POST['title'];
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $due_date = $_POST['due_date'];
        $status = $_POST['status'];
        
        // Validate input
        if (empty($title) || empty($amount) || empty($due_date)) {
            $error_message = "Please fill all required fields!";
        } else {
            // Check if it's an update or a new record
            if ($_POST['action'] == 'update' && !empty($payable_id)) {
                // Update existing record
                $sql = "UPDATE accounts_payable SET 
                        title = ?, 
                        description = ?, 
                        amount = ?, 
                        due_date = ?, 
                        status = ?,
                        updated_at = NOW() 
                        WHERE id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdssi", $title, $description, $amount, $due_date, $status, $payable_id);
                
                if ($stmt->execute()) {
                    $success_message = "Payable updated successfully!";
                    // Reset form fields
                    $payable_id = '';
                    $title = '';
                    $description = '';
                    $amount = '';
                    $due_date = '';
                    $status = 'Unpaid';
                } else {
                    $error_message = "Error updating record: " . $conn->error;
                }
                $stmt->close();
            } else {
                // Insert new record
                $sql = "INSERT INTO accounts_payable (title, description, amount, due_date, status, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdss", $title, $description, $amount, $due_date, $status);
                
                if ($stmt->execute()) {
                    $success_message = "New payable added successfully!";
                    // Reset form fields
                    $title = '';
                    $description = '';
                    $amount = '';
                    $due_date = '';
                    $status = 'Unpaid';
                } else {
                    $error_message = "Error adding record: " . $conn->error;
                }
                $stmt->close();
            }
        }
    }
}


// Handle edit operation
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $id_to_edit = $_GET['edit'];
    
    $edit_sql = "SELECT * FROM accounts_payable WHERE id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("i", $id_to_edit);
    $edit_stmt->execute();
    $result = $edit_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $payable_id = $row['id'];
        $title = $row['title'];
        $description = $row['description'];
        $amount = $row['amount'];
        $due_date = $row['due_date'];
        $status = $row['status'];
    }
    $edit_stmt->close();
}

// Fetch all payables from the database
$sql = "SELECT * FROM accounts_payable ORDER BY due_date ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bookkeeper</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
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
    
    /* Accounts Payable specific styles */
    .content-container {
      width: 100%;
      max-width: 1200px;
      background-color: #3b5870;
      border-radius: 8px;
      padding: 20px;
    }
    .section-title {
      color: #fff;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #f4a825;
      text-align: center;
    }
    .form-container {
      background-color: #2f4a5f;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      color: white;
    }
    .form-row {
      display: flex;
      flex-wrap: wrap;
      margin-bottom: 15px;
      gap: 15px;
    }
    .form-group {
      flex: 1;
      min-width: 200px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: white;
    }
    .form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
    }
    .btn-container {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s;
    }
    .btn-primary {
      background-color: #f4a825;
      color: white;
    }
    .btn-primary:hover {
      background-color: #ffcc00;
    }
    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }
    .btn-secondary:hover {
      background-color: #5a6268;
    }
    .table-container {
      overflow-x: auto;
      background-color: #2f4a5f;
      border-radius: 8px;
      padding: 15px;
    }
    .data-table {
      width: 100%;
      border-collapse: collapse;
    }
    .data-table th {
      background-color: #1e3a50;
      color: #fff;
      text-align: left;
      padding: 12px;
    }
    .data-table td {
      padding: 10px;
      border-bottom: 1px solid #4a6680;
      color: white;
    }
    .data-table tr:hover {
      background-color: #4a6680;
    }
    .action-column {
      width: 120px;
    }
    .action-btn {
      padding: 5px 10px;
      margin-right: 5px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      color: white;
    }
    .edit-btn {
      background-color: #17a2b8;
    }
  
    .status-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 14px;
      font-weight: bold;
    }
    .status-paid {
      background-color: #28a745;
      color: white;
    }
    .status-unpaid {
      background-color: #dc3545;
      color: white;
    }
    .status-partial {
      background-color: #ffc107;
      color: black;
    }
    .message {
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 4px;
    }
    .success {
      background-color: rgba(40, 167, 69, 0.2);
      border: 1px solid #28a745;
      color: white;
    }
    .error {
      background-color: rgba(220, 53, 69, 0.2);
      border: 1px solid #dc3545;
      color: white;
    }
    .summary-container {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 20px;
    }
    .summary-card {
      flex: 1;
      min-width: 200px;
      background-color: #2f4a5f;
      border-radius: 8px;
      padding: 15px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      color: white;
      text-align: center;
    }
    .summary-card h3 {
      margin-bottom: 10px;
      color: white;
    }
    .summary-value {
      font-size: 24px;
      font-weight: bold;
      color: #f4a825;
    }
    .filter-container {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 20px;
      color: white;
    }
    .filter-select {
      padding: 8px;
      border-radius: 4px;
      border: 1px solid #4a6680;
      background-color: #2f4a5f;
      color: white;
    }
  </style>
</head>
<body>

<div class="header">
  <h2>Accounting</h2>
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

<div class="content-container">
  <h1 class="section-title">Accounts Payable</h1>
  
  <?php if(!empty($success_message)): ?>
    <div class="message success">
      <?php echo $success_message; ?>
    </div>
  <?php endif; ?>
  
  <?php if(!empty($error_message)): ?>
    <div class="message error">
      <?php echo $error_message; ?>
    </div>
  <?php endif; ?>
  
  <div class="summary-container">
    <div class="summary-card">
      <h3>Total Payables</h3>
      <div class="summary-value">
        <?php
          $total_query = "SELECT COUNT(*) as total FROM accounts_payable";
          $total_result = $conn->query($total_query);
          $total_row = $total_result->fetch_assoc();
          echo $total_row['total'];
        ?>
      </div>
    </div>
    <div class="summary-card">
      <h3>Unpaid Amount</h3>
      <div class="summary-value">
        <?php
          $unpaid_query = "SELECT SUM(amount) as total FROM accounts_payable WHERE status = 'Unpaid'";
          $unpaid_result = $conn->query($unpaid_query);
          $unpaid_row = $unpaid_result->fetch_assoc();
          echo "₱" . number_format($unpaid_row['total'] ?? 0, 2);
        ?>
      </div>
    </div>
    <div class="summary-card">
      <h3>Due this Month</h3>
      <div class="summary-value">
        <?php
          $month_query = "SELECT COUNT(*) as total FROM accounts_payable 
                         WHERE MONTH(due_date) = MONTH(CURRENT_DATE()) 
                         AND YEAR(due_date) = YEAR(CURRENT_DATE())";
          $month_result = $conn->query($month_query);
          $month_row = $month_result->fetch_assoc();
          echo $month_row['total'];
        ?>
      </div>
    </div>
  </div>
  
  <!-- Add/Edit Form -->
  <div class="form-container">
    <h3><?php echo !empty($payable_id) ? 'Edit Payable' : 'Add New Payable'; ?></h3>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <input type="hidden" name="payable_id" value="<?php echo $payable_id; ?>">
      <input type="hidden" name="action" value="<?php echo !empty($payable_id) ? 'update' : 'add'; ?>">
      
      <div class="form-row">
        <div class="form-group">
          <label for="title">Vendor/Supplier *</label>
          <input type="text" id="title" name="title" class="form-control" value="<?php echo $title; ?>" required>
        </div>
        <div class="form-group">
          <label for="amount">Amount (₱) *</label>
          <input type="number" id="amount" name="amount" class="form-control" value="<?php echo $amount; ?>" step="0.01" required>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label for="description">Description</label>
          <input type="text" id="description" name="description" class="form-control" value="<?php echo $description; ?>">
        </div>
        <div class="form-group">
          <label for="due_date">Due Date *</label>
          <input type="date" id="due_date" name="due_date" class="form-control" value="<?php echo $due_date; ?>" required>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label for="status">Status</label>
          <select id="status" name="status" class="form-control">
            <option value="Unpaid" <?php echo ($status == 'Unpaid') ? 'selected' : ''; ?>>Unpaid</option>
            <option value="Partial" <?php echo ($status == 'Partial') ? 'selected' : ''; ?>>Partially Paid</option>
            <option value="Paid" <?php echo ($status == 'Paid') ? 'selected' : ''; ?>>Paid</option>
          </select>
        </div>
      </div>
      
      <div class="btn-container">
        <?php if(!empty($payable_id)): ?>
          <a href="accounts_payable.php" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">
          <?php echo !empty($payable_id) ? 'Update Payable' : 'Add Payable'; ?>
        </button>
      </div>
    </form>
  </div>
  
  <!-- Filter Options -->
  <div class="filter-container">
    <label for="status-filter">Filter by Status:</label>
    <select id="status-filter" class="filter-select">
      <option value="all">All</option>
      <option value="Unpaid">Unpaid</option>
      <option value="Partial">Partially Paid</option>
      <option value="Paid">Paid</option>
    </select>
    
    <label for="date-filter">Filter by Date:</label>
    <select id="date-filter" class="filter-select">
      <option value="all">All</option>
      <option value="this-month">This Month</option>
      <option value="overdue">Overdue</option>
      <option value="next-30">Next 30 Days</option>
    </select>
  </div>
  
  <!-- Data Table -->
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Vendor/Supplier</th>
          <th>Description</th>
          <th>Amount</th>
          <th>Due Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr class="payable-row" data-status="<?php echo $row['status']; ?>" data-due="<?php echo $row['due_date']; ?>">
              <td><?php echo $row['id']; ?></td>
              <td><?php echo htmlspecialchars($row['title']); ?></td>
              <td><?php echo htmlspecialchars($row['description']); ?></td>
              <td>₱<?php echo number_format($row['amount'], 2); ?></td>
              <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
              <td>
                <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                  <?php echo $row['status']; ?>
                </span>
              </td>
              <td class="action-column">
                <a href="accounts_payable.php?edit=<?php echo $row['id']; ?>" class="action-btn edit-btn">
                  <i class="fas fa-edit"></i>
                </a>

                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align: center;">No payables found</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>


// Filter functionality
document.getElementById('status-filter').addEventListener('change', filterPayables);
document.getElementById('date-filter').addEventListener('change', filterPayables);

function filterPayables() {
  const statusFilter = document.getElementById('status-filter').value;
  const dateFilter = document.getElementById('date-filter').value;
  const today = new Date();
  
  const rows = document.querySelectorAll('.payable-row');
  
  rows.forEach(row => {
    const status = row.getAttribute('data-status');
    const dueDate = new Date(row.getAttribute('data-due'));
    
    let showByStatus = statusFilter === 'all' || status === statusFilter;
    let showByDate = true;
    
    if (dateFilter === 'this-month') {
      showByDate = dueDate.getMonth() === today.getMonth() && 
                   dueDate.getFullYear() === today.getFullYear();
    } else if (dateFilter === 'overdue') {
      showByDate = dueDate < today;
    } else if (dateFilter === 'next-30') {
      const thirtyDaysLater = new Date();
      thirtyDaysLater.setDate(today.getDate() + 30);
      showByDate = dueDate >= today && dueDate <= thirtyDaysLater;
    }
    
    if (showByStatus && showByDate) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}
</script>

</body>
</html>

<?php
// Close connection
$conn->close();
?>