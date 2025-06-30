<?php
include './configs/db.php';

include "./includes/header.php";

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
                    $customerId = $conn->lastInsertId();
                    $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'ACTIVE' LIMIT 1");
                    $statusStmt->execute();
                    $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

                    if ($statusRow) {
                        $statusId = $statusRow['Id'];

                        $statusInsertStmt = $conn->prepare("INSERT INTO CustomerStatus (userid, statusid, datecreated)
    VALUES (?, ?, NOW())");
                        $statusInsertStmt->execute([$customerId, $statusId]);

                        echo "<div class='alert alert-success'>Registration successful!</div>";
                    } else {
                        throw new Exception("Error: 'ACTIVE' status not found.");
                    }
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


<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-5">
                    <h2 class="card-title mb-4 text-center fw-bold text-primary">Create Your Account</h2>

                    <form method="POST" action="">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="firstname" class="form-control" id="firstname" placeholder="First Name" required>
                                    <label for="firstname">First Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="lastname" class="form-control" id="lastname" placeholder="Last Name" required>
                                    <label for="lastname">Last Name</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com" required>
                            <label for="email">Email address</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" name="address" class="form-control" id="address" placeholder="Address" required>
                            <label for="address">Address</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="tel" name="phone" class="form-control" id="phone" placeholder="Phone number" minlength="4" maxlength="8" required>
                            <label for="phone">Phone</label>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                            <label for="password">Password</label>
                            <div class="form-text mt-1">At least 8 characters, including uppercase, lowercase, number &amp; special character.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-semibold shadow-sm">Register</button>
                    </form>

                    <p class="mt-4 text-center text-muted small">
                        Already have an account? <a href="login.php" class="text-decoration-none">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "./includes/footer.php" ?>