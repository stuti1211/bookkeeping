<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookkeeping";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Hash the password
$hashed_password = password_hash("admin123", PASSWORD_DEFAULT);

// Insert admin user
$query = "INSERT INTO users (username, password, email) VALUES ('admin', ?, 'admin@example.com')";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $hashed_password);
if ($stmt->execute()) {
    echo "Admin user created successfully!";
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>
