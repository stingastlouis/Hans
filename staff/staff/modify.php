<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $staffId = $_POST['staff_id'];
    $fullname = $_POST['staff_fullname'];
    $email = $_POST['staff_email'];
    $phone = $_POST['staff_phone'];
    $roleId = $_POST['staff_role_id'];

    try {
        $conn->beginTransaction();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ../staff.php?error=1');
            exit;
        }

        $checkEmail = $conn->prepare("SELECT COUNT(*) FROM Staff WHERE Email = ? AND Id != ?");
        $checkEmail->execute([$email, $staffId]);
        if ($checkEmail->fetchColumn() > 0) {
            header('Location: ../staff.php?error=1');
            exit;
        }

        if (!preg_match("/^[0-9+\-\s()]*$/", $phone)) {
            header('Location: ../staff.php?error=1');
            exit;
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
            header('Location: ../staff.php?success=1');
            exit;
        } else {
            header('Location: ../staff.php?error=1');
            exit;
        }

    } catch (Exception $e) {
        $conn->rollBack();
        
        $errorMessage = $e->getMessage();
        if (strpos($errorMessage, "Invalid email") !== false) {
            header('Location: ../staff.php?error=invalid_email');
        } else if (strpos($errorMessage, "Email already exists") !== false) {
            header('Location: ../staff.php?error=email_exists');
        } else if (strpos($errorMessage, "Invalid phone") !== false) {
            header('Location: ../staff.php?error=invalid_phone');
        } else if (strpos($errorMessage, "Staff member not found") !== false) {
            header('Location: ../staff.php?error=staff_not_found');
        } else {
            header('Location: ../staff.php?error=general&message=' . urlencode($errorMessage));
        }
        exit;
    }
} else {
    header('Location: ../staff.php');
    exit;
}
?>
