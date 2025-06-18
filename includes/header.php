<?php
include "./configs/constants.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$request = $_SERVER["REQUEST_URI"];
$subDomain = "/hans";
$activeClassName = 'active';
$isLoggedIn = isset($_SESSION['customerId']);
$role = $_SESSION['role'] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Light & Genius</title>
    <link rel="icon" type="image/png" sizes="512x512" href="assets/img/spotlight.png">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800&amp;display=swap">
    <link rel="stylesheet" href="assets/css/Pretty-Product-List-.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/cart.css">
</head>
<body class="main-body">
<nav style="z-index: 1;" id="mainNav" class="navbar navbar-light navbar-expand-md sticky-top navbar-shrink py-3 customNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/hans/">
                <span class="custom-header-icon bs-icon-sm shadow d-flex justify-content-center align-items-center me-2 bs-icon">
                    <img src="assets/img/spotlight.png" width="50" height="50"/>
                </span><span>Light Store</span>
            </a>
            <button data-bs-toggle="collapse" class="navbar-toggler" data-bs-target="#navcol-1">
                <span class="visually-hidden">Toggle navigation</span>
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navcol-1">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link <?= $request == "$subDomain/" ? 'active' : ''; ?>" href="/hans/">Home</a></li>
                    <li class="nav-item"><a class="nav-link <?= $request == "$subDomain/bundles" ? 'active' : ''; ?>" href="/hans/bundle.php">Bundles</a></li>
                    <li class="nav-item"><a class="nav-link <?= $request == "$subDomain/products" ? 'active' : ''; ?>" href="/hans/product.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link <?= $request == "$subDomain/events" ? 'active' : ''; ?>" href="/hans/event.php">Events</a></li>
                    <li class="nav-item"><a class="nav-link <?= $request == "$subDomain/contact" ? 'active' : ''; ?>" href="/hans/contact.php">Contact us</a></li>
                </ul>
                <?php if (!in_array($role, ALLOWED_ROLES)): ?>
                <a href="#" id="cart-icon" class="btn btn-outline-secondary position-relative me-2">
                        cart
                        <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            0
                        </span>
                    </a>
                <?php if (!$isLoggedIn): ?>
                    <a class="btn btn-primary shadow" role="button" href="/hans/register.php" style="margin-right: 10px">Sign up</a>
                    <a class="btn btn-primary shadow" role="button" href="/hans/login.php">Sign in</a>
                <?php else: ?>
                    <div style="width:25%; display:flex; justify-content: space-evenly;">
                    

                        <a class="btn btn-primary shadow" role="button" href="/hans/profile.php">Profile</a>
                        <a class="btn btn-danger shadow" role="button" href="/hans/logout.php">Logout</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            </div>
        </div>
</nav>
<?php include "cartview.php"; ?>

<style>
    /* General Navbar Styling */
.customNavbar {
    background: linear-gradient(to right, #fefcea, #f1da36);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-radius: 0 0 15px 15px;
}

/* Navbar Brand Title */
.navbar-brand span:last-child {
    font-weight: 700;
    font-size: 1.5rem;
    color: #2b2b2b;
}

/* Active Link Styling */
.nav-link.active {
    font-weight: 600;
    color: #d63384 !important;
    border-bottom: 2px solid #d63384;
}

/* Regular Links */
.nav-link {
    color: #444;
    transition: color 0.3s ease;
}

.nav-link:hover {
    color: #d63384;
}

/* Cart Icon */
#cart-icon {
    font-weight: 500;
    border-color: #d63384;
    color: #d63384;
    transition: all 0.3s ease;
}

#cart-icon:hover {
    background-color: #d63384;
    color: white;
}

/* Cart Count Badge */
#cart-count {
    font-size: 0.75rem;
    padding: 0.25em 0.4em;
}

/* Buttons */
.btn-primary.shadow {
    background-color: #d63384;
    border-color: #d63384;
}

.btn-primary.shadow:hover {
    background-color: #bd2c75;
    border-color: #bd2c75;
}

.btn-danger.shadow {
    background-color: #ff4d4d;
    border-color: #ff4d4d;
}

.btn-danger.shadow:hover {
    background-color: #cc0000;
    border-color: #cc0000;
}

/* Profile/Logout button group on the right */
@media (max-width: 768px) {
    .navbar-collapse > div[style*="width:25%"] {
        width: 100% !important;
        margin-top: 10px;
        justify-content: space-around !important;
    }
}

</style>
