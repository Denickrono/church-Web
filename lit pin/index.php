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

// Function to get all sermons
function getSermons($limit = null) {
    global $conn;
    
    $sermons = array();
    
    // Query to get sermons ordered by date (newest first)
    $sql = "SELECT * FROM sermons ORDER BY sermon_date DESC";
    
    // Add limit if specified
    if ($limit) {
        $sql .= " LIMIT " . $limit;
    }
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $sermons[] = $row;
        }
    }
    
    return $sermons;
}

// Get recent sermons for display (limit to 2)
$recent_sermons = getSermons(2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Life International</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        .sermon-card {
            flex-basis: calc(50% - 15px);
            margin-bottom: 2rem;
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 1.5rem;
        }
        .sermon-title {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .sermon-date {
            color: #e74c3c;
            font-weight: bold;
            font-style: italic;
            margin-bottom: 0.5rem;
        }
        .sermon-speaker {
            font-weight: bold;
            margin-bottom: 0.5rem;
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
            color: #bdc3c7;
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
        .social-icons p {
            margin-bottom: 10px;
        }
        audio {
            width: 100%;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            .service-card, .event-card, .footer-section, .sermon-card {
                flex-basis: 100%;
            }
            .nav-links {
                display: none;
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
                    <li><a href="admin.php">Admin</a></li>
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
                        <img src="/api/placeholder/600/300" alt="Community Picnic" style="width: 100%; height: 100%; object-fit: cover;">
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
                        <img src="/api/placeholder/600/300" alt="Vacation Bible School" style="width: 100%; height: 100%; object-fit: cover;">
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
            <div class="events">
                <?php if (empty($recent_sermons)): ?>
                    <p style="text-align: center; width: 100%;">No sermons available at this time.</p>
                <?php else: ?>
                    <?php foreach($recent_sermons as $sermon): ?>
                        <div class="sermon-card">
                            <h3 class="sermon-title"><?php echo htmlspecialchars($sermon['title']); ?></h3>
                            <p class="sermon-date"><?php echo date('F j, Y', strtotime($sermon['sermon_date'])); ?></p>
                            <p class="sermon-speaker"><strong><?php echo htmlspecialchars($sermon['speaker']); ?></strong></p>
                            <p><strong>Scripture:</strong> <?php echo htmlspecialchars($sermon['scripture']); ?></p>
                            <p><?php echo nl2br(htmlspecialchars($sermon['description'])); ?></p>
                            
                            <?php if ($sermon['audio_file']): ?>
                                <audio controls>
                                    <source src="<?php echo htmlspecialchars($sermon['audio_file']); ?>" type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="sermons.php" class="btn">View All Sermons</a>
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
                    <p>Email: info@newlifeinternational.org</p>
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

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>New Life International</h3>
                    <p>A place to belong, believe, and become</p>
                    <div class="social-icons">
                        <p>Follow us on</p>
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="#about">About Us</a>
                    <a href="#">Beliefs</a>
                    <a href="#">Leadership</a>
                    <a href="#">Calendar</a>
                    <a href="sermons.php">Sermons</a>
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
                <p>&copy; <?php echo date('Y'); ?> New Life International. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>