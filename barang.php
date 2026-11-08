<?php
require 'fuction.php';
require 'cek.php';

// ===================== PROSES TAMBAH BARANG =====================
if (isset($_POST['tambahbarang'])) {
    $nama       = trim($_POST['namabaraang'] ?? '');
    $deskripsi  = trim($_POST['deskripsi'] ?? '');
    $idkategori = ($_POST['idkategori'] ?? '') !== '' ? (int) $_POST['idkategori'] : null;
    $stock      = max(0, (int) ($_POST['stock'] ?? 0));
    $minimum    = max(0, (int) ($_POST['stok_minimum'] ?? 0));

    if ($nama === '') {
        header('location: barang.php?status=kosong');
        exit;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO stok (namabaraang, deskripsi, idkategori, stock, stok_minimum)
                                   VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssiii', $nama, $deskripsi, $idkategori, $stock, $minimum);

    if (mysqli_stmt_execute($stmt)) {
        header('location: barang.php?status=ditambah');
    } else {
        header('location: barang.php?status=gagal');
    }
    exit;
}

// ===================== PROSES EDIT BARANG =====================
if (isset($_POST['editbarang'])) {
    $id         = (int) ($_POST['idbarang'] ?? 0);
    $nama       = trim($_POST['namabaraang'] ?? '');
    $deskripsi  = trim($_POST['deskripsi'] ?? '');
    $idkategori = ($_POST['idkategori'] ?? '') !== '' ? (int) $_POST['idkategori'] : null;
    $minimum    = max(0, (int) ($_POST['stok_minimum'] ?? 0));

    if ($id <= 0 || $nama === '') {
        header('location: barang.php?status=kosong');
        exit;
    }

    $stmt = mysqli_prepare($conn, "UPDATE stok
                                   SET namabaraang = ?, deskripsi = ?, idkategori = ?, stok_minimum = ?
                                   WHERE idbarang = ?");
    mysqli_stmt_bind_param($stmt, 'ssiii', $nama, $deskripsi, $idkategori, $minimum, $id);

    if (mysqli_stmt_execute($stmt)) {
        header('location: barang.php?status=diedit');
    } else {
        header('location: barang.php?status=gagal');
    }
    exit;
}

// ===================== PROSES HAPUS BARANG =====================
if (isset($_POST['hapusbarang'])) {
    $id = (int) ($_POST['idbarang'] ?? 0);

    if ($id <= 0) {
        header('location: barang.php?status=gagal');
        exit;
    }

    // Barang yang punya riwayat transaksi tidak boleh dihapus.
    $cek = mysqli_prepare($conn, "SELECT
                                    (SELECT COUNT(*) FROM masuk WHERE idbarang = ?) +
                                    (SELECT COUNT(*) FROM keluar WHERE idbarang = ?) AS total");
    mysqli_stmt_bind_param($cek, 'ii', $id, $id);
    mysqli_stmt_execute($cek);
    $hasilCek = mysqli_stmt_get_result($cek);
    $totalTransaksi = (int) mysqli_fetch_assoc($hasilCek)['total'];

    if ($totalTransaksi > 0) {
        header('location: barang.php?status=dipakai');
        exit;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM stok WHERE idbarang = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);

    if (mysqli_stmt_execute($stmt)) {
        header('location: barang.php?status=dihapus');
    } else {
        header('location: barang.php?status=gagal');
    }
    exit;
}

// ===================== FILTER PENCARIAN =====================
$cari           = trim($_GET['q'] ?? '');
$filterKategori = trim($_GET['kategori'] ?? '');

$where = [];
if ($cari !== '') {
    $cariAman = mysqli_real_escape_string($conn, $cari);
    $where[] = "s.namabaraang LIKE '%$cariAman%'";
}
if ($filterKategori !== '') {
    $where[] = "s.idkategori = " . (int) $filterKategori;
}

$sql = "SELECT s.*, k.namakategori
        FROM stok s
        LEFT JOIN kategori k ON k.idkategori = s.idkategori";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY s.namabaraang ASC";

$dataBarang = mysqli_query($conn, $sql);
$semuaKategori = mysqli_query($conn, "SELECT * FROM kategori ORDER BY namakategori ASC");

// ===================== PESAN STATUS =====================
$status = $_GET['status'] ?? '';
$alert  = null;

if ($status === 'ditambah') {
    $alert = ['success', 'Barang berhasil ditambahkan.'];
} elseif ($status === 'diedit') {
    $alert = ['success', 'Data barang berhasil diperbarui.'];
} elseif ($status === 'dihapus') {
    $alert = ['success', 'Barang berhasil dihapus.'];
} elseif ($status === 'dipakai') {
    $alert = ['warning', 'Barang tidak bisa dihapus karena sudah memiliki riwayat transaksi.'];
} elseif ($status === 'kosong') {
    $alert = ['danger', 'Nama barang wajib diisi.'];
} elseif ($status === 'gagal') {
    $alert = ['danger', 'Data gagal disimpan. Silakan coba lagi.'];
}

$judul  = 'Data Barang';
$active = 'barang';

include 'partials/header.php';
?>

<div class="d-flex align-items-center justify-content-between mt-4 mb-3">
    <div>
        <h1 class="mb-0">Data Barang</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Data Barang</li>
        </ol>
    </div>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#tambahModal">
        <i class="fas fa-plus mr-1"></i> Tambah Barang
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
        <i class="fas fa-box mr-1"></i> Daftar Barang
    </div>
    <div class="card-body">
        <form method="get" class="form-row align-items-end mb-3">
            <div class="col-md-5 mb-2 mb-md-0">
                <label class="small mb-1" for="q">Cari barang</label>
                <input type="text" class="form-control" id="q" name="q" placeholder="Nama barang..." value="<?= htmlspecialchars($cari); ?>">
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <label class="small mb-1" for="kategori">Kategori</label>
                <select class="form-control" id="kategori" name="kategori">
                    <option value="">Semua kategori</option>
                    <?php mysqli_data_seek($semuaKategori, 0); ?>
                    <?php while ($kat = mysqli_fetch_assoc($semuaKategori)) : ?>
                        <option value="<?= (int) $kat['idkategori']; ?>" <?= $filterKategori === (string) $kat['idkategori'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($kat['namakategori']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary btn-block">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Deskripsi</th>
                        <th class="text-right">Stok</th>
                        <th class="text-right">Min.</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($dataBarang) > 0) : ?>
                        <?php $no = 1; ?>
                        <?php while ($barang = mysqli_fetch_assoc($dataBarang)) : ?>
                            <?php
                            $stock   = (int) $barang['stock'];
                            $minimum = (int) $barang['stok_minimum'];

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
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($barang['namabaraang']); ?></td>
                                <td><?= htmlspecialchars($barang['namakategori'] ?? 'Tanpa kategori'); ?></td>
                                <td><?= htmlspecialchars($barang['deskripsi']); ?></td>
                                <td class="text-right"><?= number_format($stock, 0, ',', '.'); ?></td>
                                <td class="text-right"><?= number_format($minimum, 0, ',', '.'); ?></td>
                                <td><span class="badge badge-<?= $badge; ?>"><?= $label; ?></span></td>
                                <td class="text-center text-nowrap">
                                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editModal"
                                        data-id="<?= (int) $barang['idbarang']; ?>"
                                        data-nama="<?= htmlspecialchars($barang['namabaraang'], ENT_QUOTES); ?>"
                                        data-deskripsi="<?= htmlspecialchars($barang['deskripsi'], ENT_QUOTES); ?>"
                                        data-kategori="<?= (int) $barang['idkategori']; ?>"
                                        data-minimum="<?= $minimum; ?>"
                                        data-stok="<?= $stock; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Hapus barang ini? Tindakan ini tidak bisa dibatalkan.');">
                                        <input type="hidden" name="idbarang" value="<?= (int) $barang['idbarang']; ?>">
                                        <button type="submit" name="hapusbarang" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Tidak ada barang yang cocok dengan pencarian.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Barang -->
<div class="modal fade" id="tambahModal" tabindex="-1" role="dialog" aria-labelledby="tambahModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tambahModalLabel">Tambah Barang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="tambah-nama">Nama Barang <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="tambah-nama" name="namabaraang" maxlength="25" required>
                </div>
                <div class="form-group">
                    <label for="tambah-deskripsi">Deskripsi</label>
                    <input type="text" class="form-control" id="tambah-deskripsi" name="deskripsi" maxlength="25">
                </div>
                <div class="form-group">
                    <label for="tambah-kategori">Kategori</label>
                    <select class="form-control" id="tambah-kategori" name="idkategori">
                        <option value="">Tanpa kategori</option>
                        <?php mysqli_data_seek($semuaKategori, 0); ?>
                        <?php while ($kat = mysqli_fetch_assoc($semuaKategori)) : ?>
                            <option value="<?= (int) $kat['idkategori']; ?>"><?= htmlspecialchars($kat['namakategori']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="tambah-stok">Stok Awal</label>
                        <input type="number" class="form-control" id="tambah-stok" name="stock" min="0" value="0" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="tambah-minimum">Stok Minimum</label>
                        <input type="number" class="form-control" id="tambah-minimum" name="stok_minimum" min="0" value="0" required>
                    </div>
                </div>
                <small class="text-muted">Stok awal hanya dipakai saat barang pertama kali dibuat.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" name="tambahbarang">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Barang -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Barang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-id" name="idbarang">
                <div class="form-group">
                    <label for="edit-nama">Nama Barang <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit-nama" name="namabaraang" maxlength="25" required>
                </div>
                <div class="form-group">
                    <label for="edit-deskripsi">Deskripsi</label>
                    <input type="text" class="form-control" id="edit-deskripsi" name="deskripsi" maxlength="25">
                </div>
                <div class="form-group">
                    <label for="edit-kategori">Kategori</label>
                    <select class="form-control" id="edit-kategori" name="idkategori">
                        <option value="">Tanpa kategori</option>
                        <?php mysqli_data_seek($semuaKategori, 0); ?>
                        <?php while ($kat = mysqli_fetch_assoc($semuaKategori)) : ?>
                            <option value="<?= (int) $kat['idkategori']; ?>"><?= htmlspecialchars($kat['namakategori']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Stok Saat Ini</label>
                        <input type="text" class="form-control" id="edit-stok" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="edit-minimum">Stok Minimum</label>
                        <input type="number" class="form-control" id="edit-minimum" name="stok_minimum" min="0" required>
                    </div>
                </div>
                <small class="text-muted">Stok tidak diubah dari sini. Gunakan menu Barang Masuk / Barang Keluar.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning" name="editbarang">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#editModal').on('show.bs.modal', function (event) {
            var tombol = $(event.relatedTarget);
            var modal = $(this);

            modal.find('#edit-id').val(tombol.data('id'));
            modal.find('#edit-nama').val(tombol.data('nama'));
            modal.find('#edit-deskripsi').val(tombol.data('deskripsi'));
            modal.find('#edit-kategori').val(tombol.data('kategori') || '');
            modal.find('#edit-minimum').val(tombol.data('minimum'));
            modal.find('#edit-stok').val(tombol.data('stok'));
        });
    });
</script>

<?php include 'partials/footer.php'; ?>
