<?php
// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'church_website';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success_message = '';
$error_message = '';

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $success_message = "Subscriber deleted successfully.";
    } else {
        $error_message = "Error deleting subscriber.";
    }
    $stmt->close();
}

// Fetch all subscribers
$result = $conn->query("SELECT id, name, email, created_at FROM newsletter_subscribers ORDER BY created_at DESC");
$subscribers = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subscribers[] = $row;
    }
    $result->free();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Newsletter Subscribers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        h2 {
            text-align: center;
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
            background-color: #f2f2f2;
        }
        .delete-btn {
            background-color: #d9534f;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .delete-btn:hover {
            background-color: #c9302c;
        }
        .message {
            margin: 20px 0;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
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
    <h2>Newsletter Subscribers</h2>
    <h2><a href="dash.php">Back to Dashboard</a></h2>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo $success_message; ?></div>
    <?php elseif ($error_message): ?>
        <div class="message error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if (empty($subscribers)): ?>
        <p>No subscribers found.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subscribed On</th>
                <th>Action</th>
            </tr>
            <?php foreach ($subscribers as $subscriber): ?>
                <tr>
                    <td><?php echo htmlspecialchars($subscriber['id']); ?></td>
                    <td><?php echo htmlspecialchars($subscriber['name']); ?></td>
                    <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                    <td><?php echo htmlspecialchars($subscriber['created_at']); ?></td>
                    <td>
                        <a href="?delete_id=<?php echo $subscriber['id']; ?>" 
                           class="delete-btn" 
                           onclick="return confirm('Are you sure you want to delete this subscriber?');">
                            Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>