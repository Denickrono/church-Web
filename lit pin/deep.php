<?php
// Database connection and sermon management functions
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
    
    $stmt = $conn->prepare("INSERT INTO sermons (title, sermon_date, speaker, scripture, description, audio_file) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $title, $date, $speaker, $scripture, $description, $audio_file);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Function to get all sermons
function getSermons() {
    global $conn;
    $sermons = array();
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
            
            $audio_file = null;
            if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
                $target_dir = "uploads/";
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
                $target_dir = "uploads/";
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
    <title>New Life International</title>
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
        .hero {
            background-image: url('/api/placeholder/1200/400');
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .hero-content {
            position: relative;
            z-index: 1;
            padding: 0 20px;
        }
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #c0392b;
        }
        section {
            padding: 4rem 0;
        }
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        .section-title h2 {
            font-size: 2rem;
            color: #2c3e50;
        }
        .services {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .service-card {
            flex-basis: calc(33.33% - 20px);
            background-color: #f9f9f9;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 5px;
            text-align: center;
        }
        .service-time {
            font-weight: bold;
            font-size: 1.2rem;
            color: #e74c3c;
        }
        .events {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .event-card {
            flex-basis: calc(50% - 15px);
            margin-bottom: 2rem;
            background-color: #f9f9f9;
            border-radius: 5px;
            overflow: hidden;
        }
        .event-img {
            height: 200px;
            background-color: #ddd;
        }
        .event-content {
            padding: 1.5rem;
        }
        .event-date {
            color: #e74c3c;
            font-weight: bold;
        }
        footer {
            background-color: #2c3e50;
            color: white;
            padding: 3rem 0;
        }
        .footer-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .footer-section {
            flex-basis: calc(33.33% - 20px);
            margin-bottom: 2rem;
        }
        .footer-section h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        .footer-section p, .footer-section a {
            color:rgb(230, 110, 11);
            margin-bottom: 0.5rem;
        }
        .footer-section a {
            display: block;
            text-decoration: none;
        }
        .social-icons a {
            display: inline-block;
            margin-right: 10px;
            color: white;
            font-size: 1.5rem;
        }
        .copyright {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 2rem;
        }
        
        /* Admin Panel Styles */
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
        .delete-button {
            background-color: #dc3545;
        }
        audio {
            width: 100%;
            margin-top: 10px;
        }
        
        /* Sermon Cards */
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

        @media (max-width: 768px) {
            .service-card, .event-card, .footer-section {
                flex-basis: 100%;
            }
            .nav-links {
                display: none;
            }
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
                <div class="logo">New Life International</div>
                <ul class="nav-links">
                    <li><a href="#about">About</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#events">Events</a></li>
                    <li><a href="#ministries">Ministries</a></li>
                    <li><a href="#sermons">Sermons</a></li>
                    <li><a href="#give">Give</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <?php if (isset($_GET['admin'])): ?>
                        <li><a href="?">Exit Admin</a></li>
                    <?php else: ?>
                        <li><a href="?admin=1">Admin</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <?php if (isset($_GET['admin'])): ?>
        <!-- Admin Add Content Section -->
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
                            <a href="?admin=1" class="admin-button" style="background-color: #6c757d;">Cancel</a>
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
                                    <a href="?admin=1&edit=<?php echo $sermon['id']; ?>" class="admin-button edit-button">Edit</a>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $sermon['id']; ?>">
                                        <button type="submit" class="admin-button delete-button" onclick="return confirm('Are you sure you want to delete this sermon?');">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <!-- Regular Website Content -->
        <section class="hero">
            <div class="hero-content">
                <h1>Welcome to New Life International Church</h1>
                <p><b>A place to belong, believe, and become<b></p>
                <a href="#services" class="btn">Join Us This Sunday</a>
            </div>
        </section>

        <section id="about">
            <div class="container">
                <div class="section-title">
                    <h2>About Us</h2>
                </div>
                <p>New Life International has been serving our
                     community for over 35 years. We are a welcoming congregation dedicated
                      to sharing God's love and spreading the message of hope and salvation
                       through Jesus Christ. Our mission is to create disciples who live out 
                       their faith in their daily lives and impact the world around them.</p>
                <p>We believe in the power of community and strive to create an environment where 
                    everyone feels welcome, regardless of where they are in their spiritual journey.
                     Our doors are open to all who seek to know God better and grow in their faith.</p>
            </div>
        </section>

        <section id="services" style="background-color: #f9f9f9;">
            <div class="container">
                <div class="section-title">
                    <h2>Worship Services</h2>
                </div>
                <div class="services">
                    <div class="service-card">
                        <h3>Sunday Morning</h3>
                        <p class="service-time">9:00 AM</p>
                        <p>Traditional Service</p>
                        <p>Nursery Available</p>
                    </div>
                    <div class="service-card">
                        <h3>Sunday Mid-Morning</h3>
                        <p class="service-time">11:00 AM</p>
                        <p>Contemporary Service</p>
                        <p>Children's Church Available</p>
                    </div>
                    <div class="service-card">
                        <h3>Wednesday Night</h3>
                        <p class="service-time">7:00 PM</p>
                        <p>Bible Study & Prayer</p>
                        <p>Youth Group Activities</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="events">
            <div class="container">
                <div class="section-title">
                    <h2>Upcoming Events</h2>
                </div>
                <div class="events">
                    <div class="event-card">
                        <div class="event-img">
                            <img src="p1.jpg" alt="Community Picnic" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="event-content">
                            <p class="event-date">May 15, 2025</p>
                            <h3>Community Picnic</h3>
                            <p>Join us for our annual community picnic! Bring your family and friends for food, games, and fellowship. Everyone is welcome!</p>
                            <a href="#" class="btn" style="margin-top: 1rem;">Learn More</a>
                        </div>
                    </div>
                    <div class="event-card">
                        <div class="event-img">
                            <img src="b1.jpg" alt="Vacation Bible School" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="event-content">
                            <p class="event-date">June 5-9, 2025</p>
                            <h3>Vacation Bible School</h3>
                            <p>Our annual VBS program for children ages 5-12. This year's theme is "Ocean Adventure: Diving Deep into God's Love".</p>
                            <a href="#" class="btn" style="margin-top: 1rem;">Register Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="ministries" style="background-color: #f9f9f9;">
            <div class="container">
                <div class="section-title">
                    <h2>Our Ministries</h2>
                </div>
                <div class="services">
                    <div class="service-card">
                        <h3>Children's Ministry</h3>
                        <p>Nurturing the faith of our youngest members through age-appropriate Bible lessons, activities, and worship.</p>
                    </div>
                    <div class="service-card">
                        <h3>Youth Ministry</h3>
                        <p>Guiding teenagers in their spiritual journey through fellowship, Bible study, and service opportunities.</p>
                    </div>
                    <div class="service-card">
                        <h3>Adult Small Groups</h3>
                        <p>Building deeper connections and spiritual growth through small group Bible studies and fellowship.</p>
                    </div>
                    <div class="service-card">
                        <h3>Mission Outreach</h3>
                        <p>Serving our local community and supporting global missions to share God's love around the world.</p>
                    </div>
                    <div class="service-card">
                        <h3>Music Ministry</h3>
                        <p>Enhancing our worship experience through choirs, praise teams, and instrumental ensembles.</p>
                    </div>
                    <div class="service-card">
                        <h3>Prayer Ministry</h3>
                        <p>Dedicated to lifting up the needs of our church, community, and world in prayer.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="sermons">
            <div class="container">
                <div class="section-title">
                    <h2>Recent Sermons</h2>
                </div>
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
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="#" class="btn">View All Sermons</a>
                </div>
            </div>
        </section>

        <section id="give" style="background-color: #f9f9f9;">
            <div class="container">
                <div class="section-title">
                    <h2>Giving</h2>
                </div>
                <p style="text-align: center; max-width: 800px; margin: 0 auto; margin-bottom: 2rem;">Your generosity helps us continue our mission of spreading God's love in our community and beyond. 
                    Thank you for your faithful support of our church's ministries.</p>
                <div style="text-align: center;">
                    <a href="#" class="btn">Give Online</a>
                </div>
            </div>
        </section>

        <section id="contact">
            <div class="container">
                <div class="section-title">
                    <h2>Contact Us</h2>
                </div>
                <div class="footer-content" style="margin-bottom: 2rem;">
                    <div class="footer-section">
                        <h3>Address</h3>
                        <p>123 Faith Avenue</p>
                        <p>Hometown, ST 12345</p>
                    </div>
                    <div class="footer-section">
                        <h3>Contact Information</h3>
                        <p>Phone: (555) 123-4567</p>
                        <p>Email: info@gracechurch.org</p>
                    </div>
                    <div class="footer-section">
                        <h3>Office Hours</h3>
                        <p>Monday - Friday: 9:00 AM - 4:00 PM</p>
                        <p>Saturday - Sunday: Closed</p>
                    </div>
                </div>
                <div style="text-align: center;">
                    <a href="#" class="btn">Send Us a Message</a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>New Life International</h3>
                    <p>A place to belong, believe, and become</p>
                    <div class="social-icons">
                        <p>follow us on<p>
                        <a href="#" class="text-gray-400 hover:text-gray-300"><span class="sr-only">Facebook</span><i class="fab fa-facebook-f"></i></a><br>
                        <a href="#" class="text-gray-400 hover:text-gray-300"><span class="sr-only">Instagram</span><i class="fab fa-instagram"></i></a><br>
                        <a href="#" class="text-gray-400 hover:text-gray-300"><span class="sr-only">Twitter</span><i class="fab fa-twitter"></i></a><br>
                        <a href="#" class="text-gray-400 hover:text-gray-300"><span class="sr-only">LinkedIn</span><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="#">About Us</a>
                    <a href="#">Beliefs</a>
                    <a href="#">Leadership</a>
                    <a href="#">Calendar</a>
                    <a href="#">Sermons</a>
                </div>
                <div class="footer-section">
                    <h3>Connect With Us</h3>
                    <a href="#">Become a Member</a>
                    <a href="#">Volunteer Opportunities</a>
                    <a href="#">Prayer Requests</a>
                    <a href="#">Newsletter Signup</a>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 New Life International. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>