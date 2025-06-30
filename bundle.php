<?php include "includes/header.php"; ?>

<div class="container py-4">
    <h1 class="text-center mb-5 fw-bold text-primary">Our Bundles</h1>
    <div class="row g-4">
        <?php
        include './configs/db.php';

        $limit = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = ($page - 1) * $limit;
        $isAdmin = isset($_SESSION['staff_id']);

        try {
            $totalStmt = $conn->prepare("SELECT COUNT(*) 
                                     FROM Bundle e
                                     LEFT JOIN BundleStatus es ON e.Id = es.BundleId
                                     LEFT JOIN Status s ON es.StatusId = s.Id
                                     WHERE es.Id = (
                                         SELECT MAX(es_inner.Id) 
                                         FROM BundleStatus es_inner 
                                         WHERE es_inner.BundleId = e.Id
                                     )
                                     AND LOWER(s.Name) = 'active'");
            $totalStmt->execute();
            $totalBundles = $totalStmt->fetchColumn();
            $totalPages = ceil($totalBundles / $limit);

            $stmt = $conn->prepare("SELECT e.*, 
                                       es.StatusId, 
                                       s.Name AS StatusName
                                FROM Bundle e
                                LEFT JOIN BundleStatus es ON e.Id = es.BundleId
                                LEFT JOIN Status s ON es.StatusId = s.Id
                                WHERE es.Id = (
                                    SELECT MAX(es_inner.Id) 
                                    FROM BundleStatus es_inner 
                                    WHERE es_inner.BundleId = e.Id
                                )
                                AND LOWER(s.Name) = 'active'
                                ORDER BY e.DateCreated DESC
                                LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $bundles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $conn->prepare("SELECT ep.BundleId, p.Name AS ProductName, ep.Quantity
                                    FROM BundleProducts ep
                                    JOIN Products p ON ep.ProductId = p.Id");
            $stmt->execute();
            $productData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $bundlesProducts = [];
            foreach ($productData as $product) {
                $bundlesProducts[$product['BundleId']][] = [
                    'name' => $product['ProductName'],
                    'quantity' => $product['Quantity']
                ];
            }

            if (!empty($bundles)) {
                foreach ($bundles as $bundle) {
                    echo '
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0">
                            <img src="./assets/uploads/bundles/' . htmlspecialchars($bundle['ImagePath']) . '" 
                                 class="card-img-top" alt="' . htmlspecialchars($bundle['Name']) . '" 
                                 style="object-fit: cover; height: 200px;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-primary fw-bold">' . htmlspecialchars($bundle['Name']) . '</h5>
                                <p class="card-text text-muted small" style="min-height: 60px;">' . htmlspecialchars($bundle['Description']) . '</p>
                                <div class="mb-2">
                                    <span class="badge bg-success fs-6">$ ' . number_format($bundle['Price'], 2) . '</span>
                                </div>';

                    if (!empty($bundlesProducts[$bundle['Id']])) {
                        echo '<p class="fw-semibold mb-1">Includes:</p><ul class="list-unstyled small">';
                        foreach ($bundlesProducts[$bundle['Id']] as $product) {
                            echo '<li><i class="bi bi-box-seam me-1 text-secondary"></i>' .
                                htmlspecialchars($product['name']) . ' <span class="text-muted">(x' . $product['quantity'] . ')</span></li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<p class="text-muted fst-italic small">No associated products</p>';
                    }

                    echo '
                                <div class="mt-auto text-end">
                                    <button class="btn btn-outline-primary btn-sm w-100 add-to-bundle-cart" 
                                            data-id="' . htmlspecialchars($bundle['Id']) . '" 
                                            data-name="' . htmlspecialchars($bundle['Name']) . '" 
                                            data-price="' . number_format($bundle['Price'], 2) . '" 
                                            data-type="bundle">
                                        <i class="bi bi-cart-plus me-1"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>';
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-warning text-center">No bundles available at the moment.</div></div>';
            }
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">« Prev</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next »</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>


<?php include "includes/footer.php"; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener("click", function(event) {
            if (event.target.classList.contains("add-to-bundle-cart")) {
                const button = event.target;
                const id = button.getAttribute("data-id");
                const name = button.getAttribute("data-name");
                const price = parseFloat(button.getAttribute("data-price"));
                const type = button.getAttribute("data-type");
                const quantity = 1;

                addToCart(id, name, price, quantity, type);
            }
        });
    });
</script>