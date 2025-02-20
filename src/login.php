<?php
session_start();
$host = "db";
$dbname = "my_database";
$username = "user";
$password = "user_password";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$user]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData && password_verify($pass, $userData['password'])) {
        $_SESSION['user'] = $userData['username'];
        $_SESSION['admin'] = $userData['is_admin'] ? true : false;
        
        if ($userData['is_admin']) {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        echo "Invalid login credentials";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <form method="POST">
        <label>Username:</label>
        <input type="text" name="username" required>
        <br>
        <label>Password:</label>
        <input type="password" name="password" required>
        <br>
        <button type="submit" name="login">Login</button>
    </form>
</body>
</html>
