<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

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
            header('Location: ../staff.php?error=1');
            exit;
        }

        if (!preg_match("/^[0-9+\-\s()]*$/", $phone)) {
            header('Location: ../staff.php?error=1');
            exit;
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

                header('Location: ../staff.php?success=1');
                exit;
            } else {
                header('Location: ../staff.php?error=1');
                exit;
            }
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
