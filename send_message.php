<?php
session_start();

// Redirect to login page if user is not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$mysqli = require __DIR__ . "/Database.php";

// Check if recipient (recipient_id) is passed in the URL
if (!isset($_GET['recipient_id'])) {
    die("No recipient specified.");
}

$recipient_id = $_GET['recipient_id'];

// Fetch recipient's details
$sql = "
    SELECT id, name
    FROM user
    WHERE id = ?";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("SQL error: " . $mysqli->error);
}

$stmt->bind_param("i", $recipient_id);
$stmt->execute();
$result = $stmt->get_result();
$recipient = $result->fetch_assoc();
$stmt->close();

if (!$recipient) {
    die("Recipient not found.");
}

// Handle message sending
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = $_POST["message"];

    // Insert the message into the database
    $sql = "
        INSERT INTO messages (from_user_id, recipient_id, message)
        VALUES (?, ?, ?)";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("SQL error: " . $mysqli->error);
    }

    $stmt->bind_param("iis", $_SESSION["user_id"], $recipient_id, $message);
    $stmt->execute();
    $stmt->close();

    echo "Message sent to " . htmlspecialchars($recipient["name"]);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message</title>
    <link rel="stylesheet" href="send_message.css">
</head>
<body>
    <header>
        <h1>Send Message to <?= htmlspecialchars($recipient["name"]) ?></h1>
    </header>
    <main>
        <form action="send_message.php?recipient_id=<?= $recipient_id ?>" method="POST">
            <textarea name="message" rows="5" cols="40" placeholder="Type your message here..."></textarea><br>
            <button type="submit">Send Message</button>
        </form>
        <a href="home.php"> Back To Home </a>
    </main>
</body>
</html>
