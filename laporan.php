<?php
require 'fuction.php';
require 'cek.php';

// ===================== FILTER LAPORAN =====================
$jenis = $_GET['jenis'] ?? 'stok';
if (!in_array($jenis, ['stok', 'masuk', 'keluar'], true)) {
    $jenis = 'stok';
}

/**
 * Memastikan format tanggal benar (YYYY-MM-DD).
 */
function tanggal_valid($tanggal)
{
    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    return $d && $d->format('Y-m-d') === $tanggal;
}

$dari   = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

if (!tanggal_valid($dari)) {
    $dari = date('Y-m-01');
}
if (!tanggal_valid($sampai)) {
    $sampai = date('Y-m-d');
}
if (strtotime($dari) > strtotime($sampai)) {
    $temp   = $dari;
    $dari   = $sampai;
    $sampai = $temp;
}

// ===================== AMBIL DATA SESUAI JENIS =====================
$dataLaporan = null;
$ringkasan   = [];

if ($jenis === 'stok') {
    $judulLaporan = 'Laporan Stok Barang';

    $dataLaporan = mysqli_query($conn, "SELECT s.*, k.namakategori
                                        FROM stok s
                                        LEFT JOIN kategori k ON k.idkategori = s.idkategori
                                        ORDER BY s.namabaraang ASC");

    $totalJenis   = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM stok"))[0];
    $totalStok    = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(stock), 0) FROM stok"))[0];
    $totalMenipis = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM stok WHERE stock <= stok_minimum"))[0];

    $ringkasan = [
        ['Total Jenis Barang', $totalJenis],
        ['Total Stok', $totalStok],
        ['Stok Menipis', $totalMenipis],
    ];
} elseif ($jenis === 'masuk') {
    $judulLaporan = 'Laporan Barang Masuk';

    $stmt = mysqli_prepare($conn, "SELECT m.tanggal, m.qty, m.keterangan AS info,
                                          s.namabaraang, k.namakategori
                                   FROM masuk m
                                   JOIN stok s ON s.idbarang = m.idbarang
                                   LEFT JOIN kategori k ON k.idkategori = s.idkategori
                                   WHERE DATE(m.tanggal) BETWEEN ? AND ?
                                   ORDER BY m.tanggal DESC");
    mysqli_stmt_bind_param($stmt, 'ss', $dari, $sampai);
    mysqli_stmt_execute($stmt);
    $dataLaporan = mysqli_stmt_get_result($stmt);

    $stmtTotal = mysqli_prepare($conn, "SELECT COUNT(*) AS transaksi,
                                               COALESCE(SUM(qty), 0) AS total_qty,
                                               COUNT(DISTINCT idbarang) AS jenis_barang
                                        FROM masuk
                                        WHERE DATE(tanggal) BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmtTotal, 'ss', $dari, $sampai);
    mysqli_stmt_execute($stmtTotal);
    $total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotal));

    $ringkasan = [
        ['Jumlah Transaksi', (int) $total['transaksi']],
        ['Total Qty Masuk', (int) $total['total_qty']],
        ['Jenis Barang', (int) $total['jenis_barang']],
    ];
} else {
    $judulLaporan = 'Laporan Barang Keluar';

    $stmt = mysqli_prepare($conn, "SELECT k2.tanggal, k2.qty, k2.penerima AS info,
                                          s.namabaraang, k.namakategori
                                   FROM keluar k2
                                   JOIN stok s ON s.idbarang = k2.idbarang
                                   LEFT JOIN kategori k ON k.idkategori = s.idkategori
                                   WHERE DATE(k2.tanggal) BETWEEN ? AND ?
                                   ORDER BY k2.tanggal DESC");
    mysqli_stmt_bind_param($stmt, 'ss', $dari, $sampai);
    mysqli_stmt_execute($stmt);
    $dataLaporan = mysqli_stmt_get_result($stmt);

    $stmtTotal = mysqli_prepare($conn, "SELECT COUNT(*) AS transaksi,
                                               COALESCE(SUM(qty), 0) AS total_qty,
                                               COUNT(DISTINCT idbarang) AS jenis_barang
                                        FROM keluar
                                        WHERE DATE(tanggal) BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmtTotal, 'ss', $dari, $sampai);
    mysqli_stmt_execute($stmtTotal);
    $total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotal));

    $ringkasan = [
        ['Jumlah Transaksi', (int) $total['transaksi']],
        ['Total Qty Keluar', (int) $total['total_qty']],
        ['Jenis Barang', (int) $total['jenis_barang']],
    ];
}

$periode = date('d/m/Y', strtotime($dari)) . ' s.d. ' . date('d/m/Y', strtotime($sampai));

$judul  = $judulLaporan;
$active = 'laporan';

$extra_head = <<<HTML
<style>
    @media print {
        .sb-topnav,
        #layoutSidenav_nav,
        footer,
        .no-print,
        .breadcrumb {
            display: none !important;
        }

        body.sb-nav-fixed {
            padding-top: 0 !important;
            background: #fff !important;
        }

        #layoutSidenav_content {
            margin-left: 0 !important;
        }

        #layoutSidenav_content main {
            padding: 0 !important;
        }

        .container-fluid {
            padding: 0 !important;
        }

        .card {
            border: 0 !important;
            box-shadow: none !important;
        }

        .card-header {
            background: #fff !important;
            border-bottom: 1px solid #dee2e6 !important;
        }

        a[href]:after {
            content: none !important;
        }
    }
</style>
HTML;

include 'partials/header.php';
?>

<div class="d-flex align-items-center justify-content-between mt-4 mb-3 no-print">
    <div>
        <h1 class="mb-0"><?= htmlspecialchars($judulLaporan); ?></h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Laporan</li>
        </ol>
    </div>
    <button type="button" class="btn btn-outline-secondary no-print" onclick="window.print();">
        <i class="fas fa-print mr-1"></i> Cetak
    </button>
</div>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="get" class="form-row align-items-end">
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="small mb-1" for="jenis">Jenis Laporan</label>
                <select class="form-control" id="jenis" name="jenis">
                    <option value="stok" <?= $jenis === 'stok' ? 'selected' : ''; ?>>Stok Barang</option>
                    <option value="masuk" <?= $jenis === 'masuk' ? 'selected' : ''; ?>>Barang Masuk</option>
                    <option value="keluar" <?= $jenis === 'keluar' ? 'selected' : ''; ?>>Barang Keluar</option>
                </select>
            </div>
            <?php if ($jenis !== 'stok') : ?>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="small mb-1" for="dari">Dari Tanggal</label>
                    <input type="date" class="form-control" id="dari" name="dari" value="<?= htmlspecialchars($dari); ?>">
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="small mb-1" for="sampai">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="sampai" name="sampai" value="<?= htmlspecialchars($sampai); ?>">
                </div>
            <?php endif; ?>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-filter mr-1"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Header khusus cetak -->
<div class="d-none d-print-block mb-3">
    <h4 class="mb-1"><?= htmlspecialchars($judulLaporan); ?></h4>
    <div class="text-muted">
        <?php if ($jenis === 'stok') : ?>
            Dicetak: <?= date('d/m/Y H:i'); ?>
        <?php else : ?>
            Periode: <?= $periode; ?> &middot; Dicetak: <?= date('d/m/Y H:i'); ?>
        <?php endif; ?>
    </div>
    <hr>
</div>

<div class="row">
    <?php foreach ($ringkasan as $item) : ?>
        <div class="col-md-4">
            <div class="card bg-light mb-4">
                <div class="card-body py-3">
                    <div class="small text-muted"><?= htmlspecialchars($item[0]); ?></div>
                    <div class="h4 mb-0"><?= number_format((int) $item[1], 0, ',', '.'); ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table mr-1"></i>
        <?= htmlspecialchars($judulLaporan); ?>
        <?php if ($jenis !== 'stok') : ?>
            <span class="small text-muted ml-2">(<?= $periode; ?>)</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="thead-light">
                    <?php if ($jenis === 'stok') : ?>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Deskripsi</th>
                            <th class="text-right">Stok</th>
                            <th class="text-right">Minimum</th>
                            <th>Status</th>
                        </tr>
                    <?php else : ?>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th class="text-right">Qty</th>
                            <th><?= $jenis === 'masuk' ? 'Keterangan / Supplier' : 'Penerima / Tujuan'; ?></th>
                        </tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php if ($dataLaporan && mysqli_num_rows($dataLaporan) > 0) : ?>
                        <?php $no = 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($dataLaporan)) : ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <?php if ($jenis === 'stok') : ?>
                                    <?php
                                    $stock   = (int) $row['stock'];
                                    $minimum = (int) $row['stok_minimum'];

                                    if ($stock <= 0) {
                                        $badge = 'danger';
                                        $label = 'Habis';
                                    } elseif ($stock <= $minimum) {
                                        $badge = 'warning';
                                        $label = 'Menipis';
                                    } else {
                                        $badge = 'success';
                                        $label = 'Aman';
                                    }
                                    ?>
                                    <td><?= htmlspecialchars($row['namabaraang']); ?></td>
                                    <td><?= htmlspecialchars($row['namakategori'] ?? 'Tanpa kategori'); ?></td>
                                    <td><?= htmlspecialchars($row['deskripsi']); ?></td>
                                    <td class="text-right"><?= number_format($stock, 0, ',', '.'); ?></td>
                                    <td class="text-right"><?= number_format($minimum, 0, ',', '.'); ?></td>
                                    <td><span class="badge badge-<?= $badge; ?>"><?= $label; ?></span></td>
                                <?php else : ?>
                                    <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                    <td><?= htmlspecialchars($row['namabaraang']); ?></td>
                                    <td><?= htmlspecialchars($row['namakategori'] ?? 'Tanpa kategori'); ?></td>
                                    <td class="text-right"><?= number_format((int) $row['qty'], 0, ',', '.'); ?></td>
                                    <td><?= htmlspecialchars($row['info']); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="<?= $jenis === 'stok' ? 7 : 6; ?>" class="text-center text-muted py-4">
                                Tidak ada data untuk periode ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
