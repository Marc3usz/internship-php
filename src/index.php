<?php
	$host= "127.0.0.1";
 	$dbname= "test_db";
	$username= "user";
 	$password= "user_password";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    
    $stmt = $pdo->query("SELECT * FROM users");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . "<br>";
        echo "Name: " . $row['name'] . "<br>";
        echo "Email: " . $row['email'] . "<br><br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$pdo = null;
?>