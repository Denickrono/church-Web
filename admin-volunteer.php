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

// Initialize variables for form handling
$errors = [];
$success_message = '';
$position = '';
$description = '';

// Handle add role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_role') {
    $position = trim($_POST['position'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($position)) {
        $errors[] = "Position is required.";
    }
    if (empty($description)) {
        $errors[] = "Description is required.";
    }

    if (empty($errors)) {
        $sql = "INSERT INTO volunteer_opportunities (position, description, is_hidden) VALUES (?, ?, 0)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ss", $position, $description);
            if ($stmt->execute()) {
                $success_message = "Role added successfully!";
                $position = '';
                $description = '';
            } else {
                $errors[] = "Error adding role: " . $conn->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Error preparing query: " . $conn->error;
        }
    }
}

// Handle delete role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_role') {
    $id = (int)($_POST['id'] ?? 0);
    $sql = "DELETE FROM volunteer_opportunities WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success_message = "Role deleted successfully!";
        } else {
            $errors[] = "Error deleting role: " . $conn->error;
        }
        $stmt->close();
    } else {
        $errors[] = "Error preparing query: " . $conn->error;
    }
}

// Handle toggle hide/visible
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_hide') {
    $id = (int)($_POST['id'] ?? 0);
    $sql = "UPDATE volunteer_opportunities SET is_hidden = 1 - is_hidden WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success_message = "Role visibility updated successfully!";
        } else {
            $errors[] = "Error updating role: " . $conn->error;
        }
        $stmt->close();
    } else {
        $errors[] = "Error preparing query: " . $conn->error;
    }
}

// Handle delete submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_submission') {
    $id = (int)($_POST['id'] ?? 0);
    $sql = "DELETE FROM volunteer_submissions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success_message = "Submission deleted successfully!";
        } else {
            $errors[] = "Error deleting submission: " . $conn->error;
        }
        $stmt->close();
    } else {
        $errors[] = "Error preparing query: " . $conn->error;
    }
}

// Fetch all volunteer opportunities
$opportunities = [];
$sql = "SELECT id, position, description, is_hidden FROM volunteer_opportunities";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $opportunities[] = $row;
    }
    $result->free();
}

// Fetch all volunteer submissions
$submissions = [];
$sql = "SELECT id, name, email, phone_number, role, submission_date FROM volunteer_submissions";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
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
    <title>Admin - Volunteer Management - New Life International</title>
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
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        input, textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
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
            background-color: #FF4500;
        }
        .btn-delete, .btn-toggle {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        .btn-delete {
            background-color: #a94442;
        }
        .btn-delete:hover {
            background-color: #8b2e2c;
        }
        .btn-toggle {
            background-color: #3498db;
        }
        .btn-toggle:hover {
            background-color: #2980b9;
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
        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            margin-bottom: 0.5rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 0.8rem;
            text-align: left;
        }
        th {
            background-color: #2c3e50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo"><h1>New Life International - Admin Volunteer Management</h1></div>
                <ul class="nav-links">
                    <li><a href="dash.php">Admin Dashboard</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="admin-volunteer">
        <div class="container">
            <div class="section-title">
                <h2>Volunteer Management</h2>
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

            <!-- Add New Role Form -->
            <h3>Add New Volunteer Role</h3>
            <form action="admin_volunteer.php" method="POST">
                <input type="hidden" name="action" value="add_role">
                <div class="form-group">
                    <label for="position">Position</label>
                    <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($position); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <div style="text-align: center;">
                    <button type="submit" class="btn">Add Role</button>
                </div>
            </form>

            <!-- Volunteer Opportunities Table -->
            <h3>Volunteer Opportunities</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Position</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($opportunities)): ?>
                        <tr><td colspan="5">No volunteer opportunities found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($opportunities as $opp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($opp['id']); ?></td>
                                <td><?php echo htmlspecialchars($opp['position']); ?></td>
                                <td><?php echo htmlspecialchars($opp['description']); ?></td>
                                <td><?php echo $opp['is_hidden'] ? 'Hidden' : 'Visible'; ?></td>
                                <td>
                                    <form action="admin_volunteer.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_role">
                                        <input type="hidden" name="id" value="<?php echo $opp['id']; ?>">
                                        <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this role?');">Delete</button>
                                    </form>
                                    <form action="admin_volunteer.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_hide">
                                        <input type="hidden" name="id" value="<?php echo $opp['id']; ?>">
                                        <button type="submit" class="btn btn-toggle"><?php echo $opp['is_hidden'] ? 'Show' : 'Hide'; ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Volunteer Submissions Table -->
            <h3>Volunteer Submissions</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Role</th>
                        <th>Submission Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr><td colspan="7">No volunteer submissions found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sub['id']); ?></td>
                                <td><?php echo htmlspecialchars($sub['name']); ?></td>
                                <td><?php echo htmlspecialchars($sub['email']); ?></td>
                                <td><?php echo htmlspecialchars($sub['phone_number'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($sub['role']); ?></td>
                                <td><?php echo htmlspecialchars($sub['submission_date']); ?></td>
                                <td>
                                    <form action="admin_volunteer.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_submission">
                                        <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                        <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this submission?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</body>
</html>