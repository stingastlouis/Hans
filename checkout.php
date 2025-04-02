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
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cartItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= intval($item['quantity']) ?></td>
                    <td>Rs <?= number_format($item['price'], 2) ?></td>
                    <td>Rs <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Tax (<?= $taxRate * 100 ?>%)</th>
                <th>Rs <?= number_format($tax, 2) ?></th>
            </tr>
            <tr>
                <th colspan="3" class="text-end">Total</th>
                <th>Rs <?= number_format($grandTotal, 2) ?></th>
            </tr>
        </tfoot>
    </table>

    <?php if (isset($_SESSION['customerId'])): ?>
        <form id="checkout-form">
            <input type="hidden" name="customerId" value="<?= $customerId ?>">
            <input type="hidden" name="paymentMethodId" value="<?= $paymentMethodId ?>">
            <input type="hidden" name="cartItems" value='<?= json_encode($cartItems) ?>'>
            <button type="button" id="complete-process" class="btn btn-primary btn-lg">Complete Process</button>
        </form>
    <?php else: ?>
        <a href="login.php" class="btn btn-warning btn-lg">Log in to Checkout</a>
    <?php endif; ?>

<?php else: ?>
    <p class="alert alert-warning">Your cart is empty!</p>
<?php endif; ?>

    </div>

    <script>
        document.getElementById('complete-process').addEventListener('click', async () => {
            const form = document.getElementById('checkout-form');
            const formData = new FormData(form);
            const data = {
                customerId: formData.get('customerId'),
                paymentMethodId: formData.get('paymentMethodId'),
                cartItems: JSON.parse(formData.get('cartItems'))
            };

            const response = await fetch('./processCheckout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                alert('Order placed successfully! Order ID: ' + result.orderId);
                window.location.href = 'order-success.php';
            } else {
                alert('Error: ' + result.message);
            }
        });
    </script>

<?php include './includes/footer.php'?>