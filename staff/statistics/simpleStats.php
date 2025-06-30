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
                        <div class="text-dark fw-bold h5 mb-0"> <span>$<?php echo number_format($annualEarnings, 2); ?></span></div>
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