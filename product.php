<?php include "includes/header.php";
include './configs/db.php';
$isAdmin = isset($_SESSION['staff_id']); ?>

<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="container">
        <h1 class="text-center mb-5 text-primary fw-bold">Our Products</h1>

        <?php
        $categories = [];
        try {
            $catStmt = $conn->query("SELECT Id, Name FROM Categories ORDER BY Name ASC");
            $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Category Load Error: ' . $e->getMessage() . '</div>';
        }
        ?>

        <form method="GET" class="row row-cols-1 row-cols-md-auto gy-2 gx-3 align-items-end mb-4 bg-white p-4 rounded shadow-sm">
            <div class="col">
                <label for="category" class="form-label">Category</label>
                <select name="category" id="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['Id']) ?>" <?= isset($_GET['category']) && $_GET['category'] == $cat['Id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col">
                <label for="sort" class="form-label">Sort by Price</label>
                <select name="sort" id="sort" class="form-select">
                    <option value="">Default</option>
                    <option value="asc" <?= isset($_GET['sort']) && $_GET['sort'] == 'asc' ? 'selected' : '' ?>>Lowest to Highest</option>
                    <option value="desc" <?= isset($_GET['sort']) && $_GET['sort'] == 'desc' ? 'selected' : '' ?>>Highest to Lowest</option>
                </select>
            </div>

            <div class="col">
                <label for="search" class="form-label">Product Name</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="e.g. Chocolate Cake" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>

            <div class="col text-end">
                <button type="submit" class="btn btn-primary me-2"><i class="bi bi-search"></i> Search</button>
                <a href="product.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="row">
            <?php
            $searchTerm = isset($_GET['search']) ? strtolower($_GET['search']) : '';
            $category = isset($_GET['category']) ? (int)$_GET['category'] : null;
            $sort = $_GET['sort'] ?? null;

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 9;
            $offset = ($page - 1) * $limit;

            try {
                $query = "
                    SELECT p.*, ps.StatusId, s.Name AS StatusName
                    FROM Products p
                    LEFT JOIN ProductStatus ps ON p.Id = ps.ProductId
                    LEFT JOIN Status s ON ps.StatusId = s.Id
                    WHERE ps.Id = (
                        SELECT MAX(ps_inner.Id) FROM ProductStatus ps_inner 
                        WHERE ps_inner.ProductId = p.Id
                    ) AND LOWER(s.Name) = 'active'
                ";

                if ($category) {
                    $query .= " AND p.CategoryId = :category";
                }
                if ($searchTerm) {
                    $query .= " AND LOWER(p.Name) LIKE :searchTerm";
                }

                $query .= match ($sort) {
                    'asc' => " ORDER BY p.Price ASC",
                    'desc' => " ORDER BY p.Price DESC",
                    default => " ORDER BY p.DateCreated DESC",
                };

                $query .= " LIMIT :limit OFFSET :offset";

                $stmt = $conn->prepare($query);

                if ($category) {
                    $stmt->bindParam(':category', $category, PDO::PARAM_INT);
                }
                if ($searchTerm) {
                    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
                }
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

                $stmt->execute();
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($products)) {
                    foreach ($products as $product):
                        $hasDiscount = !empty($product['DiscountPrice']) && $product['DiscountPrice'] > 0;
                        $isOutOfStock = empty($product['Stock']) || $product['Stock'] <= 0;
            ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <img src="./assets/uploads/products/<?= htmlspecialchars($product['ImagePath']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['Name']) ?>" style="height: 200px; object-fit: cover;">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($product['Name']) ?></h5>
                                    <p class="card-text"><?= htmlspecialchars($product['Description']) ?></p>
                                    <p class="card-text">
                                        <?php if ($hasDiscount): ?>
                                            <strong class="text-danger fs-5">$ <?= number_format($product['DiscountPrice'], 2) ?></strong>
                                            <span class="text-muted text-decoration-line-through">$ <?= number_format($product['Price'], 2) ?></span>
                                        <?php else: ?>
                                            <strong class="fs-5">$ <?= number_format($product['Price'], 2) ?></strong>
                                        <?php endif; ?>
                                    </p>
                                    <p class="card-text"><strong>Stock:</strong> <?= intval($product['Stock']) ?></p>
                                    <div class="mt-auto d-flex">
                                        <input type="number" class="form-control me-2 quantity-input" min="1" max="<?= intval($product['Stock']) ?>" value="1" style="width: 70px;" <?= $isOutOfStock ? 'disabled' : '' ?>>
                                        <button class="btn btn-primary add-to-cart"
                                            data-id="<?= htmlspecialchars($product['Id']) ?>"
                                            data-name="<?= htmlspecialchars($product['Name']) ?>"
                                            data-type="product"
                                            data-price="<?= number_format($hasDiscount ? $product['DiscountPrice'] : $product['Price'], 2) ?>"
                                            data-stock="<?= intval($product['Stock']) ?>"
                                            <?= $isOutOfStock ? 'disabled' : '' ?>>
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    endforeach;

                    $stmtCount = $conn->prepare("
                        SELECT COUNT(*) FROM Products p
                        LEFT JOIN ProductStatus ps ON p.Id = ps.ProductId
                        LEFT JOIN Status s ON ps.StatusId = s.Id
                        WHERE ps.Id = (
                            SELECT MAX(ps_inner.Id) FROM ProductStatus ps_inner 
                            WHERE ps_inner.ProductId = p.Id
                        ) AND LOWER(s.Name) = 'active'
                    ");
                    $stmtCount->execute();
                    $totalProducts = $stmtCount->fetchColumn();
                    $totalPages = ceil($totalProducts / $limit);
                    ?>
                    <nav aria-label="Product pagination">
                        <ul class="pagination justify-content-center mt-4">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= htmlspecialchars($_GET['search'] ?? '') ?>&category=<?= htmlspecialchars($_GET['category'] ?? '') ?>&sort=<?= htmlspecialchars($_GET['sort'] ?? '') ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
            <?php
                } else {
                    echo '<div class="alert alert-warning">No products available.</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>
    </div>
</div>


<?php include "includes/footer.php"; ?>