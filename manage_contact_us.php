<?php
session_start();
// Check if user is logged in (uncomment and adjust once login is implemented)
// if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
//     header("Location: login.php");
//     exit;
// }

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'church_website';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM contact_messages WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Message deleted successfully.";
        $message_type = "success";
    } else {
        $message = "Failed to delete message: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Fetch all contact messages
$result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Contact Messages</title>
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2b6cb0;
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #2b6cb0;
            color: white;
        }
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .action-btn:hover {
            opacity: 0.8;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Contact Messages</h1>
        <h1><p><a href="dash.php">Back to Dashboard</a></p></h1>
        <?php
        if (isset($message)) {
            echo "<div class='message $message_type'>$message</div>";
        }
        ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Message</th>
                    <th>Received At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . (empty($row['name']) ? 'N/A' : htmlspecialchars($row['name'])) . "</td>";
                        echo "<td>" . (empty($row['email']) ? 'N/A' : htmlspecialchars($row['email'])) . "</td>";
                        echo "<td>" . (empty($row['phone']) ? 'N/A' : htmlspecialchars($row['phone'])) . "</td>";
                        echo "<td>" . htmlspecialchars($row['message']) . "</td>";
                        echo "<td>" . $row['created_at'] . "</td>";
                        echo "<td>";
                        echo "<a href='?action=delete&id=" . $row['id'] . "' class='action-btn delete-btn' onclick='return confirm(\"Are you sure you want to delete this message?\")'>Delete</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No messages found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php
$conn->close();
?>