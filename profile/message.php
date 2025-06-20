<div class="tab-pane fade" id="queries" role="tabpanel" aria-labelledby="queries-tab">
    <h3>Queries</h3>
    <p>If you have any questions or queries, please feel free to ask below:</p>
    <form action="submit_query.php" method="POST">
        <div class="mb-3">
            <label for="query" class="form-label">Your Query</label>
            <textarea class="form-control" id="query" name="query" rows="4" placeholder="Describe your query..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit Query</button>
    </form>

    <h4 class="mt-4">Previous Queries</h4>
    <ul class="list-group">
        <li class="list-group-item">Query 1: Order not received</li>
        <li class="list-group-item">Query 2: Incorrect item delivered</li>
    </ul>
</div>