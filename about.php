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

// About management functions
function addAbout($title, $content) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO about (title, content, is_hidden) 
                          VALUES (?, ?, 0)");
    $stmt->bind_param("ss", $title, $content);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function getAboutItems($show_hidden = true) {
    global $conn;
    $items = array();
    $sql = $show_hidden ? "SELECT * FROM about ORDER BY created_at DESC" : "SELECT * FROM about WHERE is_hidden = 0 ORDER BY created_at DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    return $items;
}

function updateAbout($id, $title, $content) {
    global $conn;
    $stmt = $conn->prepare("UPDATE about SET title = ?, content = ? 
                          WHERE id = ?");
    $stmt->bind_param("ssi", $title, $content, $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function archiveAbout($id) {
    global $conn;
    
    // Fetch the item to archive it
    $stmt = $conn->prepare("SELECT * FROM about WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();
    
    if ($item) {
        // Insert into archived_about
        $stmt = $conn->prepare("INSERT INTO archived_about (title, content, is_hidden, created_at, archived_at) 
                              VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssis", 
            $item['title'], 
            $item['content'], 
            $item['is_hidden'], 
            $item['created_at']
        );
        $archive_result = $stmt->execute();
        $stmt->close();
        
        if ($archive_result) {
            // Delete from about
            $stmt = $conn->prepare("DELETE FROM about WHERE id = ?");
            $stmt->bind_param("i", $id);
            $delete_result = $stmt->execute();
            $stmt->close();
            return $delete_result;
        }
        return false;
    }
    return false;
}

function getAbout($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM about WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();
    return $item;
}

function toggleAboutVisibility($id, $is_hidden) {
    global $conn;
    $new_status = $is_hidden ? 0 : 1;
    $stmt = $conn->prepare("UPDATE about SET is_hidden = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Add new item
        if ($_POST['action'] == 'add') {
            $title = $_POST['title'];
            $content = $_POST['content'];
            
            $result = addAbout($title, $content);
            if ($result) {
                $message = "About item added successfully!";
            } else {
                $error = "Error adding about item: " . $conn->error;
            }
        }
        
        // Update existing item
        else if ($_POST['action'] == 'update') {
            $id = $_POST['id'];
            $title = $_POST['title'];
            $content = $_POST['content'];
            
            $result = updateAbout($id, $title, $content);
            if ($result) {
                $message = "About item updated successfully!";
            } else {
                $error = "Error updating about item: " . $conn->error;
            }
        }
        
        // Archive item
        else if ($_POST['action'] == 'archive') {
            $id = $_POST['id'];
            $result = archiveAbout($id);
            if ($result) {
                $message = "About item archived successfully!";
            } else {
                $error = "Error archiving about item: " . $conn->error;
            }
        }
        
        // Toggle visibility
        else if ($_POST['action'] == 'toggle_visibility') {
            $id = $_POST['id'];
            $is_hidden = $_POST['is_hidden'];
            $result = toggleAboutVisibility($id, $is_hidden);
            if ($result) {
                $message = $is_hidden ? "About item unhidden successfully!" : "About item hidden successfully!";
            } else {
                $error = "Error toggling about item visibility: " . $conn->error;
            }
        }
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_item = getAbout($_GET['edit']);
}

// Get all items for display (including hidden ones for admin)
$items = getAboutItems(true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Life International - About Us Management</title>
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
            min-height: 200px;
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
        .item-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .item-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .item-title {
            color: #1a3c6e;
            margin-top: 0;
            font-size: 1.4em;
        }
        .item-content {
            margin-top: 10px;
        }
        .item-status {
            color: #dc3545;
            font-weight: bold;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .item-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">New Life International - About Us Admin</div>
                <ul class="nav-links">
                    <!-- <li><a href="#">Manage Sermons</a></li>
                    <li><a href="events.php">Manage Events</a></li>
                    <li><a href="services.php">Manage Services</a></li>
                    <li><a href="ministries.php">Manage Ministries</a></li>
                    <li><a href="contacts.php">Manage Contacts</a></li> -->
                    <li><a href="dash.php">Back to Dashboard</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="admin-panel">
        <div class="container">
            <h2><?php echo $edit_item ? 'Edit About Item' : 'Add New About Item'; ?></h2>
            
            <?php if (isset($message)): ?>
                <div class="success-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="admin-form">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $edit_item ? 'update' : 'add'; ?>">
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_item['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" value="<?php echo $edit_item ? htmlspecialchars($edit_item['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content:</label>
                        <textarea id="content" name="content" required><?php echo $edit_item ? htmlspecialchars($edit_item['content']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="admin-button"><?php echo $edit_item ? 'Update About Item' : 'Add About Item'; ?></button>
                    <?php if ($edit_item): ?>
                        <a href="about.php" class="admin-button" style="background-color: #6c757d;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <h2>Manage Existing About Items</h2>
            
            <?php if (empty($items)): ?>
                <p>No about items available.</p>
            <?php else: ?>
                <div class="item-container">
                    <?php foreach ($items as $item): ?>
                        <div class="item-card">
                            <h3 class="item-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <?php if ($item['is_hidden']): ?>
                                <p class="item-status">Hidden</p>
                            <?php endif; ?>
                            <p class="item-content"><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
                            
                            <div class="admin-actions">
                                <a href="?edit=<?php echo $item['id']; ?>" class="admin-button edit-button">Edit</a>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="archive">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="admin-button archive-button" onclick="return confirm('Are you sure you want to archive this about item?');">Archive</button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_visibility">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="is_hidden" value="<?php echo $item['is_hidden']; ?>">
                                    <button type="submit" class="admin-button hide-button"><?php echo $item['is_hidden'] ? 'Unhide' : 'Hide'; ?></button>
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