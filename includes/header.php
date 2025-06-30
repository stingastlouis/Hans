<?php
include "./configs/constants.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$request = $_SERVER["REQUEST_URI"];
$subDomain = "/hans";
$activeClassName = 'active';
$isLoggedIn = isset($_SESSION['customerId']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Light & Genius</title>
    <link rel="icon" type="image/png" sizes="512x512" href="assets/img/c37a6998-6f32-4fe6-93be-62914755c41f.jpg" />
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800&amp;display=swap" />
    <link rel="stylesheet" href="assets/css/header.css" />
    <link rel="stylesheet" href="assets/css/cart.css" />
</head>

<style>
    .customNavbar {
        background: linear-gradient(90deg, rgb(132, 173, 208) 0%, rgb(90, 214, 214) 100%);

        transition: box-shadow 0.3s ease;
    }

    .customNavbar:hover {
        box-shadow: 0 8px 20px rgba(214, 51, 132, 0.3);
    }

    .navbar-brand {
        font-family: 'Inter', sans-serif;
    }


    .nav-link {
        color: #6c757d;
        padding: 0.5rem 1rem;
        transition: color 0.3s ease, border-bottom 0.3s ease;
        position: relative;
    }

    .nav-link.active,
    .nav-link:hover {
        color: #d63384 !important;
        border-bottom: 3px solid #d63384;
    }


    #cart-icon {
        font-weight: 600;
        border-width: 2px;
        border-radius: 0.375rem;
        padding: 0.4rem 0.9rem;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    #cart-icon:hover {
        background-color: #d63384;
        color: white;
        border-color: #d63384;
        text-decoration: none;
    }


    #cart-count {
        font-size: 0.75rem;
        padding: 0.25em 0.4em;
    }


    .btn-danger {
        background-color: #d63384;
        border-color: #d63384;
        font-weight: 600;
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    .btn-danger:hover {
        background-color: #b0296c;
        border-color: #b0296c;
        color: #fff;
    }

    .btn-outline-danger {
        border-color: #d63384;
        color: #d63384;
        font-weight: 600;
    }

    .btn-outline-danger:hover {
        background-color: #d63384;
        color: white;
        border-color: #d63384;
    }

    @media (max-width: 767.98px) {
        .navbar-collapse>div {
            justify-content: center !important;
        }
    }

    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }


    .bg-primary-gradient {
        background: linear-gradient(90deg, rgb(132, 173, 208) 0%, rgb(90, 214, 214) 100%);
        color: white;
    }

    footer a {
        color: #ffffffcc;
        text-decoration: none;
    }

    footer a:hover {
        color: #fff;
        text-decoration: underline;
    }

    .footer-icon svg {
        width: 1.25rem;
        height: 1.25rem;
    }
</style>

<body class="main-body">
    <nav style="z-index: 100;" id="mainNav" class="navbar navbar-expand-md sticky-top shadow-sm customNavbar py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold text-dark" href="/">
                <img src="assets/img/c37a6998-6f32-4fe6-93be-62914755c41f.jpg" alt="Logo" width="50" height="50" class="me-2" />
                <span class="fs-4">Light Store</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navcol-1" aria-controls="navcol-1" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navcol-1">
                <ul class="navbar-nav mx-auto mb-2 mb-md-0 fw-semibold">
                    <li class="nav-item">
                        <a class="nav-link <?= $request == "$subDomain/" ? 'active' : ''; ?>" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $request == "$subDomain/bundle.php" ? 'active' : ''; ?>" href="/bundle.php">Bundles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $request == "$subDomain/product.php" ? 'active' : ''; ?>" href="/product.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $request == "$subDomain/event.php" ? 'active' : ''; ?>" href="/event.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $request == "$subDomain/contact.php" ? 'active' : ''; ?>" href="/contact.php">Contact us</a>
                    </li>
                </ul>

                <a href="#" id="cart-icon" class="btn btn-outline-danger position-relative me-3 fw-semibold">
                    Cart
                    <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        0
                    </span>
                </a>

                <?php if (!$isLoggedIn): ?>
                    <a class="btn btn-danger me-2 fw-semibold" role="button" href="/register.php">Sign up</a>
                    <a class="btn btn-outline-danger fw-semibold" role="button" href="/login.php">Sign in</a>
                <?php else: ?>
                    <div class="d-flex gap-2 flex-wrap justify-content-end" style="min-width: 200px;">
                        <a class="btn btn-danger fw-semibold flex-grow-1 flex-md-grow-0" role="button" href="/profile.php">Profile</a>
                        <a class="btn btn-outline-danger fw-semibold flex-grow-1 flex-md-grow-0" role="button" href="/logout.php">Logout</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php include "cartview.php"; ?>

    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>