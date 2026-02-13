<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Database configuration
$host = 'localhost';
$dbname = 'login_system';
$db_username = 'root';
$db_password = '';

$user_stats = ['total_logins' => 'N/A', 'last_login' => 'N/A', 'created_at' => 'N/A'];
$recent_activity = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user stats
    $stmt = $pdo->prepare("SELECT created_at, last_login FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats) {
        $user_stats['created_at'] = $stats['created_at'];
        $user_stats['last_login'] = $stats['last_login'];
    }
    
    // Get login count
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM login_attempts WHERE email = ? AND success = 1");
        $stmt->execute([$_SESSION['user_email']]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_stats['total_logins'] = $count['count'] ?? 'N/A';
        
        // Get recent activity
        $stmt = $pdo->prepare("SELECT attempt_time, ip_address, success FROM login_attempts WHERE email = ? ORDER BY attempt_time DESC LIMIT 5");
        $stmt->execute([$_SESSION['user_email']]);
        $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // login_attempts table might not exist
    }
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="user-welcome">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 👋</h1>
                <p><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            </div>
            <a href="?logout=1" class="btn btn-logout">Logout</a>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-number"><?php echo htmlspecialchars($user_stats['total_logins']); ?></div>
                <div class="stat-label">Total Logins</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🕐</div>
                <div class="stat-number">
                    <?php 
                    if ($user_stats['last_login'] && $user_stats['last_login'] !== 'N/A') {
                        echo date('M j, Y', strtotime($user_stats['last_login']));
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
                <div class="stat-label">Last Login</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-number">
                    <?php 
                    if ($user_stats['created_at'] && $user_stats['created_at'] !== 'N/A') {
                        echo date('M j, Y', strtotime($user_stats['created_at']));
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
                <div class="stat-label">Member Since</div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="activity-section">
            <h2 class="section-title">Recent Activity</h2>
            <div class="activity-list">
                <?php if (!empty($recent_activity)): ?>
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo $activity['success'] ? 'success' : 'failed'; ?>">
                                <?php echo $activity['success'] ? '✅' : '❌'; ?>
                            </div>
                            <div class="activity-details">
                                <div><strong>IP:</strong> <?php echo htmlspecialchars($activity['ip_address']); ?></div>
                                <div><strong>Status:</strong> <?php echo $activity['success'] ? 'Successful' : 'Failed'; ?></div>
                                <div class="activity-time">
                                    <?php echo date('M j, Y g:i A', strtotime($activity['attempt_time'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="activity-item">
                        <div class="activity-icon">ℹ️</div>
                        <div class="activity-details">
                            <div>No recent activity found</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="index.php" class="action-card">
                <div class="action-icon">🔐</div>
                <div class="action-title">Login Page</div>
                <div class="action-desc">Return to login</div>
            </a>
            
            <a href="forgot_password.php" class="action-card">
                <div class="action-icon">🔑</div>
                <div class="action-title">Reset Password</div>
                <div class="action-desc">Change your password</div>
            </a>
            
            <a href="?logout=1" class="action-card">
                <div class="action-icon">🚪</div>
                <div class="action-title">Logout</div>
                <div class="action-desc">Sign out of your account</div>
            </a>
        </div>
    </div>
</body>
</html>