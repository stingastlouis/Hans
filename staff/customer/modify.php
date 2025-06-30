<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customerId = $_POST['customer_id'];
    $fullname = $_POST['customer_fullname'];
    $email = $_POST['customer_email'];
    $phone = $_POST['customer_phone'];

    try {
        $conn->beginTransaction();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        $checkEmail = $conn->prepare("SELECT COUNT(*) FROM Customer WHERE Email = ? AND Id != ?");
        $checkEmail->execute([$email, $customerId]);
        if ($checkEmail->fetchColumn() > 0) {
            throw new Exception("Email already exists");
        }

        if (!preg_match("/^[0-9+\-\s()]*$/", $phone)) {
            throw new Exception("Invalid phone number format");
        }

        $checkCustomer = $conn->prepare("SELECT COUNT(*) FROM Customer WHERE Id = ?");
        $checkCustomer->execute([$customerId]);
        if ($checkCustomer->fetchColumn() == 0) {
            throw new Exception("Customer member not found");
        }

        $stmt = $conn->prepare("UPDATE Customer 
                               SET Fullname = ?, 
                                   Email = ?, 
                                   Phone = ?
                               WHERE Id = ?");

        $stmt->execute([
            $fullname,
            $email,
            $phone,
            $customerId
        ]);

        if ($stmt->rowCount() >= 0) {
            $conn->commit();
            redirectBackWithMessage('success', 'Customer successfully updated.');
        } else {
            throw new Exception("Error: Unable to update the customer member in the database.");
        }
    } catch (Exception $e) {
        $conn->rollBack();

        $errorMessage = $e->getMessage();
        if (strpos($errorMessage, "Invalid email") !== false) {
            redirectBackWithMessage('error', 'Invalid email format.');
        } else if (strpos($errorMessage, "Email already exists") !== false) {
            redirectBackWithMessage('error', 'Email already exists.');
        } else if (strpos($errorMessage, "Invalid phone") !== false) {
            redirectBackWithMessage('error', 'Invalid phone number format.');
        } else if (strpos($errorMessage, "Customer member not found") !== false) {
            redirectBackWithMessage('error', 'Customer member not found.');
        } else {
            redirectBackWithMessage('error', 'General error: ' . $errorMessage);
        }
    }
} else {
    redirectBackWithMessage('error', 'Invalid request.');
}
