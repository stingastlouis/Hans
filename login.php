<?php
$week = 7 * 24 * 60 * 60;
session_set_cookie_params($week);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include './configs/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            $stmt = $conn->prepare("
                SELECT c.Id, c.Fullname, c.Email, c.Password, st.Name AS status_name
                FROM Customer c
                LEFT JOIN CustomerStatus cs ON cs.UserId = c.Id
                LEFT JOIN Status st ON st.Id = cs.StatusId
                WHERE c.Email = ?
                AND cs.DateCreated = (
                    SELECT MAX(cs2.DateCreated)
                    FROM CustomerStatus cs2
                    WHERE cs2.UserId = c.Id
                )
            ");

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['Password'])) {
                if ($user['status_name'] !== 'ACTIVE') {
                    $error = "Your account is not active. Please contact support.";
                } else {
                    $_SESSION['customerId'] = $user['Id'];
                    $_SESSION['customer_fullname'] = $user['Fullname'];
                    $_SESSION['customer_email'] = $user['Email'];


                    header("Location: product.php");
                    exit;
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<?php include "./includes/header.php" ?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm rounded-4 border-0">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center mb-4 fw-bold text-primary">Login</h2>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-floating mb-3">
                            <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com" required>
                            <label for="email">Email address</label>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                            <label for="password">Password</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-semibold shadow-sm">Login</button>
                    </form>

                    <p class="mt-4 text-center text-muted small">
                        Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "./includes/footer.php" ?>