<?php

require_once('libs/fpdf/fpdf.php');

function generateExternalId($orderId)
{
    return 'receipt_' . $orderId . '_' . uniqid();
}


function createPdfReceipt($conn, $orderId, $customerId, $externalId, $paymentMethodName, $installationRequired = false, $installationCost = 0)
{
    require_once('libs/fpdf/fpdf.php');

    $stmt = $conn->prepare("SELECT FullName FROM Customer WHERE Id = ?");
    $stmt->execute([$customerId]);
    $customerName = $stmt->fetchColumn() ?: 'Unknown Customer';

    $stmt = $conn->prepare("SELECT * FROM OrderItem WHERE OrderId = ?");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Receipt', 0, 1, 'C');

    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "Order ID: $orderId", 0, 1);
    $pdf->Cell(0, 8, "Customer: $customerName", 0, 1);
    $pdf->Cell(0, 8, "Payment Method: $paymentMethodName", 0, 1);
    $pdf->Cell(0, 8, "Receipt Number $externalId", 0, 1);
    $pdf->Ln(4);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 8, 'Type', 1);
    $pdf->Cell(70, 8, 'Name', 1);
    $pdf->Cell(20, 8, 'Qty', 1, 0, 'R');
    $pdf->Cell(30, 8, 'Unit Price', 1, 0, 'R');
    $pdf->Cell(30, 8, 'Subtotal', 1, 1, 'R');

    $pdf->SetFont('Arial', '', 12);
    $total = 0;

    foreach ($orderItems as $item) {
        $type = ucfirst($item['OrderType']);
        $qty = $item['Quantity'];
        $unit = number_format($item['UnitPrice'], 2);
        $sub = number_format($item['Subtotal'], 2);
        $total += $item['Subtotal'];

        $name = '';
        if (strtolower($item['OrderType']) === 'product') {
            $stmt2 = $conn->prepare("SELECT Name FROM Products WHERE Id = ?");
            $stmt2->execute([$item['ProductId']]);
            $name = $stmt2->fetchColumn();
        } elseif (strtolower($item['OrderType']) === 'bundle') {
            $stmt2 = $conn->prepare("SELECT Name FROM Bundle WHERE Id = ?");
            $stmt2->execute([$item['BundleId']]);
            $name = $stmt2->fetchColumn();
        } elseif (strtolower($item['OrderType']) === 'event') {
            $stmt2 = $conn->prepare("SELECT Name FROM Event WHERE Id = ?");
            $stmt2->execute([$item['EventId']]);
            $name = $stmt2->fetchColumn();
        }

        $pdf->Cell(40, 8, $type, 1);
        $pdf->Cell(70, 8, utf8_decode($name), 1);
        $pdf->Cell(20, 8, $qty, 1, 0, 'R');
        $pdf->Cell(30, 8, 'Usd ' . $unit, 1, 0, 'R');
        $pdf->Cell(30, 8, 'Usd ' . $sub, 1, 1, 'R');

        if (strtolower($item['OrderType']) === 'bundle') {
            $stmt3 = $conn->prepare("
                SELECT BP.Quantity, P.Name
                FROM BundleProducts BP
                JOIN Products P ON BP.ProductId = P.Id
                WHERE BP.BundleId = ?");
            $stmt3->execute([$item['BundleId']]);
            while ($bp = $stmt3->fetch(PDO::FETCH_ASSOC)) {
                $pdf->Cell(40, 8, '', 1);
                $pdf->Cell(70, 8, '- ' . utf8_decode($bp['Name']), 1);
                $pdf->Cell(20, 8, $bp['Quantity'] * $qty, 1, 0, 'R');
                $pdf->Cell(30, 8, '', 1);
                $pdf->Cell(30, 8, '', 1, 1);
            }
        }

        if (strtolower($item['OrderType']) === 'event') {
            $stmt5 = $conn->prepare("
               SELECT EP.Quantity, P.Name
               FROM EventProducts EP
               JOIN Products P ON EP.ProductId = P.Id
               WHERE EP.EventId = ?");
            $stmt5->execute([$item['EventId']]);
            while ($ep = $stmt5->fetch(PDO::FETCH_ASSOC)) {
                $pdf->Cell(40, 8, '', 1);
                $pdf->Cell(70, 8, '- ' . utf8_decode($ep['Name']), 1);
                $pdf->Cell(20, 8, $ep['Quantity'] * $qty, 1, 0, 'R');
                $pdf->Cell(30, 8, '', 1);
                $pdf->Cell(30, 8, '', 1, 1);
            }
            $stmt4 = $conn->prepare("SELECT RentalStartDate, RentalEndDate FROM EventRental WHERE OrderItemId = ?");
            $stmt4->execute([$item['Id']]);
            $r = $stmt4->fetch(PDO::FETCH_ASSOC);
            if ($r) {
                $start = date('d M Y', strtotime($r['RentalStartDate']));
                $end = date('d M Y', strtotime($r['RentalEndDate']));
                $pdf->Cell(160, 8, 'Rental: ' . $start . ' to ' . $end, 1);
                $pdf->Cell(30, 8, '', 1, 1);
            }
        }
    }

    if ($installationRequired) {
        $inst = number_format($installationCost, 2);
        $pdf->Cell(160, 8, 'Installation Cost', 1);
        $pdf->Cell(30, 8, 'Usd ' . $inst, 1, 1, 'R');
        $total += $installationCost;
    }

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(160, 10, 'TOTAL', 1);
    $pdf->Cell(30, 10, 'Usd ' . number_format($total, 2), 1, 1, 'R');

    if (!is_dir('./assets/uploads/receipts')) {
        mkdir('./assets/uploads/receipts', 0777, true);
    }
    $filePath = "./assets/uploads/receipts/receipt_{$orderId}_" . time() . ".pdf";
    $pdf->Output('F', $filePath);

    return $filePath;
}
