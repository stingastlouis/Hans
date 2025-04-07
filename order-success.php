<?php
session_start();
if (!isset($_SESSION['orderSuccess']) || $_SESSION['orderSuccess'] !== true) {
    // If not, redirect the user to the checkout page
    header("Location: checkout.php");
    exit();
}

// Clear the session variable after successful access
unset($_SESSION['orderSuccess']);
?>


<?php include './includes/header.php'?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header text-center">
                        <h3 class="mb-0">Order Success</h3>
                    </div>
                    <div class="card-body">
                        <h4 class="text-success">Your order has been successfully placed!</h4>
                        <p class="lead">Thank you for your purchase. Your order is being processed, and you will receive an email confirmation shortly.</p>

                        <div class="mb-4">
                            <h5>Order Summary:</h5>
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <strong>Order ID:</strong> <?= $_SESSION['orderId']; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Payment Method:</strong> PayPal
                                </li>
                                <li class="list-group-item">
                                    <strong>Total Amount:</strong> Rs <?= number_format($_SESSION['totalAmount'], 2); ?>
                                </li>
                            </ul>
                        </div>

                        <p class="text-center">
                            <a href="index.php" class="btn btn-primary">Go to Home</a>
                            <a href="order-history.php" class="btn btn-secondary">View Order History</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        localStorage.removeItem('user-cart');
    </script>
<?php include './includes/footer.php'?>