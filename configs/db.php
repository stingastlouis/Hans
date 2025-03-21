<?php
$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'light_service';

try {
    // Set the DSN (Data Source Name)
    $dsn = "mysql:host=$host;dbname=$database";
    
    // Create a PDO instance
    $conn = new PDO($dsn, $username, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optionally, you can set the default fetch mode for results
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle connection failure
    echo "Connection failed: " . $e->getMessage();
}
?>
