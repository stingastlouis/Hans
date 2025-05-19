<!DOCTYPE html>
<html lang="en">
<?php 
include '../sessionManagement.php';
require_once '../configs/constants.php';

?>

<?php 
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../unauthorised.php");
    exit;
}


?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Dashboard - Brand</title>
    <link rel="icon" type="image/png" sizes="512x512" href="../assets/img/spotlight.png">
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/Nunito.css">
    <link rel="stylesheet" href="../assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="../assets/css/dropdown.css">
</head>

<body id="page-top">

<div id="wrapper">
        <nav class="navbar navbar-dark align-items-start sidebar sidebar-dark accordion bg-gradient-primary p-0">
            <div class="container-fluid d-flex flex-column p-0"><a class="navbar-brand d-flex justify-content-center align-items-center sidebar-brand m-0" href="../staff/">
    
                    <div class="sidebar-brand-text mx-3">
                    <span class="custom-header-icon bs-icon-sm shadow d-flex justify-content-center align-items-center me-2 bs-icon">
                        <img src="../assets/img/spotlight.png" width="50" height="50"/>
                    </span>
                    <span>Light Store</span>
                    </div>
            
                </a>
                <hr class="sidebar-divider my-0">
                <?php $role = $_SESSION['role'];?>

<ul class="navbar-nav text-light" id="accordionSidebar">

    <?php if (in_array($role, ALLOWED_EDITOR_ROLES)): ?>
        <li class="nav-item custom-nav-item">
            <a class="nav-link active" href="../staff/"><span>Dashboard</span></a>
        </li>
        <li class="nav-item custom-nav-item">
            <a class="nav-link" href="category.php"><span>Category</span></a>
        </li>
        <li class="nav-item custom-nav-item">
            <a class="nav-link" href="role.php"><span>Role</span></a>
        </li>
        <li class="nav-item custom-nav-item">
            <a class="nav-link" href="bundle.php"><span>Bundle</span></a>
        </li>
        <li class="nav-item custom-nav-item">
            <a class="nav-link" href="product.php"><span>Product</span></a>
        </li>
        <li class="nav-item custom-nav-item">
            <a class="nav-link" href="staff.php"><span>Staff</span></a>
        </li>
        <li class="nav-item custom-nav-item">
            <a class="nav-link" href="order.php"><span>Order</span></a>
        </li>
        <li class="nav-item custom-nav-item">
            <a class="nav-link" href="customer.php"><span>Customer</span></a>
        </li>
        <?php endif; ?>

        <?php if (in_array($role, ALLOWED_ROLES)): ?>
        <li class="nav-item custom-nav-item">
            <a class="nav-link" href="installation.php"><span>Installation</span></a>
        </li>
    <?php endif; ?>

</ul>

                <div class="text-center d-none d-md-inline"><button class="btn rounded-circle border-0" id="sidebarToggle" type="button"></button></div>
            </div>
        </nav>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content">
                <nav class="navbar navbar-light navbar-expand bg-white shadow mb-4 topbar static-top">
                    <div class="container-fluid"><button class="btn btn-link d-md-none rounded-circle me-3" id="sidebarToggleTop" type="button"><i class="fas fa-bars"></i></button>
                        <ul class="navbar-nav flex-nowrap ms-auto">
                            <li class="nav-item dropdown d-sm-none no-arrow"><a class="dropdown-toggle nav-link" aria-expanded="false" data-bs-toggle="dropdown" href="#"><i class="fas fa-search"></i></a>
                                <div class="dropdown-menu dropdown-menu-end p-3 animated--grow-in" aria-labelledby="searchDropdown">
                                    <form class="me-auto navbar-search w-100">
                                        <div class="input-group"><input class="bg-light form-control border-0 small" type="text" placeholder="Search for ...">
                                            <div class="input-group-append"><button class="btn btn-primary py-0" type="button"><i class="fas fa-search"></i></button></div>
                                        </div>
                                    </form>
                                </div>
                            </li>
                            <div class="d-none d-sm-block topbar-divider"></div>
                            <li class="nav-item dropdown d-flex align-items-center">
                                <span class="d-none d-lg-inline me-2 text-gray-600 small">
                                    <?php echo $_SESSION['staff_name'] ?> - <b><?php echo $_SESSION['role'] ?></b>
                                </span>
                            </li>

                            <li class="nav-item dropdown no-arrow">
                                <a class="dropdown-toggle nav-link" href="logout.php">
                                    <span class="d-none d-lg-inline me-2 text-white-600 small btn btn-danger">Log out</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
                
           