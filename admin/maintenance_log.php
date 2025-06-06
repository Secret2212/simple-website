<?php
session_start();



// Database connection configuration
$servername = "localhost";
$username = "root";  // Replace with your actual database username
$password = "";      // Replace with your actual database password
$dbname = "truckingsystem";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $truck_id    = intval($_POST['truck_id']);
    $date        = $_POST['date'];
    $time        = $_POST['time'];
    $description = trim($_POST['description']);
    
    if ($truck_id > 0 && $date && $time && $description !== '') {
        // Create maintenance_schedule table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS maintenance_schedule (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            truck_id INT(11) NOT NULL,
            scheduled_date DATE NOT NULL,
            scheduled_time TIME NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (truck_id) REFERENCES trucks(id)
        )";
        
        if ($conn->query($createTable) === TRUE) {
            // Insert the maintenance schedule
            $stmt = $conn->prepare("INSERT INTO maintenance_schedule (truck_id, scheduled_date, scheduled_time, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('isss', $truck_id, $date, $time, $description);
            
            if ($stmt->execute()) {
                $message = 'Maintenance schedule logged successfully!';
            } else {
                $message = 'Error: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = 'Error creating table: ' . $conn->error;
        }
    } else {
        $message = 'Please fill in all fields.';
    }
}

// Fetch trucks for dropdown
$trucks = [];
$res = $conn->query("SELECT id, truck_type, unit_number, plate_number FROM trucks ORDER BY id");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $trucks[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Log Maintenance Schedule</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Base styles as per dashboard, all content centered */
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI",sans-serif; }
    body {
      background-color:#3b5870;
      color:#fff;
      min-height:100vh;
      display:flex;
      flex-direction:column;
      align-items:center;
      padding:20px;
      text-align:center;
    }
    .header {
      background-color:#2f4a5f;
      padding:20px;
      box-shadow:0 4px 12px rgba(0,0,0,0.2);
      width:100%;
      text-align:center;
    }
    .header h2 { color:#fff; font-size:28px; }
    .nav-container { display:flex; justify-content:center; gap:15px; margin-top:10px; }
    .nav-button { background-color:#f4a825; color:#fff; padding:10px 20px; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:all .3s; }
    .nav-button:hover { background-color:#ffcc00; transform:scale(1.05); }
    .container {
      width:600px;
      max-width:100%;
      margin-top:40px;
      background:rgba(255,255,255,0.1);
      padding:20px;
      border-radius:10px;
      text-align:left;
    }
    .message { margin-bottom:15px; padding:10px; background:rgba(0,0,0,0.2); border-radius:5px; text-align:center; }
    form { display:flex; flex-direction:column; }
    label { margin:15px 0 5px; }
    input, select, textarea { width:100%; padding:8px; border-radius:5px; border:none; }
    button {
      margin:20px auto 0;
      background-color:#f4a825;
      color:#fff;
      padding:10px 20px;
      border:none;
      border-radius:8px;
      font-size:16px;
      cursor:pointer;
      transition:all .3s;
    }
    button:hover { background-color:#ffcc00; transform:scale(1.05); }
    .success { background-color: rgba(0,128,0,0.3); }
    .error { background-color: rgba(255,0,0,0.3); }
  </style>
</head>
<body>
  <div class="header">
    <h2>Log Maintenance Schedule</h2>
    <div class="nav-container">
      <a class="nav-button" href="maintenance.php"><i class="fas fa-wrench"></i> Back</a>

    </div>
  </div>

  <div class="container">
    <?php if ($message): ?>
      <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <label for="truck_id">Select Truck</label>
      <select name="truck_id" id="truck_id" required>
        <option value="">-- choose truck --</option>
        <?php foreach ($trucks as $t): ?>
          <option value="<?php echo $t['id']; ?>">
            <?php echo htmlspecialchars($t['truck_type'] . ' - Unit: ' . $t['unit_number'] . ' - Plate: ' . $t['plate_number']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="date">Date</label>
      <input type="date" name="date" id="date" required>

      <label for="time">Time</label>
      <input type="time" name="time" id="time" required>

      <label for="description">Description</label>
      <textarea name="description" id="description" rows="4" required placeholder="Enter maintenance details..."></textarea>

      <button type="submit"><i class="fas fa-plus-circle"></i> Log Schedule</button>
    </form>
  </div>

  <script>
    // Set default date to today
    document.addEventListener('DOMContentLoaded', function() {
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('date').value = today;
      
      // Set default time to current time rounded to nearest hour
      const now = new Date();
      now.setMinutes(0);
      document.getElementById('time').value = now.toTimeString().slice(0, 5);
    });
  </script>
</body>
</html>