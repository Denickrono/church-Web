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

// Contact management functions
function addContact($section_title, $address, $contact_info, $office_hours) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO contacts (section_title, address, contact_info, office_hours, is_hidden) 
                          VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("ssss", $section_title, $address, $contact_info, $office_hours);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function getContacts($show_hidden = true) {
    global $conn;
    $contacts = array();
    $sql = $show_hidden ? "SELECT * FROM contacts ORDER BY created_at DESC" : "SELECT * FROM contacts WHERE is_hidden = 0 ORDER BY created_at DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $contacts[] = $row;
        }
    }
    return $contacts;
}

function updateContact($id, $section_title, $address, $contact_info, $office_hours) {
    global $conn;
    $stmt = $conn->prepare("UPDATE contacts SET section_title = ?, address = ?, contact_info = ?, office_hours = ? 
                          WHERE id = ?");
    $stmt->bind_param("ssssi", $section_title, $address, $contact_info, $office_hours, $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function archiveContact($id) {
    global $conn;
    
    // Fetch the contact to archive it
    $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contact = $result->fetch_assoc();
    $stmt->close();
    
    if ($contact) {
        // Insert into archived_contacts
        $stmt = $conn->prepare("INSERT INTO archived_contacts (section_title, address, contact_info, office_hours, is_hidden, created_at, archived_at) 
                              VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssis", 
            $contact['section_title'], 
            $contact['address'], 
            $contact['contact_info'], 
            $contact['office_hours'], 
            $contact['is_hidden'], 
            $contact['created_at']
        );
        $archive_result = $stmt->execute();
        $stmt->close();
        
        if ($archive_result) {
            // Delete from contacts
            $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $delete_result = $stmt->execute();
            $stmt->close();
            return $delete_result;
        }
        return false;
    }
    return false;
}

function getContact($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contact = $result->fetch_assoc();
    $stmt->close();
    return $contact;
}

function toggleContactVisibility($id, $is_hidden) {
    global $conn;
    $new_status = $is_hidden ? 0 : 1;
    $stmt = $conn->prepare("UPDATE contacts SET is_hidden = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Add new contact
        if ($_POST['action'] == 'add') {
            $section_title = $_POST['section_title'];
            $address = $_POST['address'];
            $contact_info = $_POST['contact_info'];
            $office_hours = $_POST['office_hours'];
            
            $result = addContact($section_title, $address, $contact_info, $office_hours);
            if ($result) {
                $message = "Contact added successfully!";
            } else {
                $error = "Error adding contact: " . $conn->error;
            }
        }
        
        // Update existing contact
        else if ($_POST['action'] == 'update') {
            $id = $_POST['id'];
            $section_title = $_POST['section_title'];
            $address = $_POST['address'];
            $contact_info = $_POST['contact_info'];
            $office_hours = $_POST['office_hours'];
            
            $result = updateContact($id, $section_title, $address, $contact_info, $office_hours);
            if ($result) {
                $message = "Contact updated successfully!";
            } else {
                $error = "Error updating contact: " . $conn->error;
            }
        }
        
        // Archive contact
        else if ($_POST['action'] == 'archive') {
            $id = $_POST['id'];
            $result = archiveContact($id);
            if ($result) {
                $message = "Contact archived successfully!";
            } else {
                $error = "Error archiving contact: " . $conn->error;
            }
        }
        
        // Toggle visibility
        else if ($_POST['action'] == 'toggle_visibility') {
            $id = $_POST['id'];
            $is_hidden = $_POST['is_hidden'];
            $result = toggleContactVisibility($id, $is_hidden);
            if ($result) {
                $message = $is_hidden ? "Contact unhidden successfully!" : "Contact hidden successfully!";
            } else {
                $error = "Error toggling contact visibility: " . $conn->error;
            }
        }
    }
}

// Get contact for editing
$edit_contact = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_contact = getContact($_GET['edit']);
}

// Get all contacts for display (including hidden ones for admin)
$contacts = getContacts(true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Life International - Contacts Management</title>
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
        .contact-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .contact-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .contact-title {
            color: #1a3c6e;
            margin-top: 0;
            font-size: 1.4em;
        }
        .contact-info {
            margin-top: 10px;
        }
        .contact-status {
            color: #dc3545;
            font-weight: bold;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">New Life International - Contacts Admin</div>
                <ul class="nav-links">
                    <!-- <li><a href="admin.php">Manage Sermons</a></li>
                    <li><a href="events.php">Manage Events</a></li>
                    <li><a href="services.php">Manage Services</a></li>
                    <li><a href="about.php">Manage About Us</a></li>
                    <li><a href="ministries.php">Manage Ministries</a></li> -->
                    <li><a href="dash.php">Back to dashboard</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="admin-panel">
        <div class="container">
            <h2><?php echo $edit_contact ? 'Edit Contact' : 'Add New Contact'; ?></h2>
            
            <?php if (isset($message)): ?>
                <div class="success-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="admin-form">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $edit_contact ? 'update' : 'add'; ?>">
                    <?php if ($edit_contact): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_contact['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="section_title">Section Title:</label>
                        <input type="text" id="section_title" name="section_title" value="<?php echo $edit_contact ? htmlspecialchars($edit_contact['section_title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea id="address" name="address"><?php echo $edit_contact ? htmlspecialchars($edit_contact['address']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_info">Contact Info (Phone/Email):</label>
                        <textarea id="contact_info" name="contact_info"><?php echo $edit_contact ? htmlspecialchars($edit_contact['contact_info']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="office_hours">Office Hours:</label>
                        <textarea id="office_hours" name="office_hours"><?php echo $edit_contact ? htmlspecialchars($edit_contact['office_hours']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="admin-button"><?php echo $edit_contact ? 'Update Contact' : 'Add Contact'; ?></button>
                    <?php if ($edit_contact): ?>
                        <a href="contacts.php" class="admin-button" style="background-color: #6c757d;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <h2>Manage Existing Contacts</h2>
            
            <?php if (empty($contacts)): ?>
                <p>No contacts available.</p>
            <?php else: ?>
                <div class="contact-container">
                    <?php foreach ($contacts as $contact): ?>
                        <div class="contact-card">
                            <h3 class="contact-title"><?php echo htmlspecialchars($contact['section_title']); ?></h3>
                            <?php if ($contact['is_hidden']): ?>
                                <p class="contact-status">Hidden</p>
                            <?php endif; ?>
                            <p class="contact-info"><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($contact['address'])); ?></p>
                            <p class="contact-info"><strong>Contact Info:</strong> <?php echo nl2br(htmlspecialchars($contact['contact_info'])); ?></p>
                            <p class="contact-info"><strong>Office Hours:</strong> <?php echo nl2br(htmlspecialchars($contact['office_hours'])); ?></p>
                            
                            <div class="admin-actions">
                                <a href="?edit=<?php echo $contact['id']; ?>" class="admin-button edit-button">Edit</a>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="archive">
                                    <input type="hidden" name="id" value="<?php echo $contact['id']; ?>">
                                    <button type="submit" class="admin-button archive-button" onclick="return confirm('Are you sure you want to archive this contact?');">Archive</button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_visibility">
                                    <input type="hidden" name="id" value="<?php echo $contact['id']; ?>">
                                    <input type="hidden" name="is_hidden" value="<?php echo $contact['is_hidden']; ?>">
                                    <button type="submit" class="admin-button hide-button"><?php echo $contact['is_hidden'] ? 'Unhide' : 'Hide'; ?></button>
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