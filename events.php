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

// Event management functions
function addEvent($title, $event_date, $description, $image_file = null, $link_url = null, $link_type = 'learn_more') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO events (title, event_date, description, image_file, link_url, link_type, is_hidden) 
                          VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("ssssss", $title, $event_date, $description, $image_file, $link_url, $link_type);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function getEvents($show_hidden = true) {
    global $conn;
    $events = array();
    $sql = $show_hidden ? "SELECT * FROM events ORDER BY created_at DESC" : "SELECT * FROM events WHERE is_hidden = 0 ORDER BY created_at DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }
    return $events;
}

function updateEvent($id, $title, $event_date, $description, $image_file = null, $link_url = null, $link_type = 'learn_more') {
    global $conn;
    if ($image_file === null && $link_url === null) {
        $stmt = $conn->prepare("UPDATE events SET title = ?, event_date = ?, description = ?, link_type = ? 
                              WHERE id = ?");
        $stmt->bind_param("ssssi", $title, $event_date, $description, $link_type, $id);
    } elseif ($image_file === null) {
        $stmt = $conn->prepare("UPDATE events SET title = ?, event_date = ?, description = ?, link_url = ?, link_type = ? 
                              WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $event_date, $description, $link_url, $link_type, $id);
    } elseif ($link_url === null) {
        $stmt = $conn->prepare("UPDATE events SET title = ?, event_date = ?, description = ?, image_file = ?, link_type = ? 
                              WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $event_date, $description, $image_file, $link_type, $id);
    } else {
        $stmt = $conn->prepare("UPDATE events SET title = ?, event_date = ?, description = ?, image_file = ?, link_url = ?, link_type = ? 
                              WHERE id = ?");
        $stmt->bind_param("ssssssi", $title, $event_date, $description, $image_file, $link_url, $link_type, $id);
    }
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function deleteEvent($id) {
    global $conn;
    
    // Fetch the event to archive it
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
    
    if ($event) {
        // Insert into archived_events
        $stmt = $conn->prepare("INSERT INTO archived_events (title, event_date, description, image_file, link_url, link_type, is_hidden, created_at, archived_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssssis", 
            $event['title'], 
            $event['event_date'], 
            $event['description'], 
            $event['image_file'], 
            $event['link_url'], 
            $event['link_type'], 
            $event['is_hidden'], 
            $event['created_at']
        );
        $archive_result = $stmt->execute();
        $stmt->close();
        
        if ($archive_result) {
            // Delete from events
            $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
            $stmt->bind_param("i", $id);
            $delete_result = $stmt->execute();
            $stmt->close();
            return $delete_result;
        }
        return false;
    }
    return false;
}

function getEvent($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
    return $event;
}

function toggleEventVisibility($id, $is_hidden) {
    global $conn;
    $new_status = $is_hidden ? 0 : 1;
    $stmt = $conn->prepare("UPDATE events SET is_hidden = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Add new event
        if ($_POST['action'] == 'add') {
            $title = $_POST['title'];
            $event_date = $_POST['event_date'];
            $description = $_POST['description'];
            $link_url = !empty($_POST['link_url']) ? $_POST['link_url'] : null;
            $link_type = $_POST['link_type'];
            
            $image_file = null;
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
                $target_dir = "Uploads/";
                $image_file = $target_dir . basename($_FILES["image_file"]["name"]);
                move_uploaded_file($_FILES["image_file"]["tmp_name"], $image_file);
            }
            
            $result = addEvent($title, $event_date, $description, $image_file, $link_url, $link_type);
            if ($result) {
                $message = "Event added successfully!";
            } else {
                $error = "Error adding event: " . $conn->error;
            }
        }
        
        // Update existing event
        else if ($_POST['action'] == 'update') {
            $id = $_POST['id'];
            $title = $_POST['title'];
            $event_date = $_POST['event_date'];
            $description = $_POST['description'];
            $link_url = !empty($_POST['link_url']) ? $_POST['link_url'] : null;
            $link_type = $_POST['link_type'];
            
            $image_file = null;
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
                $target_dir = "Uploads/";
                $image_file = $target_dir . basename($_FILES["image_file"]["name"]);
                move_uploaded_file($_FILES["image_file"]["tmp_name"], $image_file);
            }
            
            $result = updateEvent($id, $title, $event_date, $description, $image_file, $link_url, $link_type);
            if ($result) {
                $message = "Event updated successfully!";
            } else {
                $error = "Error updating event: " . $conn->error;
            }
        }
        
        // Archive event
        else if ($_POST['action'] == 'delete') {
            $id = $_POST['id'];
            $result = deleteEvent($id);
            if ($result) {
                $message = "Event archived successfully!";
            } else {
                $error = "Error archiving event: " . $conn->error;
            }
        }
        
        // Toggle event visibility
        else if ($_POST['action'] == 'toggle_visibility') {
            $id = $_POST['id'];
            $is_hidden = $_POST['is_hidden'];
            $result = toggleEventVisibility($id, $is_hidden);
            if ($result) {
                $message = $is_hidden ? "Event unhidden successfully!" : "Event hidden successfully!";
            } else {
                $error = "Error toggling event visibility: " . $conn->error;
            }
        }
    }
}

// Get event for editing
$edit_event = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_event = getEvent($_GET['edit']);
}

// Get all events for display (including hidden ones for admin)
$events = getEvents(true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Life International - Events Management</title>
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
        .event-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .event-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .event-title {
            color: #1a3c6e;
            margin-top: 0;
            font-size: 1.4em;
        }
        .event-date {
            color: #666;
            font-style: italic;
            margin-bottom: 10px;
        }
        .event-description {
            margin-top: 10px;
        }
        .event-image {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
        }
        .event-status {
            color: #dc3545;
            font-weight: bold;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .event-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">New Life International - Events Admin</div>
                <ul class="nav-links">
                    <li><a href="#">Manage Events</a></li>
                    <li><a href="dash.php">Back to Dashboard</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="admin-panel">
        <div class="container">
            <h2><?php echo $edit_event ? 'Edit Event' : 'Add New Event'; ?></h2>
            
            <?php if (isset($message)): ?>
                <div class="success-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="admin-form">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $edit_event ? 'update' : 'add'; ?>">
                    <?php if ($edit_event): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_event['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Event Title:</label>
                        <input type="text" id="title" name="title" value="<?php echo $edit_event ? htmlspecialchars($edit_event['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_date">Event Date(s):</label>
                        <input type="text" id="event_date" name="event_date" value="<?php echo $edit_event ? htmlspecialchars($event_date) : ''; ?>" required placeholder="e.g., May 15, 2025 or June 5-9, 2025">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" required><?php echo $edit_event ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image_file">Event Image (optional):</label>
                        <input type="file" id="image_file" name="image_file" accept="image/*">
                        <?php if ($edit_event && $edit_event['image_file']): ?>
                            <p>Current image: <img src="<?php echo htmlspecialchars($edit_event['image_file']); ?>" alt="Current event image" style="max-width: 100px; margin-top: 5px;"></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="link_url">Link URL (optional):</label>
                        <input type="url" id="link_url" name="link_url" value="<?php echo $edit_event ? htmlspecialchars($edit_event['link_url']) : ''; ?>" placeholder="https://example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="link_type">Link Type:</label>
                        <select id="link_type" name="link_type">
                            <option value="learn_more" <?php echo $edit_event && $edit_event['link_type'] == 'learn_more' ? 'selected' : ''; ?>>Learn More</option>
                            <option value="register" <?php echo $edit_event && $edit_event['link_type'] == 'register' ? 'selected' : ''; ?>>Register</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="admin-button"><?php echo $edit_event ? 'Update Event' : 'Add Event'; ?></button>
                    <?php if ($edit_event): ?>
                        <a href="events.php" class="admin-button" style="background-color: #6c757d;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <h2>Manage Existing Events</h2>
            
            <?php if (empty($events)): ?>
                <p>No events available.</p>
            <?php else: ?>
                <div class="event-container">
                    <?php foreach ($events as $event): ?>
                        <div class="event-card">
                            <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p class="event-date"><?php echo htmlspecialchars($event['event_date']); ?></p>
                            <?php if ($event['is_hidden']): ?>
                                <p class="event-status">Hidden</p>
                            <?php endif; ?>
                            <p class="event-description"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                            <?php if ($event['image_file']): ?>
                                <img src="<?php echo htmlspecialchars($event['image_file']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-image">
                            <?php endif; ?>
                            <?php if ($event['link_url']): ?>
                                <p><strong>Link:</strong> <a href="<?php echo htmlspecialchars($event['link_url']); ?>"><?php echo htmlspecialchars($event['link_url']); ?></a> (<?php echo $event['link_type'] == 'register' ? 'Register' : 'Learn More'; ?>)</p>
                            <?php endif; ?>
                            
                            <div class="admin-actions">
                                <a href="?edit=<?php echo $event['id']; ?>" class="admin-button edit-button">Edit</a>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                                    <button type="submit" class="admin-button archive-button" onclick="return confirm('Are you sure you want to archive this event?');">Archive</button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_visibility">
                                    <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                                    <input type="hidden" name="is_hidden" value="<?php echo $event['is_hidden']; ?>">
                                    <button type="submit" class="admin-button hide-button"><?php echo $event['is_hidden'] ? 'Unhide' : 'Hide'; ?></button>
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