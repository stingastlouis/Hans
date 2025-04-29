<?php
session_start();
if (!isset($_SESSION['orderSuccess']) || $_SESSION['orderSuccess'] !== true) {
    header("Location: checkout.php");
    exit();
}

unset($_SESSION['orderSuccess']);
?>
<?php include './includes/header.php' ?>
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
                                <strong>Order ID:</strong> <span id="orderId"></span>
                            </li>
                            <li class="list-group-item">
                                <strong>Payment Method:</strong> PayPal
                            </li>
                            <li class="list-group-item">
                                <strong>Total Amount:</strong> Rs <span id="totalAmount"></span>
                            </li>
                        </ul>
                    </div>

                    <p class="text-center">
                        <a href="index.php" class="btn btn-primary">Go to Home</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const orderId = sessionStorage.getItem('orderId');
    const paypalTransaction = sessionStorage.getItem('paypalTransaction');
    const totalAmount = sessionStorage.getItem('total');
    console.log('jee',paypalTransaction, totalAmount)
    if (orderId && totalAmount) {
        document.getElementById('orderId').textContent = orderId;
        document.getElementById('totalAmount').textContent = parseFloat(totalAmount).toFixed(2);
    }

    window.onbeforeunload = function() {
        sessionStorage.removeItem('orderId');
        sessionStorage.removeItem('paypalTransaction');
        sessionStorage.removeItem('total');
    };
</script>

<?php include './includes/footer.php' ?>
