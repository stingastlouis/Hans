<?php

// $host = 'sql104.infinityfree.com';
// $username = 'if0_39401824';
// $password = 'ZEBKoq8xwdP';
// $database = 'if0_39401824_light_service';

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'light_service';

try {
    $dsn = "mysql:host=$host;dbname=$database";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
