<?php


// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'truckingsystem';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = "AND (i.invoice_number LIKE '%$search%' OR i.customer_name LIKE '%$search%')";
}

// Filter by status
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$status_condition = '';
if (!empty($status_filter)) {
    $status_condition = "AND i.status = '$status_filter'";
}

// Sort functionality
$sort_column = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : 'i.created_at';
$sort_direction = isset($_GET['dir']) ? $conn->real_escape_string($_GET['dir']) : 'DESC';

$valid_columns = ['i.invoice_number', 'i.invoice_date', 'i.customer_name', 'i.total_amount', 'i.status', 'i.created_at'];
if (!in_array($sort_column, $valid_columns)) {
    $sort_column = 'i.created_at';
}

$valid_directions = ['ASC', 'DESC'];
if (!in_array($sort_direction, $valid_directions)) {
    $sort_direction = 'DESC';
}

// Get invoice list with proper filters and sorting
$sql = "SELECT 
            i.id, 
            i.invoice_number, 
            i.invoice_date, 
            i.customer_name, 
            i.total_amount, 
            i.currency,
            i.status, 
            i.created_at,
            COUNT(ih.id) as history_count
        FROM 
            invoices i
        LEFT JOIN 
            invoice_history ih ON i.id = ih.invoice_id
        WHERE 
            1=1 $search_condition $status_condition
        GROUP BY 
            i.id
        ORDER BY 
            $sort_column $sort_direction
        LIMIT 
            $offset, $records_per_page";

$result = $conn->query($sql);

// Get total records for pagination
$total_records_sql = "SELECT 
                        COUNT(DISTINCT i.id) as total 
                     FROM 
                        invoices i 
                     WHERE 
                        1=1 $search_condition $status_condition";
$total_result = $conn->query($total_records_sql);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get all available statuses for the filter dropdown
$statuses_sql = "SELECT DISTINCT status FROM invoices ORDER BY status";
$statuses_result = $conn->query($statuses_sql);
$statuses = [];
while ($status_row = $statuses_result->fetch_assoc()) {
    $statuses[] = $status_row['status'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }
        body { background-color:  #3b5870; min-height: 100vh; padding: 20px; }
        
        .header {
            background-color: #2f4a5f;
            width: 100%;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            margin-bottom: 30px;
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
            margin-top: 20px;
        }
        
        .nav-button, .action-button {
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
        }
        
        .nav-button:hover, .action-button:hover {
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

        .container {
            max-width: 1200px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        h1 {
            font-size: 28px;    margin-bottom: 20px; color: #333; text-align: center;
        }
        
        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .search-box {
            display: flex;
            max-width: 400px;
            width: 100%;
        }
        
        .search-box input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-right: none;
            border-radius: 5px 0 0 5px;
            font-size: 14px;
        }
        
        .search-box button {
            padding: 10px 15px;
            background-color: #3b5870;
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
        }
        
        .filter-box {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-box select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .invoice-table th, .invoice-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        .invoice-table th {
            background-color: #f4f7f9;
            color: #333;
            font-weight: bold;
            position: relative;
            cursor: pointer;
        }
        
        .invoice-table th:hover {
            background-color: #e8eef2;
        }
        
        .invoice-table th .sort-icon {
            margin-left: 5px;
            font-size: 12px;
        }
        
        .invoice-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .invoice-table tr:hover {
            background-color: #f0f0f0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-overdue {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-cancelled {
            background-color: #f2f2f2;
            color: #555;
        }
        
        .status-draft {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 6px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn-view {
            background-color: #3b5870;
            color: white;
        }
        
        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .pagination a:hover {
            background-color: #f4f7f9;
        }
        
        .pagination .active {
            background-color: #3b5870;
            color: white;
            border-color: #3b5870;
        }
        
        .no-results {
            text-align: center;
            padding: 20px;
            font-size: 16px;
            color: #666;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            flex: 1;
            min-width: 200px;
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid #3b5870;
        }
        
        .stat-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 22px;
            font-weight: bold;
            color: #333;
        }
        
        .add-invoice {
            margin-bottom: 20px;
            text-align: right;
        }
        
        .btn-add {
            background-color: #f4a825;
            color: white;
            padding: 10px 15px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .invoice-table {
                display: block;
                overflow-x: auto;
            }
            
            .stats {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

 <div class="header">
  <h2>Invoice History</h2>
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

    <div class="container">
        <h1>Invoice History</h1>
        
        <?php
        // Get some quick stats for the dashboard
        $total_invoices_sql = "SELECT COUNT(*) as total FROM invoices";
        $paid_invoices_sql = "SELECT COUNT(*) as total FROM invoices WHERE status = 'Paid'";
        $pending_invoices_sql = "SELECT COUNT(*) as total FROM invoices WHERE status = 'Pending'";
        $total_value_sql = "SELECT SUM(total_amount) as total FROM invoices";
        
        $total_invoices = $conn->query($total_invoices_sql)->fetch_assoc()['total'];
        $paid_invoices = $conn->query($paid_invoices_sql)->fetch_assoc()['total'];
        $pending_invoices = $conn->query($pending_invoices_sql)->fetch_assoc()['total'];
        $total_value = $conn->query($total_value_sql)->fetch_assoc()['total'] ?? 0;
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-title">Total Invoices</div>
                <div class="stat-value"><?php echo $total_invoices; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #28a745;">
                <div class="stat-title">Paid Invoices</div>
                <div class="stat-value"><?php echo $paid_invoices; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #ffc107;">
                <div class="stat-title">Pending Invoices</div>
                <div class="stat-value"><?php echo $pending_invoices; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #17a2b8;">
                <div class="stat-title">Total Value</div>
                <div class="stat-value"><?php echo number_format($total_value, 2); ?></div>
            </div>
        </div>
        
    
        
        <div class="controls">
            <form action="" method="GET" class="search-box">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search invoice number or customer...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
            
            <div class="filter-box">
                <label for="status-filter">Filter by status:</label>
                <select id="status-filter" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <?php foreach($statuses as $status): ?>
                    <option value="<?php echo $status; ?>" <?php echo ($status_filter == $status) ? 'selected' : ''; ?>>
                        <?php echo $status; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>
                            <a href="?sort=i.invoice_number&dir=<?php echo ($sort_column == 'i.invoice_number' && $sort_direction == 'ASC') ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                Invoice #
                                <?php if ($sort_column == 'i.invoice_number'): ?>
                                <i class="fas fa-sort-<?php echo ($sort_direction == 'ASC') ? 'up' : 'down'; ?> sort-icon"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=i.invoice_date&dir=<?php echo ($sort_column == 'i.invoice_date' && $sort_direction == 'ASC') ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                Date
                                <?php if ($sort_column == 'i.invoice_date'): ?>
                                <i class="fas fa-sort-<?php echo ($sort_direction == 'ASC') ? 'up' : 'down'; ?> sort-icon"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=i.customer_name&dir=<?php echo ($sort_column == 'i.customer_name' && $sort_direction == 'ASC') ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                Customer
                                <?php if ($sort_column == 'i.customer_name'): ?>
                                <i class="fas fa-sort-<?php echo ($sort_direction == 'ASC') ? 'up' : 'down'; ?> sort-icon"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=i.total_amount&dir=<?php echo ($sort_column == 'i.total_amount' && $sort_direction == 'ASC') ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                Amount
                                <?php if ($sort_column == 'i.total_amount'): ?>
                                <i class="fas fa-sort-<?php echo ($sort_direction == 'ASC') ? 'up' : 'down'; ?> sort-icon"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=i.status&dir=<?php echo ($sort_column == 'i.status' && $sort_direction == 'ASC') ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                Status
                                <?php if ($sort_column == 'i.status'): ?>
                                <i class="fas fa-sort-<?php echo ($sort_direction == 'ASC') ? 'up' : 'down'; ?> sort-icon"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['invoice_number']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['invoice_date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo number_format($row['total_amount'], 2) . ' ' . htmlspecialchars($row['currency']); ?></td>
                        <td>
                            <?php 
                            $status_class = '';
                            switch(strtolower($row['status'])) {
                                case 'paid':
                                    $status_class = 'status-paid';
                                    break;
                                case 'pending':
                                    $status_class = 'status-pending';
                                    break;
                                case 'overdue':
                                    $status_class = 'status-overdue';
                                    break;
                                case 'cancelled':
                                    $status_class = 'status-cancelled';
                                    break;
                                case 'draft':
                                    $status_class = 'status-draft';
                                    break;
                                default:
                                    $status_class = '';
                            }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="view_invoice.php?id=<?php echo $row['id']; ?>" class="btn btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>    

                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=1&sort=<?php echo $sort_column; ?>&dir=<?php echo $sort_direction; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">First</a>
            <a href="?page=<?php echo ($page - 1); ?>&sort=<?php echo $sort_column; ?>&dir=<?php echo $sort_direction; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Previous</a>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++): 
            ?>
                <?php if ($i == $page): ?>
                <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="?page=<?php echo $i; ?>&sort=<?php echo $sort_column; ?>&dir=<?php echo $sort_direction; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo ($page + 1); ?>&sort=<?php echo $sort_column; ?>&dir=<?php echo $sort_direction; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Next</a>
            <a href="?page=<?php echo $total_pages; ?>&sort=<?php echo $sort_column; ?>&dir=<?php echo $sort_direction; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Last</a>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        <div class="no-results">
            <p>No invoices found matching your criteria.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
                window.location.href = 'delete_invoice.php?id=' + id;
            }
        }
    </script>
</body>
</html>