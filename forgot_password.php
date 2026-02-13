<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$message_type = '';
$debug_info = '';

// Database configuration
$host = 'localhost';
$dbname = 'login_system';
$db_username = 'root';
$db_password = '';

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $debug_info .= "Form submitted<br>";
    $debug_info .= "Email: " . htmlspecialchars($email) . "<br>";
    $debug_info .= "Password length: " . strlen($new_password) . "<br>";
    
    // Validate inputs
    if (empty($email)) {
        $message = 'Please enter your email address';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address';
        $message_type = 'error';
    } elseif (empty($new_password)) {
        $message = 'Please enter a new password';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match';
        $message_type = 'error';
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $debug_info .= "Database connected<br>";
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $message = 'No account found with this email address';
                $message_type = 'error';
                $debug_info .= "User not found<br>";
            } else {
                $debug_info .= "User found: ID=" . $user['id'] . ", Name=" . htmlspecialchars($user['full_name']) . "<br>";
                
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $debug_info .= "Password hashed: " . substr($hashed_password, 0, 20) . "...<br>";
                
                // Update password
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $result = $stmt->execute([$hashed_password, $email]);
                $rows_affected = $stmt->rowCount();
                
                $debug_info .= "Update executed: " . ($result ? 'Success' : 'Failed') . "<br>";
                $debug_info .= "Rows affected: " . $rows_affected . "<br>";
                
                if ($rows_affected > 0) {
                    // Verify the update
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $updated_user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (password_verify($new_password, $updated_user['password'])) {
                        $debug_info .= "Password verification: SUCCESS<br>";
                        // Success!
                        $_SESSION['success_message'] = 'Password reset successfully! You can now login with your new password.';
                        header('Location: index.php');
                        exit;
                    } else {
                        $message = 'Password was updated but verification failed. Please try logging in.';
                        $message_type = 'warning';
                        $debug_info .= "Password verification: FAILED<br>";
                    }
                } else {
                    $message = 'No changes were made. The password might already be the same.';
                    $message_type = 'warning';
                    $debug_info .= "No rows affected<br>";
                }
            }
            
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
            $debug_info .= "Exception: " . htmlspecialchars($e->getMessage()) . "<br>";
            error_log("Password reset error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .reset-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .reset-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .reset-header p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .alert-warning {
            background: #ffeaa7;
            color: #d63031;
            border: 1px solid #fdcb6e;
        }
        
        .debug-info {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 12px;
            font-family: monospace;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .password-match {
            font-size: 12px;
            margin-top: 5px;
            font-weight: 500;
        }
        
        .password-match.match {
            color: #4caf50;
        }
        
        .password-match.no-match {
            color: #f44336;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>🔒 Reset Password</h1>
            <p>Enter your email and new password</p>
        </div>
        
        <?php if (!empty($debug_info)): ?>
            <div class="debug-info">
                <strong>Debug Info:</strong><br>
                <?php echo $debug_info; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="resetForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       placeholder="your@email.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="6"
                       placeholder="Enter new password">
                <div class="password-requirements">Minimum 6 characters</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                       placeholder="Confirm new password">
                <div class="password-match" id="matchIndicator"></div>
            </div>
            
            <button type="submit" class="btn btn-primary">Reset Password</button>
            
            <div class="back-link">
                <a href="index.php">← Back to Login</a>
            </div>
        </form>
    </div>
    
    <script>
        const form = document.getElementById('resetForm');
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const matchIndicator = document.getElementById('matchIndicator');
        
        form.addEventListener('submit', function(e) {
            if (newPassword.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPassword.focus();
                return false;
            }
            
            if (newPassword.value.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters!');
                newPassword.focus();
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.textContent = 'Resetting Password...';
            submitBtn.disabled = true;
        });
        
        // Real-time password match indicator
        function checkPasswordMatch() {
            if (!confirmPassword.value) {
                matchIndicator.textContent = '';
                confirmPassword.style.borderColor = '#e0e0e0';
                return;
            }
            
            if (confirmPassword.value === newPassword.value && confirmPassword.value.length >= 6) {
                matchIndicator.textContent = '✓ Passwords match';
                matchIndicator.className = 'password-match match';
                confirmPassword.style.borderColor = '#4caf50';
            } else {
                matchIndicator.textContent = '✗ Passwords do not match';
                matchIndicator.className = 'password-match no-match';
                confirmPassword.style.borderColor = '#f44336';
            }
        }
        
        newPassword.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
    </script>
</body>
</html>