<?php
session_start();

// Redirect to login page if user is not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$mysqli = require __DIR__ . "/Database.php";

// Fetch user details and profile
$sql = "
    SELECT 
        user.id,
        user.name, 
        profile.age, 
        profile.gender, 
        profile.likes, 
        profile.dislikes, 
        profile.photo
    FROM 
        user
    LEFT JOIN 
        profile 
    ON 
        user.id = profile.user_id
    WHERE 
        user.id = ?";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("SQL error: " . $mysqli->error);
}

$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle missing data
$user["age"] = $user["age"] ?? "Not set";
$user["gender"] = $user["gender"] ?? "Not set";
$user["likes"] = $user["likes"] ?? "Not set";
$user["dislikes"] = $user["dislikes"] ?? "Not set";
$user["photo"] = $user["photo"] ?? null;

// Fetch all users (except the logged-in user) for messaging
$other_users = getOtherUsers($user["id"]);

function getOtherUsers($userId) {
    global $mysqli;

    // Fetch all users except the logged-in one
    $sql = "
        SELECT 
            u.id, u.name, u.email, p.age, p.gender, p.likes, p.dislikes, p.photo
        FROM 
            user u
        LEFT JOIN 
            profile p ON u.id = p.user_id
        WHERE 
            u.id != ?";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $other_users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $other_users;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <header>
        <h1>Welcome, <?= htmlspecialchars($user["name"]) ?>!</h1>
        <nav>
            <ul>
                <li><a href="edit_profile.php">Edit Profile</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="logout.php">Log Out</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>Your Profile</h2>
        <div class="profile-info">
            <p><strong>Name:</strong> <?= htmlspecialchars($user["name"]) ?></p>
            <p><strong>Age:</strong> <?= htmlspecialchars($user["age"]) ?></p>
            <p><strong>Gender:</strong> <?= htmlspecialchars($user["gender"]) ?></p>
            <p><strong>Likes:</strong> <?= htmlspecialchars($user["likes"]) ?></p>
            <p><strong>Dislikes:</strong> <?= htmlspecialchars($user["dislikes"]) ?></p>
        </div>

        <?php if ($user["photo"]): ?>
            <p>
                <img src="<?= htmlspecialchars($user['photo']) ?>" alt="Profile Picture" style="max-width: 150px; border-radius: 50%;">
            </p>
        <?php else: ?>
            <p>No profile picture uploaded.</p>
        <?php endif; ?>

        <h3>Other Users</h3>
        <?php if (empty($other_users)): ?>
            <p>No other users found. Please check back later!</p>
        <?php else: ?>
            <ul class="users-list">
                <?php foreach ($other_users as $other_user): ?>
                    <li>
                        <div class="user-info">
                            <p><strong>Name:</strong> <?= htmlspecialchars($other_user["name"]) ?></p>
                            <p><strong>Age:</strong> <?= htmlspecialchars($other_user["age"]) ?></p>
                            <p><strong>Gender:</strong> <?= htmlspecialchars($other_user["gender"]) ?></p>
                            <img src="<?= htmlspecialchars($other_user['photo']) ?>" alt="Profile Picture" style="max-width: 150px; border-radius: 50%;"> <br>

                            <p><strong>Likes:</strong> <?= htmlspecialchars($other_user["likes"]) ?></p>
                            <p><strong>Dislikes:</strong> <?= htmlspecialchars($other_user["dislikes"]) ?></p>

                            <a href="send_message.php?recipient_id=<?= $other_user['id'] ?>">Send Message</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>
</body>
</html>
