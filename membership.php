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

// Include PHPMailer files manually
require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

// Use the correct namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check token to prevent duplicate submissions
    $token = $_POST['token'] ?? '';
    if (isset($_SESSION['tokens'][$token]) && $_SESSION['tokens'][$token] + 300 > time()) {
        header('Location: membership.php?error=duplicate');
        exit;
    }
    $_SESSION['tokens'][$token] = time();

    // Sanitize and validate inputs
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $message_text = $conn->real_escape_string($_POST['message'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: membership.php?error=invalid_email');
        exit;
    }

    // Insert into approvals table
    $sql = "INSERT INTO approvals (name, email, phone, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $phone, $message_text);

    if ($stmt->execute()) {
        // Send confirmation email using PHPMailer
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
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Membership Application Received - New Life International Church';
            $mail->Body = "
                <h2>Thank You for Your Application!</h2>
                <p>Dear $name,</p>
                <p>We have received your membership application at New Life International Church as of " . date('F j, Y, g:i A T') . ". Here are the details you submitted:</p>
                <ul>
                    <li><strong>Name:</strong> $name</li>
                    <li><strong>Email:</strong> $email</li>
                    <li><strong>Phone:</strong> $phone</li>
                    <li><strong>Message:</strong> " . htmlspecialchars($message_text, ENT_QUOTES, 'UTF-8') . "</li>
                </ul>
                <p>We will review your application and contact you soon with the next steps. If you have any questions, feel free to reply to this email.</p>
                <p>Best regards,<br>New Life International Church Team</p>
            ";
            $mail->AltBody = "Dear $name,\n\nWe have received your membership application at New Life International Church as of " . date('F j, Y, g:i A T') . ".\n\nDetails:\n- Name: $name\n- Email: $email\n- Phone: $phone\n- Message: $message_text\n\nWe will review your application and contact you soon.\n\nBest regards,\nNew Life International Church Team";

            $mail->send();
            // Set a session flag to indicate successful submission
            $_SESSION['submission_success'] = true;
            header('Location: membership.php?success=1');
            exit;
        } catch (Exception $e) {
            header('Location: membership.php?error=email_failed&details=' . urlencode($e->getMessage()));
            exit;
        }
    } else {
        header('Location: membership.php?error=db_error&details=' . urlencode($conn->error));
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
    <title>Become a Member - New Life International Church</title>
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
        <h1>Become a Member</h1>
        <?php
        if (isset($_GET['error'])) {
            $message = '';
            $details = urldecode($_GET['details'] ?? '');
            switch ($_GET['error']) {
                case 'duplicate':
                    $message = "Duplicate submission detected. Please try again later.";
                    break;
                case 'invalid_email':
                    $message = "Invalid email format.";
                    break;
                case 'email_failed':
                    $message = "Registration successful, but email could not be sent. Error: $details";
                    break;
                case 'db_error':
                    $message = "Registration failed. Error: $details";
                    break;
            }
            if ($message) {
                echo "<div class='message error'>$message</div>";
            }
        }
        ?>
        <div class="thank-you message success">
            <h2>Thank You!</h2>
            <p>Your registration was successful, and a confirmation email has been sent to your email address. We will review your application and contact you soon.</p>
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
                <input type="hidden" name="token" value="<?php echo uniqid(); ?>">
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="message">Message (Optional):</label>
                    <textarea id="message" name="message" rows="4"></textarea>
                </div>
                <button type="submit">Submit Application</button>
            </form>
        </div>
    </div>
</body>
</html>