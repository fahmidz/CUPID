<?php
session_start();

// Redirect to login page if user is not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$mysqli = require __DIR__ . "/Database.php";

// Fetch the original message based on message_id
if (!isset($_GET['message_id']) || !is_numeric($_GET['message_id'])) {
    die("Invalid message ID.");
}

$message_id = $_GET['message_id'];

// Fetch the original message
$sql = "
    SELECT 
        m.id AS message_id, 
        m.message, 
        m.sent_at, 
        m.from_user_id, 
        m.recipient_id, 
        u.name AS sender_name
    FROM 
        messages m
    LEFT JOIN 
        user u ON m.from_user_id = u.id
    WHERE 
        m.id = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $message_id);
$stmt->execute();
$result = $stmt->get_result();
$message = $result->fetch_assoc();
$stmt->close();

// Check if the message exists and is meant for the logged-in user
if (!$message || $message["recipient_id"] != $_SESSION["user_id"]) {
    die("Message not found or you don't have permission to reply.");
}

// Handle the form submission to reply to the message
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reply_message = trim($_POST['reply_message']);

    // Validate the reply message
    if (empty($reply_message)) {
        $error = "Reply message cannot be empty.";
    } else {
        // Insert the reply into the messages table
        $sql = "INSERT INTO messages (message, from_user_id, recipient_id, sent_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sii", $reply_message, $_SESSION["user_id"], $message["from_user_id"]);
        $stmt->execute();
        $stmt->close();

        // Redirect back to the messages page after sending the reply
        header("Location: messages.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reply to Message</title>
    <link rel="stylesheet" href="reply_message.css">
</head>
<body>
    <header>
        <h1>Reply to Message</h1>
        <nav>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="logout.php">Log Out</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <h2>Original Message</h2>
        <p><strong>From:</strong> <?= htmlspecialchars($message["sender_name"]) ?></p>
        <p><strong>Message:</strong> <?= nl2br(htmlspecialchars($message["message"])) ?></p>
        <p><strong>Sent at:</strong> <?= htmlspecialchars($message["sent_at"]) ?></p>

        <h3>Your Reply</h3>
        <form action="reply_message.php?message_id=<?= $message['message_id'] ?>" method="post">
            <textarea name="reply_message" rows="5" required></textarea><br>
            <button type="submit">Send Reply</button>
        </form>
    </main>
</body>
</html>
