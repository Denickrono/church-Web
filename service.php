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

// Service management functions
function addService($title, $service_time, $description) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO services (title, service_time, description, is_hidden) 
                          VALUES (?, ?, ?, 0)");
    $stmt->bind_param("sss", $title, $service_time, $description);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function getServices($show_hidden = true) {
    global $conn;
    $services = array();
    $sql = $show_hidden ? "SELECT * FROM services ORDER BY created_at DESC" : "SELECT * FROM services WHERE is_hidden = 0 ORDER BY created_at DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }
    return $services;
}

function updateService($id, $title, $service_time, $description) {
    global $conn;
    $stmt = $conn->prepare("UPDATE services SET title = ?, service_time = ?, description = ? 
                          WHERE id = ?");
    $stmt->bind_param("sssi", $title, $service_time, $description, $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function archiveService($id) {
    global $conn;
    
    // Fetch the service to archive it
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();
    $stmt->close();
    
    if ($service) {
        // Insert into archived_services
        $stmt = $conn->prepare("INSERT INTO archived_services (title, service_time, description, is_hidden, created_at, archived_at) 
                              VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssds", 
            $service['title'], 
            $service['service_time'], 
            $service['description'], 
            $service['is_hidden'], 
            $service['created_at']
        );
        $archive_result = $stmt->execute();
        $stmt->close();
        
        if ($archive_result) {
            // Delete from services
            $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
            $stmt->bind_param("i", $id);
            $delete_result = $stmt->execute();
            $stmt->close();
            return $delete_result;
        }
        return false;
    }
    return false;
}

function getService($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();
    $stmt->close();
    return $service;
}

function toggleServiceVisibility($id, $is_hidden) {
    global $conn;
    $new_status = $is_hidden ? 0 : 1;
    $stmt = $conn->prepare("UPDATE services SET is_hidden = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Add new service
        if ($_POST['action'] == 'add') {
            $title = $_POST['title'];
            $service_time = $_POST['service_time'];
            $description = $_POST['description'];
            
            $result = addService($title, $service_time, $description);
            if ($result) {
                $message = "Service added successfully!";
            } else {
                $error = "Error adding service: " . $conn->error;
            }
        }
        
        // Update existing service
        else if ($_POST['action'] == 'update') {
            $id = $_POST['id'];
            $title = $_POST['title'];
            $service_time = $_POST['service_time'];
            $description = $_POST['description'];
            
            $result = updateService($id, $title, $service_time, $description);
            if ($result) {
                $message = "Service updated successfully!";
            } else {
                $error = "Error updating service: " . $conn->error;
            }
        }
        
        // Archive service
        else if ($_POST['action'] == 'archive') {
            $id = $_POST['id'];
            $result = archiveService($id);
            if ($result) {
                $message = "Service archived successfully!";
            } else {
                $error = "Error archiving service: " . $conn->error;
            }
        }
        
        // Toggle service visibility
        else if ($_POST['action'] == 'toggle_visibility') {
            $id = $_POST['id'];
            $is_hidden = $_POST['is_hidden'];
            $result = toggleServiceVisibility($id, $is_hidden);
            if ($result) {
                $message = $is_hidden ? "Service unhidden successfully!" : "Service hidden successfully!";
            } else {
                $error = "Error toggling service visibility: " . $conn->error;
            }
        }
    }
}

// Get service for editing
$edit_service = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_service = getService($_GET['edit']);
}

// Get all services for display (including hidden ones for admin)
$services = getServices(true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Life International - Services Management</title>
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
        .service-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .service-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .service-title {
            color: #1a3c6e;
            margin-top: 0;
            font-size: 1.4em;
        }
        .service-time {
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .service-description {
            margin-top: 10px;
        }
        .service-status {
            color: #dc3545;
            font-weight: bold;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .service-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">New Life International - Services Admin</div>
                <ul class="nav-links">
                    <!-- <li><a href="admin.php">Manage Sermons</a></li>
                    <li><a href="events.php">Manage Events</a></li> -->
                    <li><a href="dash.php">Back to Dashboard</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="admin-panel">
        <div class="container">
            <h2><?php echo $edit_service ? 'Edit Service' : 'Add New Service'; ?></h2>
            
            <?php if (isset($message)): ?>
                <div class="success-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="admin-form">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $edit_service ? 'update' : 'add'; ?>">
                    <?php if ($edit_service): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_service['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Service Title:</label>
                        <input type="text" id="title" name="title" value="<?php echo $edit_service ? htmlspecialchars($edit_service['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_time">Service Time:</label>
                        <input type="text" id="service_time" name="service_time" value="<?php echo $edit_service ? htmlspecialchars($edit_service['service_time']) : ''; ?>" required placeholder="e.g., 9:00 AM or Wednesday 7:00 PM">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" required><?php echo $edit_service ? htmlspecialchars($edit_service['description']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="admin-button"><?php echo $edit_service ? 'Update Service' : 'Add Service'; ?></button>
                    <?php if ($edit_service): ?>
                        <a href="services.php" class="admin-button" style="background-color: #6c757d;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <h2>Manage Existing Services</h2>
            
            <?php if (empty($services)): ?>
                <p>No services available.</p>
            <?php else: ?>
                <div class="service-container">
                    <?php foreach ($services as $service): ?>
                        <div class="service-card">
                            <h3 class="service-title"><?php echo htmlspecialchars($service['title']); ?></h3>
                            <?php if ($service['is_hidden']): ?>
                                <p class="service-status">Hidden</p>
                            <?php endif; ?>
                            <p class="service-time"><?php echo htmlspecialchars($service['service_time']); ?></p>
                            <p class="service-description"><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                            
                            <div class="admin-actions">
                                <a href="?edit=<?php echo $service['id']; ?>" class="admin-button edit-button">Edit</a>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="archive">
                                    <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                    <button type="submit" class="admin-button archive-button" onclick="return confirm('Are you sure you want to archive this service?');">Archive</button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_visibility">
                                    <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                    <input type="hidden" name="is_hidden" value="<?php echo $service['is_hidden']; ?>">
                                    <button type="submit" class="admin-button hide-button"><?php echo $service['is_hidden'] ? 'Unhide' : 'Hide'; ?></button>
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