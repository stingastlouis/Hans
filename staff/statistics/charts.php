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