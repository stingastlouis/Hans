<?php
include '../sessionManagement.php';
include '../configs/db.php';
include '../configs/constants.php';

$role = $_SESSION['role'];
if (in_array($role, INSTALLER_ONLY_ROLE)) {
    header("Location: installation.php");
    exit;
}

$stmt = $conn->query("SELECT COUNT(*) AS total_customers FROM Customer");
$totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'];

$stmt = $conn->query("SELECT COUNT(*) AS total_staff FROM Staff");
$totalStaff = $stmt->fetch(PDO::FETCH_ASSOC)['total_staff'];

$currentMonth = date('m');
$currentYear = date('Y');
$stmt = $conn->prepare("SELECT SUM(TotalAmount) AS monthly_earnings FROM `Order` WHERE MONTH(DateCreated) = ? AND YEAR(DateCreated) = ?");
$stmt->execute([$currentMonth, $currentYear]);
$monthlyEarnings = $stmt->fetch(PDO::FETCH_ASSOC)['monthly_earnings'] ?? 0;

$stmt = $conn->prepare("SELECT SUM(TotalAmount) AS annual_earnings FROM `Order` WHERE YEAR(DateCreated) = ?");
$stmt->execute([$currentYear]);
$annualEarnings = $stmt->fetch(PDO::FETCH_ASSOC)['annual_earnings'] ?? 0;


$stmt = $conn->query("SELECT COUNT(*) AS total_orders FROM `Order`");
$totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];
$stmt = $conn->query("SELECT COUNT(*) AS total_order_items FROM OrderItem");
$totalOrderItems = $stmt->fetch(PDO::FETCH_ASSOC)['total_order_items'];

$monthlyRevenue = [];
for ($month = 1; $month <= 12; $month++) {
    $stmt = $conn->prepare("SELECT SUM(TotalAmount) AS earnings FROM `Order` WHERE MONTH(DateCreated) = ? AND YEAR(DateCreated) = ?");
    $stmt->execute([$month, date('Y')]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthlyRevenue[] = $result['earnings'] ?? 0;
}

$stmt = $conn->query("SELECT OrderType, SUM(Subtotal) AS total_revenue FROM OrderItem GROUP BY OrderType");
$colors = ['red', 'blue', 'yellow'];
$colorIndex = 0;
$revenueLabels = [];
$revenueData = [];
$revenueColors = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $revenueLabels[] = ucfirst($row['OrderType']);
    $revenueData[] = (float)$row['total_revenue'];
    $revenueColors[] = $colors[$colorIndex % count($colors)];
    $colorIndex++;
}

?>


<?php include 'includes/header.php' ?>
<div class="container-fluid">
    <?php require_once "statistics/simpleStats.php" ?>
    <?php require_once "statistics/charts.php" ?>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('earningsChart').getContext('2d');
    const earningsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Earnings',
                data: <?php echo json_encode($monthlyRevenue); ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    grid: {
                        color: 'rgb(234, 236, 244)',
                        zeroLineColor: 'rgb(234, 236, 244)',
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    },
                    ticks: {
                        color: '#858796',
                        padding: 20,
                        beginAtZero: true
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById("revenueSourcesChart").getContext("2d");

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($revenueLabels) ?>,
                datasets: [{
                    data: <?= json_encode($revenueData) ?>,
                    backgroundColor: <?= json_encode($revenueColors) ?>,
                    borderColor: ["#ffffff", "#ffffff"],
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    display: false
                }
            }
        });
    });
</script>

<?php include 'includes/footer.php' ?>