<?php include 'includes/header.php'; ?>

<?php
// Fetch categories from the database
include '../configs/db.php';

$success = isset($_GET["success"]) ? $_GET["success"] : null;
$stmt = $conn->prepare("SELECT * FROM role");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Roles</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-primary m-0 fw-bold">Role List</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                Add Role
            </button>
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
                            <th>Role Name</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $role): ?>
                            <tr>
                                <td><?= htmlspecialchars($role['Id']) ?></td>
                                <td><?= htmlspecialchars($role['Name']) ?></td>
                                <td><?= htmlspecialchars($role['DateCreated']) ?></td>
                                <td>
                                    <!-- <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modifyRoleModal">Edit</button> -->
                                    <button class="btn btn-danger btn-del" data-bs-toggle="modal" data-bs-target="#deleteRoleModal" data-id="<?= $role['Id'] ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRoleModalLabel">Add Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="role/add_role.php" method="POST">
                    <div class="mb-3">
                        <label for="roleName" class="form-label">Role Name</label>
                        <input type="text" class="form-control" id="roleName" name="role_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Role</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modify Role Modal -->
<div class="modal fade" id="modifyRoleModal" tabindex="-1" aria-labelledby="modifyRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifyRoleModalLabel">Modify Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="role/modify_role.php" method="POST">
                    <div class="mb-3">
                        <label for="modifyRoleName" class="form-label">Role Name</label>
                        <input type="text" class="form-control" id="modifyRoleName" name="role_name" required>
                        <input type="hidden" name="role_id" id="modifyRoleId">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Success Message -->
<div class="modal fade" id="SuccessMessage" tabindex="-1" aria-labelledby="successMessgeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifyRoleModalLabel">Message</h5>
            </div>
            <div class="modal-body">
                <form action="role/modify_role.php" method="POST">
                    Created Successfully
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Delete Role Modal -->
<div class="modal fade" id="deleteRoleModal" tabindex="-1" aria-labelledby="deleteRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteRoleModalLabel">Delete Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this role?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="role/delete_role.php" method="POST">
                    <input type="hidden" id="roleIdToDelete" name="role_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>

<script>
    // If success=1 is passed in the URL, show the SuccessMessage modal
    
    <?php if ($success): ?>
        var myModal = new bootstrap.Modal(document.getElementById('SuccessMessage'));
        myModal.show();

        // Hide the modal after 3 seconds
        setTimeout(function() {
            myModal.hide();
        }, 3000); // 3000 milliseconds = 3 seconds
    <?php endif; ?>
</script>

<script>
    var deleteButtons = document.querySelectorAll('.btn-del');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var roleId = this.getAttribute('data-id');
            console.log(roleId)
            let x = document.getElementById('roleIdToDelete').value = roleId;
            console.log(x,"object");
        });
    });
</script>

<script>
    document.getElementById('searchInput').addEventListener('input', function () {
    var query = this.value.toLowerCase(); // Get the search query in lowercase
    var rows = document.querySelectorAll('#dataTable tbody tr'); // Get all table rows
    rows.forEach(function (row) {
        var roleName = row.querySelector('td:nth-child(2)').textContent.toLowerCase(); // Get the role name (2nd column)

        // Check if the role name matches the query
        if (roleName.includes(query)) {
            row.style.display = ''; // Show the row
        } else {
            row.style.display = 'none'; // Hide the row
        }
    });
});
</script>
