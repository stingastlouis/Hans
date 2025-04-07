<?php include "includes/header.php" ?>
<div class="container py-4">
    <h1 class="text-center mb-4">Product Page</h1>
    <div class="row">
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
    ORDER BY p.DateCreated DESC;
");

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
                                </p>
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
                                </div>
                            </div>
                        </div>
                    </div>';
            }
            echo '</div>';
        } else {
            echo '<p>No products available.</p>';
        }
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
    ?>
    </div>
</div>

<?php include "cartview.php"; ?>


<?php include "includes/footer.php" ?>
