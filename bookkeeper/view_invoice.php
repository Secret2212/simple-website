<?php
// Simulated invoice data (replace this with dynamic data from your database)
$invoice = [
    'invoice_no' => 'INV-00123',
    'date' => '2025-05-22',
    'due_date' => '2025-05-29',
    'company' => [
        'name' => 'Acme Corporation',
        'address' => '123 Business Rd, Cityville, USA',
        'email' => 'info@acme.com',
        'phone' => '(123) 456-7890'
    ],
    'client' => [
        'name' => 'John Doe',
        'address' => '456 Client St, Townville, USA',
        'email' => 'john@example.com',
        'phone' => '(987) 654-3210'
    ],
    'items' => [
        ['description' => 'Delivery to 5th Ave', 'qty' => 1, 'unit_price' => 50.00],
        ['description' => 'Return Pickup', 'qty' => 1, 'unit_price' => 25.00],
    ],
    'tax_rate' => 0.12
];

// Calculate totals
$subtotal = 0;
foreach ($invoice['items'] as $item) {
    $subtotal += $item['qty'] * $item['unit_price'];
}
$tax = $subtotal * $invoice['tax_rate'];
$total = $subtotal + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $invoice['invoice_no'] ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            color: #333;
        }
        h1, h2, h3 {
            margin: 0;
        }
        .header, .client-info, .invoice-details {
            margin-bottom: 20px;
        }
        .company, .client {
            width: 45%;
            display: inline-block;
            vertical-align: top;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .invoice-table th, .invoice-table td {
            border: 1px solid #ddd;
            padding: 10px;
        }
        .invoice-table th {
            background-color: #f4f4f4;
        }
        .totals {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 5px;
        }
        .print-btn {
            margin-top: 30px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>INVOICE</h1>
</div>

<div class="invoice-details">
    <div class="company">
        <h3><?= $invoice['company']['name'] ?></h3>
        <p><?= $invoice['company']['address'] ?></p>
        <p>Email: <?= $invoice['company']['email'] ?></p>
        <p>Phone: <?= $invoice['company']['phone'] ?></p>
    </div>

    <div class="client">
        <h3>Billed To:</h3>
        <p><?= $invoice['client']['name'] ?></p>
        <p><?= $invoice['client']['address'] ?></p>
        <p>Email: <?= $invoice['client']['email'] ?></p>
        <p>Phone: <?= $invoice['client']['phone'] ?></p>
    </div>
</div>

<div class="invoice-info">
    <p><strong>Invoice #: </strong><?= $invoice['invoice_no'] ?></p>
    <p><strong>Invoice Date: </strong><?= $invoice['date'] ?></p>
    <p><strong>Due Date: </strong><?= $invoice['due_date'] ?></p>
</div>

<table class="invoice-table">
    <thead>
    <tr>
        <th>Description</th>
        <th>Quantity</th>
        <th>Unit Price</th>
        <th>Line Total</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($invoice['items'] as $item): ?>
        <tr>
            <td><?= $item['description'] ?></td>
            <td><?= $item['qty'] ?></td>
            <td>$<?= number_format($item['unit_price'], 2) ?></td>
            <td>$<?= number_format($item['qty'] * $item['unit_price'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="totals">
    <table>
        <tr>
            <td><strong>Subtotal:</strong></td>
            <td>$<?= number_format($subtotal, 2) ?></td>
        </tr>
        <tr>
            <td><strong>Tax (<?= $invoice['tax_rate'] * 100 ?>%):</strong></td>
            <td>$<?= number_format($tax, 2) ?></td>
        </tr>
        <tr>
            <td><strong>Total:</strong></td>
            <td><strong>$<?= number_format($total, 2) ?></strong></td>
        </tr>
    </table>
</div>

<div class="print-btn">
    <button onclick="window.print()">Print Invoice</button>
</div>

</body>
</html>
