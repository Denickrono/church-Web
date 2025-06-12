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

// Function to get all non-hidden sermons
function getAllSermons($show_hidden = false) {
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

// Get all non-hidden sermons for display
$sermons = getAllSermons(false);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Life International - All Sermons</title>
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
        audio {
            width: 100%;
            margin-top: 10px;
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
        @media (max-width: 768px) {
            .sermon-container {
                grid-template-columns: 1fr;
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
                    <a href="index.php#sermons" class="btn">Back to Home</a>
                   
                </ul>
            </nav>
        </div>
    </header>

    <section id="all-sermons">
        <div class="container">
            <div class="section-title">
                <h2>All Sermons</h2>
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
                
            </div>
        </div>
    </section>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>