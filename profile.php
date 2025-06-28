<?php include "includes/header.php"; ?>

<div class="container py-4">
    <h1 class="text-center mb-4">User Profile</h1>

    <div class="row">
        <div class="col-md-3">
            <div class="list-group" id="profileTabs" role="tablist">
                <a class="list-group-item list-group-item-action active" id="user-info-tab" data-bs-toggle="list" href="#user-info" role="tab" aria-controls="user-info">User Information</a>
                <a class="list-group-item list-group-item-action" id="order-history-tab" data-bs-toggle="list" href="#order-history" role="tab" aria-controls="order-history">Order History</a>
                <a class="list-group-item list-group-item-action" id="queries-tab" data-bs-toggle="list" href="#queries" role="tab" aria-controls="queries">Queries</a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="tab-content mt-4" id="profileTabsContent">
                <?php require_once "profile/info.php" ?>
                <?php require_once "profile/message.php" ?>
                <?php require_once "profile/order-history.php" ?>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        const hash = window.location.hash;

        if (hash) {
            const tabTrigger = document.querySelector(`a[href="${hash}"]`);
            if (tabTrigger) {
                // Remove 'active' from any currently active tab
                document.querySelectorAll('#profileTabs a').forEach(tab => tab.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active', 'show'));

                // Activate the target tab
                const tab = new bootstrap.Tab(tabTrigger);
                tab.show();
            }
        }
    });
</script>


<?php include "includes/footer.php"; ?>