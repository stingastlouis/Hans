<?php include "includes/header.php" ?>
<link rel="stylesheet" href="assets/css/index.css">

<header class="bg-primary-gradient py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8 col-xl-6 text-center text-md-start mx-auto mb-4 mb-md-0">

                <h1 class="fw-bold display-4 lh-sm">The best lighting solutions for every space</h1>
                <p class="lead text-light mb-5">Explore our diverse range of lights, from sleek designs to powerful bundles, all crafted for elegance and efficiency.</p>
            </div>
            <div class="col-12 col-lg-10 mx-auto d-flex flex-wrap justify-content-end gap-4">
                <img src="assets/img/cheryl-winn-boujnida-jhDof9B6vPY-unsplash.jpg" width="300" height="300" class="img-fluid rounded-4 shadow-lg" style=" transform: translate(-15%, 35%);" alt="Product 3" data-bss-parallax data-bss-parallax-speed="0.8">
                <img src="assets/img/zhaoli-jin-EaPQ_Baocd8-unsplash.jpg" width="300" height="300" class="img-fluid rounded-4 shadow-lg" style="transform: translate(-5%, 20%);" alt="Product 2" data-bss-parallax data-bss-parallax-speed="0.4">
                <img src="assets/img/swabdesign-SMFRH-Fs9is-unsplash.jpg" width="300" height="300" class="img-fluid rounded-4 shadow-lg" style=" transform: translate(0, 0);" alt="Product 1" data-bss-parallax data-bss-parallax-speed="0.25">
            </div>
        </div>
    </div>
</header>
<?php
include './configs/db.php';

try {
    $stmt = $conn->prepare("
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
        ORDER BY p.DateCreated DESC
        LIMIT 3;
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-2">Latest Products</h2>
            <p class="text-muted fs-5">Check out our most recent additions to the store.</p>
        </div>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <a href="product.php" class="text-decoration-none text-dark">
                        <div class="card shadow-sm rounded-4 h-100 bg-white border-0 hover-shadow">
                            <img src="./assets/uploads/products/<?= htmlspecialchars($product['ImagePath']) ?>" class="card-img-top rounded-top-4" alt="<?= htmlspecialchars($product['Name']) ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($product['Name']) ?></h5>
                                <p class="card-text flex-grow-1"><?= htmlspecialchars($product['Description']) ?></p>
                                <p class="fw-bold text-primary mt-3 fs-5">Rs<?= number_format($product['Price'], 2) ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
try {
    $bundleStmt = $conn->prepare("
        SELECT * FROM Bundle
        ORDER BY DateCreated DESC
        LIMIT 2
    ");
    $bundleStmt->execute();
    $bundles = $bundleStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($bundles as &$bundle) {
        $productStmt = $conn->prepare("
            SELECT p.Name, ep.Quantity
            FROM BundleProducts ep
            INNER JOIN Products p ON ep.ProductId = p.Id
            WHERE ep.BundleId = ?
        ");
        $productStmt->execute([$bundle['Id']]);
        $bundle['Products'] = $productStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>


<section class="py-5 bg-light">
    <div class="container text-center py-5">
        <h2 class="fw-bold mb-3">Bundle Lighting Offers</h2>
        <p class="mb-5 fs-5">Get the best value with our lighting bundles. Perfect for bundles, offices, and home renovations.</p>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php foreach ($bundles as $bundle): ?>
                <div class="col">
                    <a href="bundle.php" class="text-decoration-none text-dark">
                        <div class="card shadow-sm rounded-4 h-100 bg-white border-0 hover-shadow">
                            <img src="./assets/uploads/bundles<?= htmlspecialchars($bundle['ImagePath']) ?>" class="card-img-top rounded-top-4" alt="<?= htmlspecialchars($bundle['Name']) ?>">
                            <div class="card-body d-flex flex-column text-start">
                                <h5 class="card-title"><?= htmlspecialchars($bundle['Name']) ?></h5>
                                <p class="card-text flex-grow-1"><?= htmlspecialchars($bundle['Description']) ?></p>

                                <?php if (!empty($bundle['Products'])): ?>
                                    <div class="mb-3">
                                        <strong>Included Products:</strong>
                                        <ul class="list-unstyled small mb-0 ps-3">
                                            <?php foreach ($bundle['Products'] as $prod): ?>
                                                <li>• <?= htmlspecialchars($prod['Name']) ?> (x<?= $prod['Quantity'] ?>)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <p class="fw-bold text-primary fs-5 mt-auto">
                                    <?php if ($bundle['DiscountPrice']): ?>
                                        <span class="text-decoration-line-through text-secondary me-2">Rs<?= number_format($bundle['Price'], 2) ?></span>
                                        <span>Rs<?= number_format($bundle['DiscountPrice'], 2) ?></span>
                                    <?php else: ?>
                                        Rs<?= number_format($bundle['Price'], 2) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>



<?php include "includes/footer.php" ?>