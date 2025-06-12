<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['admin_name'])) {
    header('Location: login.php');
    exit;
}

// Set timezone to EAT
date_default_timezone_set('Africa/Nairobi');

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'church_website';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include PHPMailer files manually
require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

// Use the correct namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check token to prevent duplicate submissions
    $token = $_POST['token'] ?? '';
    if (isset($_SESSION['tokens'][$token]) && $_SESSION['tokens'][$token] + 300 > time()) {
        header('Location: admin_send_newsletter.php?error=duplicate');
        exit;
    }
    $_SESSION['tokens'][$token] = time();

    // Sanitize and validate inputs
    $subject = $conn->real_escape_string($_POST['subject']);
    $message_text = $conn->real_escape_string($_POST['message']);
    $admin_name = $conn->real_escape_string($_SESSION['admin_name']);

    if (empty($subject) || empty($message_text)) {
        header('Location: admin_send_newsletter.php?error=missing_fields');
        exit;
    }

    // Fetch subscriber emails
    $result = $conn->query("SELECT email, name FROM newsletter_subscribers");
    $subscribers = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $subscribers[] = $row;
        }
        $result->free();
    }

    if (empty($subscribers)) {
        header('Location: admin_send_newsletter.php?error=no_subscribers');
        exit;
    }

    // Send newsletter using PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'denickkipchirchir@gmail.com';
        $mail->Password = 'nooo uxwt cnbx whvv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('denickkipchirchir@gmail.com', 'New Life International Church');
        $mail->addReplyTo('denickkipchirchir@gmail.com', 'New Life International Church');

        // Add all subscribers as BCC
        foreach ($subscribers as $subscriber) {
            $mail->addBCC($subscriber['email'], $subscriber['name']);
        }

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = "
            <h2>$subject</h2>
            <p>Dear Subscriber,</p>
            <p>" . nl2br(htmlspecialchars($message_text, ENT_QUOTES, 'UTF-8')) . "</p>
            <p>We hope you find this newsletter informative. If you have any questions, feel free to reply to this email.</p>
            <p>Best regards,<br>New Life International Church Team</p>
            <hr>
            <p style='font-size: 12px;'>You received this email because you subscribed to our newsletter at New Life International Church.</p>
        ";
        $mail->AltBody = "Dear Subscriber,\n\n$subject\n\n$message_text\n\nWe hope you find this newsletter informative. If you have any questions, feel free to reply to this email.\n\nBest regards,\nNew Life International Church Team\n\nYou received this email because you subscribed to our newsletter.";

        $mail->send();

        // Save newsletter details to database
        $recipient_count = count($subscribers);
        $sql = "INSERT INTO newsletter_messages (subject, message, recipient_count, admin_name, sent_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssis", $subject, $message_text, $recipient_count, $admin_name);

        if ($stmt->execute()) {
            $_SESSION['submission_success'] = true;
            header('Location: admin_send_newsletter.php?success=1&count=' . $recipient_count . '&admin=' . urlencode($admin_name));
            exit;
        } else {
            header('Location: admin_send_newsletter.php?error=db_error&details=' . urlencode($conn->error));
            exit;
        }
        $stmt->close();
    } catch (Exception $e) {
        header('Location: admin_send_newsletter.php?error=email_failed&details=' . urlencode($e->getMessage()));
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Send Newsletter - New Life International Church</title>
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
        textarea {
            height: 200px;
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
            border: 1px solid green;
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
        <h1>Send Newsletter</h1>
        <?php
        if (isset($_GET['error'])) {
            $message = '';
            $details = urldecode($_GET['details'] ?? '');
            switch ($_GET['error']) {
                case 'duplicate':
                    $message = "Duplicate submission detected. Please try again later.";
                    break;
                case 'missing_fields':
                    $message = "Please fill in both subject and message.";
                    break;
                case 'no_subscribers':
                    $message = "No subscribers found to send the newsletter.";
                    break;
                case 'email_failed':
                    $message = "Failed to send newsletter. Error: $details";
                    break;
                case 'db_error':
                    $message = "Failed to save newsletter details. Error: $details";
                    break;
            }
            if ($message) {
                echo "<div class='message error'>$message</div>";
            }
        }
        ?>
        <div class="thank-you message success">
            <h2>Newsletter Sent!</h2>
            <p>Newsletter was successfully sent to <?php echo isset($_GET['count']) ? htmlspecialchars($_GET['count']) : 0; ?> subscribers by <?php echo isset($_GET['admin']) ? htmlspecialchars(urldecode($_GET['admin'])) : 'Admin'; ?>.</p>
            <p>Redirecting to home page in 5 seconds...</p>
            <?php
            if (isset($_GET['success']) && $_GET['success'] == 1 && isset($_SESSION['submission_success']) && $_SESSION['submission_success'] === true) {
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "dash.php";
                    }, 5000);
                </script>';
                unset($_SESSION['submission_success']);
            }
            ?>
        </div>
        <div class="hidden-form">
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo uniqid(); ?>">
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="8" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                </div>
                <button type="submit">Send Newsletter</button>
            </form>
        </div>
    </div>
</body>
</html>