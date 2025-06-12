<?php
session_start();

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'church_website';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle admin login
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT id, name, password, is_hidden FROM admins WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        if ($admin['is_hidden'] == 1) {
            $login_error = "Account suspended. Please contact an admin.";
        } elseif (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            header('Location: admin_management.php');
            exit;
        } else {
            $login_error = "Invalid email or password.";
        }
    } else {
        $login_error = "Invalid email or password.";
    }
    $stmt->close();
}

// Handle admin actions (delete or hide)
if (isset($_GET['action']) && isset($_GET['id']) && isset($_SESSION['admin_id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($id == $_SESSION['admin_id']) {
        $action_error = "You cannot modify your own account.";
    } else {
        if ($action == 'delete') {
            $sql = "DELETE FROM admins WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $action_success = "Admin deleted successfully.";
            } else {
                $action_error = "Failed to delete admin: " . $conn->error;
            }
            $stmt->close();
        } elseif ($action == 'hide') {
            $sql = "UPDATE admins SET is_hidden = 1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $action_success = "Admin hidden successfully.";
            } else {
                $action_error = "Failed to hide admin: " . $conn->error;
            }
            $stmt->close();
        } elseif ($action == 'unhide') {
            $sql = "UPDATE admins SET is_hidden = 0 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $action_success = "Admin restored successfully.";
            } else {
                $action_error = "Failed to restore admin: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all admins for display
$sql = "SELECT id, name, email, phone, created_at, is_hidden FROM admins";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - New Life International Church</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e6f0fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #2b6cb0;
            text-align: center;
        }
        .message {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: #e6ffe6;
            color: green;
        }
        .error {
            background-color: #ffe6e6;
            color: red;
        }
        .login-form, .admin-table {
            display: <?php echo isset($_SESSION['admin_id']) ? 'none' : 'block'; ?>;
        }
        .admin-table {
            display: <?php echo isset($_SESSION['admin_id']) ? 'block' : 'none'; ?>;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #2b6cb0;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #4a90e2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
        }
        th {
            background-color: #2b6cb0;
            color: white;
        }
        .action-btn {
            padding: 5px 10px;
            margin: 2px;
            font-size: 14px;
        }
        .delete-btn {
            background-color: #dc3545;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .hide-btn {
            background-color: #ffc107;
        }
        .hide-btn:hover {
            background-color: #e0a800;
        }
        .unhide-btn {
            background-color: #28a745;
        }
        .unhide-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Management</h1>
                <h1><a href="dash.php">Back to Dashboard</a></h1>
        
        <?php
        if (isset($action_success)) {
            echo "<div class='message success'>$action_success</div>";
        }
        if (isset($action_error)) {
            echo "<div class='message error'>$action_error</div>";
        }
        ?>

        <div class="login-form">
            <h2>Admin Login</h2>
            <?php
            if (isset($login_error)) {
                echo "<div class='message error'>$login_error</div>";
            }
            ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login">Login</button>
            </form>
        </div>

        <div class="admin-table">
            <h2>Manage Admins</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Created At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['id'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                            echo "<td>" . $row['created_at'] . "</td>";
                            echo "<td>" . ($row['is_hidden'] ? 'Hidden' : 'Active') . "</td>";
                            echo "<td>";
                            echo "<a href='?action=delete&id=" . $row['id'] . "' class='action-btn delete-btn' onclick='return confirm(\"Are you sure you want to delete this admin?\")'>Delete</a>";
                            if ($row['is_hidden']) {
                                echo "<a href='?action=unhide&id=" . $row['id'] . "' class='action-btn unhide-btn'>Unhide</a>";
                            } else {
                                echo "<a href='?action=hide&id=" . $row['id'] . "' class='action-btn hide-btn'>Hide</a>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>No admins found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>