<?php
// Database connection
//require 'config.php';

// Fetch drivers from the database
//$sql = "SELECT id, name, email, phone FROM drivers";
//$result = $conn->query($sql);



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
    }
    .header h2 { color: #fff; font-size: 28px; margin-bottom: 10px; }
    .nav-container {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 20px;
      margin-bottom: 20px;
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

<<div class="header">
  <h2>Contract File</h2>
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


<div style="  
  margin-top: 30px; background:  #2f4a5f; padding: 20px; border-radius: 10px; width: 800px;">
  <h3 style="color: #fff;">Upload PDF Document</h3>
  <form action="upload_pdf.php" method="post" enctype="multipart/form-data">
    <input type="file" name="pdf_file" accept="application/pdf" required style="margin-top:10px;"><br><br>
    <button class="action-button" type="submit" name="upload">Upload PDF</button>
    
    <?php
      // Directory where PDFs are saved
      $pdfDir = 'uploads/';
      $pdfFiles = [];

      if (is_dir($pdfDir)) {
          $files = scandir($pdfDir);
          foreach ($files as $file) {
              if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                  $pdfFiles[] = $file;
              }
          }
      }
    ?>

  </form>
  <?php if (!empty($pdfFiles)): ?>
    <div style="margin-top: 20px; background: #1f2d3a; padding: 20px; border-radius: 12px; border: 1px solid #4a637a;">
      <h4 style="color:rgb(239, 237, 232); margin-bottom: 15px;">📄 Saved PDF Files</h4>
      <ul style="list-style: none; padding-left: 0;">
        <?php foreach ($pdfFiles as $pdf): ?>
          <li style="margin: 10px 0; background: #2f4a5f; padding: 10px 15px; border-radius: 8px;">
            <a href="uploads/<?= htmlspecialchars($pdf) ?>" target="_blank" style="color:rgb(254, 254, 254); text-decoration: none; font-weight: bold;">
              <i class="fas fa-file-pdf" style="margin-right: 8px;"></i><?= htmlspecialchars($pdf) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

  <?php else: ?>
    <div style="margin-top: 20px; color: #ddd; font-style: italic;">
      No PDFs uploaded yet.
    </div>
  <?php endif; ?>

</div>

</body>
</html>
