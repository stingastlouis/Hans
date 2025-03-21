<?php include 'includes/header.php'; ?>

<?php
// Fetch staff and roles from the database
include '../configs/db.php';

$success = isset($_GET["success"]) ? $_GET["success"] : null;

// Fetch staff members with their roles
$stmt = $conn->prepare("
    SELECT s.*, 
           r.Name AS RoleName,
           ls.Name AS LatestStatus
    FROM Staff s
    LEFT JOIN Role r ON s.RoleId = r.Id
    LEFT JOIN (
        SELECT ss.StaffId, 
               MAX(ss.Id) AS LatestStatusId
        FROM StaffStatus ss
        GROUP BY ss.StaffId
    ) latest_ss ON s.Id = latest_ss.StaffId
    LEFT JOIN StaffStatus ss ON latest_ss.LatestStatusId = ss.Id
    LEFT JOIN Status ls ON ss.StatusId = ls.Id;
");

$stmt->execute();
$staffMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch roles for dropdown
$stmt2 = $conn->prepare("SELECT * FROM Role");
$stmt2->execute();
$roles = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Fetch statuses
$stmt3 = $conn->prepare("SELECT * FROM Status");
$stmt3->execute();
$statuses = $stmt3->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Staff Management</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-primary m-0 fw-bold">Staff List</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                Add Staff Member
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive table mt-2" id="dataTable" role="grid" aria-describedby="dataTable_info">
                <table class="table my-0" id="dataTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Latest Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffMembers as $staff): ?>
                            <tr>
                                <td><?= htmlspecialchars($staff['Id']) ?></td>
                                <td><?= htmlspecialchars($staff['Fullname']) ?></td>
                                <td><?= htmlspecialchars($staff['Email']) ?></td>
                                <td><?= htmlspecialchars($staff['Phone']) ?></td>
                                <td><?= htmlspecialchars($staff['RoleName']) ?></td>
                                <td><?= htmlspecialchars($staff['LatestStatus']) ?: 'No Status' ?></td>
                                <td><?= htmlspecialchars($staff['DateCreated']) ?></td>
                                <td>
                                    <button class='btn btn-warning btn-sm edit-staff-btn' 
                                        data-id='<?= $staff['Id'] ?>' 
                                        data-fullname='<?= $staff['Fullname'] ?>' 
                                        data-email='<?= $staff['Email'] ?>' 
                                        data-phone='<?= $staff['Phone'] ?>' 
                                        data-role-id='<?= $staff['RoleId'] ?>'>Edit</button>
                                    <button class="btn btn-info btn-sm reset-password-btn" 
                                        data-id="<?= $staff['Id'] ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#resetPasswordModal">Reset Password</button>
                                    <button class="btn btn-danger btn-sm btn-del" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteStaffModal" 
                                        data-id="<?= $staff['Id'] ?>">Delete</button>
                                    <form method="POST" action="status/add_staffStatus.php" style="display: inline; width:80px;">
                                        <input type="hidden" name="staff_id" value="<?= $staff['Id'] ?>">
                                        <select name="status_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="" disabled selected>Change Status</option>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['Name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStaffModalLabel">Add Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="staff/add_staff.php" method="POST">
                    <div class="mb-3">
                        <label for="staffFullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="staffFullname" name="staff_fullname" required>
                    </div>
                    <div class="mb-3">
                        <label for="staffEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="staffEmail" name="staff_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="staffPhone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="staffPhone" name="staff_phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="staffRole" class="form-label">Role</label>
                        <select class="form-select" id="staffRole" name="staff_role_id" required>
                            <option value="" disabled selected>Select Role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['Id'] ?>"><?= htmlspecialchars($role['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="staffPassword" class="form-label">Initial Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="staffPassword" name="staff_password" required>
                            <button class="btn btn-outline-secondary" type="button" id="generatePasswordBtn">
                                Generate Password
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Staff Member</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editStaffForm" method="POST" action="staff/modify.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStaffModalLabel">Edit Staff Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="staff_id" id="editStaffId">
                    
                    <div class="mb-3">
                        <label for="editStaffFullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="editStaffFullname" name="staff_fullname" required>
                    </div>
                    <div class="mb-3">
                        <label for="editStaffEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editStaffEmail" name="staff_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editStaffPhone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="editStaffPhone" name="staff_phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="editStaffRole" class="form-label">Role</label>
                        <select class="form-select" id="editStaffRole" name="staff_role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['Id'] ?>"><?= htmlspecialchars($role['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="staff/reset_password.php" method="POST">
                    <input type="hidden" name="staff_id" id="resetPasswordStaffId">
                    <div class="mb-3">
                        <label for="staffPassword" class="form-label">Initial Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="staffNewPassword" name="staff_password" required>
                            <button class="btn btn-outline-secondary" type="button" id="generateNewPasswordBtn">
                                Generate Password
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Staff Modal -->
<div class="modal fade" id="deleteStaffModal" tabindex="-1" aria-labelledby="deleteStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteStaffModalLabel">Delete Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this staff member?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="staff/delete_staff.php" method="POST">
                    <input type="hidden" id="staffIdToDelete" name="staff_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    // Delete staff event listener
    document.querySelectorAll('.btn-del').forEach(function(button) {
        button.addEventListener('click', function() {
            var staffId = this.getAttribute('data-id');
            document.getElementById('staffIdToDelete').value = staffId;
        });
    });

    // Edit staff event listener
    document.querySelectorAll('.edit-staff-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const fullname = this.getAttribute('data-fullname');
            const email = this.getAttribute('data-email');
            const phone = this.getAttribute('data-phone');
            const roleId = this.getAttribute('data-role-id');

            // Populate the edit form
            document.getElementById('editStaffId').value = id;
            document.getElementById('editStaffFullname').value = fullname;
            document.getElementById('editStaffEmail').value = email;
            document.getElementById('editStaffPhone').value = phone;
            document.getElementById('editStaffRole').value = roleId;

            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('editStaffModal'));
            modal.show();
        });
    });

    // Reset password event listener
    document.querySelectorAll('.reset-password-btn').forEach(button => {
        button.addEventListener('click', function() {
            const staffId = this.getAttribute('data-id');
            document.getElementById('resetPasswordStaffId').value = staffId;
        });
    });

    // Password confirmation validation
    document.querySelector('#resetPasswordModal form').addEventListener('submit', function(e) {
        const password = document.getElementById('newPassword').value;
        const confirm = document.getElementById('confirmPassword').value;
        
        if (password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match!');
        }
    });
</script>

<script>
    function generatePassword() {
        const uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; 
        const lowercase = 'abcdefghijkmnpqrstuvwxyz'; 
        const numbers = '23456789'; 
        const symbols = '!@#$%^&*';
        let password = '';

        password += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
        password += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
        password += numbers.charAt(Math.floor(Math.random() * numbers.length));
        password += symbols.charAt(Math.floor(Math.random() * symbols.length));
        
        const allChars = uppercase + lowercase + numbers + symbols;
        for (let i = password.length; i < 12; i++) {
            password += allChars.charAt(Math.floor(Math.random() * allChars.length));
        }

        password = password.split('').sort(() => Math.random() - 0.5).join('');
        
        return password;
    }

    document.getElementById('generatePasswordBtn').addEventListener('click', function() {
        const passwordField = document.getElementById('staffPassword');
        passwordField.value = generatePassword();
        passwordField.type = 'text';
        
        setTimeout(() => {
            passwordField.type = 'password';
        }, 5000);
    });

    document.getElementById('generateNewPasswordBtn').addEventListener('click', function() {
        const passwordField = document.getElementById('staffNewPassword');
        passwordField.value = generatePassword();
        passwordField.type = 'text';
        
        setTimeout(() => {
            passwordField.type = 'password';
        }, 5000);
    });

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            // Clear all forms within this modal
            const forms = this.getElementsByTagName('form');
            for (let form of forms) {
                form.reset();
            }
            
            // Additionally ensure the password field is reset to type="password"
            const passwordFields = this.querySelectorAll('input[type="text"][id$="Password"]');
            passwordFields.forEach(field => {
                field.type = 'password';
            });
        });
    });
</script>