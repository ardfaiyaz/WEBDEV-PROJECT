<?php
// php/database.php

$host = 'localhost'; // Your database host
$db   = 'clearance'; // Your database name, as defined in clearance-3.sql
$user = 'root'; // Your database username (default for XAMPP)
$pass = ''; // Your database password (default for XAMPP, usually empty)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

