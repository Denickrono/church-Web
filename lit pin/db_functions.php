<?php
// Database connection details
$db_host = 'localhost';
$db_user = 'root'; // Change as needed for your environment
$db_pass = '';     // Change as needed for your environment
$db_name = 'church_website';

// Connect to database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to add a new sermon
function addSermon($title, $date, $speaker, $scripture, $description, $audio_file = null) {
    global $conn;
    
    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO sermons (title, sermon_date, speaker, scripture, description, audio_file) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    
    // Bind parameters
    $stmt->bind_param("ssssss", $title, $date, $speaker, $scripture, $description, $audio_file);
    
    // Execute statement
    $result = $stmt->execute();
    
    // Close statement
    $stmt->close();
    
    return $result;
}

// Function to get all sermons
function getSermons() {
    global $conn;
    
    $sermons = array();
    
    // Query to get all sermons ordered by date (newest first)
    $sql = "SELECT * FROM sermons ORDER BY sermon_date DESC";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $sermons[] = $row;
        }
    }
    
    return $sermons;
}

// Function to update a sermon
function updateSermon($id, $title, $date, $speaker, $scripture, $description, $audio_file = null) {
    global $conn;
    
    // If no new audio file, don't update that field
    if ($audio_file === null) {
        $stmt = $conn->prepare("UPDATE sermons SET title = ?, sermon_date = ?, speaker = ?, scripture = ?, description = ? 
                              WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $date, $speaker, $scripture, $description, $id);
    } else {
        $stmt = $conn->prepare("UPDATE sermons SET title = ?, sermon_date = ?, speaker = ?, scripture = ?, description = ?, audio_file = ? 
                              WHERE id = ?");
        $stmt->bind_param("ssssssi", $title, $date, $speaker, $scripture, $description, $audio_file, $id);
    }
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Function to delete a sermon
function deleteSermon($id) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM sermons WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Function to get a single sermon
function getSermon($id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM sermons WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $sermon = $result->fetch_assoc();
    
    $stmt->close();
    
    return $sermon;
}

// Function to check login credentials
function checkLogin($username, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify password - assuming passwords are stored as hashes
        if (password_verify($password, $user['passwor