<?php
// Include database connection
include './configs/db.php';

// Example Cart Data (In a real-world scenario, fetch this from a session or database)
session_start();
$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$customerId = $_SESSION['customerId'] ?? 1; // Replace with actual logged-in customer ID
$paymentMethodId = 1; // Default payment method

// Calculate Totals
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
                            <td>$<?= number_format($item['price'], 2) ?></td>
                            <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Tax (<?= $taxRate * 100 ?>%)</th>
                        <th>$<?= number_format($tax, 2) ?></th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">Total</th>
                        <th>$<?= number_format($grandTotal, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
            <form id="checkout-form">
                <input type="hidden" name="customerId" value="<?= $customerId ?>">
                <input type="hidden" name="paymentMethodId" value="<?= $paymentMethodId ?>">
                <input type="hidden" name="cartItems" value='<?= json_encode($cartItems) ?>'>
                <button type="button" id="complete-process" class="btn btn-primary btn-lg">Complete Process</button>
            </form>
        <?php else: ?>
            <p class="alert alert-warning">Your cart is empty!</p>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('complete-process').addEventListener('click', async () => {
            const form = document.getElementById('checkout-form');
            const formData = new FormData(form);

            // Convert form data to JSON
            const data = {
                customerId: formData.get('customerId'),
                paymentMethodId: formData.get('paymentMethodId'),
                cartItems: JSON.parse(formData.get('cartItems'))
            };

            // Send data to the backend
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