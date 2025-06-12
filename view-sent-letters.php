<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['admin_name'])) {
    header('Location: admin_login.php');
    exit;
}

// Set timezone to EAT
date_default_timezone_set('Africa/Nairobi');

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'church_website';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all sent newsletters
$result = $conn->query("SELECT id, subject, message, recipient_count, admin_name, sent_at FROM newsletter_messages ORDER BY sent_at DESC");
$newsletters = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $newsletters[] = $row;
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
    <title>Admin - View Sent Newsletters - New Life International Church</title>
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
        h1 {
            color: #2b6cb0;
            text-align: center;
            margin-bottom: 20px;
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
        .message-content {
            max-width: 300px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sent Newsletters</h1>
        <h1><a href="dash.php">Back to Dashboard</a></h1>

        <?php if (empty($newsletters)): ?>
            <p>No newsletters found.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Recipients</th>
                    <th>Sent By</th>
                    <th>Sent On</th>
                </tr>
                <?php foreach ($newsletters as $newsletter): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($newsletter['id']); ?></td>
                        <td><?php echo htmlspecialchars($newsletter['subject']); ?></td>
                        <td class="message-content"><?php echo htmlspecialchars($newsletter['message']); ?></td>
                        <td><?php echo htmlspecialchars($newsletter['recipient_count']); ?></td>
                        <td><?php echo htmlspecialchars($newsletter['admin_name']); ?></td>
                        <td><?php echo htmlspecialchars($newsletter['sent_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>