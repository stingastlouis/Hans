<?php
include './configs/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $fullname = $firstname . ' ' . $lastname;
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='alert alert-danger'>Invalid email format.</div>";
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*]/', $password)) {
        echo "<div class='alert alert-danger'>Password must be at least 8 characters long and include an uppercase letter, a lowercase letter, a number, and a special character.</div>";
    } else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $conn->prepare("SELECT Id FROM Customer WHERE Email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                echo "<div class='alert alert-danger'>Email already registered.</div>";
            } else {
                $stmt = $conn->prepare("INSERT INTO Customer (fullname, email, address, phone, password) VALUES (?, ?, ?, ?, ?)");
                $success = $stmt->execute([$fullname, $email, $address, $phone, $password_hashed]);
                
                if ($success) {
                    echo "<div class='alert alert-success'>Registration successful!</div>";
                    header("Location: login.php");
                    exit;
                } else {
                    echo "<div class='alert alert-danger'>Error during registration.</div>";
                }
            }
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Database error: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<?php include "./includes/header.php"?>

<div class="container mt-5">
    <h2 class="mb-4">Register</h2>
    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">First Name</label>
            <input type="text" name="firstname" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" name="lastname" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
            <small class="text-muted">Password must be at least 8 characters, including uppercase, lowercase, number, and special character.</small>
        </div>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</div>
<?php include "./includes/footer.php"?>