<?php
include '../sessionManagement.php';
require_once '../configs/db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../unauthorised.php");
    exit;
}

try {
    $stmt = $conn->query("SELECT * FROM Query ORDER BY DateCreated DESC");
    $queries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>


<?php include 'includes/header.php'; ?>
<div class="container mt-5">
    <h2 class="mb-4">Queries</h2>

    <?php if (count($queries) === 0): ?>
        <div class="alert alert-info">No queries found.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Is Customer</th>
                        <th>Customer ID</th>
                        <th>Date Created</th>
                        <th>Seen</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queries as $q): ?>
                        <tr>
                            <td><?= htmlspecialchars($q['Id']) ?></td>
                            <td><?= htmlspecialchars($q['FullName']) ?></td>
                            <td><?= htmlspecialchars($q['Email']) ?></td>
                            <td><?= htmlspecialchars($q['Subject']) ?></td>
                            <td><?= nl2br(htmlspecialchars($q['Message'])) ?></td>
                            <td>
                                <?= $q['IsCustomer'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
                            </td>
                            <td><?= $q['CustomerId'] ?? '<em>None</em>' ?></td>
                            <td><?= htmlspecialchars($q['DateCreated']) ?></td>
                            <td>
                                <?= $q['Seen'] ? '<span class="badge bg-success">Seen</span>' : '<span class="badge bg-warning text-dark">Unseen</span>' ?>
                            </td>
                            <td>
                                <?php if (!$q['Seen']): ?>
                                    <form method="post" action="updateMessage.php" onsubmit="return confirm('Mark this message as seen?');">
                                        <input type="hidden" name="query_id" value="<?= $q['Id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success">Mark as Seen</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-success">✓ Seen</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>