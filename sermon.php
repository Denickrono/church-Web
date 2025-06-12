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

// Sermon management functions
function addSermon($title, $date, $speaker, $scripture, $description, $audio_file = null) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO sermons (title, sermon_date, speaker, scripture, description, audio_file, is_hidden) 
                          VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("ssssss", $title, $date, $speaker, $scripture, $description, $audio_file);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function getSermons($show_hidden = true) {
    global $conn;
    $sermons = array();
    $sql = $show_hidden ? "SELECT * FROM sermons ORDER BY sermon_date DESC" : "SELECT * FROM sermons WHERE is_hidden = 0 ORDER BY sermon_date DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $sermons[] = $row;
        }
    }
    return $sermons;
}

function updateSermon($id, $title, $date, $speaker, $scripture, $description, $audio_file = null) {
    global $conn;
    if ($audio_file === null) {
        $stmt = $conn->prepare("UPDATE sermons SET title = ?, sermon_date = ?, speaker = ?, scripture = ?, description = ? 
                              WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $date, $speaker, $scripture, $description, $id);
    } else {
        $stmt = $conn->prepare("UPDATE sermons SET title = ?, sermon_date = ?, speaker = ?, scripture = ?, description = ?, audio_file = ? 
                              WHERE id = ?");
        $stmt->bind_param("ssssssi", $title, $date, $speaker, $scripture, $description, $audio_file, $id);
    }
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function deleteSermon($id) {
    global $conn;
    
    // Fetch the sermon to archive it
    $stmt = $conn->prepare("SELECT * FROM sermons WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sermon = $result->fetch_assoc();
    $stmt->close();
    
    if ($sermon) {
        // Insert into archived_sermons
        $stmt = $conn->prepare("INSERT INTO archived_sermons (title, sermon_date, speaker, scripture, description, audio_file, is_hidden, created_at, archived_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssssis", 
            $sermon['title'], 
            $sermon['sermon_date'], 
            $sermon['speaker'], 
            $sermon['scripture'], 
            $sermon['description'], 
            $sermon['audio_file'], 
            $sermon['is_hidden'], 
            $sermon['created_at']
        );
        $archive_result = $stmt->execute();
        $stmt->close();
        
        if ($archive_result) {
            // Delete from sermons
            $stmt = $conn->prepare("DELETE FROM sermons WHERE id = ?");
            $stmt->bind_param("i", $id);
            $delete_result = $stmt->execute();
            $stmt->close();
            return $delete_result;
        }
        return false;
    }
    return false;
}

function getSermon($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM sermons WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sermon = $result->fetch_assoc();
    $stmt->close();
    return $sermon;
}

function toggleSermonVisibility($id, $is_hidden) {
    global $conn;
    $new_status = $is_hidden ? 0 : 1;
    $stmt = $conn->prepare("UPDATE sermons SET is_hidden = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Add new sermon
        if ($_POST['action'] == 'add') {
            $title = $_POST['title'];
            $date = $_POST['date'];
            $speaker = $_POST['speaker'];
            $scripture = $_POST['scripture'];
            $description = $_POST['description'];
            
            $audio_file = null;
            if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
                $target_dir = "Uploads/";
                $audio_file = $target_dir . basename($_FILES["audio_file"]["name"]);
                move_uploaded_file($_FILES["audio_file"]["tmp_name"], $audio_file);
            }
            
            $result = addSermon($title, $date, $speaker, $scripture, $description, $audio_file);
            if ($result) {
                $message = "Sermon added successfully!";
            } else {
                $error = "Error adding sermon: " . $conn->error;
            }
        }
        
        // Update existing sermon
        else if ($_POST['action'] == 'update') {
            $id = $_POST['id'];
            $title = $_POST['title'];
            $date = $_POST['date'];
            $speaker = $_POST['speaker'];
            $scripture = $_POST['scripture'];
            $description = $_POST['description'];
            
            $audio_file = null;
            if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
                $target_dir = "Uploads/";
                $audio_file = $target_dir . basename($_FILES["audio_file"]["name"]);
                move_uploaded_file($_FILES["audio_file"]["tmp_name"], $audio_file);
            }
            
            $result = updateSermon($id, $title, $date, $speaker, $scripture, $description, $audio_file);
            if ($result) {
                $message = "Sermon updated successfully!";
            } else {
                $error = "Error updating sermon: " . $conn->error;
            }
        }
        
        // Archive sermon
        else if ($_POST['action'] == 'delete') {
            $id = $_POST['id'];
            $result = deleteSermon($id);
            if ($result) {
                $message = "Sermon archived successfully!";
            } else {
                $error = "Error archiving sermon: " . $conn->error;
            }
        }
        
        // Toggle sermon visibility
        else if ($_POST['action'] == 'toggle_visibility') {
            $id = $_POST['id'];
            $is_hidden = $_POST['is_hidden'];
            $result = toggleSermonVisibility($id, $is_hidden);
            if ($result) {
                $message = $is_hidden ? "Sermon unhidden successfully!" : "Sermon hidden successfully!";
            } else {
                $error = "Error toggling sermon visibility: " . $conn->error;
            }
        }
    }
}

// Get sermon for editing
$edit_sermon = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_sermon = getSermon($_GET['edit']);
}

// Get all sermons for display (including hidden ones for admin)
$sermons = getSermons(true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Life International - Admin Panel</title>
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
        input, textarea, select {
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
        audio {
            width: 100%;
            margin-top: 10px;
        }
        .sermon-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .sermon-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .sermon-title {
            color: #1a3c6e;
            margin-top: 0;
            font-size: 1.4em;
        }
        .sermon-date {
            color: #666;
            font-style: italic;
            margin-bottom: 10px;
        }
        .sermon-speaker {
            font-weight: bold;
            color: #444;
        }
        .sermon-scripture {
            margin: 10px 0;
            color: #333;
        }
        .sermon-description {
            margin-top: 10px;
        }
        .sermon-status {
            color: #dc3545;
            font-weight: bold;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .sermon-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">New Life International - Admin Sermon</div>
                <ul class="nav-links">
                    <li><a href="dash.php">Back to Dashboard</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="admin-panel">
        <div class="container">
            <h2><?php echo $edit_sermon ? 'Edit Sermon' : 'Add New Sermon'; ?></h2>
            
            <?php if (isset($message)): ?>
                <div class="success-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="admin-form">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $edit_sermon ? 'update' : 'add'; ?>">
                    <?php if ($edit_sermon): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_sermon['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Sermon Title:</label>
                        <input type="text" id="title" name="title" value="<?php echo $edit_sermon ? htmlspecialchars($edit_sermon['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Date:</label>
                        <input type="date" id="date" name="date" value="<?php echo $edit_sermon ? $edit_sermon['sermon_date'] : date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="speaker">Speaker:</label>
                        <input type="text" id="speaker" name="speaker" value="<?php echo $edit_sermon ? htmlspecialchars($edit_sermon['speaker']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="scripture">Scripture Reference:</label>
                        <input type="text" id="scripture" name="scripture" value="<?php echo $edit_sermon ? htmlspecialchars($edit_sermon['scripture']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description"><?php echo $edit_sermon ? htmlspecialchars($edit_sermon['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="audio_file">Audio File (optional):</label>
                        <input type="file" id="audio_file" name="audio_file" accept="audio/*">
                        <?php if ($edit_sermon && $edit_sermon['audio_file']): ?>
                            <p>Current file: <?php echo basename($edit_sermon['audio_file']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="admin-button"><?php echo $edit_sermon ? 'Update Sermon' : 'Add Sermon'; ?></button>
                    <?php if ($edit_sermon): ?>
                        <a href="admin.php" class="admin-button" style="background-color: #6c757d;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <h2>Manage Existing Sermons</h2>
            
            <?php if (empty($sermons)): ?>
                <p>No sermons available.</p>
            <?php else: ?>
                <div class="sermon-container">
                    <?php foreach ($sermons as $sermon): ?>
                        <div class="sermon-card">
                            <h3 class="sermon-title"><?php echo htmlspecialchars($sermon['title']); ?></h3>
                            <p class="sermon-date"><?php echo date('F j, Y', strtotime($sermon['sermon_date'])); ?></p>
                            <?php if ($sermon['is_hidden']): ?>
                                <p class="sermon-status">Hidden</p>
                            <?php endif; ?>
                            <p class="sermon-speaker"><?php echo htmlspecialchars($sermon['speaker']); ?></p>
                            <p class="sermon-scripture"><strong>Scripture:</strong> <?php echo htmlspecialchars($sermon['scripture']); ?></p>
                            <p class="sermon-description"><?php echo nl2br(htmlspecialchars($sermon['description'])); ?></p>
                            
                            <?php if ($sermon['audio_file']): ?>
                                <audio controls>
                                    <source src="<?php echo htmlspecialchars($sermon['audio_file']); ?>" type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>
                            <?php endif; ?>
                            
                            <div class="admin-actions">
                                <a href="?edit=<?php echo $sermon['id']; ?>" class="admin-button edit-button">Edit</a>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $sermon['id']; ?>">
                                    <button type="submit" class="admin-button archive-button" onclick="return confirm('Are you sure you want to archive this sermon?');">Archive</button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_visibility">
                                    <input type="hidden" name="id" value="<?php echo $sermon['id']; ?>">
                                    <input type="hidden" name="is_hidden" value="<?php echo $sermon['is_hidden']; ?>">
                                    <button type="submit" class="admin-button hide-button"><?php echo $sermon['is_hidden'] ? 'Unhide' : 'Hide'; ?></button>
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