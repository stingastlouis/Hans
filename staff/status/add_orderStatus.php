<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $orderId = trim($_POST['order_id']);
    $staffId = trim($_POST['staff_id']);
    $date = date('Y-m-d H:i:s');

    if (empty($statusId) || empty($orderId)) {
        echo "<h1>Field missing</h1>";
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO OrderStatus (orderid, statusid, staffid, datecreated) VALUES (:orderid, :statusid, :staffid, :datecreated)");
        $stmt->bindParam(':orderid', $orderId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':staffid', $staffId);
        $stmt->bindParam(':datecreated', $date);
        $stmt->execute();

        $statusStmt = $conn->prepare("SELECT Name FROM Status WHERE Id = :statusId LIMIT 1");
        $statusStmt->bindValue(':statusId', $statusId);
        $statusStmt->execute();
        $statusName = $statusStmt->fetchColumn();

        if (strtolower($statusName) === 'completed') {
            $itemStmt = $conn->prepare("SELECT Id FROM OrderItem WHERE OrderId = :orderId AND OrderType = 'event'");
            $itemStmt->bindValue(':orderId', $orderId);
            $itemStmt->execute();
            $eventOrderItemIds = $itemStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($eventOrderItemIds)) {
                $inClause = implode(',', array_fill(0, count($eventOrderItemIds), '?'));
                $updateStmt = $conn->prepare("UPDATE EventRental SET `Returned` = TRUE WHERE OrderItemId IN ($inClause)");
                $updateStmt->execute($eventOrderItemIds);
            }

            $installationStmt = $conn->prepare("SELECT Id FROM Installation WHERE OrderId = :orderId");
            $installationStmt->execute([':orderId' => $orderId]);
            $installationId = $installationStmt->fetchColumn();

            if ($installationId) {
                $installedStatusStmt = $conn->prepare("SELECT Id FROM Status WHERE Name = 'Installed' LIMIT 1");
                $installedStatusStmt->execute();
                $installedStatusId = $installedStatusStmt->fetchColumn();

                if ($installedStatusId) {
                    $insertInstallationStatus = $conn->prepare("
                        INSERT INTO InstallationStatus (installationid, statusid, staffid, datecreated)
                        VALUES (:installationid, :statusid, :staffid, :datecreated)
                    ");
                    $insertInstallationStatus->execute([
                        ':installationid' => $installationId,
                        ':statusid' => $installedStatusId,
                        ':staffid' => $staffId,
                        ':datecreated' => $date
                    ]);
                }
            }
        } elseif (strtolower($statusName) === 'cancelled') {
            $stmt = $conn->prepare("SELECT * FROM OrderItem WHERE OrderId = :orderId");
            $stmt->bindValue(':orderId', $orderId);
            $stmt->execute();
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $eventOrderItemIds = [];

            foreach ($orderItems as $item) {
                $orderType = $item['OrderType'];
                $quantity = $item['Quantity'];

                if ($orderType === 'product') {
                    $productId = $item['ProductId'];
                    $update = $conn->prepare("UPDATE Products SET Stock = Stock + :qty WHERE Id = :productId");
                    $update->execute([':qty' => $quantity, ':productId' => $productId]);
                } elseif ($orderType === 'bundle') {
                    $bundleId = $item['BundleId'];
                    $bundleQty = $item['Quantity'];

                    $bpStmt = $conn->prepare("SELECT ProductId, Quantity FROM BundleProducts WHERE BundleId = :bundleId");
                    $bpStmt->execute([':bundleId' => $bundleId]);
                    while ($bp = $bpStmt->fetch(PDO::FETCH_ASSOC)) {
                        $totalQty = $bp['Quantity'] * $bundleQty;
                        $update = $conn->prepare("UPDATE Products SET Stock = Stock + :qty WHERE Id = :productId");
                        $update->execute([':qty' => $totalQty, ':productId' => $bp['ProductId']]);
                    }
                } elseif ($orderType === 'event') {
                    $eventId = $item['EventId'];
                    $eventQty = $item['Quantity'];

                    $eventOrderItemIds[] = $item['Id'];

                    $epStmt = $conn->prepare("SELECT ProductId, Quantity FROM EventProducts WHERE EventId = :eventId");
                    $epStmt->execute([':eventId' => $eventId]);
                    while ($ep = $epStmt->fetch(PDO::FETCH_ASSOC)) {
                        $totalQty = $ep['Quantity'] * $eventQty;
                        $update = $conn->prepare("UPDATE Products SET Stock = Stock + :qty WHERE Id = :productId");
                        $update->execute([':qty' => $totalQty, ':productId' => $ep['ProductId']]);
                    }
                }
            }

            if (!empty($eventOrderItemIds)) {
                $inClause = implode(',', array_fill(0, count($eventOrderItemIds), '?'));
                $updateStmt = $conn->prepare("UPDATE EventRental SET `Returned` = TRUE WHERE OrderItemId IN ($inClause)");
                $updateStmt->execute($eventOrderItemIds);
            }
        }



        header("Location: ../order.php?success=1");
        exit;
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: ../order.php");
    exit;
}
