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

// Fetch hero image
$hero_image = 'Uploads/hero/default_hero.jpg'; // Default image
$sql = "SELECT image_path FROM hero_image WHERE id = 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $hero_image = $row['image_path'];
}

// Fetch social media links
$social_media_links = array();
$sql = "SELECT platform, link_url FROM social_media_links";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $social_media_links[$row['platform']] = $row['link_url'];
    }
}

// Function to get latest 4 sermons
function getLatestSermons($show_hidden = true) {
    global $conn;
    $sermons = array();
    $sql = $show_hidden ? "SELECT * FROM sermons ORDER BY sermon_date DESC LIMIT 4" : "SELECT * FROM sermons WHERE is_hidden = 0 ORDER BY sermon_date DESC LIMIT 4";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $sermons[] = $row;
        }
    }
    return $sermons;
}

// Function to get events
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

// Function to get services
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

// Function to get about items
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

// Function to get ministries
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

// Function to get contacts
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

// Function to get announcements
function getAnnouncements($show_hidden = true) {
    global $conn;
    $announcements = array();
    $sql = $show_hidden ? "SELECT * FROM announcements ORDER BY created_at DESC" : "SELECT * FROM announcements WHERE is_hidden = 0 ORDER BY created_at DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
    }
    return $announcements;
}

// Get data for display
$sermons = getLatestSermons(false); // Fetch only non-hidden sermons
$events = getEvents(false); // Fetch only non-hidden events
$services = getServices(false); // Fetch only non-hidden services
$about_items = getAboutItems(false); // Fetch only non-hidden about items
$ministries = getMinistries(false); // Fetch only non-hidden ministries
$contacts = getContacts(false); // Fetch only non-hidden contacts
$announcements = getAnnouncements(false); // Fetch only non-hidden announcements
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
            background-image: url('<?php echo htmlspecialchars($hero_image); ?>');
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
        .event-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            color: rgb(230, 110, 11);
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
        .social-icons a:hover {
            color: #e74c3c;
        }
        .copyright {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 2rem;
        }
        .sermon-container, .announcement-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .sermon-card, .announcement-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .sermon-title, .announcement-title {
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
        .sermon-description, .announcement-description {
            margin-top: 10px;
        }
        audio {
            width: 100%;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            .service-card, .event-card, .footer-section {
                flex-basis: 100%;
            }
            .nav-links {
                display: none;
            }
            .sermon-container, .announcement-container {
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
                    <li><a href="#announcements">Announcements</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#events">Events</a></li>
                    <li><a href="#ministries">Ministries</a></li>
                    <li><a href="#sermons">Sermons</a></li>
                    <li><a href="#give">Give</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to New Life International Church</h1>
            <p><b>A place to belong, believe, and become</b></p>
            <a href="#services" class="btn">Join Us This Sunday</a>
        </div>
    </section>

    <section id="about">
        <div class="container">
            <div class="section-title">
                <b><h2>About Us</h2></b>
            </div>
            <?php if (empty($about_items)): ?>
                <p><b>No about content available.</b></p>
            <?php else: ?>
                <?php foreach ($about_items as $item): ?>
                    <p><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section id="announcements" style="background-color:rgb(157, 176, 179);">
        <div class="container">
            <div class="section-title">
                <h2>Announcements</h2>
            </div>
            <?php if (empty($announcements)): ?>
                <p>No announcements available.</p>
            <?php else: ?>
                <div class="announcement-container">
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-card">
                            <h3 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                            <p class="announcement-description"><?php echo nl2br(htmlspecialchars($announcement['description'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="services" style="background-color:rgb(157, 176, 179);">
        <div class="container">
            <div class="section-title">
                <h2>Worship Services</h2>
            </div>
            <?php if (empty($services)): ?>
                <p>No services available.</p>
            <?php else: ?>
                <div class="services">
                    <?php foreach ($services as $service): ?>
                        <div class="service-card">
                            <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                            <p class="service-time"><?php echo htmlspecialchars($service['service_time']); ?></p>
                            <p><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="events">
        <div class="container">
            <div class="section-title">
                <h2>Upcoming Events</h2>
            </div>
            <?php if (empty($events)): ?>
                <p>No events available.</p>
            <?php else: ?>
                <div class="events">
                    <?php foreach ($events as $event): ?>
                        <div class="event-card">
                            <div class="event-img">
                                <?php if ($event['image_file']): ?>
                                    <img src="<?php echo htmlspecialchars($event['image_file']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background-color: #ddd;"></div>
                                <?php endif; ?>
                            </div>
                            <div class="event-content">
                                <p class="event-date"><?php echo htmlspecialchars($event['event_date']); ?></p>
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                <?php if ($event['link_url']): ?>
                                    <a href="<?php echo htmlspecialchars($event['link_url']); ?>" class="btn" style="margin-top: 1rem;">
                                        <?php echo $event['link_type'] == 'register' ? 'Register Now' : 'Learn More'; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="ministries" style="background-color:rgb(157, 176, 179);">
        <div class="container">
            <div class="section-title">
                <h2>Our Ministries</h2>
            </div>
            <?php if (empty($ministries)): ?>
                <b><p>No ministries available.</p></b>
            <?php else: ?>
                <div class="services">
                    <?php foreach ($ministries as $ministry): ?>
                        <div class="service-card">
                            <h3><?php echo htmlspecialchars($ministry['title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($ministry['description'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
                <a href="sermons.php" class="btn">View All Sermons</a>
            </div>
        </div>
    </section>

    <section id="give" style="background-color:rgb(157, 176, 179);">
        <div class="container">
            <div class="section-title">
                <h2>Giving</h2>
            </div>
            <p style="text-align: center; max-width: 800px; margin: 0 auto; margin-bottom: 2rem;">Your generosity helps us continue our mission of spreading God's love in our community and beyond. Thank you for your faithful support of our church's ministries.</p>
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
            <?php if (empty($contacts)): ?>
                <p>No contact information available.</p>
            <?php else: ?>
                <div class="footer-content" style="margin-bottom: 2rem;">
                    <?php foreach ($contacts as $contact): ?>
                        <div class="footer-section">
                            <h3><?php echo htmlspecialchars($contact['section_title']); ?></h3>
                            <?php if ($contact['address']): ?>
                                <p><?php echo nl2br(htmlspecialchars($contact['address'])); ?></p>
                            <?php endif; ?>
                            <?php if ($contact['contact_info']): ?>
                                <p><?php echo nl2br(htmlspecialchars($contact['contact_info'])); ?></p>
                            <?php endif; ?>
                            <?php if ($contact['office_hours']): ?>
                                <p><?php echo nl2br(htmlspecialchars($contact['office_hours'])); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div style="text-align: center;">
                <a href="contact_us.php" class="btn">Send Us a Message</a>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>New Life International</h3>
                    <p>A place to belong, believe, and become</p>
                    <div class="social-icons">
                        <p>Follow us on</p>
                        <a href="<?php echo isset($social_media_links['Facebook']) ? htmlspecialchars($social_media_links['Facebook']) : '#'; ?>" class="text-gray-400 hover:text-gray-300">
                            <span class="sr-only">Facebook</span><i class="fab fa-facebook-f"></i>
                        </a><br>
                        <a href="<?php echo isset($social_media_links['Instagram']) ? htmlspecialchars($social_media_links['Instagram']) : '#'; ?>" class="text-gray-400 hover:text-gray-300">
                            <span class="sr-only">Instagram</span><i class="fab fa-instagram"></i>
                        </a><br>
                        <a href="<?php echo isset($social_media_links['Twitter']) ? htmlspecialchars($social_media_links['Twitter']) : '#'; ?>" class="text-gray-400 hover:text-gray-300">
                            <span class="sr-only">Twitter</span><i class="fab fa-twitter"></i>
                        </a><br>
                        <a href="<?php echo isset($social_media_links['YouTube']) ? htmlspecialchars($social_media_links['YouTube']) : '#'; ?>" class="text-gray-400 hover:text-gray-300">
                            <span class="sr-only">YouTube</span><i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="#">Beliefs</a>
                    <a href="#">Leadership</a>
                    <a href="#">Calendar</a>
                </div>
                <div class="footer-section">
                    <h3>Connect With Us</h3>
                    <a href="membership.php">Become a Member</a>
                    <a href="volunteer_opportunities.php">Volunteer Opportunities</a>
                    <a href="prayer_request.php">Prayer Requests</a>
                    <a href="news_letter.php">Newsletter Signup</a>
                </div>
            </div>
            <div class="copyright">
                <p>© 2025 New Life International. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>