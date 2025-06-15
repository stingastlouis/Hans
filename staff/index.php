<?php
include '../configs/db.php'; 

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

$revenueLabels = [];
$revenueData = [];
$revenueColors = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $revenueLabels[] = ucfirst($row['OrderType']);
    $revenueData[] = (float)$row['total_revenue'];

    $revenueColors[] = $row['OrderType'] === 'event' ? '#4e73df' : '#1cc88a';
}

?>


<?php include 'includes/header.php'?>
<div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-start-primary py-2">
                                <div class="card-body">
                                    <div class="row align-items-center no-gutters">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-primary fw-bold text-xs mb-1"><span>Customers</span></div>
                                            <div class="text-dark fw-bold h5 mb-0"> <span><?php echo $totalCustomers; ?></span></div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-calendar fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-start-success py-2">
                                <div class="card-body">
                                    <div class="row align-items-center no-gutters">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-success fw-bold text-xs mb-1"><span>Earnings (annual)</span></div>
                                            <div class="text-dark fw-bold h5 mb-0">  <span>$<?php echo number_format($annualEarnings, 2); ?></span></div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-start-info py-2">
                                <div class="card-body">
                                    <div class="row align-items-center no-gutters">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-info fw-bold text-xs mb-1"><span>Orders</span></div>
                                            <div class="row g-0 align-items-center">
                                                <div class="col-auto">
                                                    <div class="text-dark fw-bold h5 mb-0 me-3"> <span><?php echo $totalOrders; ?></span></div>
                                                </div>
    
                                            </div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-clipboard-list fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-start-warning py-2">
                                <div class="card-body">
                                    <div class="row align-items-center no-gutters">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-warning fw-bold text-xs mb-1"><span>earned this month</span></div>
                                            <div class="text-dark fw-bold h5 mb-0"><span>$<?php echo number_format($monthlyEarnings, 2); ?> </span></div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-comments fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-7 col-xl-8">
                            <div class="card shadow mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="text-primary fw-bold m-0">Earnings Overview</h6>
                                </div>
                                <div class="card-body">
                                <div class="chart-area">
                                    <canvas id="earningsChart"></canvas>
                                </div>

                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5 col-xl-4">
                            <div class="card shadow mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="text-primary fw-bold m-0">Revenue Sources</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-area">
                                        <canvas id="revenueSourcesChart"></canvas>
                                    </div>
                                    <div class="text-center small mt-4">
                                        <?php foreach ($revenueLabels as $i => $label): ?>
                                            <span class="me-2">
                                                <i class="fas fa-circle" style="color: <?= $revenueColors[$i] ?>"></i>&nbsp;<?= $label ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
    document.addEventListener("DOMContentLoaded", function () {
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

<?php include 'includes/footer.php'?>