<?php
// Initialize session
session_start();

// Check if there's an error parameter
$error = isset($_GET['error']) ? $_GET['error'] : '';
$error_message = '';

// Set appropriate error message
switch($error) {
    case 'invalid_password':
        $error_message = 'Invalid password!';
        break;
    case 'user_not_found':
        $error_message = 'User not found with the provided email and role!';
        break;
    case 'database_error':
        $error_message = 'Database connection error. Please try again later.';
        break;
    default:
        $error_message = '';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Truck Monitoring System - Login</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,700">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Roboto', sans-serif;
    }

    body {
      height: 100vh;
      background: linear-gradient(135deg,rgb(36, 66, 89),rgb(10, 24, 36));
      display: flex;
      justify-content: center;
      align-items: center;
      animation: fadeInBody 1s ease-in-out;
    }

    @keyframes fadeInBody {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .login-box {
      background-color: #ffffff10;
      backdrop-filter: blur(10px);
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 8px 24px rgba(0, 0, 3, 0.46);
      animation: slideUp 1s ease-out;
      width: 100%;
      max-width: 400px;
    }


    @keyframes slideUp {
      from { transform: translateY(30px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .input-box {
      margin-bottom: 20px;
    }

    input[type="text"],
    input[type="password"],
    input[type="email"],
    select {
      width: 100%;
      padding: 12px 15px;
      border: none;
      border-radius: 8px;
      background: #fff;
      box-shadow: inset 0 0 5px rgba(1,0,0,1.1);
      font-size: 16px;
      transition: all 0.3s ease;
    }

    input:focus,
    select:focus {
      outline: none;
      transform: scale(1.03);
      box-shadow: 0 0 8px #f4a825;
    }

    .btn {
      width: 100%;
      padding: 12px;
      background-color: #f4a825;
      color: white;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .btn:hover {
      background-color: #ffcc00;
      transform: scale(1.05);
      box-shadow: 0 0 12px #fff3b0;
    }
    
    .error-message {
      background-color: rgba(255, 87, 87, 0.2);
      color: #ff5757;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      font-size: 14px;
    }
    
    h2 {
      font-style: bold;
      color: white;
      text-align: center;
      margin-bottom: 25px;
      font-weight: 800;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
  </style>
</head>
<body>

  <div class="login-box">
    <h2>Truck Monitoring System</h2>
    
    <?php if(!empty($error_message)): ?>
    <div class="error-message">
      <?php echo $error_message; ?>
    </div>
    <?php endif; ?>
    
    <form method="post" action="login.php">
      <div class="input-box">
        <input type="email" name="email" placeholder="Email Address" required>
      </div>
      <div class="input-box">
        <input type="password" name="password" placeholder="Password" required>
      </div>
      <div class="input-box">
        <select name="role" required>
          <option value="" disabled selected>Select Role</option>
          <option value="admin">Admin</option>
          <option value="bookkeeper">Bookkeeper</option>
          <option value="dispatcher">Dispatcher</option>
          <option value="driver">Driver</option>


        </select>
      </div>
      <button type="submit" class="btn">LOGIN</button>
    </form>
  </div>

</body>
</html>