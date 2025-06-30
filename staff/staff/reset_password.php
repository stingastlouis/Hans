<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $staffId = $_POST['staff_id'];
    $newPassword = $_POST['staff_password'];

    try {
        $conn->beginTransaction();

        if (empty($staffId)) {
            throw new Exception("Staff ID is required");
        }

        if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/", $newPassword)) {
            throw new Exception("Password must be at least 8 characters long and include a mix of uppercase, lowercase, numbers, and special characters");
        }

        $checkStaff = $conn->prepare("SELECT COUNT(*) FROM Staff WHERE Id = ?");
        $checkStaff->execute([$staffId]);
        if ($checkStaff->fetchColumn() == 0) {
            throw new Exception("Staff member not found");
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE Staff SET PasswordHash = ? WHERE Id = ?");
        $stmt->execute([$passwordHash, $staffId]);

        if ($stmt->rowCount() > 0) {
            $conn->commit();
            redirectBackWithMessage('success', 'Password reset successfully.');
        } else {
            throw new Exception("Error: Unable to reset the password. No changes were made.");
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $errorMessage = $e->getMessage();
        if (strpos($errorMessage, "Password must be") !== false) {
            redirectBackWithMessage('error', 'Weak password.');
        } else if (strpos($errorMessage, "Staff member not found") !== false) {
            redirectBackWithMessage('error', 'Staff member not found.');
        } else {
            redirectBackWithMessage('error', 'General error occurred: ' . $errorMessage);
        }
    }
} else {
    redirectBackWithMessage('error', 'Invalid request method.');
}
