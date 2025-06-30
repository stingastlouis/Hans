<?php
$type = $_GET['success'] ?? ($_GET['error'] ?? null);
$message = $_GET['msg'] ?? null;

if ($type && $message):
    $alertType = $type === '1' && isset($_GET['success']) ? 'success' : 'error';
?>
    <style>
        #modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        #modal-message {
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border-top: 6px solid <?= $alertType === 'success' ? '#28a745' : '#dc3545' ?>;
            text-align: center;
            font-family: Arial, sans-serif;
        }

        #modal-message h2 {
            margin: 0 0 0.5rem;
            color: <?= $alertType === 'success' ? '#28a745' : '#dc3545' ?>;
        }

        #modal-message p {
            margin: 0;
            color: #333;
        }
    </style>

    <div id="modal-overlay">
        <div id="modal-message">
            <h2><?= $alertType === 'success' ? 'Success' : 'Error' ?></h2>
            <p><?= htmlspecialchars(urldecode($message)) ?></p>
        </div>
    </div>

    <script>
        setTimeout(() => {
            const overlay = document.getElementById('modal-overlay');
            if (overlay) overlay.remove(); // cleaner than .style.display = 'none'

            const url = new URL(window.location.href);
            url.searchParams.delete('success');
            url.searchParams.delete('error');
            url.searchParams.delete('msg');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }, 3000);
    </script>
<?php endif; ?>