<?php
// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'church_website';

// Connect to database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Handle form submissions (add, delete, hide/unhide)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_announcement'])) {
        $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
        $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
        if (!empty($title) && !empty($description)) {
            $sql = "INSERT INTO announcements (title, description, is_hidden) VALUES (?, ?, 0)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $title, $description);
            if ($stmt->execute()) {
                $message = "Announcement added successfully!";
            } else {
                $message = "Error adding announcement: " . $conn->error;
            }
            $stmt->close();
        } else {
            $message = "Title and description are required!";
        }
    } elseif (isset($_POST['delete_id'])) {
        $id = filter_var($_POST['delete_id'], FILTER_SANITIZE_NUMBER_INT);
        $sql = "DELETE FROM announcements WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Announcement deleted successfully!";
        } else {
            $message = "Error deleting announcement: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['toggle_hidden_id'])) {
        $id = filter_var($_POST['toggle_hidden_id'], FILTER_SANITIZE_NUMBER_INT);
        $sql = "UPDATE announcements SET is_hidden = 1 - is_hidden WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Announcement visibility toggled successfully!";
        } else {
            $message = "Error toggling announcement visibility: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all announcements
function getAllAnnouncements() {
    global $conn;
    $announcements = array();
    $sql = "SELECT * FROM announcements ORDER BY created_at DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
    }
    return $announcements;
}

$announcements = getAllAnnouncements();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - New Life International</title>
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
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        header {
            background-color: #2c3e50;
            color: white;
            padding: 1rem 0;
            text-align: center;
        }
        header h1 {
            font-size: 1.8rem;
        }
        .admin-section {
            padding: 2rem 0;
        }
        .section-title {
            text-align: center;
            margin-bottom: 2rem;
        }
        .section-title h2 {
            font-size: 2rem;
            color: #2c3e50;
        }
        .form-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .form-container label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .form-container input[type="text"],
        .form-container textarea {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-container textarea {
            height: 150px;
            resize: vertical;
        }
        .btn {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #c0392b;
        }
        .btn-secondary {
            background-color: #7f8c8d;
        }
        .btn-secondary:hover {
            background-color: #6c7a7b;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            text-align: center;
        }
        .message.success {
            background-color: #2ecc71;
            color: white;
        }
        .message.error {
            background-color: #e74c3c;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 1rem;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #2c3e50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .action-buttons form {
            display: inline-block;
            margin-right: 0.5rem;
        }
        @media (max-width: 768px) {
            .form-container input[type="text"],
            .form-container textarea {
                font-size: 1rem;
            }
            th, td {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Manage Announcements</h1>
            <H1><p><a href="dash.php">Back to Dash Board</a></p></H1>
        </div>
    </header>

    <section class="admin-section">
        <div class="container">
            <div class="section-title">
                <h2>Add New Announcement</h2>
            </div>
            <?php if (isset($message)): ?>
                <div class="message <?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <div class="form-container">
                <form method="POST" action="">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                    <button type="submit" name="add_announcement" class="btn">Add Announcement</button>
                </form>
            </div>

            <div class="section-title">
                <h2>All Announcements</h2>
            </div>
            <?php if (empty($announcements)): ?>
                <p>No announcements available.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Created At</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($announcements as $announcement): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($announcement['id']); ?></td>
                                <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($announcement['description'])); ?></td>
                                <td><?php echo date('F j, Y, H:i', strtotime($announcement['created_at'])); ?></td>
                                <td><?php echo $announcement['is_hidden'] ? 'Hidden' : 'Visible'; ?></td>
                                <td class="action-buttons">
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="toggle_hidden_id" value="<?php echo $announcement['id']; ?>">
                                        <button type="submit" class="btn btn-secondary"><?php echo $announcement['is_hidden'] ? 'Unhide' : 'Hide'; ?></button>
                                    </form>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $announcement['id']; ?>">
                                        <button type="submit" class="btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>

    <?php
    // Close database connection
    $conn->close();
    ?>
</body>
</html>