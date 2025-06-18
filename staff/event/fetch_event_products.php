<?php
include '../../configs/db.php';

$bundleId = $_GET['event_id'];

$stmt = $conn->prepare("
    SELECT ep.ProductId, ep.Quantity, p.Name 
    FROM EventProducts ep
    JOIN Products p ON ep.ProductId = p.Id
    WHERE ep.EventId = ?
");
$stmt->execute([$bundleId]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($products);
