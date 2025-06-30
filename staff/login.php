<?php

include '../sessionManagement.php';
include '../configs/constants.php';

if ($_SESSION && isset($_SESSION['staff_id'])) {
    header("Location: ../staff/");
    exit;
}

include '../configs/db.php';
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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-lg rounded-4 border-0">
                    <div class="card-body p-4">
                        <h4 class="card-title text-center mb-4 fw-semibold">Admin Login</h4>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    placeholder="Enter email"
                                    required
                                    autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    placeholder="Enter password"
                                    required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Sign In</button>
                        </form>
                    </div>
                </div>
                <p class="text-center mt-3 text-muted small">
                    © <?= date('Y') ?> Admin Panel
                </p>
            </div>
        </div>
    </div>

</body>

</html>