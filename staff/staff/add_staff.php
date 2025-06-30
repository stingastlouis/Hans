<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
include '../../utils/communicationUtils.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['staff_fullname'];
    $email = $_POST['staff_email'];
    $phone = $_POST['staff_phone'];
    $modifyby = $_POST["modify_by"];
    $roleId = $_POST['staff_role_id'];
    $password = $_POST['staff_password'];

    try {
        $conn->beginTransaction();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        $checkEmail = $conn->prepare("SELECT COUNT(*) FROM Staff WHERE Email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetchColumn() > 0) {
            redirectBackWithMessage('error', 'Email already exists.');
        }

        if (!preg_match("/^[0-9+\-\s()]*$/", $phone)) {
            redirectBackWithMessage('error', 'Invalid phone number format.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO Staff (Fullname, Email, Phone, RoleId, PasswordHash, DateCreated) 
                               VALUES (?, ?, ?, ?, ?, NOW())");

        $stmt->execute([
            $fullname,
            $email,
            $phone,
            $roleId,
            $passwordHash
        ]);

        if ($stmt->rowCount() > 0) {
            $staffId = $conn->lastInsertId();
            $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'ACTIVE' LIMIT 1");
            $statusStmt->execute();
            $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

            if ($statusRow) {
                $statusId = $statusRow['Id'];
                $statusInsertStmt = $conn->prepare("INSERT INTO StaffStatus (staffid, statusid, modifyby, datecreated) 
                                                    VALUES (?, ?, ?,  NOW())");
                $statusInsertStmt->execute([$staffId, $statusId, $modifyby]);

                $conn->commit();

                redirectBackWithMessage('success', 'Staff member added successfully.');
            } else {
                redirectBackWithMessage('error', 'Failed to add staff member status.');
            }
        } else {
            redirectBackWithMessage('error', 'Failed to add staff member.');
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
        } else {
            redirectBackWithMessage('error', 'General error occurred: ' . $errorMessage);
        }
        exit;
    }
} else {
    redirectBackWithMessage('error', 'Invalid request method.');
}
