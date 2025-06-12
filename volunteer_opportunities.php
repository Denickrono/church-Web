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
$email = '';
$phone_number = '';
$role = '';

// Fetch volunteer opportunities for dropdown (exclude hidden roles)
$opportunities = [];
$sql = "SELECT position FROM volunteer_opportunities WHERE is_hidden = 0";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $opportunities[] = $row['position'];
    }
    $result->free();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $role = trim($_POST['role'] ?? '');

    // Validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($role)) {
        $errors[] = "Please select a volunteer role.";
    } elseif (!in_array($role, $opportunities)) {
        $errors[] = "Invalid role selected.";
    }

    // If no errors, insert data into volunteer_submissions table
    if (empty($errors)) {
        $sql = "INSERT INTO volunteer_submissions (name, email, phone_number, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $phone_number, $role);
            if ($stmt->execute()) {
                $success_message = "Volunteer application submitted successfully! Redirecting to home page...";
                // Reset form fields
                $name = '';
                $email = '';
                $phone_number = '';
                $role = '';
            } else {
                $errors[] = "Error submitting application: " . $conn->error;
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
    <title>Volunteer Opportunities - New Life International</title>
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
        input, select {
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
            background-color: #FF4500;
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
                <div class="logo"><h1>New Life International - Volunteer Opportunities</h1></div>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="volunteer-opportunities">
        <div class="container">
            <div class="section-title">
                <h2>Volunteer Opportunities</h2>
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
            <form action="volunteer_opportunities.php" method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number (optional)</label>
                    <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>">
                </div>
                <div class="form-group">
                    <label for="role">Volunteer Role</label>
                    <select id="role" name="role" required>
                        <option value="">-- Select a Role --</option>
                        <?php foreach ($opportunities as $opportunity): ?>
                            <option value="<?php echo htmlspecialchars($opportunity); ?>" <?php echo $role === $opportunity ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($opportunity); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="text-align: center;">
                    <button type="submit" class="btn">Submit Application</button>
                </div>
            </form>
        </div>
    </section>
</body>
</html>
