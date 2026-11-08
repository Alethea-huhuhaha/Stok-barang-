<?php
require 'fuction.php';
require 'cek.php';

// ===================== PROSES TAMBAH KATEGORI =====================
if (isset($_POST['tambahkategori'])) {
    $nama = trim($_POST['namakategori'] ?? '');

    if ($nama === '') {
        header('location: kategori.php?status=kosong');
        exit;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO kategori (namakategori) VALUES (?)");
    mysqli_stmt_bind_param($stmt, 's', $nama);

    if (mysqli_stmt_execute($stmt)) {
        header('location: kategori.php?status=ditambah');
    } else {
        header('location: kategori.php?status=gagal');
    }
    exit;
}

// ===================== PROSES EDIT KATEGORI =====================
if (isset($_POST['editkategori'])) {
    $id   = (int) ($_POST['idkategori'] ?? 0);
    $nama = trim($_POST['namakategori'] ?? '');

    if ($id <= 0 || $nama === '') {
        header('location: kategori.php?status=kosong');
        exit;
    }

    $stmt = mysqli_prepare($conn, "UPDATE kategori SET namakategori = ? WHERE idkategori = ?");
    mysqli_stmt_bind_param($stmt, 'si', $nama, $id);

    if (mysqli_stmt_execute($stmt)) {
        header('location: kategori.php?status=diedit');
    } else {
        header('location: kategori.php?status=gagal');
    }
    exit;
}

// ===================== PROSES HAPUS KATEGORI =====================
if (isset($_POST['hapuskategori'])) {
    $id = (int) ($_POST['idkategori'] ?? 0);

    if ($id <= 0) {
        header('location: kategori.php?status=gagal');
        exit;
    }

    // Kategori yang masih dipakai barang tidak boleh dihapus.
    $cek = mysqli_prepare($conn, "SELECT COUNT(*) AS jumlah FROM stok WHERE idkategori = ?");
    mysqli_stmt_bind_param($cek, 'i', $id);
    mysqli_stmt_execute($cek);
    $hasilCek = mysqli_stmt_get_result($cek);
    $jumlahBarang = (int) mysqli_fetch_assoc($hasilCek)['jumlah'];

    if ($jumlahBarang > 0) {
        header('location: kategori.php?status=dipakai');
        exit;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM kategori WHERE idkategori = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);

    if (mysqli_stmt_execute($stmt)) {
        header('location: kategori.php?status=dihapus');
    } else {
        header('location: kategori.php?status=gagal');
    }
    exit;
}

// ===================== DAFTAR KATEGORI =====================
$dataKategori = mysqli_query($conn, "SELECT k.*, COUNT(s.idbarang) AS jumlah
                                     FROM kategori k
                                     LEFT JOIN stok s ON s.idkategori = k.idkategori
                                     GROUP BY k.idkategori, k.namakategori
                                     ORDER BY k.namakategori ASC");

// ===================== PESAN STATUS =====================
$status = $_GET['status'] ?? '';
$alert  = null;

if ($status === 'ditambah') {
    $alert = ['success', 'Kategori berhasil ditambahkan.'];
} elseif ($status === 'diedit') {
    $alert = ['success', 'Kategori berhasil diperbarui.'];
} elseif ($status === 'dihapus') {
    $alert = ['success', 'Kategori berhasil dihapus.'];
} elseif ($status === 'dipakai') {
    $alert = ['warning', 'Kategori tidak bisa dihapus karena masih dipakai oleh barang.'];
} elseif ($status === 'kosong') {
    $alert = ['danger', 'Nama kategori wajib diisi.'];
} elseif ($status === 'gagal') {
    $alert = ['danger', 'Data gagal disimpan. Silakan coba lagi.'];
}

$judul  = 'Kategori';
$active = 'kategori';

include 'partials/header.php';
?>

<div class="d-flex align-items-center justify-content-between mt-4 mb-3">
    <div>
        <h1 class="mb-0">Kategori</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Kategori</li>
        </ol>
    </div>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#tambahModal">
        <i class="fas fa-plus mr-1"></i> Tambah Kategori
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
        <i class="fas fa-tags mr-1"></i> Daftar Kategori
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Kategori</th>
                        <th class="text-right">Jumlah Barang</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($dataKategori) > 0) : ?>
                        <?php $no = 1; ?>
                        <?php while ($kategori = mysqli_fetch_assoc($dataKategori)) : ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($kategori['namakategori']); ?></td>
                                <td class="text-right"><?= (int) $kategori['jumlah']; ?></td>
                                <td class="text-center text-nowrap">
                                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editModal"
                                        data-id="<?= (int) $kategori['idkategori']; ?>"
                                        data-nama="<?= htmlspecialchars($kategori['namakategori'], ENT_QUOTES); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Hapus kategori ini?');">
                                        <input type="hidden" name="idkategori" value="<?= (int) $kategori['idkategori']; ?>">
                                        <button type="submit" name="hapuskategori" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                Belum ada kategori. Klik "Tambah Kategori" untuk membuat yang pertama.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="tambahModal" tabindex="-1" role="dialog" aria-labelledby="tambahModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tambahModalLabel">Tambah Kategori</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-0">
                    <label for="tambah-nama">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="tambah-nama" name="namakategori" maxlength="50" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" name="tambahkategori">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Kategori</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-id" name="idkategori">
                <div class="form-group mb-0">
                    <label for="edit-nama">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit-nama" name="namakategori" maxlength="50" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning" name="editkategori">Update</button>
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
        });
    });
</script>

<?php include 'partials/footer.php'; ?>
