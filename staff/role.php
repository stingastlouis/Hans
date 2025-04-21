<?php 

include '../sessionManagement.php';
include '../configs/constants.php';

$role = $_SESSION['role'];
if (!in_array($role, ALLOWED_ROLES)){
    header("Location: ../unauthorised.php");
    exit;
}

include 'includes/header.php';
include '../configs/db.php';

$success = isset($_GET["success"]) ? $_GET["success"] : null;

$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("SELECT * FROM role LIMIT :limit OFFSET :offset");
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $conn->prepare("SELECT COUNT(*) FROM role");
$stmt2->execute();
$totalRecords = $stmt2->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Roles</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-primary m-0 fw-bold">Role List</p>
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
                    <div id="dataTable_length" class="dataTables_length" aria-controls="dataTable"></div>
                </div>
            </div>
            <div class="table-responsive table mt-2" id="dataTable" role="grid" aria-describedby="dataTable_info">
                <table class="table my-0" id="dataTable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Role Name</th>
                            <th>Date Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $role): ?>
                            <tr>
                                <td><?= htmlspecialchars($role['Id']) ?></td>
                                <td><?= htmlspecialchars($role['Name']) ?></td>
                                <td><?= htmlspecialchars($role['DateCreated']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <nav aria-label="Page navigation example">
                <ul class="pagination justify-content-center">

                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                <span aria-hidden="true">Previous</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                        $range = 2;
                        $start = max(1, $page - $range);
                        $end = min($totalPages, $page + $range);

                        for ($i = $start; $i <= $end; $i++):
                    ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                <span aria-hidden="true">Next</span>
                            </a>
                        </li>
                    <?php endif; ?>

                </ul>
            </nav>
        </div>
    </div>
</div>
