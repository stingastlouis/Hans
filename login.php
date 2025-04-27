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
                SELECT c.Id, c.Fullname, c.Password, st.Name AS status_name
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
                    $_SESSION['customer_fullname'] = $user['fullname'];
                    
                    
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

<?php include "./includes/header.php"?>
    <div class="container mt-5">
        <h2 class="mb-4">Login</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
<?php include "./includes/footer.php"?>