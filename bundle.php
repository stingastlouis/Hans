<?php include "includes/header.php"; ?>

<div class="container py-4">
    <h1 class="text-center mb-4">Bundle Page</h1>
    <div class="row">
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

            $bundlesProducts = [];
            $stmt = $conn->prepare("SELECT ep.BundleId, p.Name AS ProductName, ep.Quantity
                                FROM BundleProducts ep
                                JOIN Products p ON ep.ProductId = p.Id");
            $stmt->execute();
            $productData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($productData as $product) {
                $bundlesProducts[$product['BundleId']][] = [
                    'name' => $product['ProductName'],
                    'quantity' => $product['Quantity']
                ];
            }

            if (!empty($bundles)) {
                echo '<div class="row">';
                foreach ($bundles as $bundle) {
                    echo ' 
                    <div class="col-md-4 mb-4">
                        <div class="card" style="height: 450px; display: flex; flex-direction: column; justify-content: space-between;">
                            <img src="./assets/uploads/' . htmlspecialchars($bundle['ImagePath']) . '" class="card-img-top" alt="' . htmlspecialchars($bundle['Name']) . '" style="object-fit: cover; height: 200px;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">' . htmlspecialchars($bundle['Name']) . '</h5>
                                <p class="card-text">' . htmlspecialchars($bundle['Description']) . '</p>
                                <p class="card-text"><strong>Price:</strong> Rs ' . number_format($bundle['Price'], 2) . '</p>';

                    if (!empty($bundlesProducts[$bundle['Id']])) {
                        echo '<p class="card-text"><strong>Included Products:</strong></p><ul>';
                        foreach ($bundlesProducts[$bundle['Id']] as $product) {
                            echo '<li>' . htmlspecialchars($product['name']) . ' (Qty: ' . $product['quantity'] . ')</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<p class="card-text"><em>No associated products</em></p>';
                    }

                    echo '
                        <div class="mt-auto">
                            <button class="btn btn-primary add-to-bundle-cart" 
                                data-id="' . htmlspecialchars($bundle['Id']) . '" 
                                data-name="' . htmlspecialchars($bundle['Name']) . '" 
                                data-price="' . number_format($bundle['Price'], 2) . '" 
                                data-type="bundle">
                                Add to Cart
                            </button>
                        </div>';

                    echo '
                            </div>
                        </div>
                    </div>';
                }
                echo '</div>';
            } else {
                echo '<p>No bundles available.</p>';
            }
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center mt-4">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
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