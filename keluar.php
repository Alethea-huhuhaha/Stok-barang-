<?php
require 'fuction.php';
require 'cek.php';

$dataKeluar = mysqli_query($conn, "SELECT kl.*, s.namabaraang, k.namakategori
                                   FROM keluar kl
                                   JOIN stok s ON s.idbarang = kl.idbarang
                                   LEFT JOIN kategori k ON k.idkategori = s.idkategori
                                   ORDER BY kl.tanggal DESC");

$status = $_GET['status'] ?? '';
$alert  = null;

if ($status === 'berhasil') {
    $alert = ['success', 'Transaksi barang keluar berhasil disimpan. Stok sudah diperbarui.'];
} elseif ($status === 'stokkurang') {
    $alert = ['warning', 'Stok tidak mencukupi. Jumlah keluar melebihi stok yang tersedia.'];
} elseif ($status === 'gagal') {
    $alert = ['danger', 'Transaksi gagal disimpan. Silakan coba lagi.'];
} elseif ($status === 'qty') {
    $alert = ['danger', 'Jumlah barang harus lebih dari 0.'];
}

$judul  = 'Barang Keluar';
$active = 'keluar';

$extra_head = '<link href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css" rel="stylesheet" crossorigin="anonymous" />';

$extra_scripts = <<<HTML
<script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js" crossorigin="anonymous"></script>
<script>
    $(document).ready(function () {
        $('#dataTable').DataTable({
            order: [[0, "desc"]],
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada data",
                zeroRecords: "Data tidak ditemukan",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Berikutnya",
                    previous: "Sebelumnya"
                }
            }
        });
    });
</script>
HTML;

include 'partials/header.php';
?>

<div class="d-flex align-items-center justify-content-between mt-4 mb-3">
    <div>
        <h1 class="mb-0">Barang Keluar</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Barang Keluar</li>
        </ol>
    </div>
    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#myModal">
        <i class="fas fa-plus mr-1"></i> Tambah Barang Keluar
    </button>
</div>

<?php if ($alert) : ?>
    <div class="alert alert-<?= $alert[0]; ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($alert[1]); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-arrow-circle-up mr-1"></i> Riwayat Barang Keluar
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th class="text-right">Jumlah</th>
                        <th>Penerima / Tujuan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($data = mysqli_fetch_assoc($dataKeluar)) : ?>
                        <tr>
                            <td data-order="<?= htmlspecialchars($data['tanggal']); ?>"><?= date('d/m/Y H:i', strtotime($data['tanggal'])); ?></td>
                            <td><?= htmlspecialchars($data['namabaraang']); ?></td>
                            <td><?= htmlspecialchars($data['namakategori'] ?? 'Tanpa kategori'); ?></td>
                            <td class="text-right"><?= number_format((int) $data['qty'], 0, ',', '.'); ?></td>
                            <td><?= htmlspecialchars($data['penerima']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Barang Keluar -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Tambah Barang Keluar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="barangnya">Barang <span class="text-danger">*</span></label>
                    <select name="barangnya" id="barangnya" class="form-control" required>
                        <option value="">-- Pilih barang --</option>
                        <?php $dataBarang = mysqli_query($conn, "SELECT idbarang, namabaraang, stock FROM stok ORDER BY namabaraang ASC"); ?>
                        <?php while ($barang = mysqli_fetch_assoc($dataBarang)) : ?>
                            <option value="<?= (int) $barang['idbarang']; ?>">
                                <?= htmlspecialchars($barang['namabaraang']); ?> (stok: <?= (int) $barang['stock']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="qty">Jumlah <span class="text-danger">*</span></label>
                    <input type="number" name="qty" id="qty" class="form-control" placeholder="Jumlah barang keluar" min="1" required>
                </div>
                <div class="form-group mb-0">
                    <label for="penerima">Penerima / Tujuan <span class="text-danger">*</span></label>
                    <input type="text" name="penerima" id="penerima" class="form-control" placeholder="Contoh: Divisi Produksi" maxlength="25" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger" name="addbarangkeluar">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
