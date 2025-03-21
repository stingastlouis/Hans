<?php include 'includes/header.php'; ?>

<?php
include '../configs/db.php';

$success = isset($_GET["success"]) ? $_GET["success"] : null;
$stmt = $conn->prepare("
    SELECT c.*, 
           s.Name AS LatestStatus
    FROM Customer c
    LEFT JOIN (
        SELECT cs.UserId, 
               MAX(cs.Id) AS LatestStatusId
        FROM CustomerStatus cs
        GROUP BY cs.UserId
    ) latest_cs ON c.Id = latest_cs.UserId
    LEFT JOIN CustomerStatus cs ON latest_cs.LatestStatusId = cs.Id
    LEFT JOIN Status s ON cs.StatusId = s.Id;
");


$stmt->execute();
$customerMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $conn->prepare("SELECT * FROM Role");
$stmt2->execute();
$roles = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$stmt3 = $conn->prepare("SELECT * FROM Status");
$stmt3->execute();
$statuses = $stmt3->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Customer Management</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-primary m-0 fw-bold">Customer List</p>
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
                            <th>Address</th>
                            <th>Latest Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerMembers as $customer): ?>
                            <tr>
                                <td><?= htmlspecialchars($customer['Id']) ?></td>
                                <td><?= htmlspecialchars($customer['Fullname']) ?></td>
                                <td><?= htmlspecialchars($customer['Email']) ?></td>
                                <td><?= htmlspecialchars($customer['Phone']) ?></td>
                                <td><?= htmlspecialchars($customer['RoleName']) ?></td>
                                <td><?= htmlspecialchars($customer['LatestStatus']) ?: 'No Status' ?></td>
                                <td><?= htmlspecialchars($customer['DateCreated']) ?></td>
                                <td>
                                    <button class='btn btn-warning btn-sm edit-customer-btn' 
                                        data-id='<?= $customer['Id'] ?>' 
                                        data-fullname='<?= $customer['Fullname'] ?>' 
                                        data-email='<?= $customer['Email'] ?>' 
                                        data-phone='<?= $customer['Phone'] ?>' 
                                        data-role-id='<?= $customer['RoleId'] ?>'>Edit</button>
                                    <button class="btn btn-info btn-sm reset-password-btn" 
                                        data-id="<?= $customer['Id'] ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#resetPasswordModal">Reset Password</button>
                                    <button class="btn btn-danger btn-sm btn-del" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteCustomerModal" 
                                        data-id="<?= $customer['Id'] ?>">Delete</button>
                                    <form method="POST" action="status/add_customerStatus.php" style="display: inline; width:80px;">
                                        <input type="hidden" name="customer_id" value="<?= $customer['Id'] ?>">
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

<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCustomerForm" method="POST" action="customer/modify.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="customer_id" id="editCustomerId">
                    
                    <div class="mb-3">
                        <label for="editCustomerFullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="editCustomerFullname" name="customer_fullname" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCustomerEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editCustomerEmail" name="customer_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCustomerPhone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="editCustomerPhone" name="customer_phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCustomerRole" class="form-label">Role</label>
                        <select class="form-select" id="editCustomerRole" name="customer_role_id" required>
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

<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="customer/reset_password.php" method="POST">
                    <input type="hidden" name="customer_id" id="resetPasswordCustomerId">
                    <div class="mb-3">
                        <label for="customerPassword" class="form-label">Initial Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="customerNewPassword" name="customer_password" required>
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

<!-- Delete Customer Modal -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCustomerModalLabel">Delete Customer Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this customer member?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="customer/delete_customer.php" method="POST">
                    <input type="hidden" id="customerIdToDelete" name="customer_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    document.querySelectorAll('.btn-del').forEach(function(button) {
        button.addEventListener('click', function() {
            var customerId = this.getAttribute('data-id');
            document.getElementById('customerIdToDelete').value = customerId;
        });
    });

    document.querySelectorAll('.edit-customer-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const fullname = this.getAttribute('data-fullname');
            const email = this.getAttribute('data-email');
            const phone = this.getAttribute('data-phone');
            const roleId = this.getAttribute('data-role-id');

            document.getElementById('editCustomerId').value = id;
            document.getElementById('editCustomerFullname').value = fullname;
            document.getElementById('editCustomerEmail').value = email;
            document.getElementById('editCustomerPhone').value = phone;
            document.getElementById('editCustomerRole').value = roleId;

            const modal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
            modal.show();
        });
    });


    document.querySelectorAll('.reset-password-btn').forEach(button => {
        button.addEventListener('click', function() {
            const customerId = this.getAttribute('data-id');
            document.getElementById('resetPasswordCustomerId').value = customerId;
        });
    });

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
        const passwordField = document.getElementById('customerPassword');
        passwordField.value = generatePassword();
        passwordField.type = 'text';
        
        setTimeout(() => {
            passwordField.type = 'password';
        }, 5000);
    });

    document.getElementById('generateNewPasswordBtn').addEventListener('click', function() {
        const passwordField = document.getElementById('customerNewPassword');
        passwordField.value = generatePassword();
        passwordField.type = 'text';
        
        setTimeout(() => {
            passwordField.type = 'password';
        }, 5000);
    });

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            const forms = this.getElementsByTagName('form');
            for (let form of forms) {
                form.reset();
            }
            
            const passwordFields = this.querySelectorAll('input[type="text"][id$="Password"]');
            passwordFields.forEach(field => {
                field.type = 'password';
            });
        });
    });
</script>