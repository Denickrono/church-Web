<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset(); // Clear all session variables
    session_destroy(); // Destroy the session
    header("Location: admin_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Life International Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: #1a3a8f;
            color: white;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .logo-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .logo-text h1 {
            font-size: 20px;
            font-weight: bold;
        }
        
        .logo-text p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            text-decoration: none;
            color: white;
            transition: background-color 0.3s;
        }
        
        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .menu-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .logout {
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }
        
        .main-content {
            flex: 1;
            background-color: #f0f2f5;
        }
        
        .header {
            background-color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .user-welcome {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #1a3a8f;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-left: 10px;
        }
        
        .dashboard-content {
            padding: 30px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .link-section {
            margin-top: 10px;
        }
        
        .page-link {
            display: inline-block;
            background-color: #1a3a8f;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .page-link:hover {
            background-color: #152f6e;
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo-icon">✝️</div>
            <div class="logo-text">
                <h1>New Life International</h1>
                <p>Management System</p>
            </div>
        </div>
        
        <a href="#overview" class="menu-item active">
            <span class="menu-icon">📊</span>
            <span>Overview</span>
        </a>
        
        <a href="#users" class="menu-item">
            <span class="menu-icon">👥</span>
            <span>Users</span>
        </a>
        
        <a href="#pastors" class="menu-item">
            <span class="menu-icon">🙏</span>
            <span>Pastors</span>
        </a>
        
        <a href="#administrators" class="menu-item">
            <span class="menu-icon">👨‍💼</span>
            <span>Administrators</span>
        </a>
        
        <a href="#about" class="menu-item">
            <span class="menu-icon">ℹ️</span>
            <span>About</span>
        </a>
       
        <a href="#services" class="menu-item">
            <span class="menu-icon">🛐</span>
            <span>Services</span>
        </a>

        <a href="#events" class="menu-item">
            <span class="menu-icon">🎉</span>
            <span>Events</span>
        </a>

        <a href="#ministries" class="menu-item">
            <span class="menu-icon">👨‍👩‍👧‍👦</span>
            <span>Ministries</span>
        </a>

        <a href="#sermons" class="menu-item">
            <span class="menu-icon">📖</span>
            <span>Sermons</span>
        </a>

        <a href="#contacts" class="menu-item">
            <span class="menu-icon">📧</span>
            <span>Contact</span>
        </a>

         <a href="admin-volunteer.php" class="menu-item">
            <span class="menu-icon">📧</span>
            <span>Volunteers</span>
        </a>


        <a href="view_tables.php" class="menu-item">
            <span class="menu-icon">📋</span>
            <span>All Tables</span>
        </a>

         <a href="bg_image.php" class="menu-item">
            <span class="menu-icon">🔳</span>
            <span>BG Image</span>
        </a>

         <a href="admin_social_media.php" class="menu-item">
            <span class="menu-icon">🔳</span>0720380975
            <span>Social Media Links</span>
        </a>
       
        <a href="?action=logout" class="menu-item logout">
            <span class="menu-icon">🚪</span>
            <span>Logout</span>
        </a>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="user-welcome">
                <span>Welcome, Admin <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'kname') ?></span>
                <div class="user-avatar"><?php echo htmlspecialchars(substr($_SESSION['name'] ?? 'A', 0, 1)); ?></div>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="card">
                <h2>Dashboard Overview</h2>
                <p>Welcome to New Life International Church Management System. Use the sidebar to navigate through different sections.</p>
            </div>
            
            <div class="grid-container">
                <!-- Users Card -->
                <div class="card">
                    <h2>Users</h2>
                    <p>Manage Membership Applications.</p>
                    <div class="link-section">
                        <h3>Quick Links:</h3>
                        <a href="manage_members.php" class="page-link">Manage All Users</a>
                        <a href="mem.php" class="page-link">Confirm Membership</a>
                    </div>
                </div>

                <div class="card">
                    <h2>News Letter subscribers</h2>
                    <p>Manage Subscribers</p>
                    <div class="link-section">
                        <h3>Quick Links:</h3>
                        <a href="manage_subscribers.php" class="page-link">Manage subscribers</a>
                        <a href="admin_send_newsletter.php" class="page-link">Send Messages</a>
                        <a href="view-sent-letters.php" class="page-link">View Sent Newsletters</a>
                    </div>
                </div>
                
                <!-- Pastors Card -->
                <div class="card">
                    <h2>Pastors</h2>
                    <p>Manage our Pastors.</p>
                    <div class="link-section">
                        <h3>Quick Links:</h3>
                        <a href="manage_pastors.php" class="page-link">Manage Pastors</a>
                    </div>
                </div>
                
                <!-- Administrators Card -->
                <div class="card">
                    <h2>Administrators</h2>
                    <p>Manage system administrators.</p>
                    <div class="link-section">
                        <h3>Quick Links:</h3>
                        <a href="manage_admin.php" class="page-link">View All Admins</a>
                    </div>
                </div>
                
                <!-- About Card -->
                <div class="card">
                    <h2>About</h2>
                    <p>Manage information about our church.</p>
                    <div class="link-section">
                        <h3>Quick Links:</h3>
                        <a href="about.php" class="page-link">Manage About</a>
                    </div>
                </div>
                
                <!-- Services Card -->
                <div class="card">
                    <h2>Services</h2>
                    <p>Manage our Services.</p>
                    <div class="link-section">
                        <h3>Quick Links:</h3>
                        <a href="service.php" class="page-link">Manage Services</a>
                    </div>
                </div>

                <!-- Events Card -->
                <div class="card">
                    <h2>Manage Events</h2>
                    <p>Manage our Events.</p>
                    <div class="link-section">
                        <h3>Quick Links:</h3>
                        <a href="events.php" class="page-link">Manage Events</a>
                    </div>
                </div>
                
                <!-- Ministries Card -->
                <div class="card">
                    <h2>Ministries</h2>
                    <p>Manage our Ministries.</p>
                    <div class="link-section">
                        <h3>Quick Links:</h3>
                        <a href="ministries.php" class="page-link">Manage Ministries</a>
                    </div>
                </div>
                
                <!-- Sermons Card -->
                <div class="card">
                    <h2>Sermons</h2>
                    <p>Manage Sermons.</p>
                    <div class="link-section">
                        <h3>Quick Links:</h3>
                        <a href="sermon.php" class="page-link">Manage Sermons</a>
                        <a href="sermons.php" class="page-link">View All Sermons</a>
                    </div>
                </div>

                <!-- Contacts Card -->
                <div class="card">
                    <h2>Contact Management</h2>
                    <p>Manage contact messages and inquiries.</p>
                    <div class="link-section">
                        <h3>Quick Links:</h3>
                        <a href="contacts.php" class="page-link">Manage Contacts</a>
                        <a href="manage_contact_us.php" class="page-link">View Contact Messages</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>