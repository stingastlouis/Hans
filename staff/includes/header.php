<!DOCTYPE html>
<html lang="en">
<?php
include '../sessionManagement.php';
include '../utils/communicationUtils.php';
include '../utils/notificationModal.php';

require_once '../configs/constants.php';
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
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
            <div class="container-fluid d-flex flex-column p-0">
                <a class="navbar-brand d-flex justify-content-center align-items-center sidebar-brand m-0" href="../staff/">
                    <div class="sidebar-brand-text mx-3">
                        <span class="custom-header-icon bs-icon-sm shadow d-flex justify-content-center align-items-center me-2 bs-icon">
                            <img src="../assets/img/c37a6998-6f32-4fe6-93be-62914755c41f.jpg" width="50" height="50" />
                        </span>
                        <span>Light Store</span>
                    </div>
                </a>
                <hr class="sidebar-divider my-0">
                <?php $role = $_SESSION['role']; ?>

                <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

                <ul class="navbar-nav text-light" id="accordionSidebar">
                    <?php if (in_array($role, ALLOWED_EDITOR_ROLES)): ?>
                        <li class="nav-item custom-nav-item">
                            <a class="nav-link <?= $currentPage === 'index.php' || $currentPage === '' ? 'active' : '' ?>" href="../staff/">
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item custom-nav-item">
                            <a class="nav-link <?= $currentPage === 'category.php' ? 'active' : '' ?>" href="category.php">
                                <span>Category</span>
                            </a>
                        </li>
                        <li class="nav-item custom-nav-item">
                            <a class="nav-link <?= $currentPage === 'role.php' ? 'active' : '' ?>" href="role.php">
                                <span>Role</span>
                            </a>
                        </li>
                        <li class="nav-item custom-nav-item">
                            <a class="nav-link <?= $currentPage === 'bundle.php' ? 'active' : '' ?>" href="bundle.php">
                                <span>Bundle</span>
                            </a>
                        </li>
                        <li class="nav-item custom-nav-item">
                            <a class="nav-link <?= $currentPage === 'product.php' ? 'active' : '' ?>" href="product.php">
                                <span>Product</span>
                            </a>
                        </li>
                        <li class="nav-item custom-nav-item">
                            <a class="nav-link <?= $currentPage === 'staff.php' ? 'active' : '' ?>" href="staff.php">
                                <span>Staff</span>
                            </a>
                        </li>
                        <li class="nav-item custom-nav-item">
                            <a class="nav-link <?= $currentPage === 'order.php' ? 'active' : '' ?>" href="order.php">
                                <span>Order</span>
                            </a>
                        </li>
                        <li class="nav-item custom-nav-item">
                            <a class="nav-link <?= $currentPage === 'event.php' ? 'active' : '' ?>" href="event.php">
                                <span>Event</span>
                            </a>
                        </li>
                        <li class="nav-item custom-nav-item">
                            <a class="nav-link <?= $currentPage === 'customer.php' ? 'active' : '' ?>" href="customer.php">
                                <span>Customer</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (in_array($role, INSTALLER_ONLY_ROLE)): ?>
                        <li class="nav-item custom-nav-item">
                            <a class="nav-link <?= $currentPage === 'installation.php' ? 'active' : '' ?>" href="installation.php">
                                <span>Installation</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>


                <div class="text-center d-none d-md-inline">
                    <button class="btn rounded-circle border-0" id="sidebarToggle" type="button"></button>
                </div>
            </div>
        </nav>

        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content">
                <nav class="navbar navbar-light navbar-expand bg-white shadow mb-4 topbar static-top">
                    <div class="container-fluid">
                        <button class="btn btn-link d-md-none rounded-circle me-3" id="sidebarToggleTop" type="button"><i class="fas fa-bars"></i></button>
                        <ul class="navbar-nav flex-nowrap ms-auto">
                            <?php
                            $queryCount = 0;
                            try {
                                require_once '../configs/db.php';
                                $stmt = $conn->query("SELECT COUNT(*) FROM Query WHERE Seen = FALSE");
                                $queryCount = $stmt->fetchColumn();
                            } catch (PDOException $e) {
                                $queryCount = 0;
                            }
                            ?>
                            <?php if (in_array($role, ALLOWED_EDITOR_ROLES)): ?>
                                <li class="nav-item dropdown no-arrow mx-1">
                                    <a class="nav-link dropdown-toggle" href="../admin-messages.php" id="alertsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-bell fa-fw"></i>
                                        <span class="badge bg-danger badge-counter"><?php echo $queryCount; ?></span>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="alertsDropdown">
                                        <h6 class="dropdown-header">New Messages</h6>
                                        <?php if ($queryCount > 0): ?>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item d-flex align-items-center" href="./admin-messages.php">
                                                <div>You have <?php echo $queryCount; ?> new message<?php echo $queryCount != 1 ? 's' : ''; ?>.</div>
                                            </a>
                                        <?php else: ?>
                                            <div class="dropdown-item text-center">No new messages</div>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endif; ?>
                            <div class="d-none d-sm-block topbar-divider"></div>
                            <li class="nav-item dropdown d-flex align-items-center">
                                <span class=" d-lg-inline me-2 text-gray-600 small">
                                    <?php echo $_SESSION['staff_name'] ?> - <b><?php echo $_SESSION['role'] ?></b>
                                </span>
                            </li>
                            <li class="nav-item dropdown no-arrow">
                                <a class="dropdown-toggle nav-link" href="logout.php">
                                    <span class=" d-lg-inline me-2 text-white-600 small btn btn-danger">Log out</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>