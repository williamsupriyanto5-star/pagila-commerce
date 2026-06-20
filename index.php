<?php
// 1. Ambil file konfigurasi database
require_once(__DIR__ . "/config/db.php");

// 2. Buat instance koneksi database
$conn = koneksiDB(); 

// 3. Tangkap parameter halaman dari URL (Default: dashboard)
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Movie Rental BI Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow-x: hidden; }
        .wrapper { display: flex; align-items: stretch; min-height: 100vh; }
        
        /* Sidebar Tetap Terkunci di Kiri */
        #sidebar-container { min-width: 260px; max-width: 260px; background: #1e2229; color: #fff; transition: all 0.3s; }
        #sidebar-container .sidebar-header { padding: 20px; background: #16191d; font-weight: bold; font-size: 1.15rem; color: #00ecb9; }
        #sidebar-container ul li a { padding: 14px 20px; display: block; color: #98a6ad; text-decoration: none; transition: 0.2s; font-size: 0.95rem; }
        #sidebar-container ul li a:hover, #sidebar-container ul li a.active { color: #fff; background: #282e38; border-left: 4px solid #00ecb9; }
        
        /* Area Konten Utama Dengan Fitur Scroll Alami Kebawah */
        .main-content { flex-grow: 1; padding: 25px; width: calc(100% - 260px); height: 100vh; overflow-y: auto; }
        
        /* Modifikasi Card KPI & Grafik Agar Kompak & Pas di Layar */
        .card-kpi { background: #fff; border: none; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); padding: 15px 20px; transition: transform 0.2s; }
        .card-kpi h3 { font-size: 1.6rem; }
        .chart-card { background: #fff; border: none; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); padding: 15px; margin-bottom: 20px; }
        .chart-card h6 { font-size: 0.9rem; margin-bottom: 12px; font-weight: 700; color: #495057; }
        
        /* Kontainer khusus scrollable jika data tabel atau grafik terlalu panjang */
        .scrollable-card-body { max-height: 280px; overflow-y: auto; padding-right: 5px; }
        .table-responsive { background: #fff; border-radius: 8px; }
    </style>
</head>
<body>

<div class="wrapper w-100">
    <nav id="sidebar-container">
        <div class="sidebar-header border-bottom border-secondary d-flex align-items-center">
            <i class="fa-solid fa-cube me-2 fs-4"></i> <span>PAGILA BI v2.0</span>
        </div>
        <ul class="list-unstyled pt-3">
            <li><a href="index.php?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : ''; ?>"><i class="fa-solid fa-gauge me-2"></i> Executive Summary</a></li>
            <li><a href="index.php?page=revenue" class="<?= $page === 'revenue' ? 'active' : ''; ?>"><i class="fa-solid fa-sack-dollar me-2"></i> Financial & Sales</a></li>
            <li><a href="index.php?page=customer" class="<?= $page === 'customer' ? 'active' : ''; ?>"><i class="fa-solid fa-user-astronaut me-2"></i> Customer Analytics</a></li>
            <li><a href="index.php?page=film" class="<?= $page === 'film' ? 'active' : ''; ?>"><i class="fa-solid fa-clapperboard me-2"></i> Film Performance</a></li>
            <li><a href="index.php?page=store" class="<?= $page === 'store' ? 'active' : ''; ?>"><i class="fa-solid fa-store me-2"></i> Store Performance</a></li>
        </ul>
        <div class="p-3 mt-5">
            <div class="text-center p-2 rounded" style="background: rgba(0,236,185,0.1); color: #00ecb9; font-size: 0.85rem;">
                <i class="fa-solid fa-circle-check me-1"></i> Compact Layout Active
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <div>
                <h4 class="text-dark fw-bold m-0" style="font-size: 1.3rem;">
                    <?= $page === 'revenue' ? 'Financial & Sales Analytics' : 'Executive Summary Dashboard'; ?>
                </h4>
                <small class="text-muted" style="font-size: 0.8rem;">Real-time Dynamic Analytics Overview</small>
            </div>
            <div class="badge bg-dark px-2 py-1" style="font-size: 0.75rem;"><i class="fa-regular fa-clock me-1"></i> State: Responsive</div>
        </div>

        <?php
        // ====================================================
        // ROUTING KONTEN INTERNAL
        // ====================================================
        
        // --- 1. DETAILED FINANCIAL & SALES PAGE ---
        if ($page === 'revenue') {
            $revKpiTotal = 0; $revKpiTxCount = 0; $revKpiAvgTx = 0;
            $revTrendLabels = []; $revTrendValues = [];
            $methodLabels = ['Cash Payment', 'Credit Card']; $methodValues = [0, 0];

            try {
                // Perhitungan KPI Ringkasan Keuangan
                $stmtKpi = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total_rev, COALESCE(SUM(payment_count), 0) AS total_tx FROM public.fact_sales");
                $kpiRes = $stmtKpi->fetch(PDO::FETCH_ASSOC);
                $revKpiTotal = $kpiRes['total_rev'];
                $revKpiTxCount = $kpiRes['total_tx'];
                $revKpiAvgTx = $revKpiTxCount > 0 ? ($revKpiTotal / $revKpiTxCount) : 0;

                // Ambil tren harian/bulanan detail keuangan
                $stmtRevTrend = $conn->query("SELECT SUBSTRING(date_key::text FROM 1 FOR 4) || '-' || SUBSTRING(date_key::text FROM 5 FOR 2) || '-' || SUBSTRING(date_key::text FROM 7 FOR 2) as tanggal, SUM(amount) as total_harian FROM public.fact_sales GROUP BY tanggal ORDER BY tanggal ASC LIMIT 30");
                foreach($stmtRevTrend->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $revTrendLabels[] = $row['tanggal'];
                    $revTrendValues[] = floatval($row['total_harian']);
                }

                // Ambil data distribusi metode pembayaran dari ringkasan performa toko
                $stmtMethod = $conn->query("SELECT COALESCE(SUM(cash_revenue), 0) as total_cash, COALESCE(SUM(credit_revenue), 0) as total_credit FROM public.fact_store_performance");
                $methodRes = $stmtMethod->fetch(PDO::FETCH_ASSOC);
                $methodValues = [floatval($methodRes['total_cash']), floatval($methodRes['total_credit'])];

                // Ambil daftar log transaksi data penjualan terkini
                $stmtSales = $conn->query("SELECT sales_key, customer_key, amount, payment_count FROM public.fact_sales ORDER BY sales_key DESC LIMIT 10");
                $salesData = $stmtSales->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) { $salesData = []; }
            ?>
            <div class="container-fluid p-0">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card-kpi border-start border-primary border-4">
                            <div class="text-muted text-uppercase small fw-bold" style="font-size:0.75rem;">Total Financial Gross</div>
                            <h3 class="m-0 mt-1 fw-bold text-primary">Rp <?= number_format($revKpiTotal, 0, ",", "."); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-kpi border-start border-success border-4">
                            <div class="text-muted text-uppercase small fw-bold" style="font-size:0.75rem;">Volume Penjualan Terlacak</div>
                            <h3 class="m-0 mt-1 fw-bold text-success"><?= number_format($revKpiTxCount); ?> Transaksi</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-kpi border-start border-info border-4">
                            <div class="text-muted text-uppercase small fw-bold" style="font-size:0.75rem;">AOV (Average Order Value)</div>
                            <h3 class="m-0 mt-1 fw-bold text-info">Rp <?= number_format($revKpiAvgTx, 0, ",", "."); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-lg-8">
                        <div class="chart-card">
                            <h6><i class="fa-solid fa-chart-line me-1"></i> Timeline Tren Penjualan Harian</h6>
                            <div style="position: relative; height:260px;"><canvas id="detailedRevenueChart"></canvas></div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="chart-card">
                            <h6><i class="fa-solid fa-wallet me-1"></i> Komparasi Metode Pembayaran</h6>
                            <div style="position: relative; height:260px;"><canvas id="paymentMethodChart"></canvas></div>
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <h6>Log Transaksi Penjualan Terkini (fact_sales)</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-sm" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr><th>Sales Key ID</th><th>Customer Identifier</th><th>Payment Volume</th><th>Net Revenue Amount</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($salesData as $tx): ?>
                                <tr>
                                    <td><span class="badge bg-secondary">#<?= $tx['sales_key']; ?></span></td>
                                    <td>Customer ID: <?= $tx['customer_key']; ?></td>
                                    <td><?= $tx['payment_count']; ?> Tx</td>
                                    <td class="text-success fw-bold">Rp <?= number_format(floatval($tx['amount']), 0, ",", "."); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    new Chart(document.getElementById('detailedRevenueChart'), {
                        type: 'line',
                        data: {
                            labels: <?= json_encode($revTrendLabels ?: ['No Data']); ?>,
                            datasets: [{
                                label: 'Revenue (Rp)',
                                data: <?= json_encode($revTrendValues ?: [0]); ?>,
                                borderColor: '#1cc88a',
                                backgroundColor: 'rgba(28,200,138,0.05)',
                                fill: true,
                                tension: 0.2,
                                pointRadius: 3
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });

                    new Chart(document.getElementById('paymentMethodChart'), {
                        type: 'doughnut',
                        data: {
                            labels: <?= json_encode($methodLabels); ?>,
                            datasets: [{
                                data: <?= json_encode($methodValues); ?>,
                                backgroundColor: ['#f6c23e', '#4e73df']
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                    });
                });
            </script>
            <?php
        } 
        
        // --- 2. ADVANCED CUSTOMER BEHAVIOR PAGE ---
        elseif ($page === 'customer') {
            try {
                $stmtCust = $conn->query("SELECT customer_key, total_lifetime_rentals, customer_lifetime_value, churn_risk_score, days_since_last_rental FROM public.fact_customer_activity ORDER BY customer_lifetime_value DESC LIMIT 15");
                $customerData = $stmtCust->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) { $customerData = []; }
            ?>
            <div class="container-fluid p-0">
                <h5 class="mb-3 fw-bold text-success"><i class="fa-solid fa-users-gear me-2"></i>Profil & Perilaku Pelanggan</h5>
                <div class="chart-card">
                    <h6>Prediksi Churn & Valuasi Pelanggan (fact_customer_activity)</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr><th>Customer Key</th><th>Lifetime Rentals</th><th>Lifetime Value (CLV)</th><th>Recency</th><th>Churn Risk</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($customerData as $c): ?>
                                <tr>
                                    <td><strong>Pelanggan #<?= $c['customer_key']; ?></strong></td>
                                    <td><?= $c['total_lifetime_rentals']; ?> Kali</td>
                                    <td class="text-primary fw-bold">Rp <?= number_format($c['customer_lifetime_value'], 2, ",", "."); ?></td>
                                    <td><?= $c['days_since_last_rental']; ?> Hari Lalu</td>
                                    <td>
                                        <?php 
                                        $risk = $c['churn_risk_score'] * 100;
                                        if($risk >= 70) echo "<span class='badge bg-danger'>High (".round($risk)."%)</span>";
                                        elseif($risk >= 40) echo "<span class='badge bg-warning text-dark'>Medium (".round($risk)."%)</span>";
                                        else echo "<span class='badge bg-success'>Loyal (".round($risk)."%)</span>";
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php
        } 
        
        // --- 3. FILM PERFORMANCE ENGINE ---
        elseif ($page === 'film') {
            try {
                $stmtFilm = $conn->query("
                    SELECT fp.film_key, f.title, fp.inventory_count, fp.rented_copies, fp.utilization_rate, fp.rental_revenue, fp.roi_percent 
                    FROM public.fact_film_performance fp
                    INNER JOIN public.dim_film f ON fp.film_key = f.film_key
                    ORDER BY fp.rental_revenue DESC 
                    LIMIT 15
                ");
                $filmData = $stmtFilm->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) { $filmData = []; }
            ?>
            <div class="container-fluid p-0">
                <h5 class="mb-3 fw-bold text-warning"><i class="fa-solid fa-film me-2"></i>Analisis Performa Film</h5>
                <div class="chart-card">
                    <h6>Top Performa Finansial Judul Film (fact_film_performance)</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr><th>Film Title</th><th>Total Kopi</th><th>Sedang Disewa</th><th>Utilisasi</th><th>Revenue</th><th>ROI</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($filmData as $f): ?>
                                <tr>
                                    <td><strong><?= $f['title']; ?> (ID: <?= $f['film_key']; ?>)</strong></td>
                                    <td><?= $f['inventory_count']; ?> Kopi</td>
                                    <td><?= $f['rented_copies']; ?> Unit</td>
                                    <td><?= round($f['utilization_rate']*100, 1); ?>%</td>
                                    <td class="text-success fw-bold">Rp <?= number_format(floatval($f['rental_revenue']), 0, ",", "."); ?></td>
                                    <td><span class="text-primary fw-bold"><?= round($f['roi_percent'], 1); ?>%</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php
        }

        // --- 4. DEEP STORE PERFORMANCE PAGE ---
        elseif ($page === 'store') {
            try {
                $stmtStore = $conn->query("SELECT store_key, total_transactions, total_revenue, cash_revenue, credit_revenue, late_fee_revenue, net_profit FROM public.fact_store_performance ORDER BY total_revenue DESC");
                $storeData = $stmtStore->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) { $storeData = []; }
            ?>
            <div class="container-fluid p-0">
                <h5 class="mb-3 fw-bold text-info"><i class="fa-solid fa-store me-2"></i>Kinerja Operasional Toko</h5>
                <div class="chart-card">
                    <h6>Metrik Keuangan Cabang Toko (fact_store_performance)</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr><th>Store ID</th><th>Volume Tx</th><th>Cash Rev</th><th>Credit Rev</th><th>Denda</th><th>Net Profit</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($storeData as $s): ?>
                                <tr>
                                    <td><span class="badge bg-dark">Store #<?= $s['store_key']; ?></span></td>
                                    <td><?= number_format($s['total_transactions']); ?> Tx</td>
                                    <td>Rp <?= number_format($s['cash_revenue'], 2, ",", "."); ?></td>
                                    <td>Rp <?= number_format($s['credit_revenue'], 2, ",", "."); ?></td>
                                    <td class="text-danger">Rp <?= number_format($s['late_fee_revenue'], 2, ",", "."); ?></td>
                                    <td class="text-primary fw-bold">Rp <?= number_format($s['net_profit'], 2, ",", "."); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php
        }
        
        // --- DEFAULT: EXECUTIVE SUMMARY (MAIN DASHBOARD) ---
        else {
            $storeLabels = []; $storeNetProfit = []; $trendLabels = []; $trendValues = []; 
            $filmLabels = []; $filmRevenue = []; $churnLabels = []; $churnValues = []; $clvLabels = []; $clvValues = [];
            $totalRevenue = 0; $totalRentalsCount = 0; $totalActiveCustomers = 0; $avgNetProfit = 0;

            try {
                $stmt = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM public.fact_sales");
                $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

                $stmtRental = $conn->query("SELECT COALESCE(SUM(payment_count), 0) AS total FROM public.fact_sales");
                $totalRentalsCount = $stmtRental->fetch(PDO::FETCH_ASSOC)['total'];

                $stmtCustomer = $conn->query("SELECT COUNT(DISTINCT customer_key) AS total FROM public.fact_sales");
                $totalActiveCustomers = $stmtCustomer->fetch(PDO::FETCH_ASSOC)['total'];

                $stmtAvgProfit = $conn->query("SELECT COALESCE(ROUND(AVG(net_profit), 2), 0) AS avg_profit FROM public.fact_store_performance");
                $avgNetProfit = $stmtAvgProfit->fetch(PDO::FETCH_ASSOC)['avg_profit'];

                $stmtStorePerf = $conn->query("SELECT store_key, total_revenue FROM public.fact_store_performance ORDER BY total_revenue DESC");
                foreach($stmtStorePerf->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $storeLabels[] = "Branch " . $row['store_key'];
                    $storeNetProfit[] = floatval($row['total_revenue']);
                }

                $stmtTrend = $conn->query("SELECT SUBSTRING(date_key::text FROM 1 FOR 4) || '-' || SUBSTRING(date_key::text FROM 5 FOR 2) as bulan, SUM(amount) as total_bulanan FROM public.fact_sales GROUP BY bulan ORDER BY bulan ASC");
                foreach($stmtTrend->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $trendLabels[] = $row['bulan'];
                    $trendValues[] = floatval($row['total_bulanan']);
                }

                $stmtFilmPerf = $conn->query("
                    SELECT f.title, fp.rental_revenue 
                    FROM public.fact_film_performance fp
                    INNER JOIN public.dim_film f ON fp.film_key = f.film_key
                    ORDER BY fp.rental_revenue DESC 
                    LIMIT 10
                ");
                
                $filmLabels = [];
                $filmRevenue = [];
                
                foreach($stmtFilmPerf->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $filmLabels[] = $row['title'];
                    $filmRevenue[] = floatval($row['rental_revenue']);
                }

                $stmtChurn = $conn->query("SELECT CASE WHEN churn_risk_score >= 0.7 THEN 'High Risk' WHEN churn_risk_score >= 0.4 THEN 'Medium Risk' ELSE 'Low Risk' END as status_risk, COUNT(*) as total_cust FROM public.fact_customer_activity GROUP BY status_risk");
                foreach($stmtChurn->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $churnLabels[] = $row['status_risk'];
                    $churnValues[] = (int)$row['total_cust'];
                }

                $stmtCLV = $conn->query("SELECT customer_key, MAX(customer_lifetime_value) as clv FROM public.fact_customer_activity GROUP BY customer_key ORDER BY clv DESC LIMIT 5");
                foreach($stmtCLV->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $clvLabels[] = "Cust " . $row['customer_key'];
                    $clvValues[] = floatval($row['clv']);
                }
            } catch (PDOException $e) {}
            ?>

            <div class="row g-3 mb-3">
                <div class="col-lg-3 col-md-6">
                    <div class="card-kpi border-start border-primary border-4">
                        <div class="text-muted text-uppercase small fw-bold" style="font-size:0.75rem;">Gross Sales Volume</div>
                        <h3 class="m-0 mt-1 fw-bold text-primary">Rp <?= number_format($totalRevenue, 0, ",", "."); ?></h3>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card-kpi border-start border-success border-4">
                        <div class="text-muted text-uppercase small fw-bold" style="font-size:0.75rem;">Total Rentals Tracked</div>
                        <h3 class="m-0 mt-1 fw-bold text-success"><?= number_format($totalRentalsCount); ?> Log</h3>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card-kpi border-start border-warning border-4">
                        <div class="text-muted text-uppercase small fw-bold" style="font-size:0.75rem;">Active Demographics</div>
                        <h3 class="m-0 mt-1 fw-bold text-warning"><?= number_format($totalActiveCustomers); ?> User</h3>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card-kpi border-start border-danger border-4">
                        <div class="text-muted text-uppercase small fw-bold" style="font-size:0.75rem;">Avg Branch Profit</div>
                        <h3 class="m-0 mt-1 fw-bold text-danger">Rp <?= number_format($avgNetProfit, 0, ",", "."); ?></h3>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="chart-card">
                        <h6><i class="fa-solid fa-chart-area me-1"></i> Enterprise Revenue Trend Line</h6>
                        <div style="position: relative; height:240px;"><canvas id="revenueChart"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="chart-card">
                        <h6><i class="fa-solid fa-chart-pie me-1"></i> Market Share by Store</h6>
                        <div style="position: relative; height:240px;"><canvas id="storeChart"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="chart-card">
                        <h6><i class="fa-solid fa-chart-bar me-1"></i> Top 10 Film Revenue Performance</h6>
                        <div style="position: relative; height:240px;"><canvas id="filmChart"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="chart-card">
                        <h6><i class="fa-solid fa-user-shield me-1"></i> Customer Churn Risk</h6>
                        <div style="position: relative; height:240px;"><canvas id="customerChart"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-12">
                    <div class="chart-card">
                        <h6><i class="fa-solid fa-crown me-1"></i> High-Value Renter Profiles (Top Customer Lifetime Value)</h6>
                        <div class="scrollable-card-body">
                            <div style="position: relative; height:220px;"><canvas id="topCustomerChart"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                const storeLabels = <?= json_encode(!empty($storeLabels) ? $storeLabels : ['No Data']); ?>;
                const storeValues = <?= json_encode(!empty($storeNetProfit) ? $storeNetProfit : [0]); ?>;
                const trendLabels = <?= json_encode(!empty($trendLabels) ? $trendLabels : ['No Data']); ?>;
                const trendValues = <?= json_encode(!empty($trendValues) ? $trendValues : [0]); ?>;
                const filmLabels  = <?= json_encode(!empty($filmLabels) ? $filmLabels : ['No Data']); ?>;
                const filmValues  = <?= json_encode(!empty($filmRevenue) ? $filmRevenue : [0]); ?>;
                const churnLabels = <?= json_encode(!empty($churnLabels) ? $churnLabels : ['No Data']); ?>;
                const churnValues = <?= json_encode(!empty($churnValues) ? $churnValues : [0]); ?>;
                const clvLabels   = <?= json_encode(!empty($clvLabels) ? $clvLabels : ['No Data']); ?>;
                const clvValues   = <?= json_encode(!empty($clvValues) ? $clvValues : [0]); ?>;

                document.addEventListener("DOMContentLoaded", function() {
                    const ctxRevenue = document.getElementById('revenueChart');
                    if (ctxRevenue) {
                        new Chart(ctxRevenue, {
                            type: 'line',
                            data: {
                                labels: trendLabels,
                                datasets: [{
                                    label: 'Gross Revenue (Rp)',
                                    data: trendValues,
                                    borderColor: '#4e73df',
                                    backgroundColor: 'rgba(78,115,223,0.05)',
                                    fill: true,
                                    tension: 0.15,
                                pointRadius: 2
                                }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
                        });
                    }

                    const ctxStore = document.getElementById('storeChart');
                    if (ctxStore) {
                        new Chart(ctxStore, {
                            type: 'doughnut',
                            data: {
                                labels: storeLabels,
                                datasets: [{
                                    data: storeValues,
                                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
                                }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
                        });
                    }

                    const ctxFilm = document.getElementById('filmChart');
                    if (ctxFilm) {
                        new Chart(ctxFilm, {
                            type: 'bar',
                            data: {
                                labels: filmLabels,
                                datasets: [{
                                    label: 'Total Yield (Rp)',
                                    data: filmValues,
                                    backgroundColor: '#f6c23e'
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } },
                                scales: { x: { beginAtZero: true } }
                            }
                        });
                    }

                    const ctxCustomer = document.getElementById('customerChart');
                    if (ctxCustomer) {
                        new Chart(ctxCustomer, {
                            type: 'pie',
                            data: {
                                labels: churnLabels,
                                datasets: [{
                                    data: churnValues,
                                    backgroundColor: ['#e74a3b', '#1cc88a', '#f6c23e']
                                }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
                        });
                        
                    }

                    

                    const ctxTopCust = document.getElementById('topCustomerChart');
                    if (ctxTopCust) {
                        new Chart(ctxTopCust, {
                            type: 'bar',
                            data: {
                                labels: clvLabels,
                                datasets: [{
                                    label: 'CLV Value (Rp)',
                                    data: clvValues,
                                    backgroundColor: '#4e73df'
                                }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
                        });
                    }
                });
            </script>
        <?php } ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>