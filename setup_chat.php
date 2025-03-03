<?php
require_once 'config/config.php';

// Read the SQL file
$sql = file_get_contents('sql/chat_messages.sql');

// Execute the SQL commands
if ($conn->multi_query($sql)) {
    echo "Chat messages table created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// Close the connection
$conn->close();
?> 