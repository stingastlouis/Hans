<?php
include '../configs/db.php';
include '../utils/communicationUtils.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
try {
    $fullName = $_POST['fullName'] ?? null;
    $email = $_POST['email'] ?? null;
    $subject = $_POST['subject'] ?? null;
    $message = $_POST['message'] ?? null;

    if (!$subject || !$message) {
        redirectBackWithMessage('error', 'Please fill in all required fields.');
    }

    $isCustomer = false;
    $customerId = null;

    if (isset($_SESSION['customerId'])) {
        $isCustomer = true;
        $customerId = $_SESSION['customerId'];
        $fullName = $_SESSION['customer_fullname'];
        $email = $_SESSION['customer_email'];
    }

    $sql = "INSERT INTO Query (FullName, Email, Subject, Message, IsCustomer, CustomerId)
            VALUES (:fullName, :email, :subject, :message, :isCustomer, :customerId)";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':fullName', $fullName);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':message', $message);
    $stmt->bindValue(':isCustomer', $isCustomer, PDO::PARAM_BOOL);
    $stmt->bindValue(':customerId', $customerId, $customerId ? PDO::PARAM_INT : PDO::PARAM_NULL);

    $stmt->execute();

    $redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'index.php';

    $parsedUrl = parse_url($redirectUrl);
    $path = $parsedUrl['path'] ?? '';
    $page = basename($path);

    if ($page === 'profile.php') {
        $redirectUrl = $redirectUrl . '#queries';
        header("Location: $redirectUrl");
        exit;
    } else {
        redirectBackWithMessage('success', 'Your message has been sent successfully.');
    }
} catch (Exception $e) {
    redirectBackWithMessage('error', 'An unexpected error occurred: ' . $e->getMessage());
}
