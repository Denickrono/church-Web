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

// Fetch pending and processed applications from approvals table
$result = $conn->query("SELECT * FROM approvals ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Membership Applications</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #e6f0fa; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #2b6cb0; text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #2b6cb0; color: white; }
        .action-btn { padding: 5px 10px; margin-right: 5px; border: none; border-radius: 3px; cursor: pointer; }
        .approve-btn { background-color: #28a745; color: white; }
        .reject-btn { background-color: #dc3545; color: white; }
        .delete-btn { background-color: #6c757d; color: white; }
        .action-btn:hover { opacity: 0.8; }
        .message { text-align: center; color: #28a745; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Membership Applications</h1>
        <h1><a href="dash.php">Back to Admin Dashboard</a></h1>
        <?php if (isset($_GET['message']) && $_GET['message'] === 'success'): ?>
            <div class="message">Thank you! Your application has been received.</div>
        <?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['message']); ?></td>
                        <td><?php echo $row['status'] ? htmlspecialchars($row['status']) : 'Pending'; ?></td>
                        <td>
                            <button class="action-btn approve-btn" onclick="updateStatus(<?php echo $row['id']; ?>, 'Approved')">Approve</button>
                            <button class="action-btn reject-btn" onclick="updateStatus(<?php echo $row['id']; ?>, 'Rejected')">Reject</button>
                            <button class="action-btn delete-btn" onclick="updateStatus(<?php echo $row['id']; ?>, 'Deleted')">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <script>
        function updateStatus(id, status) {
            if (confirm(`Are you sure you want to mark this application as ${status}?`)) {
                fetch('update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${encodeURIComponent(id)}&status=${encodeURIComponent(status)}`
                }).then(response => response.text()).then(() => location.reload());
            }
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>