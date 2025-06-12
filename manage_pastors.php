<?php
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
$success_message = '';
$errors = [];

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Get pastor's name to drop their specific table
    $sql = "SELECT name FROM pastors WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $pastor = $result->fetch_assoc();
        $safe_table_name = 'pastor_' . preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($pastor['name']));
        
        // Begin transaction for safe deletion
        $conn->begin_transaction();
        try {
            // Delete from pastors table
            $delete_sql = "DELETE FROM pastors WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $id);
            $delete_stmt->execute();
            
            // Drop pastor-specific table
            $drop_sql = "DROP TABLE IF EXISTS `$safe_table_name`";
            if ($conn->query($drop_sql) === TRUE) {
                $success_message = "Pastor and associated table deleted successfully.";
            } else {
                throw new Exception("Error dropping pastor table: " . $conn->error);
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error deleting pastor: " . $e->getMessage();
        }
        $delete_stmt->close();
    } else {
        $errors[] = "Pastor not found.";
    }
    $stmt->close();
}

// Handle hide/unhide action (assumes 'is_hidden' column exists)
$sql_alter = "ALTER TABLE pastors ADD COLUMN IF NOT EXISTS is_hidden TINYINT(1) DEFAULT 0";
$conn->query($sql_alter);

if (isset($_GET['action']) && in_array($_GET['action'], ['hide', 'unhide']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $is_hidden = ($_GET['action'] === 'hide') ? 1 : 0;
    $sql = "UPDATE pastors SET is_hidden = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $is_hidden, $id);
    if ($stmt->execute()) {
        $success_message = "Pastor " . ($_GET['action'] === 'hide' ? 'hidden' : 'unhidden') . " successfully.";
    } else {
        $errors[] = "Error updating pastor visibility: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all pastors
$sql = "SELECT id, name, phone_number, email, created_at, is_hidden FROM pastors ORDER BY created_at DESC";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Pastors - New Life International</title>
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
            max-width: 1000px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        th, td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #2c3e50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            color: white;
            transition: background-color 0.3s;
            margin-right: 0.5rem;
        }
        .btn-delete {
            background-color: #e74c3c;
        }
        .btn-delete:hover {
            background-color: #c0392b;
        }
        .btn-hide {
            background-color: #f39c12;
        }
        .btn-hide:hover {
            background-color: #e67e22;
        }
        .btn-unhide {
            background-color: #27ae60;
        }
        .btn-unhide:hover {
            background-color: #219a52;
        }
        .hidden-row {
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo"><h1>New Life International - Admin Panel</h1></div>
                </nav>
                <h1><a href="dash.php">Back to Dashboard</a></h1>
            
        </div>
    </header>

    <section id="manage-pastors">
        <div class="container">
            <div class="section-title">
                <h2>Manage Pastors</h2>
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
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone Number</th>
                        <th>Email</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="<?php echo $row['is_hidden'] ? 'hidden-row' : ''; ?>">
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone_number'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td>
                                    <a href="?action=delete&id=<?php echo $row['id']; ?>" 
                                       class="btn btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this pastor and their associated table?');">
                                        Delete
                                    </a>
                                    <?php if ($row['is_hidden']): ?>
                                        <a href="?action=unhide&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-unhide" 
                                           onclick="return confirm('Are you sure you want to unhide this pastor?');">
                                            Unhide
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=hide&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-hide" 
                                           onclick="return confirm('Are you sure you want to hide this pastor?');">
                                            Hide
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No pastors found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</body>
</html>