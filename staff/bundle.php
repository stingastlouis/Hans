<?php
include '../sessionManagement.php';
include '../configs/constants.php';

$role = $_SESSION['role'];
if (!in_array($role, ALLOWED_EDITOR_ROLES)) {
    header("Location: ../unauthorised.php");
    exit;
}

include 'includes/header.php';
include '../configs/db.php';

$success = isset($_GET["success"]) ? $_GET["success"] : null;
$staffId = $_SESSION["staff_id"];

$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$totalStmt = $conn->query("SELECT COUNT(*) FROM Bundle");
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $conn->prepare("
    SELECT e.*, 
           s.Name AS LatestStatus, 
           GROUP_CONCAT(CONCAT(p.Name, ' (', ep.Quantity, ')') SEPARATOR ', ') AS ProductDetails
    FROM Bundle e
    LEFT JOIN (
        SELECT es.BundleId, MAX(es.Id) AS LatestStatusId
        FROM BundleStatus es
        GROUP BY es.BundleId
    ) latest_es ON e.Id = latest_es.BundleId
    LEFT JOIN BundleStatus es ON latest_es.LatestStatusId = es.Id
    LEFT JOIN Status s ON es.StatusId = s.Id
    LEFT JOIN BundleProducts ep ON e.Id = ep.BundleId
    LEFT JOIN Products p ON ep.ProductId = p.Id
    GROUP BY e.Id, s.Name
    ORDER BY e.Id DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$bundles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $conn->prepare("SELECT * FROM Status WHERE Name IN ('ACTIVE','INACTIVE')");
$stmt2->execute();
$statuses = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$productQuery = $conn->prepare("SELECT Id, Name FROM Products");
$productQuery->execute();
$prods = $productQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Bundles</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-primary m-0 fw-bold">Bundle List</p>
            <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBundleModal">Add Bundle</button>
            <?php endif ?>
        </div>
        <div class="card-body">
            <div class="table-responsive table mt-2" id="dataTable" role="grid">
                <table class="table my-0">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Discount Price</th>
                            <th>Image</th>
                            <th>Latest Status</th>
                            <th>Products</th>
                            <th>Date Created</th>
                            <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bundles as $bundle): ?>
                            <tr>
                                <td><?= htmlspecialchars($bundle['Id']) ?></td>
                                <td><?= htmlspecialchars($bundle['Name']) ?></td>
                                <td><?= htmlspecialchars($bundle['Description']) ?></td>
                                <td><?= htmlspecialchars($bundle['Price']) ?></td>
                                <td><?= htmlspecialchars($bundle['DiscountPrice']) ?></td>
                                <td>
                                    <img src="../assets/uploads/bundles/<?= htmlspecialchars($bundle['ImagePath']) ?>" alt="<?= htmlspecialchars($bundle['Name']) ?>" style="width: 100px; height: auto;">
                                </td>
                                <td><?= htmlspecialchars($bundle['LatestStatus']) ?: 'No Status' ?></td>
                                <td><?= htmlspecialchars($bundle['ProductDetails']) ?: 'No products linked' ?></td>
                                <td><?= htmlspecialchars($bundle['DateCreated']) ?></td>
                                <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
                                    <td>
                                        <button class='btn btn-warning btn-sm edit-bundle-btn'
                                            data-id='<?= $bundle['Id'] ?>'
                                            data-name='<?= $bundle['Name'] ?>'
                                            data-description='<?= $bundle['Description'] ?>'
                                            data-price='<?= $bundle['Price'] ?>'
                                            data-discount='<?= $bundle['DiscountPrice'] ?>'>Edit</button>
                                        <button style="font-size: 12px;" class="btn btn-danger btn-del" data-bs-toggle="modal" data-bs-target="#deleteBundleModal" data-id="<?= $bundle['Id'] ?>">Delete</button>
                                        <form method="POST" action="status/add_bundleStatus.php" style="display: inline;">
                                            <input type="hidden" name="bundle_id" value="<?= $bundle['Id'] ?>">
                                            <input type="hidden" name="staff_id" value="<?= $staffId ?>">
                                            <select name="status_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="" disabled selected>Change Status</option>
                                                <?php foreach ($statuses as $status): ?>
                                                    <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['Name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                <?php endif ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <nav class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item<?= ($i == $page) ? ' active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>


<div class="modal fade" id="addBundleModal" tabindex="-1" aria-labelledby="addBundleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBundleModalLabel">Add Bundle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="bundle/add_bundle.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="staff_id" value="<?= $staffId ?>">
                    <div class="mb-3">
                        <label for="bundleName" class="form-label">Bundle Name</label>
                        <input type="text" class="form-control" id="bundleName" name="bundle_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="bundleDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="bundleDescription" name="bundle_description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="bundlePrice" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="bundlePrice" name="bundle_price" required>
                    </div>
                    <div class="mb-3">
                        <label for="bundleDiscountPrice" class="form-label">Discount Price</label>
                        <input type="number" step="0.01" class="form-control" id="bundleDiscountPrice" name="bundle_discount_price">
                    </div>
                    <div class="mb-3">
                        <label for="bundleImage" class="form-label">Bundle Image</label>
                        <input type="file" class="form-control" id="bundleImage" name="bundle_image" accept="image/*" required>
                    </div>

                    <div class="mb-3">
                        <label for="productSelect" class="form-label">Add Product to Bundle</label>

                        <div class="d-flex mb-2 align-items-center">
                            <select id="productSelect" class="form-select me-2" style="max-width: 300px;">
                                <option value="">-- Select Product --</option>
                                <?php foreach ($prods as $product): ?>
                                    <option value="<?php echo $product['Id']; ?>"><?php echo htmlspecialchars($product['Name']); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <input type="number" id="productQuantity" class="form-control me-2" style="width: 100px;" min="1" value="1" placeholder="Quantity">
                            <button type="button" class="btn btn-primary" onclick="addProduct()">Add</button>
                        </div>

                        <div id="addedProductsList"></div>
                    </div>

                    <script>
                        const products = <?php echo json_encode($prods); ?>;
                        const addedProducts = new Map();

                        function addProduct() {
                            const select = document.getElementById('productSelect');
                            const quantity = document.getElementById('productQuantity').value;
                            const productId = select.value;
                            const productName = select.options[select.selectedIndex]?.text;

                            if (!productId || quantity < 1) {
                                alert("Please select a product and a valid quantity.");
                                return;
                            }

                            if (addedProducts.has(productId)) {
                                alert("Product already added.");
                                return;
                            }

                            addedProducts.set(productId, quantity);
                            const container = document.getElementById('addedProductsList');

                            const wrapper = document.createElement('div');
                            wrapper.className = 'd-flex align-items-center mb-2';
                            wrapper.id = `product_row_${productId}`;
                            wrapper.innerHTML = `
            <input type="hidden" name="product_ids[]" value="${productId}">
            <input type="hidden" name="quantities[]" value="${quantity}">
            <span class="me-2">${productName}</span>
            <span class="me-2">Qty: ${quantity}</span>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeProduct('${productId}')">Remove</button>
        `;
                            container.appendChild(wrapper);
                            select.value = "";
                            document.getElementById('productQuantity').value = 1;
                        }

                        function removeProduct(productId) {
                            addedProducts.delete(productId);
                            const row = document.getElementById(`product_row_${productId}`);
                            if (row) row.remove();
                        }
                    </script>


                    <button type="submit" class="btn btn-primary">Add Bundle</button>
                </form>
            </div>
        </div>
    </div>
</div>



<div class="modal fade" id="deleteBundleModal" tabindex="-1" aria-labelledby="deleteBundleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteBundleModalLabel">Delete Bundle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this bundle?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="bundle/delete_bundle.php" method="POST">
                    <input type="hidden" id="bundleIdToDelete" name="bundle_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="./utils/message.js"></script>
<script>
    document.addBundleListener('DOMContentLoaded', handleSuccessOrErrorModal);
</script>

<?php include 'includes/footer.php'; ?>

<script>
    var deleteButtons = document.querySelectorAll('.btn-del');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var bundleId = this.getAttribute('data-id');
            document.getElementById('bundleIdToDelete').value = bundleId;
        });
    });
</script>



<div class="modal fade" id="editBundleModal" tabindex="-1" aria-labelledby="editBundleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editBundleForm" action="bundle/edit_bundle.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBundleModalLabel">Edit Bundle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="bundle_id" id="editBundleId">
                    <input type="hidden" name="staff_id" value="<?= $staffId ?>">

                    <div class="mb-3">
                        <label class="form-label">Bundle Name</label>
                        <input type="text" class="form-control" id="editBundleName" name="bundle_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="editBundleDescription" name="bundle_description" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="editBundlePrice" name="bundle_price" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Discount Price</label>
                        <input type="number" step="0.01" class="form-control" id="editBundleDiscountPrice" name="bundle_discount_price">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Replace Image</label>
                        <input type="file" class="form-control" name="bundle_image" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Edit Products</label>
                        <div class="d-flex mb-2 align-items-center">
                            <select id="editProductSelect" class="form-select me-2" style="max-width: 300px;">
                                <option value="">-- Select Product --</option>
                                <?php foreach ($prods as $product): ?>
                                    <option value="<?= $product['Id'] ?>"><?= htmlspecialchars($product['Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" id="editProductQuantity" class="form-control me-2" style="width: 100px;" min="1" value="1">
                            <button type="button" class="btn btn-primary" onclick="addEditProduct()">Add</button>
                        </div>

                        <div id="editAddedProductsList"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Update Bundle</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    const editProducts = new Map();

    function addEditProduct() {
        const select = document.getElementById('editProductSelect');
        const quantity = document.getElementById('editProductQuantity').value;
        const productId = select.value;
        const productName = select.options[select.selectedIndex]?.text;

        if (!productId || quantity < 1) {
            alert("Please select a product and a valid quantity.");
            return;
        }

        if (editProducts.has(productId)) {
            alert("Product already added.");
            return;
        }

        editProducts.set(productId, quantity);

        const container = document.getElementById('editAddedProductsList');
        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex align-items-center mb-2';
        wrapper.id = `edit_product_row_${productId}`;
        wrapper.innerHTML = `
            <input type="hidden" name="product_ids[]" value="${productId}">
            <input type="hidden" name="quantities[]" value="${quantity}">
            <span class="me-2">${productName}</span>
            <span class="me-2">Qty: ${quantity}</span>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeEditProduct('${productId}')">Remove</button>
        `;
        container.appendChild(wrapper);

        select.value = "";
        document.getElementById('editProductQuantity').value = 1;
    }

    function removeEditProduct(productId) {
        editProducts.delete(productId);
        document.getElementById(`edit_product_row_${productId}`).remove();
    }

    document.querySelectorAll('.edit-bundle-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            const desc = btn.getAttribute('data-description');
            const price = btn.getAttribute('data-price');
            const discount = btn.getAttribute('data-discount');

            document.getElementById('editBundleId').value = id;
            document.getElementById('editBundleName').value = name;
            document.getElementById('editBundleDescription').value = desc;
            document.getElementById('editBundlePrice').value = price;
            document.getElementById('editBundleDiscountPrice').value = discount;

            document.getElementById('editAddedProductsList').innerHTML = '';
            editProducts.clear();
            fetch(`bundle/fetch_bundle_products.php?bundle_id=${id}`)
                .then(res => res.json())
                .then(data => {
                    data.forEach(prod => {
                        editProducts.set(prod.ProductId, prod.Quantity);

                        const wrapper = document.createElement('div');
                        wrapper.className = 'd-flex align-items-center mb-2';
                        wrapper.id = `edit_product_row_${prod.ProductId}`;
                        wrapper.innerHTML = `
                            <input type="hidden" name="product_ids[]" value="${prod.ProductId}">
                            <input type="hidden" name="quantities[]" value="${prod.Quantity}">
                            <span class="me-2">${prod.Name}</span>
                            <span class="me-2">Qty: ${prod.Quantity}</span>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeEditProduct('${prod.ProductId}')">Remove</button>
                        `;
                        document.getElementById('editAddedProductsList').appendChild(wrapper);
                    });
                });

            const modal = new bootstrap.Modal(document.getElementById('editBundleModal'));
            modal.show();
        });
    });
</script>