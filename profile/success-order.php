<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['cart']);
?>
<script>
    localStorage.removeItem("lightstore-cart");
</script>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
    <meta http-equiv="refresh" content="5;url=../profile.php#order-history">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="modal fade show d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-success">Order Confirmed</h5>
                </div>
                <div class="modal-body text-center">
                    <p class="fs-5">Your order has been placed successfully.</p>
                    <p class="text-muted">Please wait while we confirm it. Redirecting...</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <div class="spinner-border text-success" role="status" aria-hidden="true"></div>
                    <small class="text-muted ms-2">Redirecting in 5 seconds...</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>