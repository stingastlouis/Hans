<?php

include '../configs/db.php';
session_start();


$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("
        SELECT s.*, r.Name AS RoleName
        FROM Staff s
        JOIN Role r ON s.RoleId = r.Id
        WHERE s.Email = :email
    ");
    $stmt->execute(['email' => $email]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($staff && password_verify($password, $staff['PasswordHash'])) {
        $_SESSION['staff_id'] = $staff['Id'];
        $_SESSION['staff_name'] = $staff['Fullname'];
        $_SESSION['role'] = $staff['RoleName'];
        header('Location: ../staff');
        exit;
    } else {
        $error = "Invalid credentials or not an admin.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h4 class="card-title text-center mb-4">Admin Login</h4>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" required />
                            </div>
                            <div class="mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required />
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
                <p class="text-center mt-3 text-muted small">© <?= date('Y') ?> Admin Panel</p>
            </div>
        </div>
    </div>
</body>
</html>
