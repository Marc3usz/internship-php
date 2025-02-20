<?php
session_start();
$host = "db";
$dbname = "my_database";
$username = "user";
$password = "user_password";

$env = parse_ini_file('.env');
$newenv = $env['TINYMCE_API_KEY'] ?? "no-api-key";
putenv("TINYMCE_API_KEY=$newenv");
$tinymce_api_key = getenv('TINYMCE_API_KEY');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle post submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title'], $_POST['content'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    if (!empty($title) && !empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $title, $content]);

        header("Location: index.php");
        exit;
    } else {
        $error = "Title and content cannot be empty.";
    }
}

// Handle post deletion (Admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_post']) && isset($_POST['post_id']) && $_SESSION['admin']) {
    $post_id = $_POST['post_id'];

    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);

    header("Location: index.php");
    exit;
}

// Fetch posts
$stmt = $pdo->query("
    SELECT posts.id, posts.title, posts.content, posts.created_at, users.name 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts</title>
    <script src="https://cdn.tiny.cloud/1/<?= $tinymce_api_key ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: 'textarea#content',
            height: 300,
            menubar: false,
            plugins: 'advlist autolink lists link charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
            toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | removeformat | help',
            setup: function (editor) {
                editor.on('change', function () {
                    tinymce.triggerSave();
                });
            }
            });
    </script>
</head>
<body>
    <h1>Submit a Post</h1>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST" onsubmit="tinymce.triggerSave();">
        <label>Title:</label>
        <input type="text" name="title" required><br>
        <label>Content:</label>
        <textarea id="content" name="content" required></textarea><br>
        <button type="submit">Post</button>
    </form>

    <hr>

    <h1>All Posts</h1>
    <?php foreach ($posts as $post): ?>
        <div>
            <h2><?= htmlspecialchars($post['title']) ?></h2>
            <p><?= $post['content'] ?></p> <!-- No htmlspecialchars() to allow formatted content -->
            <p><small>Posted by <?= htmlspecialchars($post['name']) ?> on <?= $post['created_at'] ?></small></p>

            <?php if ($_SESSION['admin']): ?>
                <!-- Delete button for admins -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <button type="submit" name="delete_post" onclick="return confirm('Are you sure you want to delete this post?');">Delete</button>
                </form>
            <?php endif; ?>

            <hr>
        </div>
    <?php endforeach; ?>

    <br>
    <a href="logout.php">Log Out</a>
</body>
</html>
