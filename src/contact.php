<?php
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$smtpHost = $_ENV['SMTP_HOST'];
$smtpUser = $_ENV['SMTP_USER'];
$smtpPass = $_ENV['SMTP_PASS'];
$smtpPort = $_ENV['SMTP_PORT'];
$smtpEmail = $_ENV['SMTP_EMAIL'];

$host = "db"; 
$dbname = "my_database";
$username = "user";
$password = "user_password";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$stmt = $pdo->query("SELECT email FROM users WHERE is_admin = 1");
$adminEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);

$userName = $_SESSION['user'] ?? 'Unknown User';
$userEmail = $_SESSION['email'] ?? 'anon@anon.anon';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = trim($_POST["message"]);

    if (empty($message)) {
        $error = "Message cannot be empty.";
    } else {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpPort;

            $mail->setFrom($smtpEmail, $userName);
            
            // BCC all admin emails from the database
            foreach ($adminEmails as $adminEmail) {
                $mail->addBCC($adminEmail);
            }

            $mail->Subject = "New Contact Form Submission";
            $mail->Body = "Name: $userName\nEmail: $userEmail\n\nMessage:\n$message";

            $mail->send();
            $success = "Your message has been sent!";
        } catch (Exception $e) {
            $error = "Email could not be sent. Error: {$mail->ErrorInfo}";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
</head>
<body>
    <h1>Contact Us</h1>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <p style="color: green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST">
        <p><strong>Your Name:</strong> <?= htmlspecialchars($userName) ?></p>
        <p><strong>Your Email:</strong> <?= htmlspecialchars($userEmail) ?></p>

        <label>Message:</label>
        <textarea name="message" required></textarea><br><br>

        <button type="submit">Send</button>
    </form>
    <a href="index.php">Posts</a>
</body>
</html>
