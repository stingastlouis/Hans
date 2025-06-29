<?php include "includes/header.php" ?>
<link rel="stylesheet" href="assets/css/index.css">
<header class="bg-primary-gradient">
    <div class="container pt-4 pt-xl-5">
        <div class="row pt-5">
            <div class="col-md-8 col-xl-6 text-center text-md-start mx-auto">
                <div class="text-center">
                    <p class="fw-bold text-success mb-2">Voted #1 Worldwide</p>
                    <h1 class="fw-bold">The best lighting solutions for every space</h1>
                    <p class="lead text-light mb-4">Explore our diverse range of lights, from sleek designs to powerful bundles, all crafted for elegance and efficiency.</p>
                </div>
            </div>
            <div class="col-12 col-lg-10 mx-auto">
                <div class="position-relative" style="display: flex; flex-wrap: wrap; justify-content: flex-end;">
                    <div style="position: relative; flex: 0 0 45%; transform: translate3d(-15%, 35%, 0);"><img class="img-fluid" data-bss-parallax="" data-bss-parallax-speed="0.8" src="assets/img/products/3.jpg"></div>
                    <div style="position: relative; flex: 0 0 45%; transform: translate3d(-5%, 20%, 0);"><img class="img-fluid" data-bss-parallax="" data-bss-parallax-speed="0.4" src="assets/img/products/2.jpg"></div>
                    <div style="position: relative; flex: 0 0 60%; transform: translate3d(0, 0%, 0);"><img class="img-fluid" data-bss-parallax="" data-bss-parallax-speed="0.25" src="assets/img/products/1.jpg"></div>
                </div>
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
            <h2 class="fw-bold">Latest Products</h2>
            <p class="text-muted">Check out our most recent additions to the store.</p>
        </div>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <a href="product.php" class="text-decoration-none text-dark">
                        <div class="card shadow-sm h-100">
                            <img 
                                src="./assets/uploads/<?= htmlspecialchars($product['ImagePath']) ?>" 
                                class="card-img-top" 
                                alt="<?= htmlspecialchars($product['Name']) ?>"
                            >
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($product['Name']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($product['Description']) ?></p>
                                <p class="fw-bold text-primary mt-auto">Rs<?= number_format($product['Price'], 2) ?></p>
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
        <h2 class="fw-bold">Bundle Lighting Offers</h2>
        <p class="mb-4" style="font-size: 1.6rem;">Get the best value with our lighting bundles. Perfect for bundles, offices, and home renovations.</p>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php foreach ($bundles as $bundle): ?>
                <div class="col">
                    <a href="bundle.php" class="text-decoration-none text-dark">
                        <div class="card shadow-sm h-100">
                            <img src="./assets/uploads/<?= htmlspecialchars($bundle['ImagePath']) ?>" class="card-img-top" alt="<?= htmlspecialchars($bundle['Name']) ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($bundle['Name']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($bundle['Description']) ?></p>

                                <?php if (!empty($bundle['Products'])): ?>
                                    <div class="text-start mb-3">
                                        <strong>Included Products:</strong>
                                        <ul class="mb-0">
                                            <?php foreach ($bundle['Products'] as $prod): ?>
                                                <li><?= htmlspecialchars($prod['Name']) ?> (x<?= $prod['Quantity'] ?>)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <p class="fw-bold text-primary mt-auto">
                                    <?= $bundle['DiscountPrice'] 
                                        ? '<span class="text-decoration-line-through text-secondary me-2">Rs' . number_format($bundle['Price'], 2) . '</span><span>Rs' . number_format($bundle['DiscountPrice'], 2) . '</span>' 
                                        : 'Rs' . number_format($bundle['Price'], 2) ?>
                                </p>
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
    $catStmt = $conn->prepare("SELECT * FROM Categories ORDER BY Name ASC");
    $catStmt->execute();
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<section class="py-5 bg-white">
    <div class="container text-center">
        <h2 class="fw-bold mb-4">Explore by Category</h2>
        <div class="row row-cols-2 row-cols-md-4 g-4">
            <?php foreach ($categories as $cat): ?>
                <div class="col">
                    <a href="category.php?id=<?= $cat['Id'] ?>" class="text-decoration-none text-dark">
                        <div class="card shadow-sm h-100 p-3">
                            <h5 class="mb-0"><?= htmlspecialchars($cat['Name']) ?></h5>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
    .card.shadow-sm {
    max-width: 85%; /* Reduce width within column */
    margin: 0 auto;  /* Center card horizontally */
    border-radius: 12px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background-color: #fffaf6;
}

/* Optional: subtle bohemian touch with a hover effect */
.card.shadow-sm:hover {
    transform: scale(1.02);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
}

/* Bohemian font styling */
.card-title {
    font-family: 'Georgia', serif;
    font-weight: 700;
    color: #7b3f00;
}

.card-text {
    font-family: 'Georgia', serif;
    font-size: 0.95rem;
    color: #5e4630;
}

.text-primary {
    color: #b95c50 !important;
}

</style>

<?php include "includes/footer.php" ?>
