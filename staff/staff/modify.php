<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $staffId = $_POST['staff_id'];
    $fullname = $_POST['staff_fullname'];
    $email = $_POST['staff_email'];
    $phone = $_POST['staff_phone'];
    $roleId = $_POST['staff_role_id'];

    try {
        $conn->beginTransaction();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectBackWithMessage('error', 'Invalid email format.');
        }

        $checkEmail = $conn->prepare("SELECT COUNT(*) FROM Staff WHERE Email = ? AND Id != ?");
        $checkEmail->execute([$email, $staffId]);
        if ($checkEmail->fetchColumn() > 0) {
            \redirectBackWithMessage('error', 'Email already exists.');
        }

        if (!preg_match("/^[0-9+\-\s()]*$/", $phone)) {
            redirectBackWithMessage('error', 'Invalid phone number format.');
        }

        $checkStaff = $conn->prepare("SELECT COUNT(*) FROM Staff WHERE Id = ?");
        $checkStaff->execute([$staffId]);
        if ($checkStaff->fetchColumn() == 0) {
            throw new Exception("Staff member not found");
        }

        $stmt = $conn->prepare("UPDATE Staff 
                               SET Fullname = ?, 
                                   Email = ?, 
                                   Phone = ?, 
                                   RoleId = ? 
                               WHERE Id = ?");

        $stmt->execute([
            $fullname,
            $email,
            $phone,
            $roleId,
            $staffId
        ]);

        if ($stmt->rowCount() >= 0) {
            $conn->commit();
            redirectBackWithMessage('success', 'Staff member updated successfully.');
        } else {
            redirectBackWithMessage('error', 'Failed to update staff member.');
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
        } else if (strpos($errorMessage, "Staff member not found") !== false) {
            redirectBackWithMessage('error', 'Staff member not found.');
        } else {
            redirectBackWithMessage('error', 'General error occurred: ' . $errorMessage);
        }
        exit;
    }
} else {
    redirectBackWithMessage('error', 'Invalid request method.');
}
