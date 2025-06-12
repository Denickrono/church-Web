<?php
session_start();

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'church_website';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $message_text = $conn->real_escape_string($_POST['message'] ?? '');

    // At least one of name, email, or phone must be provided, message is required
    if (empty($name) && empty($email) && empty($phone)) {
        header('Location: contact_us.php?error=missing_contact');
        exit;
    }
    if (empty($message_text)) {
        header('Location: contact_us.php?error=missing_message');
        exit;
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: contact_us.php?error=invalid_email');
        exit;
    }

    // Insert into database
    $sql = "INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $phone, $message_text);

    if ($stmt->execute()) {
        // Set a session flag to indicate successful submission
        $_SESSION['submission_success'] = true;
        header('Location: contact_us.php?success=1');
        exit;
    } else {
        header('Location: contact_us.php?error=db_error&details=' . urlencode($conn->error));
        exit;
    }

    $stmt->close();
}

$conn->close();
date_default_timezone_set('Africa/Nairobi'); // Set timezone to EAT
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - New Life International Church</title>
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
        input, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #2b6cb0;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #4a90e2;
        }
        .message {
            text-align: center;
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: #e6ffe6;
            color: green;
        }
        .error {
            background-color: #ffe6e6;
            color: red;
        }
        .thank-you {
            display: <?php echo isset($_GET['success']) && $_GET['success'] == 1 ? 'block' : 'none'; ?>;
        }
        .hidden-form {
            display: <?php echo isset($_GET['success']) && $_GET['success'] == 1 ? 'none' : 'block'; ?>;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Contact Us</h1>
        <?php
        if (isset($_GET['error'])) {
            $message = '';
            $details = urldecode($_GET['details'] ?? '');
            switch ($_GET['error']) {
                case 'missing_contact':
                    $message = "Please provide at least one of: name, email, or phone number.";
                    break;
                case 'missing_message':
                    $message = "Please enter a message.";
                    break;
                case 'invalid_email':
                    $message = "Invalid email format.";
                    break;
                case 'db_error':
                    $message = "Failed to send message. Error: $details";
                    break;
            }
            if ($message) {
                echo "<div class='message error'>$message</div>";
            }
        }
        ?>
        <div class="thank-you message success">
            <h2>Thank You!</h2>
            <p>Your message has been received. We will get back to you soon.</p>
            <p>Redirecting to home page in 5 seconds...</p>
            <?php
            // Check if the success flag is set and trigger redirect only then
            if (isset($_GET['success']) && $_GET['success'] == 1 && isset($_SESSION['submission_success']) && $_SESSION['submission_success'] === true) {
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "index.php";
                    }, 5000);
                </script>';
                // Clear the session flag after triggering the redirect
                unset($_SESSION['submission_success']);
            }
            ?>
        </div>
        <div class="hidden-form">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name (Optional):</label>
                    <input type="text" id="name" name="name">
                </div>
                <div class="form-group">
                    <label for="email">Email (Optional):</label>
                    <input type="email" id="email" name="email">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number (Optional):</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="4" required></textarea>
                </div>
                <button type="submit">Submit Message</button>
            </form>
        </div>
    </div>
</body>
</html>