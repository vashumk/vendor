<?php
// config/db.php - Database connection
$host = 'localhost';
$dbname = 'freshlink';
$dbuser = 'root';      // change if your MySQL user is different
$dbpass = '';          // change if your MySQL has a password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
