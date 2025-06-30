<div class="tab-pane fade" id="queries" role="tabpanel" aria-labelledby="queries-tab">
    <h3>Queries</h3>
    <p>If you have any questions or queries, please feel free to ask below:</p>

    <form action="profile/addMessage.php" method="POST">
        <input hidden type="text" class="form-control" id="customerId" name="customerId">

        <div class="mb-3">
            <label for="subject" class="form-label">Subject</label>
            <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject of your query">
        </div>

        <div class="mb-3">
            <label for="message" class="form-label">Your Query</label>
            <textarea class="form-control" id="message" name="message" rows="4" placeholder="Describe your query..." required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Submit Query</button>
    </form>

    <h4 class="mt-4">Previous Queries</h4>
    <?php
    require_once('./configs/db.php');

    $customerId = $_SESSION['customerId'] ?? null;

    try {
        if ($customerId) {
            $stmt = $conn->prepare("
            SELECT Subject, Message, DateCreated 
            FROM Query 
            WHERE CustomerId = ?
            ORDER BY DateCreated DESC 
            LIMIT 3
        ");
            $stmt->execute([$customerId]);
        }

        $queries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($queries) > 0) {
            echo '<ul class="list-group">';
            foreach ($queries as $query) {
                echo '<li class="list-group-item">';
                echo '<strong>' . htmlspecialchars($query['Subject']) . '</strong><br>';
                echo nl2br(htmlspecialchars($query['Message'])) . '<br>';
                echo '<small class="text-muted">' . date('F j, Y, g:i a', strtotime($query['DateCreated'])) . '</small>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p class="text-muted">No previous queries found.</p>';
        }
    } catch (PDOException $e) {
        echo '<p class="text-danger">Error loading queries: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    ?>


</div>