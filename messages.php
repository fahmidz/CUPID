<?php
session_start();

// Redirect to login page if user is not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$mysqli = require __DIR__ . "/Database.php";

// Fetch messages for the logged-in user
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
        m.recipient_id = ? 
    ORDER BY 
        m.sent_at DESC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="messages.css">
</head>
<body>
    <header>
        <h1>Your Messages</h1>
        <nav>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="logout.php">Log Out</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <?php if (empty($messages)): ?>
            <p>No messages yet. Start a conversation!</p>
        <?php else: ?>
            <ul class="messages-list"> <!-- Added class here -->
                <?php foreach ($messages as $message): ?>
                    <li>
                        <strong>From: <?= htmlspecialchars($message["sender_name"]) ?></strong><br>
                        <p><?= htmlspecialchars($message["message"]) ?></p>
                        <small>Sent at: <?= htmlspecialchars($message["sent_at"]) ?></small><br>
                        <a href="reply_message.php?message_id=<?= $message['message_id'] ?>">Reply</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>
</body>
</html>
