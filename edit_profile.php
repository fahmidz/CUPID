<?php
session_start();

// Redirect to login page if the user is not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$mysqli = require __DIR__ . "/Database.php";

// Fetch current profile data for the logged-in user
$sql = "
    SELECT 
        profile.age, 
        profile.gender, 
        profile.likes, 
        profile.dislikes, 
        profile.photo
    FROM 
        profile
    WHERE 
        user_id = ? 
    LIMIT 1"; // Ensure only one row is fetched

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("SQL error: " . $mysqli->error);
}

$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $age = $_POST["age"] ?? null;
    $gender = $_POST["gender"] ?? null;
    $likes = $_POST["likes"] ?? null;
    $dislikes = $_POST["dislikes"] ?? null;

    // Handle file upload
    $photo = $profile["photo"]; // Retain current photo if no new file is uploaded

    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
        // Delete the old photo file if it exists
        if (!empty($photo) && file_exists(__DIR__ . "/" . $photo)) {
            unlink(__DIR__ . "/" . $photo);
        }

        // Upload the new photo
        $uploadDirectory = "uploads/profile_pictures/";
        $uploadedFileName = uniqid() . "_" . basename($_FILES["photo"]["name"]); // Avoid filename conflicts
        $relativePath = $uploadDirectory . $uploadedFileName;
        $absolutePath = __DIR__ . "/" . $relativePath;

        // Move the uploaded file
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $absolutePath)) {
            $photo = $relativePath; // Save the relative path to the database
        } else {
            die("Failed to upload file.");
        }
    }

    // Update profile in the database
    $sql = "
        INSERT INTO profile (user_id, age, gender, likes, dislikes, photo)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            age = VALUES(age),
            gender = VALUES(gender),
            likes = VALUES(likes),
            dislikes = VALUES(dislikes),
            photo = VALUES(photo)";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("SQL error: " . $mysqli->error);
    }

    $stmt->bind_param("isssss", $_SESSION["user_id"], $age, $gender, $likes, $dislikes, $photo);

    // Execute query and handle errors
    if (!$stmt->execute()) {
        die("SQL error: " . $stmt->error);
    }

    $stmt->close();

    // Redirect to avoid form resubmission
    header("Location: edit_profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="profile.css">
</head>
<body>
    <header>
        <h1>Edit Profile</h1>
    </header>
    <main>
        <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
            <label for="age">Age:</label>
            <input type="number" name="age" id="age" value="<?= htmlspecialchars($profile["age"] ?? "") ?>" min="1">

            <label for="gender">Gender:</label>
            <select name="gender" id="gender">
                <option value="Male" <?= isset($profile["gender"]) && $profile["gender"] === "Male" ? "selected" : "" ?>>Male</option>
                <option value="Female" <?= isset($profile["gender"]) && $profile["gender"] === "Female" ? "selected" : "" ?>>Female</option>
                <option value="Other" <?= isset($profile["gender"]) && $profile["gender"] === "Other" ? "selected" : "" ?>>Other</option>
            </select>

            <label for="likes">Likes:</label>
            <textarea name="likes" id="likes" rows="3"><?= htmlspecialchars($profile["likes"] ?? "") ?></textarea>

            <label for="dislikes">Dislikes:</label>
            <textarea name="dislikes" id="dislikes" rows="3"><?= htmlspecialchars($profile["dislikes"] ?? "") ?></textarea>

            <label for="photo">Profile Picture:</label>
            <?php if (!empty($profile["photo"])): ?>
                <p>Current Picture:</p>
                <img src="<?= htmlspecialchars($profile["photo"]) ?>" alt="Profile Picture">
            <?php endif; ?>
            <input type="file" name="photo" id="photo">

            <button type="submit">Save Changes</button>
        </form>
        <p><a href="home.php">Back to Home</a></p>
    </main>
</body>
</html>
