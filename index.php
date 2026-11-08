<?php
require 'fuction.php';
require 'cek.php';

/**
 * Mengambil satu nilai (angka) dari query agregat.
 */
function ambil_nilai($conn, $query)
{
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_row($result);
    return $row ? $row[0] : 0;
}

// ===================== KARTU STATISTIK =====================
$totalBarang     = (int) ambil_nilai($conn, "SELECT COUNT(*) FROM stok");
$totalStok       = (int) ambil_nilai($conn, "SELECT COALESCE(SUM(stock), 0) FROM stok");
$totalKategori   = (int) ambil_nilai($conn, "SELECT COUNT(*) FROM kategori");
$masukBulanIni   = (int) ambil_nilai($conn, "SELECT COALESCE(SUM(qty), 0) FROM masuk
                                            WHERE MONTH(tanggal) = MONTH(CURDATE())
                                              AND YEAR(tanggal) = YEAR(CURDATE())");
$keluarBulanIni  = (int) ambil_nilai($conn, "SELECT COALESCE(SUM(qty), 0) FROM keluar
                                            WHERE MONTH(tanggal) = MONTH(CURDATE())
                                              AND YEAR(tanggal) = YEAR(CURDATE())");
$jumlahMenipis   = (int) ambil_nilai($conn, "SELECT COUNT(*) FROM stok WHERE stock <= stok_minimum");

// ===================== DATA GRAFIK 6 BULAN =====================
$mulaiGrafik = date('Y-m-01', strtotime('first day of -5 month'));

$mapMasuk = [];
$queryMasuk = mysqli_query($conn, "SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan, SUM(qty) AS total
                                   FROM masuk
                                   WHERE tanggal >= '$mulaiGrafik'
                                   GROUP BY bulan");
while ($row = mysqli_fetch_assoc($queryMasuk)) {
    $mapMasuk[$row['bulan']] = (int) $row['total'];
}

$mapKeluar = [];
$queryKeluar = mysqli_query($conn, "SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan, SUM(qty) AS total
                                    FROM keluar
                                    WHERE tanggal >= '$mulaiGrafik'
                                    GROUP BY bulan");
while ($row = mysqli_fetch_assoc($queryKeluar)) {
    $mapKeluar[$row['bulan']] = (int) $row['total'];
}

$labelBulan = [];
$dataMasuk  = [];
$dataKeluar = [];

for ($i = 5; $i >= 0; $i--) {
    $waktu = strtotime("first day of -$i month");
    $kunci = date('Y-m', $waktu);

    $labelBulan[] = date('M Y', $waktu);
    $dataMasuk[]  = $mapMasuk[$kunci] ?? 0;
    $dataKeluar[] = $mapKeluar[$kunci] ?? 0;
}

// ===================== STOK MENIPIS =====================
$stokMenipis = mysqli_query($conn, "SELECT s.*, k.namakategori
                                    FROM stok s
                                    LEFT JOIN kategori k ON k.idkategori = s.idkategori
                                    WHERE s.stock <= s.stok_minimum
                                    ORDER BY s.stock ASC
                                    LIMIT 5");

// ===================== TRANSAKSI TERBARU =====================
$transaksiTerbaru = mysqli_query($conn, "SELECT * FROM (
                                            (SELECT 'Masuk' AS jenis, m.tanggal, s.namabaraang, m.qty, m.keterangan AS info
                                             FROM masuk m
                                             JOIN stok s ON s.idbarang = m.idbarang
                                             ORDER BY m.tanggal DESC LIMIT 5)
                                            UNION ALL
                                            (SELECT 'Keluar' AS jenis, k.tanggal, s.namabaraang, k.qty, k.penerima AS info
                                             FROM keluar k
                                             JOIN stok s ON s.idbarang = k.idbarang
                                             ORDER BY k.tanggal DESC LIMIT 5)
                                        ) AS gabungan
                                        ORDER BY tanggal DESC
                                        LIMIT 6");

// ===================== RINGKASAN KATEGORI =====================
$ringkasanKategori = mysqli_query($conn, "SELECT k.namakategori, COUNT(s.idbarang) AS jumlah
                                          FROM kategori k
                                          LEFT JOIN stok s ON s.idkategori = k.idkategori
                                          GROUP BY k.idkategori, k.namakategori
                                          ORDER BY jumlah DESC, k.namakategori ASC
                                          LIMIT 5");

$judul  = 'Dashboard';
$active = 'dashboard';

$labelBulanJson = json_encode($labelBulan);
$dataMasukJson  = json_encode($dataMasuk);
$dataKeluarJson = json_encode($dataKeluar);

$extra_scripts = <<<HTML
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
    var ctx = document.getElementById("chartTransaksi");
    new Chart(ctx, {
        type: "bar",
        data: {
            labels: {$labelBulanJson},
            datasets: [
                {
                    label: "Barang Masuk",
                    backgroundColor: "rgba(40, 167, 69, 0.75)",
                    borderColor: "rgba(40, 167, 69, 1)",
                    borderWidth: 1,
                    data: {$dataMasukJson}
                },
                {
                    label: "Barang Keluar",
                    backgroundColor: "rgba(220, 53, 69, 0.75)",
                    borderColor: "rgba(220, 53, 69, 1)",
                    borderWidth: 1,
                    data: {$dataKeluarJson}
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { position: "bottom" },
            scales: {
                yAxes: [{
                    ticks: { beginAtZero: true, precision: 0 }
                }]
            }
        }
    });
</script>
HTML;

include 'partials/header.php';
?>

<h1 class="mt-4">Dashboard</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item active">Ringkasan inventaris</li>
</ol>

<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50">Total Jenis Barang</div>
                        <div class="h3 mb-0"><?= number_format($totalBarang, 0, ',', '.'); ?></div>
                    </div>
                    <i class="fas fa-box fa-2x text-white-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="barang.php">Lihat data barang</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card bg-info text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50">Total Stok Tersedia</div>
                        <div class="h3 mb-0"><?= number_format($totalStok, 0, ',', '.'); ?></div>
                    </div>
                    <i class="fas fa-cubes fa-2x text-white-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="barang.php">Kelola stok</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50">Barang Masuk Bulan Ini</div>
                        <div class="h3 mb-0"><?= number_format($masukBulanIni, 0, ',', '.'); ?></div>
                    </div>
                    <i class="fas fa-arrow-circle-down fa-2x text-white-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="masuk.php">Catat barang masuk</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card bg-danger text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-white-50">Barang Keluar Bulan Ini</div>
                        <div class="h3 mb-0"><?= number_format($keluarBulanIni, 0, ',', '.'); ?></div>
                    </div>
                    <i class="fas fa-arrow-circle-up fa-2x text-white-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="keluar.php">Catat barang keluar</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-bar mr-1"></i>
                Barang Masuk vs Keluar (6 Bulan Terakhir)
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="chartTransaksi"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="fas fa-exclamation-triangle mr-1"></i> Stok Menipis</span>
                <span class="badge badge-danger"><?= $jumlahMenipis; ?></span>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (mysqli_num_rows($stokMenipis) > 0) : ?>
                        <?php while ($item = mysqli_fetch_assoc($stokMenipis)) : ?>
                            <a href="barang.php?q=<?= urlencode($item['namabaraang']); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="font-weight-bold"><?= htmlspecialchars($item['namabaraang']); ?></div>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars($item['namakategori'] ?? 'Tanpa kategori'); ?>
                                        &middot; minimum <?= (int) $item['stok_minimum']; ?>
                                    </div>
                                </div>
                                <span class="badge badge-danger badge-pill"><?= (int) $item['stock']; ?></span>
                            </a>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                            Tidak ada stok di bawah minimum.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer small text-muted">
                Atur batas minimum stok di menu <a href="barang.php">Data Barang</a>.
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="fas fa-history mr-1"></i> Transaksi Terbaru</span>
                <a class="small" href="laporan.php">Lihat laporan</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Barang</th>
                                <th class="text-right">Qty</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($transaksiTerbaru) > 0) : ?>
                                <?php while ($trx = mysqli_fetch_assoc($transaksiTerbaru)) : ?>
                                    <tr>
                                        <td class="small"><?= date('d/m/Y H:i', strtotime($trx['tanggal'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?= $trx['jenis'] === 'Masuk' ? 'success' : 'danger'; ?>">
                                                <?= $trx['jenis']; ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($trx['namabaraang']); ?></td>
                                        <td class="text-right"><?= number_format((int) $trx['qty'], 0, ',', '.'); ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($trx['info']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada transaksi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-tags mr-1"></i> Ringkasan Kategori
                <span class="badge badge-secondary ml-1"><?= $totalKategori; ?></span>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($ringkasanKategori) > 0) : ?>
                    <?php while ($kat = mysqli_fetch_assoc($ringkasanKategori)) : ?>
                        <?php
                        $jumlah = (int) $kat['jumlah'];
                        $persen = $totalBarang > 0 ? round(($jumlah / $totalBarang) * 100) : 0;
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span><?= htmlspecialchars($kat['namakategori']); ?></span>
                                <span class="text-muted"><?= $jumlah; ?> barang</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $persen; ?>%;"
                                    aria-valuenow="<?= $persen; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <p class="text-muted small mb-0">
                        Belum ada kategori. <a href="kategori.php">Tambahkan kategori</a> untuk mengelompokkan barang.
                    </p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a class="btn btn-primary btn-sm btn-block" href="kategori.php">
                    <i class="fas fa-plus mr-1"></i> Kelola Kategori
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-bolt mr-1"></i> Aksi Cepat
            </div>
            <div class="card-body">
                <a href="barang.php" class="btn btn-primary btn-block">
                    <i class="fas fa-plus mr-1"></i> Tambah Barang
                </a>
                <a href="masuk.php" class="btn btn-success btn-block">
                    <i class="fas fa-arrow-circle-down mr-1"></i> Barang Masuk
                </a>
                <a href="keluar.php" class="btn btn-danger btn-block">
                    <i class="fas fa-arrow-circle-up mr-1"></i> Barang Keluar
                </a>
                <a href="laporan.php" class="btn btn-outline-secondary btn-block mb-0">
                    <i class="fas fa-file-alt mr-1"></i> Buka Laporan
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
