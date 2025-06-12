<?php
session_start();

// Check if pastor is logged in
if (!isset($_SESSION['pastor_id']) || !isset($_SESSION['pastor_name'])) {
    header('Location: pastor_login.php');
    exit;
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'church_website';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$errors = [];
$success_message = '';
$prayer_count = 0;
$pending_count = 0;
$recent_requests = null;

// Get pastor's table name (matches pastor_signup.php logic)
$pastor_name = $_SESSION['pastor_name'];
$safe_table_name = 'pastor_' . preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($pastor_name));

// Check if pastor's table exists
$table_check = $conn->query("SHOW TABLES LIKE '$safe_table_name'");
if ($table_check->num_rows == 0) {
    $errors[] = "Your prayer request table does not exist. Please contact the administrator.";
} else {
    // Fetch overview metrics
    $prayer_count_result = $conn->query("SELECT COUNT(*) AS count FROM `$safe_table_name`");
    if ($prayer_count_result) {
        $prayer_count = $prayer_count_result->fetch_assoc()['count'];
    }
    $pending_count_result = $conn->query("SELECT COUNT(*) AS count FROM `$safe_table_name` WHERE status = 'pending'");
    if ($pending_count_result) {
        $pending_count = $pending_count_result->fetch_assoc()['count'];
    }
    // Fetch full prayer requests without truncation
    $recent_requests = $conn->query("SELECT id, name, email_address, prayer_request, submitted_at, status FROM `$safe_table_name` ORDER BY submitted_at DESC LIMIT 5");
}

// Handle prayer request status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && $table_check->num_rows > 0) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE `$safe_table_name` SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $request_id);
    if ($stmt->execute()) {
        $success_message = "Prayer request status updated.";
    } else {
        $errors[] = "Error updating status: " . $conn->error;
    }
    $stmt->close();
}

// Handle prayer request deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_request']) && $table_check->num_rows > 0) {
    $request_id = $_POST['request_id'];
    $stmt = $conn->prepare("DELETE FROM `$safe_table_name` WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    if ($stmt->execute()) {
        $success_message = "Prayer request deleted successfully.";
    } else {
        $errors[] = "Error deleting prayer request: " . $conn->error;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pastor Dashboard - New Life International</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        body {
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 1000px; /* Increased for larger table */
            margin: 0 auto;
            padding: 2rem 15px;
        }
        .section-title {
            text-align: center;
            margin-bottom: 2rem;
        }
        .section-title h2 {
            font-size: 2rem;
            color: #2c3e50;
        }
        .dashboard-section {
            margin-bottom: 2rem;
        }
        .dashboard-section h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .stat-box {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 5px;
            flex: 1;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 0.8rem;
            text-align: left;
        }
        th {
            background-color: #e74c3c;
            color: white;
        }
        /* Make prayer request column wider and wrap text */
        th.prayer-request, td.prayer-request {
            width: 40%; /* Increased width for prayer request */
            max-width: 400px;
            word-wrap: break-word;
            white-space: normal;
        }
        .btn {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            margin-right: 0.5rem;
        }
        .btn:hover {
            background-color: #c0392b;
        }
        .btn-delete {
            background-color: #a94442;
        }
        .btn-delete:hover {
            background-color: #8b2e2c;
        }
        .message {
            text-align: center;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 5px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">New Life International</div>
                <ul class="nav-links">
                    <!-- <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="index.php#services">Services</a></li>
                    <li><a href="index.php#events">Events</a></li>
                    <li><a href="index.php#ministries">Ministries</a></li>
                    <li><a href="index.php#sermons">Sermons</a></li>
                    <li><a href="index.php#give">Give</a></li>
                    <li><a href="index.php#contact">Contact</a></li> -->
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="pastor-dashboard">
        <div class="container">
            <div class="section-title">
                <h2>Welcome, Pastor <?php echo htmlspecialchars($pastor_name); ?></h2>
            </div>
            <?php if ($success_message): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($table_check->num_rows > 0): ?>
                <div class="dashboard-section">
                    <h3>Overview</h3>
                    <div class="stats">
                        <div class="stat-box">
                            <h4>Total Prayer Requests</h4>
                            <p><?php echo $prayer_count; ?></p>
                        </div>
                        <div class="stat-box">
                            <h4>Pending Prayer Requests</h4>
                            <p><?php echo $pending_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="dashboard-section">
                    <h3>Recent Prayer Requests</h3>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th class="prayer-request">Prayer Request</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <?php if ($recent_requests && $recent_requests->num_rows > 0): ?>
                            <?php while ($request = $recent_requests->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['id']); ?></td>
                                    <td><?php echo htmlspecialchars($request['name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['email_address']); ?></td>
                                    <td class="prayer-request"><?php echo htmlspecialchars($request['prayer_request']); ?></td>
                                    <td><?php echo htmlspecialchars($request['submitted_at']); ?></td>
                                    <td><?php echo htmlspecialchars($request['status']); ?></td>
                                    <td>
                                        <form action="pastor_dashboard.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <select name="status">
                                                <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="responded" <?php echo $request['status'] === 'responded' ? 'selected' : ''; ?>>Responded</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn">Update</button>
                                        </form>
                                        <form action="pastor_dashboard.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" name="delete_request" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this prayer request?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7">No prayer requests available.</td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>