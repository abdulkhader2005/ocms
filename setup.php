<?php
// Setup script for OCMS
echo "OCMS Setup Script\n";
echo "==================\n\n";

// Check if database config exists
if (!file_exists('config/database.php')) {
    echo "❌ Database configuration not found!\n";
    echo "Please create config/database.php with your database settings.\n";
    exit(1);
}

// Test database connection
require_once 'config/database.php';

if ($conn->connect_error) {
    echo "❌ Database connection failed: " . $conn->connect_error . "\n";
    echo "Please check your database configuration in config/database.php\n";
    exit(1);
}

echo "✅ Database connection successful\n";

// Check if database schema exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo "❌ Database schema not found!\n";
    echo "Please import database/schema.sql into your database.\n";
    echo "You can do this by running:\n";
    echo "mysql -u root -p < database/schema.sql\n";
    exit(1);
}

echo "✅ Database schema found\n";

// Check upload directories
$uploadDirs = ['uploads', 'uploads/content'];
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "✅ Created directory: $dir\n";
        } else {
            echo "❌ Failed to create directory: $dir\n";
        }
    } else {
        echo "✅ Directory exists: $dir\n";
    }
}

// Check default users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$userCount = $result->fetch_assoc()['count'];

if ($userCount == 0) {
    echo "❌ No users found in database\n";
    echo "Please import database/schema.sql to create default users\n";
} else {
    echo "✅ Found $userCount users in database\n";
}

echo "\n🎉 Setup completed successfully!\n";
echo "\nDefault login credentials:\n";
echo "Admin: username=admin, password=password\n";
echo "Instructor: username=instructor1, password=password\n";
echo "Student: username=student1, password=password\n";
echo "\nYou can now access the system at: http://localhost/OCMS/\n";
?>
