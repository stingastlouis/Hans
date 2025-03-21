<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['cart'])) {
    $_SESSION['cart'] = $data['cart'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Cart data missing']);
}
?>
