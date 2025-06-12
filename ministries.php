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

// Ministry management functions
function addMinistry($title, $description) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO ministries (title, description, is_hidden) 
                          VALUES (?, ?, 0)");
    $stmt->bind_param("ss", $title, $description);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function getMinistries($show_hidden = true) {
    global $conn;
    $ministries = array();
    $sql = $show_hidden ? "SELECT * FROM ministries ORDER BY created_at DESC" : "SELECT * FROM ministries WHERE is_hidden = 0 ORDER BY created_at DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $ministries[] = $row;
        }
    }
    return $ministries;
}

function updateMinistry($id, $title, $description) {
    global $conn;
    $stmt = $conn->prepare("UPDATE ministries SET title = ?, description = ? 
                          WHERE id = ?");
    $stmt->bind_param("ssi", $title, $description, $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function archiveMinistry($id) {
    global $conn;
    
    // Fetch the ministry to archive it
    $stmt = $conn->prepare("SELECT * FROM ministries WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ministry = $result->fetch_assoc();
    $stmt->close();
    
    if ($ministry) {
        // Insert into archived_ministries
        $stmt = $conn->prepare("INSERT INTO archived_ministries (title, description, is_hidden, created_at, archived_at) 
                              VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssis", 
            $ministry['title'], 
            $ministry['description'], 
            $ministry['is_hidden'], 
            $ministry['created_at']
        );
        $archive_result = $stmt->execute();
        $stmt->close();
        
        if ($archive_result) {
            // Delete from ministries
            $stmt = $conn->prepare("DELETE FROM ministries WHERE id = ?");
            $stmt->bind_param("i", $id);
            $delete_result = $stmt->execute();
            $stmt->close();
            return $delete_result;
        }
        return false;
    }
    return false;
}

function getMinistry($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM ministries WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ministry = $result->fetch_assoc();
    $stmt->close();
    return $ministry;
}

function toggleMinistryVisibility($id, $is_hidden) {
    global $conn;
    $new_status = $is_hidden ? 0 : 1;
    $stmt = $conn->prepare("UPDATE ministries SET is_hidden = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Add new ministry
        if ($_POST['action'] == 'add') {
            $title = $_POST['title'];
            $description = $_POST['description'];
            
            $result = addMinistry($title, $description);
            if ($result) {
                $message = "Ministry added successfully!";
            } else {
                $error = "Error adding ministry: " . $conn->error;
            }
        }
        
        // Update existing ministry
        else if ($_POST['action'] == 'update') {
            $id = $_POST['id'];
            $title = $_POST['title'];
            $description = $_POST['description'];
            
            $result = updateMinistry($id, $title, $description);
            if ($result) {
                $message = "Ministry updated successfully!";
            } else {
                $error = "Error updating ministry: " . $conn->error;
            }
        }
        
        // Archive ministry
        else if ($_POST['action'] == 'archive') {
            $id = $_POST['id'];
            $result = archiveMinistry($id);
            if ($result) {
                $message = "Ministry archived successfully!";
            } else {
                $error = "Error archiving ministry: " . $conn->error;
            }
        }
        
        // Toggle visibility
        else if ($_POST['action'] == 'toggle_visibility') {
            $id = $_POST['id'];
            $is_hidden = $_POST['is_hidden'];
            $result = toggleMinistryVisibility($id, $is_hidden);
            if ($result) {
                $message = $is_hidden ? "Ministry unhidden successfully!" : "Ministry hidden successfully!";
            } else {
                $error = "Error toggling ministry visibility: " . $conn->error;
            }
        }
    }
}

// Get ministry for editing
$edit_ministry = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_ministry = getMinistry($_GET['edit']);
}

// Get all ministries for display (including hidden ones for admin)
$ministries = getMinistries(true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Life International - Ministries Management</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        header {
            background-color: #2c3e50;
            color: white;
            padding: 1rem 0;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .nav-links {
            display: flex;
            list-style: none;
        }
        .nav-links li {
            margin-left: 20px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
        }
        .admin-panel {
            background-color: #f5f5f5;
            padding: 2rem;
            margin: 2rem 0;
            border-radius: 5px;
        }
        .admin-form {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border: 1px solid #ddd;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
        }
        textarea {
            min-height: 100px;
        }
        .admin-button {
            background-color: #1a3c6e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .admin-button:hover {
            background-color: #16325e;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 15px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 15px;
        }
        .admin-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .edit-button {
            background-color: #28a745;
        }
        .archive-button {
            background-color: #dc3545;
        }
        .hide-button {
            background-color: #6c757d;
        }
        .ministry-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .ministry-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .ministry-title {
            color: #1a3c6e;
            margin-top: 0;
            font-size: 1.4em;
        }
        .ministry-description {
            margin-top: 10px;
        }
        .ministry-status {
            color: #dc3545;
            font-weight: bold;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .ministry-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">New Life International</div>
                <ul class="nav-links">
                    <li><a href="#">Manage Ministries</a></li>
                    <!-- <li><a href="events.php">Manage Events</a></li>
                    <li><a href="services.php">Manage Services</a></li>
                    <li><a href="about.php">Manage About Us</a></li>
                    <li><a href="contacts.php">Manage Contacts</a></li> -->
                    <li><a href="dash.php">Back to Dashboard</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="admin-panel">
        <div class="container">
            <h2><?php echo $edit_ministry ? 'Edit Ministry' : 'Add New Ministry'; ?></h2>
            
            <?php if (isset($message)): ?>
                <div class="success-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="admin-form">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $edit_ministry ? 'update' : 'add'; ?>">
                    <?php if ($edit_ministry): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_ministry['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Ministry Title:</label>
                        <input type="text" id="title" name="title" value="<?php echo $edit_ministry ? htmlspecialchars($edit_ministry['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" required><?php echo $edit_ministry ? htmlspecialchars($edit_ministry['description']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="admin-button"><?php echo $edit_ministry ? 'Update Ministry' : 'Add Ministry'; ?></button>
                    <?php if ($edit_ministry): ?>
                        <a href="ministries.php" class="admin-button" style="background-color: #6c757d;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <h2>Manage Existing Ministries</h2>
            
            <?php if (empty($ministries)): ?>
                <p>No ministries available.</p>
            <?php else: ?>
                <div class="ministry-container">
                    <?php foreach ($ministries as $ministry): ?>
                        <div class="ministry-card">
                            <h3 class="ministry-title"><?php echo htmlspecialchars($ministry['title']); ?></h3>
                            <?php if ($ministry['is_hidden']): ?>
                                <p class="ministry-status">Hidden</p>
                            <?php endif; ?>
                            <p class="ministry-description"><?php echo nl2br(htmlspecialchars($ministry['description'])); ?></p>
                            
                            <div class="admin-actions">
                                <a href="?edit=<?php echo $ministry['id']; ?>" class="admin-button edit-button">Edit</a>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="archive">
                                    <input type="hidden" name="id" value="<?php echo $ministry['id']; ?>">
                                    <button type="submit" class="admin-button archive-button" onclick="return confirm('Are you sure you want to archive this ministry?');">Archive</button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_visibility">
                                    <input type="hidden" name="id" value="<?php echo $ministry['id']; ?>">
                                    <input type="hidden" name="is_hidden" value="<?php echo $ministry['is_hidden']; ?>">
                                    <button type="submit" class="admin-button hide-button"><?php echo $ministry['is_hidden'] ? 'Unhide' : 'Hide'; ?></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>