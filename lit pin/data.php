<?php
// This is a more complete PHP/MySQL based solution for sermon management

// Database connection details
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

// Function to add a new sermon
function addSermon($title, $date, $speaker, $scripture, $description, $audio_file = null) {
    global $conn;
    
    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO sermons (title, sermon_date, speaker, scripture, description, audio_file) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    
    // Bind parameters
    $stmt->bind_param("ssssss", $title, $date, $speaker, $scripture, $description, $audio_file);
    
    // Execute statement
    $result = $stmt->execute();
    
    // Close statement
    $stmt->close();
    
    return $result;
}

// Function to get all sermons
function getSermons() {
    global $conn;
    
    $sermons = array();
    
    // Query to get all sermons ordered by date (newest first)
    $sql = "SELECT * FROM sermons ORDER BY sermon_date DESC";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $sermons[] = $row;
        }
    }
    
    return $sermons;
}

// Function to update a sermon
function updateSermon($id, $title, $date, $speaker, $scripture, $description, $audio_file = null) {
    global $conn;
    
    // If no new audio file, don't update that field
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

// Function to delete a sermon
function deleteSermon($id) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM sermons WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Function to get a single sermon
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
            
            // Handle file upload
            $audio_file = null;
            if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
                $target_dir = "uploads/";
                $audio_file = $target_dir . basename($_FILES["audio_file"]["name"]);
                
                // Move the uploaded file
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
            
            // Handle file upload
            $audio_file = null;
            if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
                $target_dir = "uploads/";
                $audio_file = $target_dir . basename($_FILES["audio_file"]["name"]);
                
                // Move the uploaded file
                move_uploaded_file($_FILES["audio_file"]["tmp_name"], $audio_file);
            }
            
            $result = updateSermon($id, $title, $date, $speaker, $scripture, $description, $audio_file);
            
            if ($result) {
                $message = "Sermon updated successfully!";
            } else {
                $error = "Error updating sermon: " . $conn->error;
            }
        }
        
        // Delete sermon
        else if ($_POST['action'] == 'delete') {
            $id = $_POST['id'];
            
            $result = deleteSermon($id);
            
            if ($result) {
                $message = "Sermon deleted successfully!";
            } else {
                $error = "Error deleting sermon: " . $conn->error;
            }
        }
    }
}

// Get sermon for editing
$edit_sermon = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_sermon = getSermon($_GET['edit']);
}

// Get all sermons for display
$sermons = getSermons();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grace Community Church - Sermons</title>
    <style>
        /* Base styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: #1a3c6e;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        
        /* Sermon display styles */
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
        
        /* Admin form styles */
        .admin-form {
            background-color: #f9f9f9;
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
        
        button, .button {
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
        
        button:hover, .button:hover {
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
        
        /* Tabs for admin/public view */
        .tabs {
            margin-bottom: 20px;
            display: flex;
        }
        
        .tab {
            padding: 10px 20px;
            background-color: #eee;
            text-decoration: none;
            color: #333;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        
        .tab.active {
            background-color: #1a3c6e;
            color: white;
        }
        
        /* Audio player */
        audio {
            width: 100%;
            margin-top: 10px;
        }
        
        /* Admin action buttons */
        .admin-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .edit-button {
            background-color: #28a745;
        }
        
        .delete-button {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <header>
        <h1>Grace Community Church</h1>
        <p>Sharing God's Word Every Sunday</p>
    </header>
    
    <div class="tabs">
        <a href="sermons.php" class="tab <?php echo (!isset($_GET['admin'])) ? 'active' : ''; ?>">Public View</a>
        <a href="sermons.php?admin=1" class="tab <?php echo (isset($_GET['admin'])) ? 'active' : ''; ?>">Admin Panel</a>
    </div>
    
    <?php if (isset($_GET['admin'])): ?>
        <!-- Admin Panel View -->
        <h2><?php echo $edit_sermon ? 'Edit Sermon' : 'Add New Sermon'; ?></h2>
        
        <?php if (isset($message)): ?>
            <div class="success-message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="admin-form">
            <form method="post" action="sermons.php?admin=1" enctype="multipart/form-data">
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
                
                <button type="submit"><?php echo $edit_sermon ? 'Update Sermon' : 'Add Sermon'; ?></button>
                <?php if ($edit_sermon): ?>
                    <a href="sermons.php?admin=1" class="button" style="background-color: #6c757d;">Cancel</a>
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
                            <a href="sermons.php?admin=1&edit=<?php echo $sermon['id']; ?>" class="button edit-button">Edit</a>
                            <form method="post" action="sermons.php?admin=1" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $sermon['id']; ?>">
                                <button type="submit" class="button delete-button" onclick="return confirm('Are you sure you want to delete this sermon?');">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- Public View -->
        <h2>Recent Sermons</h2>
        
        <?php if (empty($sermons)): ?>
            <p>No sermons available.</p>
        <?php else: ?>
            <div class="sermon-container">
                <?php foreach ($sermons as $sermon): ?>
                    <div class="sermon-card">
                        <h3 class="sermon-title"><?php echo htmlspecialchars($sermon['title']); ?></h3>
                        <p class="sermon-date"><?php echo date('F j, Y', strtotime($sermon['sermon_date'])); ?></p>
                        <p class="sermon-speaker"><?php echo htmlspecialchars($sermon['speaker']); ?></p>
                        <p class="sermon-scripture"><strong>Scripture:</strong> <?php echo htmlspecialchars($sermon['scripture']); ?></p>
                        <p class="sermon-description"><?php echo nl2br(htmlspecialchars($sermon['description'])); ?></p>
                        
                        <?php if ($sermon['audio_file']): ?>
                            <audio controls>
                                <source src="<?php echo htmlspecialchars($sermon['audio_file']); ?>" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <footer style="margin-top: 50px; text-align: center; padding: 20px; border-top: 1px solid #ddd;">
        <p>&copy; <?php echo date('Y'); ?> Grace Community Church. All rights reserved.</p>
    </footer>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>