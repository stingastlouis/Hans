<?php include "includes/header.php"; include './configs/db.php'; $isAdmin = isset($_SESSION['staff_id']); ?>

<div class="container py-4">
    <h1 class="text-center mb-4">Product Page</h1>

    <?php
    $categories = [];
    try {
        $catStmt = $conn->query("SELECT Id, Name FROM Categories ORDER BY Name ASC");
        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo 'Category Load Error: ' . $e->getMessage();
    }
    ?>

    <div class="container py-4">
        <form method="GET" class="row mb-4 g-2 align-items-end">
            <div class="col-md-2">
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

            <div class="col-md-2">
                <label for="sort" class="form-label">Sort by Price</label>
                <select name="sort" id="sort" class="form-select">
                    <option value="">Default</option>
                    <option value="asc" <?= isset($_GET['sort']) && $_GET['sort'] == 'asc' ? 'selected' : '' ?>>Lowest to Highest</option>
                    <option value="desc" <?= isset($_GET['sort']) && $_GET['sort'] == 'desc' ? 'selected' : '' ?>>Highest to Lowest</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="search" class="form-label">Product Name</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="e.g. Chocolate Cake" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>

            <div class="col-2 text-end">
                <a href="product.php" class="btn btn-secondary">Reset</a>
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <div class="row">

    <?php
    $searchTerm = isset($_GET['search']) ? strtolower($_GET['search']) : ''; 
    $category = isset($_GET['category']) ? (int)$_GET['category'] : null;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : null;

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 9;
    $offset = ($page - 1) * $limit;

    try {
        // Build the SQL query dynamically based on search and category filters
        $query = "
            SELECT p.*, 
                   ps.StatusId, 
                   s.Name AS StatusName
            FROM Products p
            LEFT JOIN ProductStatus ps ON p.Id = ps.ProductId
            LEFT JOIN Status s ON ps.StatusId = s.Id
            WHERE ps.Id = (
                SELECT MAX(ps_inner.Id) 
                FROM ProductStatus ps_inner 
                WHERE ps_inner.ProductId = p.Id
            )
            AND LOWER(s.Name) = 'active'
        ";

        if ($category) {
            $query .= " AND p.CategoryId = :category";
        }
        if ($searchTerm) {
            $query .= " AND LOWER(p.Name) LIKE :searchTerm";
        }
        if ($sort) {
            $query .= $sort == 'asc' ? " ORDER BY p.Price ASC" : " ORDER BY p.Price DESC";
        } else {
            $query .= " ORDER BY p.DateCreated DESC";
        }

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
            echo '<div class="row">';
            foreach ($products as $product) {
                $hasDiscount = !empty($product['DiscountPrice']) && $product['DiscountPrice'] > 0;
                $isOutOfStock = empty($product['Stock']) || $product['Stock'] <= 0;

                echo ' 
                    <div class="col-md-4 mb-4">
                        <div class="card" style="height: 450px; display: flex; flex-direction: column; justify-content: space-between;">
                            <img src="./assets/uploads/' . htmlspecialchars($product['ImagePath']) . '" class="card-img-top" alt="' . htmlspecialchars($product['Name']) . '" style="object-fit: cover; height: 200px;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">' . htmlspecialchars($product['Name']) . '</h5>
                                <p class="card-text">' . htmlspecialchars($product['Description']) . '</p>
                                <p class="card-text">
                                    ' . ($hasDiscount ? '
                                        <strong style="font-size: 1.5rem; color: red;">Rs ' . number_format($product['DiscountPrice'], 2) . '</strong>
                                        <span style="text-decoration: line-through; color: grey; font-size: 1rem;">Rs ' . number_format($product['Price'], 2) . '</span>
                                    ' : '
                                        <strong>Rs ' . number_format($product['Price'], 2) . '</strong>
                                    ') . '
                                </p>
                                <p class="card-text">
                                    <strong>Stock:</strong> ' . intval($product['Stock']) . '
                                </p>';

                // Only show the Add to Cart section if the user is not an admin
                if (!$isAdmin) {
                    echo ' 
                        <div class="d-flex align-items-center mt-auto">
                            <input type="number" class="form-control me-2 quantity-input" min="1" max="' . intval($product['Stock']) . '" value="1" style="width: 70px;" ' . ($isOutOfStock ? 'disabled' : '') . '>
                            <button class="btn btn-primary add-to-cart" 
                                data-id="' . htmlspecialchars($product['Id']) . '" 
                                data-name="' . htmlspecialchars($product['Name']) . '" 
                                data-type="product" 
                                data-price="' . number_format($hasDiscount ? $product['DiscountPrice'] : $product['Price'], 2) . '" 
                                data-stock="' . intval($product['Stock']) . '" 
                                ' . ($isOutOfStock ? 'disabled' : '') . '>
                                Add to Cart
                            </button>
                        </div>';
                }

                echo '
                            </div>
                        </div>
                    </div>';
            }
            echo '</div>';

            $stmtCount = $conn->prepare("
                SELECT COUNT(*) FROM Products p
                LEFT JOIN ProductStatus ps ON p.Id = ps.ProductId
                LEFT JOIN Status s ON ps.StatusId = s.Id
                WHERE ps.Id = (
                    SELECT MAX(ps_inner.Id) 
                    FROM ProductStatus ps_inner 
                    WHERE ps_inner.ProductId = p.Id
                )
                AND LOWER(s.Name) = 'active'
            ");
            $stmtCount->execute();
            $totalProducts = $stmtCount->fetchColumn();
            $totalPages = ceil($totalProducts / $limit);

            echo '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
            for ($i = 1; $i <= $totalPages; $i++) {
                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '&search=' . htmlspecialchars($_GET['search'] ?? '') . '&category=' . htmlspecialchars($_GET['category'] ?? '') . '&sort=' . htmlspecialchars($_GET['sort'] ?? '') . '">' . $i . '</a></li>';
            }
            echo '</ul></nav>';
        } else {
            echo '<p>No products available.</p>';
        }
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
    ?>
    </div>
</div>

<?php include "includes/footer.php"; ?>