<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'truckingsystem';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$error = '';

// Initialize variables with default values
$invoice_data = [
    'invoice_number' => '',
    'customer_name' => '',
    'customer_address' => '',
    'business_style' => '',
    'osca_pwd_id' => '',
    'tin' => '',
    'invoice_date' => date('Y-m-d'),
    'terms' => '',
    'currency' => 'PHP',
    'subtotal' => '0.00',
    'vat_percent' => '12.00',
    'vat_amount' => '0.00',
    'discount' => '0.00',
    'total_amount' => '0.00',
    'status' => 'pending',
    'payment_method' => '',
    'payment_reference' => '',
    'payment_date' => null,
    'notes' => ''
];

$invoice_items = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get invoice data from form
    $invoice_data = [
        'invoice_number' => $_POST['invoice_number'],
        'customer_name' => $_POST['customer_name'],
        'customer_address' => $_POST['customer_address'],
        'business_style' => $_POST['business_style'] ?? '',
        'osca_pwd_id' => $_POST['osca_pwd_id'] ?? '',
        'tin' => $_POST['tin'] ?? '',
        'invoice_date' => $_POST['invoice_date'],
        'terms' => $_POST['terms'] ?? '',
        'currency' => $_POST['currency'],
        'subtotal' => $_POST['subtotal'],
        'vat_percent' => $_POST['vat_percent'],
        'vat_amount' => $_POST['vat_amount'],
        'discount' => $_POST['discount'] ?? '0.00',
        'total_amount' => $_POST['total_amount'],
        'status' => 'pending',
        'payment_method' => $_POST['payment_method'] ?? '',
        'payment_reference' => $_POST['payment_reference'] ?? '',
        'payment_date' => !empty($_POST['payment_date']) ? $_POST['payment_date'] : null,
        'notes' => $_POST['notes'] ?? ''
    ];

    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert invoice
        $sql = "INSERT INTO invoices (
            invoice_number, customer_name, customer_address, business_style, 
            osca_pwd_id, tin, invoice_date, terms, currency, subtotal, 
            vat_percent, vat_amount, discount, total_amount, status, 
            payment_method, payment_reference, payment_date, notes, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $created_by = $_SESSION['user_id'];
        
        // Handle null payment_date
        if ($invoice_data['payment_date'] === null) {
            $stmt->bind_param(
                "sssssssssdddddsssis",
                $invoice_data['invoice_number'],
                $invoice_data['customer_name'],
                $invoice_data['customer_address'],
                $invoice_data['business_style'],
                $invoice_data['osca_pwd_id'],
                $invoice_data['tin'],
                $invoice_data['invoice_date'],
                $invoice_data['terms'],
                $invoice_data['currency'],
                $invoice_data['subtotal'],
                $invoice_data['vat_percent'],
                $invoice_data['vat_amount'],
                $invoice_data['discount'],
                $invoice_data['total_amount'],
                $invoice_data['status'],
                $invoice_data['payment_method'],
                $invoice_data['payment_reference'],
                null,
                $invoice_data['notes'],
                $created_by
            );
        } else {
            $stmt->bind_param(
                "sssssssssdddddsssis",
                $invoice_data['invoice_number'],
                $invoice_data['customer_name'],
                $invoice_data['customer_address'],
                $invoice_data['business_style'],
                $invoice_data['osca_pwd_id'],
                $invoice_data['tin'],
                $invoice_data['invoice_date'],
                $invoice_data['terms'],
                $invoice_data['currency'],
                $invoice_data['subtotal'],
                $invoice_data['vat_percent'],
                $invoice_data['vat_amount'],
                $invoice_data['discount'],
                $invoice_data['total_amount'],
                $invoice_data['status'],
                $invoice_data['payment_method'],
                $invoice_data['payment_reference'],
                $invoice_data['payment_date'],
                $invoice_data['notes'],
                $created_by
            );
        }
        
        $stmt->execute();
        $invoice_id = $conn->insert_id;
        
        // Insert invoice items
        if (isset($_POST['item_description']) && is_array($_POST['item_description'])) {
            $sql_item = "INSERT INTO invoice_items (invoice_id, description, quantity, unit, unit_price, amount) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_item = $conn->prepare($sql_item);
            
            for ($i = 0; $i < count($_POST['item_description']); $i++) {
                if (!empty($_POST['item_description'][$i])) {
                    $description = $_POST['item_description'][$i];
                    $quantity = $_POST['item_quantity'][$i];
                    $unit = $_POST['item_unit'][$i];
                    $unit_price = $_POST['item_price'][$i];
                    $amount = $_POST['item_amount'][$i];
                    
                    $stmt_item->bind_param("isdsdd", $invoice_id, $description, $quantity, $unit, $unit_price, $amount);
                    $stmt_item->execute();
                }
            }
        }
        
        // Add to invoice_history
        $sql_history = "INSERT INTO invoice_history (invoice_id, action, performed_by, notes) VALUES (?, ?, ?, ?)";
        $stmt_history = $conn->prepare($sql_history);
        $action = "Invoice created";
        $notes = "Initial invoice creation";
        $stmt_history->bind_param("isis", $invoice_id, $action, $created_by, $notes);
        $stmt_history->execute();
        
        // Commit transaction
        $conn->commit();
        
        $message = "Invoice #" . $invoice_data['invoice_number'] . " has been successfully created.";
        
        // Redirect to the invoice view page
        header("Location: view_invoice.php?id=$invoice_id&success=1");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Generate new invoice number (format: INV-YYYYMMDD-XXX)
$today = date('Ymd');
$sql = "SELECT MAX(invoice_number) as last_invoice FROM invoices WHERE invoice_number LIKE 'INV-$today-%'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

$last_num = 0;
if ($row['last_invoice']) {
    $parts = explode('-', $row['last_invoice']);
    $last_num = intval(end($parts));
}
$new_num = $last_num + 1;
$invoice_data['invoice_number'] = "INV-$today-" . sprintf('%03d', $new_num);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Invoice</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="date"], input[type="number"], select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -10px;
            margin-left: -10px;
        }
        .col {
            flex: 1;
            padding: 0 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        .action-buttons {
            text-align: center;
            margin-top: 20px;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        button.btn-secondary {
            background-color: #6c757d;
        }
        button.btn-secondary:hover {
            background-color: #5a6268;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .remove-item {
            color: red;
            cursor: pointer;
        }
        .totals-section {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        .totals-table {
            width: 100%;
        }
        .totals-table td {
            padding: 5px;
        }
        .totals-table td:last-child {
            text-align: right;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Create New Invoice</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <form method="post" action="" id="invoiceForm">
        <div class="row">
            <div class="col">
                <div class="form-group">
                    <label for="invoice_number">Invoice Number:</label>
                    <input type="text" id="invoice_number" name="invoice_number" value="<?php echo htmlspecialchars($invoice_data['invoice_number']); ?>" required readonly>
                </div>
                
                <div class="form-group">
                    <label for="invoice_date">Invoice Date:</label>
                    <input type="date" id="invoice_date" name="invoice_date" value="<?php echo htmlspecialchars($invoice_data['invoice_date']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="pending" <?php if ($invoice_data['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="paid" <?php if ($invoice_data['status'] == 'paid') echo 'selected'; ?>>Paid</option>
                        <option value="cancelled" <?php if ($invoice_data['status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                </div>
            </div>
            
            <div class="col">
                <div class="form-group">
                    <label for="customer_name">Customer Name:</label>
                    <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($invoice_data['customer_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_address">Customer Address:</label>
                    <input type="text" id="customer_address" name="customer_address" value="<?php echo htmlspecialchars($invoice_data['customer_address']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="business_style">Business Style:</label>
                    <input type="text" id="business_style" name="business_style" value="<?php echo htmlspecialchars($invoice_data['business_style']); ?>">
                </div>
            </div>
            
            <div class="col">
                <div class="form-group">
                    <label for="tin">TIN:</label>
                    <input type="text" id="tin" name="tin" value="<?php echo htmlspecialchars($invoice_data['tin']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="osca_pwd_id">OSCA/PWD ID:</label>
                    <input type="text" id="osca_pwd_id" name="osca_pwd_id" value="<?php echo htmlspecialchars($invoice_data['osca_pwd_id']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="terms">Terms:</label>
                    <input type="text" id="terms" name="terms" value="<?php echo htmlspecialchars($invoice_data['terms']); ?>">
                </div>
            </div>
        </div>
        
        <h3>Invoice Items</h3>
        
        <table id="itemsTable">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Unit Price</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="itemsBody">
                <tr>
                    <td><input type="text" name="item_description[]" required></td>
                    <td><input type="number" name="item_quantity[]" step="0.01" min="0" value="1" onchange="calculateItemAmount(this)" required></td>
                    <td><input type="text" name="item_unit[]" value="unit" required></td>
                    <td><input type="number" name="item_price[]" step="0.01" min="0" value="0.00" onchange="calculateItemAmount(this)" required></td>
                    <td><input type="number" name="item_amount[]" step="0.01" min="0" value="0.00" readonly></td>
                    <td><i class="fas fa-trash remove-item" onclick="removeItem(this)"></i></td>
                </tr>
            </tbody>
        </table>
        
        <button type="button" onclick="addItem()" class="btn-secondary">+ Add Item</button>
        
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Currency:</td>
                    <td>
                        <select id="currency" name="currency">
                            <option value="PHP" <?php if ($invoice_data['currency'] == 'PHP') echo 'selected'; ?>>PHP</option>
                            <option value="USD" <?php if ($invoice_data['currency'] == 'USD') echo 'selected'; ?>>USD</option>
                            <option value="EUR" <?php if ($invoice_data['currency'] == 'EUR') echo 'selected'; ?>>EUR</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Subtotal:</td>
                    <td><input type="number" id="subtotal" name="subtotal" step="0.01" min="0" value="<?php echo htmlspecialchars($invoice_data['subtotal']); ?>" readonly></td>
                </tr>
                <tr>
                    <td>VAT (%):</td>
                    <td><input type="number" id="vat_percent" name="vat_percent" step="0.01" min="0" value="<?php echo htmlspecialchars($invoice_data['vat_percent']); ?>" onchange="calculateTotals()"></td>
                </tr>
                <tr>
                    <td>VAT Amount:</td>
                    <td><input type="number" id="vat_amount" name="vat_amount" step="0.01" min="0" value="<?php echo htmlspecialchars($invoice_data['vat_amount']); ?>" readonly></td>
                </tr>
                <tr>
                    <td>Discount:</td>
                    <td><input type="number" id="discount" name="discount" step="0.01" min="0" value="<?php echo htmlspecialchars($invoice_data['discount']); ?>" onchange="calculateTotals()"></td>
                </tr>
                <tr>
                    <td><strong>Total Amount:</strong></td>
                    <td><input type="number" id="total_amount" name="total_amount" step="0.01" min="0" value="<?php echo htmlspecialchars($invoice_data['total_amount']); ?>" readonly></td>
                </tr>
            </table>
        </div>
        
        <div style="clear:both;"></div>
        
        <h3>Payment Information</h3>
        
        <div class="row">
            <div class="col">
                <div class="form-group">
                    <label for="payment_method">Payment Method:</label>
                    <select id="payment_method" name="payment_method">
                        <option value="">Select Method</option>
                        <option value="cash" <?php if ($invoice_data['payment_method'] == 'cash') echo 'selected'; ?>>Cash</option>
                        <option value="bank_transfer" <?php if ($invoice_data['payment_method'] == 'bank_transfer') echo 'selected'; ?>>Bank Transfer</option>
                        <option value="check" <?php if ($invoice_data['payment_method'] == 'check') echo 'selected'; ?>>Check</option>
                        <option value="credit_card" <?php if ($invoice_data['payment_method'] == 'credit_card') echo 'selected'; ?>>Credit Card</option>
                        <option value="other" <?php if ($invoice_data['payment_method'] == 'other') echo 'selected'; ?>>Other</option>
                    </select>
                </div>
            </div>
            
            <div class="col">
                <div class="form-group">
                    <label for="payment_reference">Payment Reference:</label>
                    <input type="text" id="payment_reference" name="payment_reference" value="<?php echo htmlspecialchars($invoice_data['payment_reference']); ?>">
                </div>
            </div>
            
            <div class="col">
                <div class="form-group">
                    <label for="payment_date">Payment Date:</label>
                    <input type="date" id="payment_date" name="payment_date" value="<?php echo htmlspecialchars($invoice_data['payment_date'] ?? ''); ?>">
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="notes">Notes:</label>
            <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($invoice_data['notes']); ?></textarea>
        </div>
        
        <div class="action-buttons">
            <button type="submit">Save Invoice</button>
            <button type="button" class="btn-secondary" onclick="location.href='invoice.php'">Cancel</button>
        </div>
    </form>
</div>

<script>
    function addItem() {
        var tbody = document.getElementById('itemsBody');
        var newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td><input type="text" name="item_description[]" required></td>
            <td><input type="number" name="item_quantity[]" step="0.01" min="0" value="1" onchange="calculateItemAmount(this)" required></td>
            <td><input type="text" name="item_unit[]" value="unit" required></td>
            <td><input type="number" name="item_price[]" step="0.01" min="0" value="0.00" onchange="calculateItemAmount(this)" required></td>
            <td><input type="number" name="item_amount[]" step="0.01" min="0" value="0.00" readonly></td>
            <td><i class="fas fa-trash remove-item" onclick="removeItem(this)"></i></td>
        `;
        tbody.appendChild(newRow);
    }
    
    function removeItem(element) {
        var row = element.closest('tr');
        if (document.getElementById('itemsBody').rows.length > 1) {
            row.remove();
            calculateTotals();
        } else {
            alert('Cannot remove the last item row.');
        }
    }
    
    function calculateItemAmount(element) {
        var row = element.closest('tr');
        var quantity = parseFloat(row.querySelector('input[name="item_quantity[]"]').value) || 0;
        var price = parseFloat(row.querySelector('input[name="item_price[]"]').value) || 0;
        var amount = quantity * price;
        row.querySelector('input[name="item_amount[]"]').value = amount.toFixed(2);
        calculateTotals();
    }
    
    function calculateTotals() {
        var subtotal = 0;
        var amountInputs = document.getElementsByName('item_amount[]');
        
        for (var i = 0; i < amountInputs.length; i++) {
            subtotal += parseFloat(amountInputs[i].value) || 0;
        }
        
        document.getElementById('subtotal').value = subtotal.toFixed(2);
        
        var vatPercent = parseFloat(document.getElementById('vat_percent').value) || 0;
        var vatAmount = subtotal * (vatPercent / 100);
        document.getElementById('vat_amount').value = vatAmount.toFixed(2);
        
        var discount = parseFloat(document.getElementById('discount').value) || 0;
        var totalAmount = subtotal + vatAmount - discount;
        document.getElementById('total_amount').value = totalAmount.toFixed(2);
    }
    
    // Initialize calculations on load
    window.onload = function() {
        var itemRows = document.getElementById('itemsBody').rows;
        for (var i = 0; i < itemRows.length; i++) {
            calculateItemAmount(itemRows[i].querySelector('input[name="item_quantity[]"]'));
        }
    };
</script>

</body>
</html>