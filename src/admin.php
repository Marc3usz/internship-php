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

// Check if user is admin
if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    header("Location: index.php");
    exit;
}

// Fetch the current admin's account creation date
$stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id'] ?? -1]);
$current_admin = $stmt->fetch(PDO::FETCH_ASSOC);
$current_admin_created_at = $current_admin['created_at'] ?? "1000-01-01 00:00:00";

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];

        // Fetch the target user's details
        $stmt = $pdo->prepare("SELECT is_admin, created_at FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($target_user) {
            $target_created_at = $target_user['created_at'];

            if (isset($_POST['grant_admin'])) {
                $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
                $stmt->execute([$user_id]);
            } elseif (isset($_POST['revoke_admin'])) {
                // Allow revoking admin rights only from younger admins
                if ($target_user['is_admin'] && strtotime($target_created_at) > strtotime($current_admin_created_at)) {
                    $stmt = $pdo->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
                    $stmt->execute([$user_id]);
                } else {
                    echo "<script>alert('You can only revoke admin privileges from younger admins.');</script>";
                }
            } elseif (isset($_POST['delete_user'])) {
                // Allow deleting only younger admins, but normal users can always be deleted
                if (!$target_user['is_admin'] || strtotime($target_created_at) > strtotime($current_admin_created_at)) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                } else {
                    echo "<script>alert('You can only delete younger admins.');</script>";
                }
            }
        }
        header("Location: admin.php");
        exit;
    } else {
        echo "Error: No user selected.";
    }
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
            <th>Account Created On</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= $user['is_admin'] ? 'Yes' : 'No' ?></td>
                <td><?= htmlspecialchars($user['created_at']) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <?php if (!$user['is_admin']): ?>
                            <button type="submit" name="grant_admin">Grant Admin</button>
                            <button type="submit" name="delete_user" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                        <?php else: ?>
                            <?php if (strtotime($user['created_at']) > strtotime($current_admin_created_at)): ?>
                                <!-- Allow actions on younger admins -->
                                <button type="submit" name="revoke_admin">Revoke Admin</button>
                                <button type="submit" name="delete_user" onclick="return confirm('Are you sure you want to delete this admin?');">Delete</button>
                            <?php else: ?>
                                <!-- Disallow actions on older admins -->
                                <button type="submit" name="revoke_admin" disabled>Revoke Admin</button>
                                <button type="submit" name="delete_user" disabled>Delete</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <br>
    <a href="logout.php">Logout</a>
    <a href="index.php">Posts</a>
</body>
</html>
