<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$host = 'localhost';  
$username = 'root';
$password = '';
$database = 'truckingsystem';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the next invoice number
function getNextInvoiceNumber($conn) {
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) as last_num FROM invoices";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $nextNum = ($row['last_num'] ?? 0) + 1;
    return "INV-" . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
}

// Initialize variables
$success_message = '';
$error_message = '';
$invoice_number = getNextInvoiceNumber($conn);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data safely
    $customer_name = $conn->real_escape_string($_POST['customer_name'] ?? '');
    $customer_address = $conn->real_escape_string($_POST['customer_address'] ?? '');
    $business_style = $conn->real_escape_string($_POST['business_style'] ?? '');
    $osca_pwd_id = $conn->real_escape_string($_POST['osca_pwd_id'] ?? '');
    $tin = $conn->real_escape_string($_POST['tin'] ?? '');
    $invoice_date = $conn->real_escape_string($_POST['invoice_date'] ?? date('Y-m-d'));
    $terms = $conn->real_escape_string($_POST['terms'] ?? '');
    $currency = $conn->real_escape_string($_POST['currency'] ?? 'PHP');
    $subtotal = isset($_POST['subtotal']) ? floatval(str_replace(',', '', $_POST['subtotal'])) : 0;
    $vat_percent = isset($_POST['vat_percent']) ? floatval($_POST['vat_percent']) : 12;
    $vat_amount = isset($_POST['vat_amount']) ? floatval(str_replace(',', '', $_POST['vat_amount'])) : 0;
    $discount = isset($_POST['discount']) ? floatval(str_replace(',', '', $_POST['discount'])) : 0;
    $total_amount = isset($_POST['total_amount']) ? floatval(str_replace(',', '', $_POST['total_amount'])) : 0;
    $status = 'pending'; // Default status

    try {
        // Start transaction
        $conn->begin_transaction();

        // Insert into invoices table
        $sql_invoice = "INSERT INTO invoices (
                            invoice_number, customer_name, customer_address, business_style, 
                            osca_pwd_id, tin, invoice_date, terms, currency, subtotal, 
                            vat_percent, vat_amount, discount, total_amount, status, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql_invoice);
        $stmt->bind_param("sssssssssddddss", 
            $invoice_number, $customer_name, $customer_address, $business_style, 
            $osca_pwd_id, $tin, $invoice_date, $terms, $currency, $subtotal, 
            $vat_percent, $vat_amount, $discount, $total_amount, $status
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting invoice: " . $stmt->error);
        }
        
        $invoice_id = $stmt->insert_id;

        // Insert invoice items
        if (isset($_POST['item_description']) && is_array($_POST['item_description'])) {
            $sql_item = "INSERT INTO invoice_items (
                            invoice_id, description, quantity, unit, unit_price, amount
                        ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt_item = $conn->prepare($sql_item);
            
            for ($i = 0; $i < count($_POST['item_description']); $i++) {
                if (empty($_POST['item_description'][$i])) continue;
                
                $description = $conn->real_escape_string($_POST['item_description'][$i]);
                $quantity = floatval($_POST['item_quantity'][$i]);
                $unit = $conn->real_escape_string($_POST['item_unit'][$i]);
                $unit_price = floatval($_POST['item_price'][$i]);
                $amount = floatval($_POST['item_amount'][$i]);
                
                $stmt_item->bind_param("isdsdd", 
                    $invoice_id, $description, $quantity, $unit, $unit_price, $amount
                );
                
                if (!$stmt_item->execute()) {
                    throw new Exception("Error inserting invoice item: " . $stmt_item->error);
                }
            }
        }

       

        // Commit transaction
        $conn->commit();
        
        $success_message = "Invoice #$invoice_number created successfully!";
        $invoice_number = getNextInvoiceNumber($conn); // Get new invoice number for the form
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

// Get currencies
$currencies = ['PHP' => 'Philippine Peso', 'USD' => 'US Dollar', 'EUR' => 'Euro'];

// Get VAT options
$vat_options = [
    'vatable' => 'VATable',
    'vat_exempt' => 'VAT-Exempt',
    'zero_rated' => 'Zero Rated',
    'exempt_12' => '12% Exempt'
];
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
            background-color: #fff;
            border-radius: 10px;
            width: 90%;
            max-width: 1200px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .company-info h2 {
            color: #2f4a5f;
            margin-bottom: 5px;
        }
        
        .company-info p {
            margin: 2px 0;
            color: #555;
        }
        
        .invoice-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .form-section {
            margin-bottom: 20px;
        }
        
        .form-section h3 {
            color: #2f4a5f;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .form-row {
            margin-bottom: 10px;
        }
        
        .form-row label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #444;
        }
        
        .form-row input, .form-row select, .form-row textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: border 0.3s;
        }
        
        .form-row input:focus, .form-row select:focus, .form-row textarea:focus {
            border-color: #f4a825;
            outline: none;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table th {
            background-color: #f4f4f4;
            padding: 12px;
            text-align: left;
            color: #333;
        }
        
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .items-table input, .items-table select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .items-table .delete-row {
            background-color: #ff6b6b;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .items-table .delete-row:hover {
            background-color: #ff5252;
        }
        
        .add-row {
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .add-row:hover {
            background-color: #3e8e41;
        }
        
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .totals-table {
            width: 350px;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px 12px;
        }
        
        .totals-table td:first-child {
            text-align: right;
            font-weight: 500;
        }
        
        .totals-table tr:last-child {
            font-size: 1.2em;
            font-weight: bold;
            background-color: #f9f9f9;
        }
        
        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        
        .action-button.primary {
            background-color: #4CAF50;
        }
        
        .action-button.primary:hover {
            background-color: #3e8e41;
        }
        
        .action-button.secondary {
            background-color: #2196F3;
        }
        
        .action-button.secondary:hover {
            background-color: #0b7dda;
        }
        
        .action-button.danger {
            background-color: #ff5252;
        }
        
        .action-button.danger:hover {
            background-color: #ff3333;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Print Styles */
        @media print {
            .header, .nav-container, .actions, .message, #addItemRow, .delete-row {
                display: none;
            }
            
            body {
                background-color: #fff;
                padding: 0;
            }
            
            .container {
                width: 100%;
                box-shadow: none;
                padding: 0;
                margin: 0;
            }
            
            input, select, textarea {
                border: none !important;
                background: transparent !important;
                pointer-events: none;
            }
            
            .company-info h2, .invoice-number h2 {
                color: #000 !important;
            }
            
            /* More print-specific styles */
            .form-section h3 {
                color: #000 !important;
            }
            
            .items-table th {
                background-color: #f9f9f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            /* Force showing background colors */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
        
        @media (max-width: 768px) {
            .invoice-form {
                grid-template-columns: 1fr;
            }
            
            .container {
                width: 95%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header and Navigation -->
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

    <div class="container" id="invoice-container">
        <?php if (!empty($success_message)): ?>
            <div class="message success">
                <?php echo $success_message; ?>
                <?php if (isset($invoice_id)): ?>
                    <br><br>

                <?php endif; ?>
            </div>
        <?php endif; ?>

        
        <?php if (!empty($error_message)): ?>
            <div class="message error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Invoice Form -->
        <div class="invoice-header">
            <div class="company-info">
                <h2>TAYO TRUCKING SERVICES</h2>
                <p>Pili, Masongsong, Pampanga, Angeles City, Philippines</p>
                <p>JAMES LABASAN TAYO - Prop.</p>
                <p>VAT Reg: TIN 432-867-534-0000</p>
            </div>
            <div class="invoice-number">
                <h2>SERVICE INVOICE</h2>
                <p><strong>Invoice #:</strong> <?php echo $invoice_number; ?></p>
                <p><strong>Date:</strong> <?php echo date('F d, Y'); ?></p>
            </div>
        </div>

        <form method="post" action="" id="invoiceForm">
            <div class="invoice-form">
                <div class="form-section">
                    <h3>Customer Information</h3>
                    <div class="form-row">
                        <label for="customer_name">Customer's Name:</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                    <div class="form-row">
                        <label for="customer_address">Address:</label>
                        <input type="text" id="customer_address" name="customer_address">
                    </div>
                    <div class="form-row">
                        <label for="business_style">Business Style:</label>
                        <input type="text" id="business_style" name="business_style">
                    </div>
                </div>

                <div class="form-section">
                    <h3>Invoice Details</h3>
                    <div class="form-row">
                        <label for="invoice_date">Date:</label>
                        <input type="date" id="invoice_date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-row">
                        <label for="osca_pwd_id">OSCA/PWD ID Number:</label>
                        <input type="text" id="osca_pwd_id" name="osca_pwd_id">
                    </div>
                    <div class="form-row">
                        <label for="tin">TIN:</label>
                        <input type="text" id="tin" name="tin">
                    </div>
                    <div class="form-row">
                        <label for="terms">Terms:</label>
                        <input type="text" id="terms" name="terms">
                    </div>
                    <div class="form-row">
                        <label for="currency">Currency:</label>
                        <select id="currency" name="currency">
                            <?php foreach ($currencies as $code => $name): ?>
                                <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <h3>Invoice Items</h3>
            <button type="button" class="add-row" id="addItemRow">
                <i class="fas fa-plus"></i> Add Item
            </button>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th width="7%">QTY</th>
                        <th width="8%">Unit</th>
                        <th width="37%">Description/Nature of Service</th>
                        <th width="20%">Unit Price</th>
                        <th width="20%">Amount</th>
                        <th width="5%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="item-row">
                        <td><input type="number" name="item_quantity[]" class="item-quantity" min="1" value="1" required></td>
                        <td>
                            <select name="item_unit[]" class="item-unit">
                                <option value="Each">Each</option>
                                <option value="Hour">Hour</option>
                                <option value="Day">Day</option>
                                <option value="Kilometer">Kilometer</option>
                                <option value="Liter">Liter</option>
                                <option value="Trip">Trip</option>
                            </select>
                        </td>
                        <td><input type="text" name="item_description[]" class="item-description" required></td>
                        <td><input type="number" name="item_price[]" class="item-price" min="0" step="0.01" value="0.00" required></td>
                        <td><input type="number" name="item_amount[]" class="item-amount" min="0" step="0.01" value="0.00" readonly></td>
                        <td><button type="button" class="delete-row"><i class="fas fa-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>

            <div class="totals-section">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal:</td>
                        <td><input type="text" name="subtotal" id="subtotal" value="0.00" readonly></td>
                    </tr>
                    <tr class="vat-row">
                        <td>
                            <select name="vat_type" id="vat_type">
                                <?php foreach ($vat_options as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="vat_percent" id="vat_percent" min="0" max="100" step="0.01" value="12" style="width: 60px;">%
                            <input type="text" name="vat_amount" id="vat_amount" value="0.00" readonly style="width: 100px;">
                        </td>
                    </tr>
                    <tr>
                        <td>Less: SC/PWD Discount:</td>
                        <td><input type="text" name="discount" id="discount" value="0.00"></td>
                    </tr>
                    <tr>
                        <td>TOTAL AMOUNT DUE:</td>
                        <td><input type="text" name="total_amount" id="total_amount" value="0.00" readonly></td>
                    </tr>
                </table>
            </div>

            <div class="actions">
                <button type="submit" class="action-button primary">
                    <i class="fas fa-save"></i> Save Invoice
                </button>
                <button type="button" class="action-button secondary" id="print-button">
                    <i class="fas fa-print"></i> Print
                </button>
                <button type="reset" class="action-button danger">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add item row
            document.getElementById('addItemRow').addEventListener('click', function() {
                const tbody = document.querySelector('#itemsTable tbody');
                const firstRow = document.querySelector('.item-row');
                const newRow = firstRow.cloneNode(true);
                
                // Clear values
                newRow.querySelectorAll('input[type="text"], input[type="number"]').forEach(input => {
                    if (input.className === 'item-quantity') {
                        input.value = 1;
                    } else if (input.className === 'item-price' || input.className === 'item-amount') {
                        input.value = '0.00';
                    } else {
                        input.value = '';
                    }
                });
                
                // Add event listeners to new row
                addRowEventListeners(newRow);
                tbody.appendChild(newRow);
            });
            
            // Initialize event listeners for existing rows
            document.querySelectorAll('.item-row').forEach(row => {
                addRowEventListeners(row);
            });
            
            // Add event listeners to row elements
            function addRowEventListeners(row) {
                // Calculate amount when quantity or price changes
                const qtyInput = row.querySelector('.item-quantity');
                const priceInput = row.querySelector('.item-price');
                const amountInput = row.querySelector('.item-amount');
                
                qtyInput.addEventListener('input', updateRowAmount);
                priceInput.addEventListener('input', updateRowAmount);
                
                function updateRowAmount() {
                    const qty = parseFloat(qtyInput.value) || 0;
                    const price = parseFloat(priceInput.value) || 0;
                    amountInput.value = (qty * price).toFixed(2);
                    updateTotals();
                }
                
                // Delete row
                row.querySelector('.delete-row').addEventListener('click', function() {
                    // Don't delete if it's the only row
                    if (document.querySelectorAll('.item-row').length > 1) {
                        row.remove();
                        updateTotals();
                    }
                });
            }
            
            // Update totals when VAT percent or discount changes
            document.getElementById('vat_percent').addEventListener('input', updateTotals);
            document.getElementById('discount').addEventListener('input', updateTotals);
            document.getElementById('vat_type').addEventListener('change', handleVatTypeChange);
            
            // Handle VAT type change
            function handleVatTypeChange() {
                const vatType = document.getElementById('vat_type').value;
                const vatPercentInput = document.getElementById('vat_percent');
                
                switch(vatType) {
                    case 'vatable':
                        vatPercentInput.value = '12';
                        vatPercentInput.disabled = false;
                        break;
                    case 'vat_exempt':
                        vatPercentInput.value = '0';
                        vatPercentInput.disabled = true;
                        break;
                    case 'zero_rated':
                        vatPercentInput.value = '0';
                        vatPercentInput.disabled = true;
                        break;
                    case 'exempt_12':
                        vatPercentInput.value = '12';
                        vatPercentInput.disabled = true;
                        break;
                }
                
                updateTotals();
            }
            
            // Update totals
            function updateTotals() {
                let subtotal = 0;
                
                // Calculate subtotal
                document.querySelectorAll('.item-amount').forEach(input => {
                    subtotal += parseFloat(input.value) || 0;
                });
                
                const vatPercent = parseFloat(document.getElementById('vat_percent').value) || 0;
                const vatAmount = subtotal * (vatPercent / 100);
                let discount = parseFloat(document.getElementById('discount').value) || 0;
                
                // Update form fields
                document.getElementById('subtotal').value = subtotal.toFixed(2);
                document.getElementById('vat_amount').value = vatAmount.toFixed(2);
                document.getElementById('total_amount').value = totalAmount.toFixed(2);
            }
            
            // Print preview
            document.getElementById('print').addEventListener('click', function() {
                // You would typically submit the form to a preview page
                const form = document.getElementById('invoiceForm');
                const formAction = form.getAttribute('action');
                
                form.setAttribute('action', 'invoice_preview.php');
                form.setAttribute('target', '_blank');
                form.submit();
                
                // Reset the form attributes
                form.setAttribute('action', formAction || '');
                form.removeAttribute('target');
            });
            
            // Initialize totals on page load
            updateTotals();
        });
    </script>
</body>
</html>