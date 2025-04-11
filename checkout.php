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

<?php include './includes/header.php' ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />

<div class="container mt-5">
    <h1 class="mb-4">Checkout</h1>
    <div id="cart-table-container">
        <?php if (!empty($cartItems)): ?>
            <table id="tablecost" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody id="cart-body">
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= ucfirst(htmlspecialchars($item['type'])) ?></td>
                            <td><?= intval($item['quantity']) ?></td>
                            <td>Rs <?= number_format($item['price'], 2) ?></td>
                            <td>Rs <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot id="cart-footer">
                    <tr>
                        <th colspan="4" class="text-end">Tax (<?= $taxRate * 100 ?>%)</th>
                        <th id="tax-cell">Rs <?= number_format($tax, 2) ?></th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th id="total-cell">Rs <?= number_format($grandTotal, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        <?php else: ?>
            <p class="alert alert-warning">Your cart is empty!</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($cartItems)): ?>
    <form id="checkout-form">
        <input type="hidden" name="paymentMethodId" value="<?= $paymentMethodId ?>">
        <input type="hidden" name="lat" id="lat">
        <input type="hidden" name="lng" id="lng">

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="installCheckbox">
            <label class="form-check-label" for="installCheckbox">
                Installation required (+Rs 20.00)
            </label>
        </div>

        <div id="map" style="height: 300px; display: none;" class="mb-3"></div>
        <p id="addressDisplay" class="mt-2 text-muted"></p>

        <div id="paypal-button-container"></div>
    </form>
    <?php endif; ?>
</div>

<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script src="https://www.paypal.com/sdk/js?client-id=AYDMJVEgkRqU66bGWK-uzYtGKsJsLzVfx5OSKIn2j6y_tISbzHdvhEbyDXFU5dngERPjuoT1AUvRVygB&currency=USD"></script>

<script>
    const installCheckbox = document.getElementById('installCheckbox');
    const mapContainer = document.getElementById('map');
    const cartBody = document.getElementById('cart-body');
    const taxCell = document.getElementById('tax-cell');
    const totalCell = document.getElementById('total-cell');

    let map, marker;
    let installAdded = false;

    const baseTotal = <?= $totalAmount ?>;
    const baseTax = <?= $tax ?>;
    const baseGrandTotal = <?= $grandTotal ?>;

    installCheckbox.addEventListener('change', function () {
        if (this.checked && !installAdded) {
            installAdded = true;
            const row = document.createElement('tr');
            row.id = 'install-row';
            row.innerHTML = `
                <td>Installation Cost</td>
                <td>Installation</td>
                <td>1</td>
                <td>Rs 20.00</td>
                <td>Rs 20.00</td>
            `;
            cartBody.appendChild(row);
        } else if (!this.checked && installAdded) {
            installAdded = false;
            const row = document.getElementById('install-row');
            if (row) row.remove();
        }
        updateTotals();
        toggleMap(this.checked);
    });

    function updateTotals() {
        const installCost = installAdded ? 20 : 0;
        const newTotal = baseTotal + installCost;
        const newTax = newTotal * <?= $taxRate ?>;
        const newGrand = newTotal + newTax;
        taxCell.textContent = `Rs ${newTax.toFixed(2)}`;
        totalCell.textContent = `Rs ${newGrand.toFixed(2)}`;
    }

    function toggleMap(show) {
        mapContainer.style.display = show ? 'block' : 'none';
        if (show) {
            setTimeout(() => {
                if (!map) {
                    map = L.map('map').setView([-20.3484, 57.5522], 9);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);

                    map.on('click', function (e) {
                        const { lat, lng } = e.latlng;
                        if (marker) {
                            marker.setLatLng(e.latlng);
                        } else {
                            marker = L.marker(e.latlng).addTo(map);
                        }
                        document.getElementById('lat').value = lat;
                        document.getElementById('lng').value = lng;

                        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
                            .then(res => res.json())
                            .then(data => {
                                document.getElementById('addressDisplay').textContent = `Selected address: ${data.display_name}`;
                            })
                            .catch(err => {
                                document.getElementById('addressDisplay').textContent = 'Unable to fetch address.';
                            });
                    });
                } else {
                    map.invalidateSize();
                }
            }, 100);
        }
    }

    paypal.Buttons({
        createOrder: function (data, actions) {
            let amount = baseGrandTotal;
            if (installAdded) {
                const updatedTotal = baseTotal + 20;
                const updatedTax = updatedTotal * <?= $taxRate ?>;
                amount = updatedTotal + updatedTax;
            }
            return actions.order.create({
                purchase_units: [{
                    amount: { value: amount.toFixed(2) }
                }]
            });
        },
        onApprove: function (data, actions) {
            return actions.order.capture().then(function (details) {
                const lat = document.getElementById('lat').value;
                const lng = document.getElementById('lng').value;

                const payload = {
                    paymentMethodId: <?= $paymentMethodId ?>,
                    cartItems: <?= json_encode($cartItems) ?>,
                    transactionId: details.id,
                    amount: details.purchase_units[0].amount.value,
                    latLng: `${lat},${lng}`,
                    installationRequired: installAdded
                };

                fetch('./processCheckout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(result => {
                    if (result.success) {
                        window.location.href = 'order-success.php';
                    } else {
                        alert('Checkout failed: ' + result.message);
                    }
                });
            });
        }
    }).render('#paypal-button-container');
</script>

<?php include './includes/footer.php' ?>
