<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'church_website';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch current hero image
$current_image = 'uploads/hero/default_hero.jpg';
$sql = "SELECT image_path FROM hero_image WHERE id = 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $current_image = $row['image_path'];
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['hero_image'])) {
    $upload_dir = 'uploads/hero/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $file = $_FILES['hero_image'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'hero_' . time() . '.' . $ext;
            $destination = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Delete old image if it exists and is not the default
                if ($current_image !== 'uploads/hero/default_hero.jpg' && file_exists($current_image)) {
                    unlink($current_image);
                }

                // Update or insert into database
                $sql = "INSERT INTO hero_image (id, image_path) VALUES (1, ?) ON DUPLICATE KEY UPDATE image_path = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $destination, $destination);
                if ($stmt->execute()) {
                    $message = "Hero image updated successfully!";
                    $current_image = $destination;
                } else {
                    $message = "Failed to update database: " . $conn->error;
                }
                $stmt->close();
            } else {
                $message = "Failed to move uploaded file.";
            }
        } else {
            $message = "Invalid file type or size. Allowed: JPEG, PNG, GIF (max 5MB).";
        }
    } else {
        $message = "Upload error: " . $file['error'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hero Image - New Life International</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e6f0fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2b6cb0;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #2b6cb0;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #4a90e2;
        }
        .message {
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            background-color: #e6ffe6;
            color: green;
        }
        .error {
            background-color: #ffe6e6;
            color: red;
        }
        .current-image {
            text-align: center;
            margin-bottom: 20px;
        }
        .current-image img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Hero Image</h1>
        <h1><a href="dash.php"> Back to Dashboard</a></h1>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <div class="current-image">
            <h3>Current Hero Image</h3>
            <img src="<?php echo htmlspecialchars($current_image); ?>" alt="Current Hero Image">
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="hero_image">Select New Hero Image (JPEG, PNG, GIF, max 5MB):</label>
                <input type="file" id="hero_image" name="hero_image" accept="image/jpeg,image/png,image/gif" required>
            </div>
            <button type="submit">Update Image</button>
        </form>
    </div>
</body>
</html>