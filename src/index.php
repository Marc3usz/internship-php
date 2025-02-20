<?php
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

session_start();
$host = "db";
$dbname = "my_database";
$username = "user";
$password = "user_password";

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$tinymce_api_key = $_ENV['TINYMCE_API_KEY'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Language selection
$language = $_GET['lang'] ?? 'en';
$languages = [
    'en' => [
        'submit_post' => 'Submit a Post', 'logout' => 'Log Out', 'title' => 'Title', 'content' => 'Content',
        'post' => 'Post', 'all_posts' => 'All Posts', 'delete' => 'Delete', 'posted_by' => 'Posted by',
        'confirm_delete' => 'Are you sure you want to delete this post?', 'switch_lang' => 'Switch to Polish',
        'tinymce_lang' => 'en', 'contact' => 'contact'
    ],
    'pl' => [
        'submit_post' => 'Dodaj post', 'logout' => 'Wyloguj się', 'title' => 'Tytuł', 'content' => 'Treść',
        'post' => 'Opublikuj', 'all_posts' => 'Wszystkie posty', 'delete' => 'Usuń', 'posted_by' => 'Dodano przez',
        'confirm_delete' => 'Czy na pewno chcesz usunąć ten post?', 'switch_lang' => 'Przełącz na angielski',
        'tinymce_lang' => 'pl', 'contact' => 'kontact'
    ]
];

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

        header("Location: index.php?lang=$language");
        exit;
    } else {
        $error = $languages[$language]['title'] . " and " . $languages[$language]['content'] . " cannot be empty.";
    }
}

// Handle post deletion (Admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_post']) && isset($_POST['post_id']) && $_SESSION['admin']) {
    $post_id = $_POST['post_id'];

    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);

    header("Location: index.php?lang=$language");
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
<html lang="<?= $language ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $languages[$language]['all_posts'] ?></title>
    <script src="https://cdn.tiny.cloud/1/<?= $tinymce_api_key ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: 'textarea#content',
            height: 300,
            menubar: false,
            language: '<?= $languages[$language]['tinymce_lang'] ?>',  // Set TinyMCE language dynamically
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
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0 20%;">
        <h1><?= $languages[$language]['submit_post'] ?></h1>
        <a href="logout.php"><?= $languages[$language]['logout'] ?></a>
        <a href="?lang=<?= $language === 'en' ? 'pl' : 'en' ?>"><?= $languages[$language]['switch_lang'] ?></a>
        <a href="contact.php"><?= $languages[$language]['contact'] ?></a>
    </div>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" onsubmit="tinymce.triggerSave();">
        <label><?= $languages[$language]['title'] ?>:</label>
        <input type="text" name="title" required><br>
        <label><?= $languages[$language]['content'] ?>:</label>
        <textarea id="content" name="content" required></textarea><br>
        <button type="submit"><?= $languages[$language]['post'] ?></button>
    </form>

    <hr>

    <h1><?= $languages[$language]['all_posts'] ?></h1>
    <?php foreach ($posts as $post): ?>
        <div>
            <h2><?= htmlspecialchars($post['title']) ?></h2>
            <p><?= $post['content'] ?></p> <!-- No htmlspecialchars() to allow formatted content -->
            <p><small><?= $languages[$language]['posted_by'] ?> <?= htmlspecialchars($post['name']) ?> on <?= $post['created_at'] ?></small></p>

            <?php if ($_SESSION['admin']): ?>
                <!-- Delete button for admins -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <button type="submit" name="delete_post" onclick="return confirm('<?= $languages[$language]['confirm_delete'] ?>');">
                        <?= $languages[$language]['delete'] ?>
                    </button>
                </form>
            <?php endif; ?>

            <hr>
        </div>
    <?php endforeach; ?>

    <br>
</body>
</html>
