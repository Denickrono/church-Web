<?php
// Include the database connection and functions
//require_once('db_functions.php');

// Get all sermons for display
// $sermons = getSermons();
// ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Life International - Sermons</title>
    <!-- Include the same styles from index.php -->
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">New Life International</div>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="index.php#services">Services</a></li>
                    <li><a href="index.php#events">Events</a></li>
                    <li><a href="index.php#ministries">Ministries</a></li>
                    <li><a href="sermons.php" class="active">Sermons</a></li>
                    <li><a href="index.php#give">Give</a></li>
                    <li><a href="index.php#contact">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="sermon-banner" style="background-color: #2c3e50; color: white; padding: 60px 0; text-align: center;">
        <div class="container">
            <h1>Our Sermons</h1>
            <p>Listen and be inspired by God's word</p>
        </div>
    </section>

    <section>
        <div class="container">
            <div class="section-title">
                <h2>Recent Sermons</h2>
            </div>

            <?php if (empty($sermons)): ?>
                <p style="text-align: center;">No sermons available at this time. Please check back soon.</p>
            <?php else: ?>
                <div class="sermon-container">
                    <?php foreach ($sermons as $sermon): ?>
                        <div class="sermon-card">
                            <h3 class="sermon-title"><?php echo htmlspecialchars($sermon['title']); ?></h3>
                            <p class="sermon-date"><?php echo date('F j, Y', strtotime($sermon['sermon_date'])); ?></p>
                            <p class="sermon-speaker"><?php echo htmlspecialchars($sermon['speaker']); ?></p>
                            <p class="sermon-scripture"><strong>Scripture:</strong> <?php echo htmlspecialchars($sermon['scripture']); ?></p>
                            <p class="sermon-description"><?php echo nl2br(htmlspecialchars($sermon['description'])); ?></p>
                            
                            <?php if (!empty($sermon['audio_file'])): ?>
                                <audio controls>
                                    <source src="<?php echo htmlspecialchars($sermon['audio_file']); ?>" type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>New Life International</h3>
                    <p>A place to belong, believe, and become</p>
                    <div class="social-icons">
                        <p>Follow us on:</p>
                        <a href="#"><i class="fab fa-facebook-f"></i> Facebook</a>
                        <a href="#"><i class="fab fa-instagram"></i> Instagram</a>
                        <a href="#"><i class="fab fa-twitter"></i> Twitter</a>
                        <a href="#"><i class="fab fa-youtube"></i> YouTube</a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="index.php#about">About Us</a>
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
                    <a href="admin.php">Staff Login</a>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 New Life International. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>