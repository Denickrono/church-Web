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

// Add is_hidden column to track table visibility
$sql_alter = "CREATE TABLE IF NOT EXISTS table_visibility (
    table_name VARCHAR(255) PRIMARY KEY,
    is_hidden TINYINT(1) DEFAULT 0
)";
$conn->query($sql_alter);

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['table'])) {
    $table = $conn->real_escape_string($_GET['table']);
    
    // Begin transaction for safe deletion
    $conn->begin_transaction();
    try {
        // Delete table
        $drop_sql = "DROP TABLE IF EXISTS `$table`";
        if ($conn->query($drop_sql) === TRUE) {
            // Remove from table_visibility
            $delete_visibility_sql = "DELETE FROM table_visibility WHERE table_name = ?";
            $delete_stmt = $conn->prepare($delete_visibility_sql);
            $delete_stmt->bind_param("s", $table);
            $delete_stmt->execute();
            $delete_stmt->close();
            $success_message = "Table '$table' deleted successfully.";
            $conn->commit();
        } else {
            throw new Exception("Error dropping table: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "Error deleting table: " . $e->getMessage();
    }
}

// Handle hide/unhide action
if (isset($_GET['action']) && in_array($_GET['action'], ['hide', 'unhide']) && isset($_GET['table'])) {
    $table = $conn->real_escape_string($_GET['table']);
    $is_hidden = ($_GET['action'] === 'hide') ? 1 : 0;
    
    // Check if table exists in table_visibility, insert or update
    $check_sql = "SELECT table_name FROM table_visibility WHERE table_name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $table);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $update_sql = "UPDATE table_visibility SET is_hidden = ? WHERE table_name = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("is", $is_hidden, $table);
        if ($update_stmt->execute()) {
            $success_message = "Table '$table' " . ($_GET['action'] === 'hide' ? 'hidden' : 'unhidden') . " successfully.";
        } else {
            $errors[] = "Error updating table visibility: " . $conn->error;
        }
        $update_stmt->close();
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO table_visibility (table_name, is_hidden) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("si", $table, $is_hidden);
        if ($insert_stmt->execute()) {
            $success_message = "Table '$table' " . ($_GET['action'] === 'hide' ? 'hidden' : 'unhidden') . " successfully.";
        } else {
            $errors[] = "Error setting table visibility: " . $conn->error;
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

// Fetch all tables and their visibility
$tables = [];
$sql = "SHOW TABLES";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_array()) {
        $table_name = $row[0];
        // Skip table_visibility itself
        if ($table_name === 'table_visibility') continue;
        
        // Get visibility status
        $visibility_sql = "SELECT is_hidden FROM table_visibility WHERE table_name = ?";
        $visibility_stmt = $conn->prepare($visibility_sql);
        $visibility_stmt->bind_param("s", $table_name);
        $visibility_stmt->execute();
        $visibility_result = $visibility_stmt->get_result();
        $is_hidden = $visibility_result->num_rows > 0 ? $visibility_result->fetch_assoc()['is_hidden'] : 0;
        $visibility_stmt->close();
        
        // Get table columns and row count
        $desc_sql = "DESCRIBE `$table_name`";
        $desc_result = $conn->query($desc_sql);
        $columns = [];
        if ($desc_result) {
            while ($col = $desc_result->fetch_assoc()) {
                $columns[] = $col['Field'] . " (" . $col['Type'] . ")";
            }
        }
        $count_sql = "SELECT COUNT(*) as total FROM `$table_name`";
        $count_result = $conn->query($count_sql);
        $row_count = $count_result ? $count_result->fetch_assoc()['total'] : 'N/A';
        
        $tables[] = [
            'name' => $table_name,
            'columns' => $columns,
            'row_count' => $row_count,
            'is_hidden' => $is_hidden
        ];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Tables - New Life International</title>
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
        .columns-col {
            max-width: 300px;
            white-space: normal;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo"><h1>New Life International - Admin Panel</h1></div><p><a href="dash.php">back to dashboard</a></p>
            </nav>
        </div>
    </header>

    <section id="manage-tables">
        <div class="container">
            <div class="section-title">
                <h2>Manage Database Tables</h2>
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
                        <th>Table Name</th>
                        <th>Columns</th>
                        <th>Row Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tables)): ?>
                        <?php foreach ($tables as $table): ?>
                            <tr class="<?php echo $table['is_hidden'] ? 'hidden-row' : ''; ?>">
                                <td><?php echo htmlspecialchars($table['name']); ?></td>
                                <td class="columns-col">
                                    <?php echo htmlspecialchars(implode(', ', $table['columns'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($table['row_count']); ?></td>
                                <td>
                                    <a href="?action=delete&table=<?php echo urlencode($table['name']); ?>" 
                                       class="btn btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete the table \'<?php echo htmlspecialchars($table['name']); ?>\'? This cannot be undone!');">
                                        Delete
                                    </a>
                                    <?php if ($table['is_hidden']): ?>
                                        <a href="?action=unhide&table=<?php echo urlencode($table['name']); ?>" 
                                           class="btn btn-unhide" 
                                           onclick="return confirm('Are you sure you want to unhide the table \'<?php echo htmlspecialchars($table['name']); ?>\'?');">
                                            Unhide
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=hide&table=<?php echo urlencode($table['name']); ?>" 
                                           class="btn btn-hide" 
                                           onclick="return confirm('Are you sure you want to hide the table \'<?php echo htmlspecialchars($table['name']); ?>\'?');">
                                            Hide
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No tables found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</body>
</html>