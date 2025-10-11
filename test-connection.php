<?php
// Simple test to check database connection
require_once 'config/database.php';

echo "Database connection test:\n";
echo "Connection status: " . ($conn->connect_error ? "Failed" : "Success") . "\n";

if ($conn->connect_error) {
    echo "Error: " . $conn->connect_error . "\n";
} else {
    echo "Connected to database: " . $conn->database . "\n";
    
    // Test a simple query
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Users in database: " . $row['count'] . "\n";
    } else {
        echo "Query failed: " . $conn->error . "\n";
    }
}

$conn->close();
?>
