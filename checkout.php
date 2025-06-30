<?php
include './configs/db.php';
session_start();

if (!isset($_SESSION['customerId'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT Id, Name FROM PaymentMethod");
$stmt->execute();
$paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$customerId = $_SESSION['customerId'];


$totalAmount = 0;

foreach ($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}
?>

<?php include './includes/header.php' ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />

<div class="container my-5">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h1 class="card-title mb-4 text-center">Checkout</h1>

            <div id="cart-table-container">
                <?php if (!empty($cartItems)): ?>
                    <div class="table-responsive">
                        <table id="tablecost" class="table table-striped align-middle text-center">
                            <thead class="table-dark">
                                <tr>
                                    <th>Product Name</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Unit Price (USD)</th>
                                    <th>Subtotal (USD)</th>
                                </tr>
                            </thead>
                            <tbody id="cart-body">
                                <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= ucfirst(htmlspecialchars($item['type'])) ?></td>
                                        <td><?= intval($item['quantity']) ?></td>
                                        <td><?= number_format($item['price'], 2) ?></td>
                                        <td><?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot id="cart-footer">
                                <tr class="fw-bold">
                                    <td colspan="4" class="text-end">Total</td>
                                    <td id="total-cell">USD <?= number_format($totalAmount, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center">Your cart is empty!</div>
                <?php endif; ?>
            </div>

            <?php if (!empty($cartItems)): ?>
                <hr class="my-4">
                <form id="checkout-form" enctype="multipart/form-data" method="post" action="processCheckout.php">

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="installCheckbox" name="installationRequired">
                        <label class="form-check-label" for="installCheckbox">Installation required (+ USD 20.00)</label>
                    </div>

                    <div id="installationDateContainer" class="mb-4" style="display: none;">
                        <label for="installationDate" class="form-label">Installation Date</label>
                        <input type="date" id="installationDate" name="installationDate" class="form-control" />
                    </div>

                    <div id="map" class="mb-3 rounded shadow-sm" style="height: 300px; display: none;"></div>
                    <p id="addressDisplay" class="text-muted fst-italic"></p>

                    <input type="hidden" name="totalAmount" id="totalAmountInput" value="<?= number_format($totalAmount, 2, '.', '') ?>">
                    <input type="hidden" name="lat" id="lat">
                    <input type="hidden" name="lng" id="lng">

                    <div class="mb-4">
                        <label for="paymentMethod" class="form-label">Payment Method</label>
                        <select name="paymentMethodId" id="paymentMethod" class="form-select" required>
                            <option value="" disabled selected>-- Choose Payment Method --</option>
                            <?php foreach ($paymentMethods as $method): ?>
                                <option value="<?= htmlspecialchars($method['Id']) ?>">
                                    <?= htmlspecialchars($method['Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="paypal-button-container" class="mb-4" style="display: none;"></div>

                    <div id="upload-screenshot-container" class="mb-4" style="display: none;">
                        <label for="paymentScreenshot" class="form-label">Upload Payment Screenshot</label>
                        <input type="file" name="paymentScreenshot" id="paymentScreenshot" accept="image/*" class="form-control" required />
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="bi bi-cart-check me-2"></i>Place Order
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>


<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script src="https://www.paypal.com/sdk/js?client-id=AYDMJVEgkRqU66bGWK-uzYtGKsJsLzVfx5OSKIn2j6y_tISbzHdvhEbyDXFU5dngERPjuoT1AUvRVygB&currency=USD"></script>

<script>
    function setMinInstallationDate() {
        const installationDateInput = document.getElementById('installationDate');
        const today = new Date();
        // Add 2 days
        today.setDate(today.getDate() + 2);

        // Format date as yyyy-mm-dd
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');

        const minDate = `${yyyy}-${mm}-${dd}`;
        installationDateInput.min = minDate;
    }

    setMinInstallationDate();
    const paymentMethodSelect = document.getElementById('paymentMethod');
    const paypalContainer = document.getElementById('paypal-button-container');
    const uploadContainer = document.getElementById('upload-screenshot-container');
    const submitBtn = document.getElementById('submitBtn');

    paymentMethodSelect.addEventListener('change', () => {
        const selected = paymentMethodSelect.options[paymentMethodSelect.selectedIndex].text.toLowerCase();

        if (selected === 'paypal') {
            paypalContainer.style.display = 'block';
            uploadContainer.style.display = 'none';
            submitBtn.style.display = 'none';
        } else if (selected === 'online payment') {
            paypalContainer.style.display = 'none';
            uploadContainer.style.display = 'block';
            submitBtn.style.display = 'inline-block';
        } else {
            paypalContainer.style.display = 'none';
            uploadContainer.style.display = 'none';
            submitBtn.style.display = 'inline-block';
        }
    });




    const installCheckbox = document.getElementById('installCheckbox');
    const installationDateContainer = document.getElementById('installationDateContainer');
    const installationDateInput = document.getElementById('installationDate');
    const mapContainer = document.getElementById('map');
    const cartBody = document.getElementById('cart-body');
    const totalCell = document.getElementById('total-cell');

    let map, marker;
    let installAdded = false;

    const baseTotal = <?= $totalAmount ?>;

    installCheckbox.addEventListener('change', function() {
        if (installCheckbox.checked) {
            installationDateContainer.style.display = 'block';
            installationDateInput.required = true;
        } else {
            installationDateContainer.style.display = 'none';
            installationDateInput.required = false;
            installationDateInput.value = '';
        }

        if (this.checked && !installAdded) {
            installAdded = true;
            const row = document.createElement('tr');
            row.id = 'install-row';
            row.innerHTML = `
                <td>Installation Cost</td>
                <td>Installation</td>
                <td>1</td>
                <td>Usd 20.00</td>
                <td>Usd 20.00</td>
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
        totalCell.textContent = `Usd ${newTotal.toFixed(2)}`;
        document.getElementById('totalAmountInput').value = newTotal.toFixed(2);
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

                    map.on('click', function(e) {
                        const {
                            lat,
                            lng
                        } = e.latlng;
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
        createOrder: function(data, actions) {
            let amount = baseTotal;
            if (installAdded) {
                amount += 20;
            }
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: amount.toFixed(2)
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                const lat = document.getElementById('lat').value;
                const lng = document.getElementById('lng').value;
                const paymentMethodId = document.getElementById('paymentMethod').value;
                const date = document.getElementById('installationDate').value;
                const payload = {
                    paymentMethodId: paymentMethodId,
                    installationDate: date,
                    cartItems: <?= json_encode($cartItems) ?>,
                    transactionId: details.id,
                    amount: details.purchase_units[0].amount.value,
                    latLng: `${lat},${lng}`,
                    installationRequired: installAdded
                };

                fetch('./processCheckout.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(data => {

                        if (data.success) {
                            window.location.href = './profile/success-order.php';
                        } else {
                            alert('Checkout failed: ' + data.message);
                        }
                    });
            });
        }
    }).render('#paypal-button-container');
</script>

<?php include './includes/footer.php' ?>