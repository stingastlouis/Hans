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

$itemsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); 
$offset = ($page - 1) * $itemsPerPage;


$totalStmt = $conn->query("SELECT COUNT(*) FROM categories");
$totalCategories = $totalStmt->fetchColumn();
$totalPages = ceil($totalCategories / $itemsPerPage);

$stmt = $conn->prepare("SELECT * FROM categories ORDER BY Id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Categories</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-primary m-0 fw-bold">Category List</p>
            <?php if (in_array($role, ['Admin'])): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                Add Category
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="text-md-end dataTables_filter" id="dataTable_filter">
                        <label class="form-label">
                            <input type="search" class="form-control form-control-sm" aria-controls="dataTable" placeholder="Search" id="searchInput">
                        </label>
                    </div>
                </div>
                <div class="col-md-6 text-nowrap">
                    <div id="dataTable_length" class="dataTables_length" aria-controls="dataTable">
                    </div>
                </div>
            </div>
            <div class="table-responsive table mt-2" id="dataTable" role="grid" aria-describedby="dataTable_info">
                <table class="table my-0" id="dataTable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Category Name</th>
                            <th>Date Created</th>
                            <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['Id']) ?></td>
                                <td><?= htmlspecialchars($category['Name']) ?></td>
                                <td><?= htmlspecialchars($category['DateCreated']) ?></td>
                                <?php if (in_array($role, ADMIN_ONLY_ROLE)): ?>
                                <td>
                                    <button class="btn btn-danger btn-del" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal" data-id="<?= $category['Id'] ?>">Delete</button>
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

<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="category/add_category.php" method="POST">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" name="category_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modifyCategoryModal" tabindex="-1" aria-labelledby="modifyCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifyCategoryModalLabel">Modify Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="category/modify_category.php" method="POST">
                    <div class="mb-3">
                        <label for="modifyCategoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="modifyCategoryName" name="category_name" required>
                        <input type="hidden" name="category_id" id="modifyCategoryId">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCategoryModalLabel">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this category?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="category/delete_category.php" method="POST">
                    <input type="hidden" id="categoryIdToDelete" name="category_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="./utils/message.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', handleSuccessOrErrorModal);
</script>

<?php include 'includes/footer.php'; ?>

<script>
    var deleteButtons = document.querySelectorAll('.btn-del');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var categoryId = this.getAttribute('data-id');
            console.log(categoryId)
            let x = document.getElementById('categoryIdToDelete').value = categoryId;
            console.log(x,"object");
        });
    });
</script>

<script>
    document.getElementById('searchInput').addEventListener('input', function () {
    var query = this.value.toLowerCase(); 
    var rows = document.querySelectorAll('#dataTable tbody tr'); 
    rows.forEach(function (row) {
        var categoryName = row.querySelector('td:nth-child(2)').textContent.toLowerCase(); 

        
        if (categoryName.includes(query)) {
            row.style.display = ''; 
        } else {
            row.style.display = 'none'; 
        }
    });
});
</script>
