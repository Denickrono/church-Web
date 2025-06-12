<?php
// Start session for authentication
session_start();

// Check if Pastor Jerry is logged in (example; replace with actual authentication)
// if (!isset($_SESSION['pastor_id']) || $_SESSION['pastor_name'] !== 'Jerry') {
//     header('Location: pastor_login.php');
//     exit;
// }

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

// Fetch overview metrics
$prayer_count = $conn->query("SELECT COUNT(*) AS count FROM pastor_jerry_ouma")->fetch_assoc()['count'];
$recent_requests = $conn->query("SELECT id, name, email_address, LEFT(prayer_request, 50) AS preview, submitted_at FROM pastor_jerry_ouma ORDER BY submitted_at DESC LIMIT 5");
$pastors_count = $conn->query("SELECT COUNT(*) AS count FROM pastors")->fetch_assoc()['count'];

// Handle prayer request status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE pastor_jerry SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $request_id);
    if ($stmt->execute()) {
        $success_message = "Prayer request status updated.";
    } else {
        $errors[] = "Error updating status: " . $conn->error;
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
    <title>Admin Dashboard - Pastor Jerry</title>
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
            max-width: 800px;
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
        .btn {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #c0392b;
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
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="index.php#services">Services</a></li>
                    <li><a href="index.php#events">Events</a></li>
                    <li><a href="index.php#ministries">Ministries</a></li>
                    <li><a href="index.php#sermons">Sermons</a></li>
                    <li><a href="index.php#give">Give</a></li>
                    <li><a href="index.php#contact">Contact</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="admin-dashboard">
        <div class="container">
            <div class="section-title">
                <h2>Pastor Jerry's Admin Dashboard</h2>
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
            <div class="dashboard-section">
                <h3>Overview</h3>
                <div class="stats">
                    <div class="stat-box">
                        <h4>Total Prayer Requests</h4>
                        <p><?php echo $prayer_count; ?></p>
                    </div>
                    <div class="stat-box">
                        <h4>Total Pastors</h4>
                        <p><?php echo $pastors_count; ?></p>
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
                        <th>Request Preview</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                    <?php while ($request = $recent_requests->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['id']); ?></td>
                            <td><?php echo htmlspecialchars($request['name']); ?></td>
                            <td><?php echo htmlspecialchars($request['email_address']); ?></td>
                            <td><?php echo htmlspecialchars($request['preview']); ?>...</td>
                            <td><?php echo htmlspecialchars($request['submitted_at']); ?></td>
                            <td>
                                <form action="admin_dashboard.php" method="POST">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <select name="status">
                                        <option value="pending">Pending</option>
                                        <option value="responded">Responded</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </section>
</body>
</html>