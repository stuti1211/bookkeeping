<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Fetch company information
$query = "SELECT * FROM company_info WHERE id=1";
$result = $conn->query($query);
$settings = $result->fetch_assoc();

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $company_name = $_POST['company_name'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $terms = $_POST['terms_and_conditions'];

    $query = "UPDATE company_info SET company_name=?, address=?, contact=?, terms_and_conditions=? WHERE id=1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssss', $company_name, $address, $contact, $terms);
    if ($stmt->execute()) {
        $message = "Settings updated successfully!";
        // Fetch updated settings
        $query = "SELECT * FROM company_info WHERE id=1";
        $result = $conn->query($query);
        $settings = $result->fetch_assoc();
    } else {
        $message = "Error updating settings.";
    }
    $stmt->close();
}

// Handle cancel button
if (isset($_POST['cancel'])) {
    // Revert to original settings fetched from the database
    $query = "SELECT * FROM company_info WHERE id=1";
    $result = $conn->query($query);
    $settings = $result->fetch_assoc();
    $message = "Changes canceled!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Settings</h2>
        <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
        <form action="settings.php" method="POST">
            <label for="company_name">Company Name:</label>
            <input type="text" id="company_name" name="company_name" value="<?= htmlspecialchars($settings['company_name']) ?>" required>

            <label for="address">Address:</label>
            <textarea id="address" name="address" required><?= htmlspecialchars($settings['address']) ?></textarea>

            <label for="contact">Contact:</label>
            <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($settings['contact']) ?>" required>

            <label for="terms_and_conditions">Terms and Conditions:</label>
            <textarea id="terms_and_conditions" name="terms_and_conditions" required><?= htmlspecialchars($settings['terms_and_conditions']) ?></textarea>

            <div class="button-group">
                <button type="submit" name="save" class="save">Save</button>
                <button type="submit" name="cancel" class="cancel">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>
