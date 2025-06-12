<?php
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
                
                // Create uploads directory if it doesn't exist
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
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
                
                // Create uploads directory if it doesn't exist
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
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
    <title>New Life International - Sermon Administration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Base styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        header {
            background-color: #2c3e50;
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
            color: #2c3e50;
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
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
            background-color: #2c3e50;
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
            background-color: #1a2836;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 3px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 3px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
        }
        
        /* Audio player */
        audio {
            width: 100%;
            margin-top: 10px;
        }
        
        /* Button styles */
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .edit-button {
            background-color: #3498db;
        }
        
        .edit-button:hover {
            background-color: #2980b9;
        }
        
        .delete-button {
            background-color: #e74c3c;
        }
        
        .delete-button:hover {
            background-color: #c0392b;
        }
        
        /* Navigation */
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .nav-back {
            padding: 8px 15px;
            background-color: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
        }
        
        .nav-back:hover {
            background-color: #1a2836;
        }
        
        /* Tabs */
        .tabs {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .tab {
            padding: 10px 20px;
            background-color: #ddd;
            border-radius: 5px 5px 0 0;
            cursor: pointer;
            border: 1px solid #ccc;
            border-bottom: none;
        }
        
        .tab.active {
            background-color: #fff;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Confirmation dialog */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }
        
        .confirmation-dialog {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 400px;
            text-align: center;
        }
        
        .confirmation-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <header>
        <h1>New Life International</h1>
        <h2>Sermon Administration</h2>
    </header>
    
    <div class="nav-container">
        <a href="index.php" class="nav-back"><i class="fas fa-arrow-left"></i> Back to Website</a>
    </div>
    
    <div class="tabs">
        <div class="tab active" data-tab="sermons">Sermons</div>
        <div class="tab" data-tab="events">Events</div>
        <div class="tab" data-tab="ministries">Ministries</div>
    </div>
    
    <div id="sermons" class="tab-content active">
        <?php if (isset($message)): ?>
            <div class="success-message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="admin-form">
            <h2><?php echo $edit_sermon ? 'Edit Sermon' : 'Add New Sermon'; ?></h2>
            
            <form action="admin.php" method="post" enctype="multipart/form-data">
                <?php if ($edit_sermon): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_sermon['id']; ?>">
                    <input type="hidden" name="action" value="update">
                <?php else: ?>
                    <input type="hidden" name="action" value="add">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">Sermon Title:</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo $edit_sermon ? htmlspecialchars($edit_sermon['title']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" required 
                           value="<?php echo $edit_sermon ? htmlspecialchars($edit_sermon['sermon_date']) : date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="speaker">Speaker:</label>
                    <input type="text" id="speaker" name="speaker" required 
                           value="<?php echo $edit_sermon ? htmlspecialchars($edit_sermon['speaker']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="scripture">Scripture Reference:</label>
                    <input type="text" id="scripture" name="scripture" required 
                           value="<?php echo $edit_sermon ? htmlspecialchars($edit_sermon['scripture']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required><?php echo $edit_sermon ? htmlspecialchars($edit_sermon['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="audio_file">Audio File (MP3):</label>
                    <input type="file" id="audio_file" name="audio_file" accept="audio/mp3,audio/mpeg">
                    <?php if ($edit_sermon && $edit_sermon['audio_file']): ?>
                        <p>Current file: <?php echo basename($edit_sermon['audio_file']); ?></p>
                        <audio controls>
                            <source src="<?php echo htmlspecialchars($edit_sermon['audio_file']); ?>" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <button type="submit"><?php echo $edit_sermon ? 'Update Sermon' : 'Add Sermon'; ?></button>
                    <?php if ($edit_sermon): ?>
                        <a href="admin.php" class="button" style="background-color: #7f8c8d;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <h2>Manage Sermons</h2>
        
        <?php if (empty($sermons)): ?>
            <p>No sermons available.</p>
        <?php else: ?>
            <div class="sermon-container">
                <?php foreach($sermons as $sermon): ?>
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
                        
                        <div class="button-group">
                            <a href="admin.php?edit=<?php echo $sermon['id']; ?>" class="button edit-button">Edit</a>
                            <button class="delete-button" onclick="confirmDelete(<?php echo $sermon['id']; ?>, '<?php echo addslashes($sermon['title']); ?>')">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div id="events" class="tab-content">
        <div class="admin-form">
            <h2>Add New Event</h2>
            <p>Event management functionality will be added here.</p>
            <!-- Event form would go here -->
        </div>
        
        <h2>Manage Events</h2>
        <p>No events available. Please add events using the form above.</p>
    </div>
    
    <div id="ministries" class="tab-content">
        <div class="admin-form">
            <h2>Add New Ministry</h2>
            <p>Ministry management functionality will be added here.</p>
            <!-- Ministry form would go here -->
        </div>
        
        <h2>Manage Ministries</h2>
        <p>No ministries available. Please add ministries using the form above.</p>
    </div>
    
    <!-- Confirmation Dialog -->
    <div class="overlay" id="deleteConfirmation">
        <div class="confirmation-dialog">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete the sermon: <span id="sermonTitle"></span>?</p>
            <div class="confirmation-buttons">
                <button onclick="hideConfirmDialog()">Cancel</button>
                <form id="deleteForm" method="post" action="admin.php" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="deleteId" name="id" value="">
                    <button type="submit" class="delete-button">Delete</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and content
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding content
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Delete confirmation functionality
        function confirmDelete(id, title) {
            document.getElementById('sermonTitle').innerText = title;
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteConfirmation').style.display = 'flex';
        }
        
        function hideConfirmDialog() {
            document.getElementById('deleteConfirmation').style.display = 'none';
        }
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>