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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $name = trim($_POST['name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    $sql = "SELECT id FROM pastors WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email is already registered.";
    }
    $stmt->close();

    // If no errors, insert data into the pastors table and create pastor-specific table
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO pastors (name, phone_number, email, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $phone_number, $email, $hashed_password);
        
        if ($stmt->execute()) {
            // Create a new table named after the pastor (sanitized to prevent SQL injection)
            $safe_table_name = 'pastor_' . preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($name));
            $create_table_sql = "CREATE TABLE IF NOT EXISTS `$safe_table_name` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone_number VARCHAR(50),
                email_address VARCHAR(255),
                prayer_request TEXT,
               submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                status ENUM('pending', 'responded') DEFAULT 'pending'
            )";
            if ($conn->query($create_table_sql) === TRUE) {
                $success_message = "Registration successful! Table '$safe_table_name' created. You can now log in.";
                // Reset form fields
                $name = '';
                $phone_number = '';
                $email = '';
            } else {
                $errors[] = "Error creating pastor table: " . $conn->error;
            }
        } else {
            $errors[] = "Error registering pastor: " . $conn->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pastor Sign-Up - New Life International</title>
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
        input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
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
                <div class="logo"><h1>New Life International-Pastors Registration Page<h1></div>
                <!-- <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="index.php#services">Services</a></li>
                    <li><a href="index.php#events">Events</a></li>
                    <li><a href="index.php#ministries">Ministries</a></li>
                    <li><a href="index.php#sermons">Sermons</a></li>
                    <li><a href="index.php#give">Give</a></li>
                    <li><a href="index.php#contact">Contact</a></li> -->
                </ul>
            </nav>
        </div>
    </header>

    <section id="pastor-signup">
        <div class="container">
            <div class="section-title">
                <h2>Pastor Sign-Up</h2>
            </div>
            <?php if ($success_message): ?>
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
            <form action="pastor_signup.php" method="POST">
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
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div style="text-align: center;">
                    <button type="submit" class="btn">Sign Up</button>
                </div>
            </form>
            <div style="text-align: center; margin-top: 1rem;">
                <p>Already registered? <a href="pastor_login.php">Log in here</a>.</p>
            </div>
        </div>
    </section>
</body>
</html>