<?php
$host = 'localhost';
$username = 'inkovscl_hans';
$password = 'YH=MKYJ1H#.@';
$database = 'inkovscl_light_service';

try {
    $dsn = "mysql:host=$host;dbname=$database";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
