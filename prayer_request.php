<?php
// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'church_website';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for form handling
$errors = [];
$success_message = '';
$name = '';
$phone_number = '';
$email = '';
$prayer_request = '';
$pastor_name = '';

// Fetch pastor names for dropdown
$pastors = [];
$sql = "SELECT name FROM pastors";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pastors[] = $row['name'];
    }
    $result->free();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $name = trim($_POST['name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $prayer_request = trim($_POST['prayer_request'] ?? '');
    $pastor_name = trim($_POST['pastor_name'] ?? '');

    // Validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($prayer_request)) {
        $errors[] = "Prayer request is required.";
    }
    if (empty($pastor_name)) {
        $errors[] = "Please select a pastor.";
    } elseif (!in_array($pastor_name, $pastors)) {
        $errors[] = "Invalid pastor selected.";
    }

    // If no errors, insert data into the pastor-specific table
    if (empty($errors)) {
        $safe_table_name = 'pastor_' . preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($pastor_name));
        $sql = "INSERT INTO `$safe_table_name` (name, phone_number, email_address, prayer_request) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $phone_number, $email, $prayer_request);
            if ($stmt->execute()) {
                $success_message = "Prayer request submitted successfully! Redirecting to home page...";
                // Reset form fields
                $name = '';
                $phone_number = '';
                $email = '';
                $prayer_request = '';
                $pastor_name = '';
            } else {
                $errors[] = "Error submitting prayer request: " . $conn->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Error preparing query: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prayer Request - New Life International</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 15px;
        }
        .section-title {
            text-align: center;
            margin-bottom: 2rem;
        }
        .section-title h2 {
            font-size: 2rem;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        input, textarea, select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        .btn {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #c0392b;
        }
        .message {
            text-align: center;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 5px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo"><h1>New Life International-Prayer Request<h1></div>
               <ul class="nav-links">
                    <li><a href="index.php#prayer request">Home</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="prayer-request">
        <div class="container">
            <div class="section-title">
                <h2>Submit a Prayer Request</h2>
            </div>
            <?php if ($success_message): ?>
                <meta http-equiv="refresh" content="5;url=index.php">
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form action="prayer_request.php" method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number (optional)</label>
                    <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="prayer_request">Prayer Request</label>
                    <textarea id="prayer_request" name="prayer_request" required><?php echo htmlspecialchars($prayer_request); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="pastor_name">Select Pastor</label>
                    <select id="pastor_name" name="pastor_name" required>
                        <option value="">-- Select a Pastor --</option>
                        <?php foreach ($pastors as $pastor): ?>
                            <option value="<?php echo htmlspecialchars($pastor); ?>" <?php echo $pastor_name === $pastor ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pastor); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="text-align: center;">
                    <button type="submit" class="btn">Submit Prayer Request</button>
                </div>
            </form>
        </div>
    </section>
</body>
</html>