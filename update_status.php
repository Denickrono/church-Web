<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'church_website';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $status = $_POST['status'];

    // If status is "Deleted", remove from approvals table
    if ($status === 'Deleted') {
        $delete_sql = "DELETE FROM approvals WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $id);
        $delete_stmt->execute();
        $delete_stmt->close();
    } else {
        // Update status in approvals table
        $sql = "UPDATE approvals SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();

        // If approved, copy to memberships table and delete from approvals
        if ($status === 'Approved') {
            // Fetch the record from approvals
            $select_sql = "SELECT name, email, phone, message, created_at FROM approvals WHERE id = ?";
            $select_stmt = $conn->prepare($select_sql);
            $select_stmt->bind_param("i", $id);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                // Insert into memberships table
                $insert_sql = "INSERT INTO memberships (name, email, phone, message, status, created_at, is_hidden) 
                               VALUES (?, ?, ?, ?, 'Approved', ?, 0)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("sssss", $row['name'], $row['email'], $row['phone'], $row['message'], $row['created_at']);
                $insert_stmt->execute();
                $insert_stmt->close();

                // Delete from approvals table
                $delete_sql = "DELETE FROM approvals WHERE id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("i", $id);
                $delete_stmt->execute();
                $delete_stmt->close();
            }
            $select_stmt->close();
        }
        $stmt->close();
    }
}

$conn->close();
?>