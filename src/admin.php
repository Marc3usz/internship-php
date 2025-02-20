<?php
session_start();
$host = "db";
$dbname = "my_database";
$username = "user";
$password = "user_password";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is admin
if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    header("Location: index.php");
    exit;
}

// Fetch the current admin's account creation date for comparison
$stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_admin = $stmt->fetch(PDO::FETCH_ASSOC);
$current_admin_created_at = $current_admin['created_at'];

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['grant_admin'])) {
        $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
    } elseif (isset($_POST['revoke_admin'])) {
        // Check if the target admin's account was created after the current admin
        $stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($target_user && strtotime($target_user['created_at']) > strtotime($current_admin_created_at)) {
            echo "<script>alert('You can only revoke admin privileges from younger admin accounts.');</script>";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
        }
    } elseif (isset($_POST['delete_user'])) {
        // Check if the target admin's account was created after the current admin
        $stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($target_user && strtotime($target_user['created_at']) > strtotime($current_admin_created_at)) {
            echo "<script>alert('You can only delete younger admin accounts.');</script>";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
        }
    }
    header("Location: admin.php");
    exit;
}

// Fetch users
$stmt = $pdo->query("SELECT id, username, is_admin, created_at FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
</head>
<body>
    <h1>Admin Panel</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Admin</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= $user['is_admin'] ? 'Yes' : 'No' ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <?php if (!$user['is_admin']): ?>
                            <button type="submit" name="grant_admin">Grant Admin</button>
                        <?php else: ?>
                            <?php if (strtotime($user['created_at']) > strtotime($current_admin_created_at)): ?>
                                <!-- Disallow actions on younger admins -->
                                <button type="submit" name="revoke_admin" disabled>Revoke Admin</button>
                                <button type="submit" name="delete_user" disabled>Delete</button>
                            <?php else: ?>
                                <button type="submit" name="revoke_admin">Revoke Admin</button>
                                <button type="submit" name="delete_user" onclick="return confirm('Are you sure?');">Delete</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <br>
    <a href="logout.php">Logout</a>
</body>
</html>
