<?php
$is_invalid = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mysqli = require __DIR__ . "/Database.php";

    // Use a prepared statement to avoid SQL injection
    $sql = "SELECT * FROM user WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("SQL error: " . $mysqli->error);
    }

    $stmt->bind_param("s", $_POST["email"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Verify password
        if (password_verify($_POST["password"], $user["password_hash"])) {
            session_start();
            session_regenerate_id();
            $_SESSION["user_id"] = $user["id"];
            header("Location: home.php");
            exit;
        }
    }

    $is_invalid = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <h1>Welcome Back!</h1>
    <?php if ($is_invalid): ?>
        <p class="error-message">Invalid login credentials. Please try again!</p>
    <?php endif; ?>

    <form method="post">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($_POST["email"] ?? "") ?>" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Log in</button>
    </form>

    <!-- Sparkle Effect -->
    <div class="sparkle-container" id="sparkle-container"></div>
    <script>
        const sparkleContainer = document.getElementById("sparkle-container");
        const numberOfSparkles = 50;

        for (let i = 0; i < numberOfSparkles; i++) {
            let sparkle = document.createElement("div");
            sparkle.classList.add("sparkle");
            sparkleContainer.appendChild(sparkle);

            sparkle.style.left = `${Math.random() * 100}vw`;
            sparkle.style.top = `${Math.random() * 100}vh`;
            sparkle.style.width = `${Math.random() * 4 + 1}px`;
            sparkle.style.height = `${Math.random() * 4 + 1}px`;
            sparkle.style.animationDelay = `${Math.random() * 2}s`;
            sparkle.style.animationDuration = `${Math.random() * 2 + 2}s`;
        }
    </script>
</body>
</html>

