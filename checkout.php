<?php
include './configs/db.php';
session_start();
if (!isset($_SESSION['customerId'])) {
    header("Location: login.php");
    exit(); 
}
$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$customerId = $_SESSION['customerId']; 
$paymentMethodId = 1;

$totalAmount = 0;
$taxRate = 0.15;
foreach ($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}
$tax = $totalAmount * $taxRate;
$grandTotal = $totalAmount + $tax;
?>

<?php include './includes/header.php'?>
    <div class="container mt-5">
        <h1 class="mb-4">Checkout</h1>
        <?php if (!empty($cartItems)): ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cartItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= ucfirst(htmlspecialchars($item['type'])) ?></td> <!-- Shows the Type -->
                    <td><?= intval($item['quantity']) ?></td>
                    <td>Rs <?= number_format($item['price'], 2) ?></td>
                    <td>Rs <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-end">Tax (<?= $taxRate * 100 ?>%)</th>
                <th>Rs <?= number_format($tax, 2) ?></th>
            </tr>
            <tr>
                <th colspan="4" class="text-end">Total</th>
                <th>Rs <?= number_format($grandTotal, 2) ?></th>
            </tr>
        </tfoot>
    </table>

    <?php if (isset($_SESSION['customerId'])): ?>
        <form id="checkout-form">
            <input type="hidden" name="paymentMethodId" value="<?= $paymentMethodId ?>">
            <input type="hidden" name="cartItems" value='<?= json_encode($cartItems) ?>'>
            <div id="paypal-button-container"></div>
        </form>
    <?php else: ?>
        <a href="login.php" class="btn btn-warning btn-lg">Log in to Checkout</a>
    <?php endif; ?>

<?php else: ?>
    <p class="alert alert-warning">Your cart is empty!</p>
<?php endif; ?>

    </div>

<script src="https://www.paypal.com/sdk/js?client-id=AYDMJVEgkRqU66bGWK-uzYtGKsJsLzVfx5OSKIn2j6y_tISbzHdvhEbyDXFU5dngERPjuoT1AUvRVygB&currency=USD"></script>

<script>
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '<?= number_format($grandTotal, 2, '.', '') ?>' // Grand total
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                fetch('./processCheckout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        paymentMethodId: <?= $paymentMethodId ?>,
                        cartItems: <?= json_encode($cartItems) ?>,
                        transactionId: details.id,  
                        amount: details.purchase_units[0].amount.value
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        window.location.href = 'order-success.php';
                    } else {
                        alert('Error: ' + result.message);
                    }
                });
            });
        }
    }).render('#paypal-button-container');
</script>

<?php include './includes/footer.php'?>
