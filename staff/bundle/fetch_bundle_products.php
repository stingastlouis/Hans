<?php
include '../../configs/db.php';

$bundleId = $_GET['bundle_id'];

$stmt = $conn->prepare("
    SELECT bp.ProductId, bp.Quantity, p.Name 
    FROM BundleProducts bp
    JOIN Products p ON bp.ProductId = p.Id
    WHERE bp.BundleId = ?
");
$stmt->execute([$bundleId]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($products);
