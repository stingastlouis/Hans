<?php

$request = $_SERVER["REQUEST_URI"];
$subDomain = "/hans";
$activeClassName = 'active';

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
<nav style="z-index: 1;" id="mainNav" class="navbar navbar-light navbar-expand-md sticky-top navbar-shrink py-3 customNavbar" >
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/">
                <span class="custom-header-icon bs-icon-sm shadow d-flex justify-content-center align-items-center me-2 bs-icon">
                    <img src="assets/img/spotlight.png" width="50" height="50"/>
                </span><span>Brand</span>
                </a><button data-bs-toggle="collapse" class="navbar-toggler" data-bs-target="#navcol-1"><span class="visually-hidden">Toggle navigation</span><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navcol-1">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link <?php echo $request == "$subDomain/" ? 'active' : ''; ?>" href="/hans/">Home</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $request == "$subDomain/events" ? 'active' : ''; ?>" href="/hans/">Events</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $request == "$subDomain/products" ? 'active' : ''; ?>" href="/hans/product.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $request == "$subDomain/about" ? 'active' : ''; ?>" href="/hans/contact.php">About us</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $request == "$subDomain/contact" ? 'active' : ''; ?>" href="/hans/contact.php">Contact us</a></li>
                </ul>
                <a class="btn btn-primary shadow" role="button" href="signup.html" style="margin-right: 10px">Sign up</a>
                <a class="btn btn-primary shadow" role="button" href="signup.html">Sign in</a>
            </div>
        </div>
</nav>
