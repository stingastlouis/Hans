<?php 

include '../sessionManagement.php';
include '../configs/constants.php';

$role = $_SESSION['role'];
if (!in_array($role, ALLOWED_EDITOR_ROLES)){
    header("Location: ../unauthorised.php");
    exit;
}

include 'includes/header.php';
include '../configs/db.php';
$success = isset($_GET["success"]) ? $_GET["success"] : null;
$staffId = $_SESSION["staff_id"];
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    $stmt = $conn->prepare("
        SELECT e.*, 
               COALESCE(s.Name, 'No Status') AS LatestStatus, 
               COALESCE(c.Name, 'No Category') AS CategoryName
        FROM Products e
        LEFT JOIN (
            SELECT es.ProductId, MAX(es.Id) AS LatestStatusId
            FROM ProductStatus es
            GROUP BY es.ProductId
        ) latest_es ON e.Id = latest_es.ProductId
        LEFT JOIN ProductStatus es ON latest_es.LatestStatusId = es.Id
        LEFT JOIN Status s ON es.StatusId = s.Id
        LEFT JOIN Categories c ON e.CategoryId = c.Id
        LIMIT :limit OFFSET :offset;
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();  

    $stmt2 = $conn->prepare("SELECT * FROM Status");
    $stmt2->execute();
    $statuses = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $stmt2->closeCursor(); 

    $stmt3 = $conn->prepare("SELECT * FROM Categories");
    $stmt3->execute();
    $categories = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    $stmt3->closeCursor(); 

    $totalCountStmt = $conn->query("SELECT COUNT(*) FROM Products");
    $totalCount = $totalCountStmt->fetchColumn();
    $totalPages = ceil($totalCount / $limit);

} catch (PDOException $e) {
    die("SQL Error: " . $e->getMessage()); 
}
?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Products</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-primary m-0 fw-bold">Product List</p>
            <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    Add Product
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive table mt-2" id="dataTable" role="grid" aria-describedby="dataTable_info">
                <table class="table my-0" id="dataTable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Discount Price</th>
                            <th>Stock</th>
                            <th>Image</th>
                            <th>Latest Status</th>
                            <th>Date Created</th>
                            <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['Id']) ?></td>
                                <td><?= htmlspecialchars($product['Name']) ?></td>
                                <td><?= htmlspecialchars($product['CategoryName']) ?></td>
                                <td><?= htmlspecialchars($product['Description']) ?></td>
                                <td><?= htmlspecialchars($product['Price']) ?></td>
                                <td><?= htmlspecialchars($product['DiscountPrice']) ?></td>
                                <td><?= htmlspecialchars($product['Stock']) ?></td>
                                <td>
                                    <img src="../assets/uploads/<?= htmlspecialchars($product['ImagePath']) ?>" alt="<?= htmlspecialchars($product['Name']) ?>" style="width: 100px; height: auto;">
                                </td>
                                <td><?= htmlspecialchars($product['LatestStatus']) ?: 'No Status' ?></td>
                                <td><?= htmlspecialchars($product['DateCreated']) ?></td>
                                <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
                                <td style="padding:10px">
                                    <button class='btn btn-warning btn-sm edit-product-btn' 
                                        data-id='<?= $product['Id'] ?>' 
                                        data-name='<?= $product['Name'] ?>' 
                                        data-category-id='<?= $product['CategoryId'] ?>' 
                                        data-description='<?= $product['Description'] ?>' 
                                        data-price='<?= $product['Price'] ?>'
                                        data-discount='<?= $product['DiscountPrice'] ?>' 
                                        data-stock='<?= $product['Stock'] ?>'>Edit</button>
                                    <button class="btn btn-danger btn-sm btn-del" data-bs-toggle="modal" data-bs-target="#deleteProductModal" data-id="<?= $product['Id'] ?>">Delete</button>
                                    <form method="POST" action="status/add_productStatus.php" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?= $product['Id'] ?>">
                                        <input type="hidden" name="staff_id" value="<?= $staffId ?>">
                                        <select name="status_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="" disabled selected>Change Status</option>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['Name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <nav>
                <ul class="pagination justify-content-center mt-3">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&success=<?= $success ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&success=<?= $success ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&success=<?= $success ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

        </div>
    </div>
</div>


<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="product/add_product.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="staff_id" value="<?= $staffId ?>">
                    <div class="mb-3">
                        <label for="productName" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="productName" name="product_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="productCategory" class="form-label">Category</label>
                        <select class="form-select" id="productCategory" name="product_category_id" required>
                            <option value="" disabled selected>Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['Id'] ?>"><?= htmlspecialchars($category['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="productDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="productDescription" name="product_description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="productPrice" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="productPrice" name="product_price" required>
                    </div>
                    <div class="mb-3">
                        <label for="productDiscountPrice" class="form-label">Discount Price</label>
                        <input type="number" step="0.01" class="form-control" id="productDiscountPrice" name="product_discount">
                    </div>
                    <div class="mb-3">
                        <label for="productStock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="productStock" name="product_stock" required>
                    </div>
                    <div class="mb-3">
                        <label for="productImage" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="productImage" name="product_image" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProductModalLabel">Delete Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this product?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="product/delete_product.php" method="POST">
                    <input type="hidden" id="productIdToDelete" name="product_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editProductForm" method="POST" action="product/modify.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="editProductId">
                    
                    <div class="mb-3">
                        <label for="editProductName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="editProductName" name="product_name">
                    </div>

                    <div class="mb-3">
                        <label for="editProductCategory" class="form-label">Category</label>
                        <select class="form-select" id="editProductCategory" name="product_category_id">
                            <option value="" disabled>Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['Id'] ?>"><?= htmlspecialchars($category['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="editProductDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editProductDescription" name="product_description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="editProductPrice" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="editProductPrice" name="product_price">
                    </div>

                    <div class="mb-3">
                        <label for="editProductDiscount" class="form-label">Discount Price</label>
                        <input type="number" step="0.01" class="form-control" id="editProductDiscount" name="product_discount">
                    </div>

                    <div class="mb-3">
                        <label for="editProductStock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="editProductStock" name="product_stock">
                    </div>

                    <div class="mb-3">
                        <label for="editProductImage" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="editProductImage" name="product_image" accept="image/*">
                        <small class="form-text text-muted">Leave empty to keep current image</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProductModalLabel">Delete Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this product?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="product/delete_product.php" method="POST">
                    <input type="hidden" id="productIdToDelete" name="product_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.btn-del').forEach(function(button) {
        button.addEventListener('click', function() {
            var productId = this.getAttribute('data-id');
            document.getElementById('productIdToDelete').value = productId;
        });
    });

    document.querySelectorAll('.edit-product-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const categoryId = this.getAttribute('data-category-id');
            const description = this.getAttribute('data-description');
            const price = this.getAttribute('data-price');
            const discount = this.getAttribute('data-discount');
            const stock = this.getAttribute('data-stock');

            document.getElementById('editProductId').value = id;
            document.getElementById('editProductName').value = name;
            document.getElementById('editProductCategory').value = categoryId;
            document.getElementById('editProductDescription').value = description;
            document.getElementById('editProductPrice').value = price;
            document.getElementById('editProductDiscount').value = discount;
            document.getElementById('editProductStock').value = stock;


            const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
            modal.show();
        });
    });
</script>