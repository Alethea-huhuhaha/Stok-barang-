<?php
session_start();

$conn = mysqli_connect("localhost","root","","stokbarang");



//menambh barng masuk
if(isset($_POST['barangmasuk'])){
    $barangnya  = (int) ($_POST['barangnya'] ?? 0);
    $keterangan = trim($_POST['penerima'] ?? '');
    $qty        = (int) ($_POST['qty'] ?? 0);

    if($barangnya <= 0 || $qty <= 0){
        header('location: masuk.php?status=qty');
        exit;
    }

    //ambil stok barang saat ini
    $cekstocksekarang = mysqli_prepare($conn, "SELECT stock FROM stok WHERE idbarang = ?");
    mysqli_stmt_bind_param($cekstocksekarang, 'i', $barangnya);
    mysqli_stmt_execute($cekstocksekarang);
    $ambildatanya = mysqli_fetch_assoc(mysqli_stmt_get_result($cekstocksekarang));

    if(!$ambildatanya){
        header('location: masuk.php?status=gagal');
        exit;
    }

    $stocksekarang = (int) $ambildatanya['stock'];
    $tambahkanstocksekarangdenganquantity = $stocksekarang + $qty;

    mysqli_begin_transaction($conn);

    $addtomasuk = mysqli_prepare($conn, "INSERT INTO masuk (idbarang, keterangan, qty) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($addtomasuk, 'isi', $barangnya, $keterangan, $qty);
    $insertBerhasil = mysqli_stmt_execute($addtomasuk);

    $updatestockmasuk = mysqli_prepare($conn, "UPDATE stok SET stock = ? WHERE idbarang = ?");
    mysqli_stmt_bind_param($updatestockmasuk, 'ii', $tambahkanstocksekarangdenganquantity, $barangnya);
    $updateBerhasil = mysqli_stmt_execute($updatestockmasuk);

    if($insertBerhasil && $updateBerhasil){
        mysqli_commit($conn);
        header('location: masuk.php?status=berhasil');
    } else {
        mysqli_rollback($conn);
        header('location: masuk.php?status=gagal');
    }
    exit;
}

//menambh barng keluar
if(isset($_POST['addbarangkeluar'])){
    $barangnya = (int) ($_POST['barangnya'] ?? 0);
    $penerima  = trim($_POST['penerima'] ?? '');
    $qty       = (int) ($_POST['qty'] ?? 0);

    if($barangnya <= 0 || $qty <= 0){
        header('location: keluar.php?status=qty');
        exit;
    }

    //ambil stok barang saat ini
    $cekstocksekarang = mysqli_prepare($conn, "SELECT stock FROM stok WHERE idbarang = ?");
    mysqli_stmt_bind_param($cekstocksekarang, 'i', $barangnya);
    mysqli_stmt_execute($cekstocksekarang);
    $ambildatanya = mysqli_fetch_assoc(mysqli_stmt_get_result($cekstocksekarang));

    if(!$ambildatanya){
        header('location: keluar.php?status=gagal');
        exit;
    }

    $stocksekarang = (int) $ambildatanya['stock'];

    //stok tidak boleh minus
    if($qty > $stocksekarang){
        header('location: keluar.php?status=stokkurang');
        exit;
    }

    $tambahkanstocksekarangdenganquantity = $stocksekarang - $qty;

    mysqli_begin_transaction($conn);

    $addtokeluar = mysqli_prepare($conn, "INSERT INTO keluar (idbarang, penerima, qty) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($addtokeluar, 'isi', $barangnya, $penerima, $qty);
    $insertBerhasil = mysqli_stmt_execute($addtokeluar);

    $updatestockmasuk = mysqli_prepare($conn, "UPDATE stok SET stock = ? WHERE idbarang = ?");
    mysqli_stmt_bind_param($updatestockmasuk, 'ii', $tambahkanstocksekarangdenganquantity, $barangnya);
    $updateBerhasil = mysqli_stmt_execute($updatestockmasuk);

    if($insertBerhasil && $updateBerhasil){
        mysqli_commit($conn);
        header('location: keluar.php?status=berhasil');
    } else {
        mysqli_rollback($conn);
        header('location: keluar.php?status=gagal');
    }
    exit;
}

?>