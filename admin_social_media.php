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

// Handle form submissions for edit and delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['platform'])) {
        $platform = $_POST['platform'];
        
        if ($_POST['action'] === 'edit' && isset($_POST['link_url'])) {
            $link_url = $_POST['link_url'];
            // Validate URL
            if (filter_var($link_url, FILTER_VALIDATE_URL) || $link_url === '#') {
                $stmt = $conn->prepare("UPDATE social_media_links SET link_url = ?, updated_at = NOW() WHERE platform = ?");
                $stmt->bind_param("ss", $link_url, $platform);
                if ($stmt->execute()) {
                    $message = "Link for $platform updated successfully.";
                } else {
                    $message = "Error updating link for $platform.";
                }
                $stmt->close();
            } else {
                $message = "Invalid URL for $platform.";
            }
        } elseif ($_POST['action'] === 'delete') {
            // Reset link to default '#'
            $stmt = $conn->prepare("UPDATE social_media_links SET link_url = '#', updated_at = NOW() WHERE platform = ?");
            $stmt->bind_param("s", $platform);
            if ($stmt->execute()) {
                $message = "Link for $platform reset successfully.";
            } else {
                $message = "Error resetting link for $platform.";
            }
            $stmt->close();
        }
    }
}

// Fetch all social media links
$social_media_links = array();
$sql = "SELECT id, platform, link_url, updated_at FROM social_media_links ORDER BY platform";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $social_media_links[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Social Media Links</title>
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
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
        }
        .message {
            text-align: center;
            margin-bottom: 1rem;
            color: #e74c3c;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        th, td {
            padding: 1rem;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #2c3e50;
            color: white;
        }
        .btn {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 0.5rem;
        }
        .btn:hover {
            background-color: #c0392b;
        }
        .btn-delete {
            background-color: #7f8c8d;
        }
        .btn-delete:hover {
            background-color: #6c7a7b;
        }
        form {
            margin-bottom: 2rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        @media (max-width: 768px) {
            table, th, td {
                display: block;
                width: 100%;
            }
            th, td {
                box-sizing: border-box;
            }
            td {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Social Media Links</h1>
        
        <?php if (isset($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Platform</th>
                    <th>Link URL</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($social_media_links as $link): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($link['platform']); ?></td>
                        <td><?php echo htmlspecialchars($link['link_url']); ?></td>
                        <td><?php echo date('F j, Y, g:i a', strtotime($link['updated_at'])); ?></td>
                        <td>
                            <a href="#edit-<?php echo htmlspecialchars($link['platform']); ?>" class="btn" onclick="showEditForm('<?php echo htmlspecialchars($link['platform']); ?>', '<?php echo htmlspecialchars($link['link_url']); ?>')">Edit</a>
                            <form action="" method="POST" style="display: inline;">
                                <input type="hidden" name="platform" value="<?php echo htmlspecialchars($link['platform']); ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to reset this link to #?');">Reset</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php foreach ($social_media_links as $link): ?>
            <div id="edit-form-<?php echo htmlspecialchars($link['platform']); ?>" style="display: none;">
                <h2>Edit <?php echo htmlspecialchars($link['platform']); ?> Link</h2>
                <form action="" method="POST">
                    <input type="hidden" name="platform" value="<?php echo htmlspecialchars($link['platform']); ?>">
                    <input type="hidden" name="action" value="edit">
                    <label for="link_url_<?php echo htmlspecialchars($link['platform']); ?>">Link URL</label>
                    <input type="text" name="link_url" id="link_url_<?php echo htmlspecialchars($link['platform']); ?>" value="<?php echo htmlspecialchars($link['link_url']); ?>" required>
                    <button type="submit" class="btn">Update Link</button>
                    <button type="button" class="btn btn-delete" onclick="hideEditForm('<?php echo htmlspecialchars($link['platform']); ?>')">Cancel</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        function showEditForm(platform, url) {
            document.querySelectorAll('[id^="edit-form-"]').forEach(form => form.style.display = 'none');
            document.getElementById('edit-form-' + platform).style.display = 'block';
        }
        function hideEditForm(platform) {
            document.getElementById('edit-form-' + platform).style.display = 'none';
        }
    </script>

<?php
// Close database connection
$conn->close();
?>
</body>
</html>