<?php $active = $active ?? ''; ?>
<div class="sb-sidenav-menu">
    <div class="nav">
        <a class="nav-link <?= $active === 'dashboard' ? 'active' : ''; ?>" href="index.php">
            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
            Dashboard
        </a>

        <div class="sb-sidenav-menu-heading">Master Data</div>
        <a class="nav-link <?= $active === 'barang' ? 'active' : ''; ?>" href="barang.php">
            <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
            Data Barang
        </a>
        <a class="nav-link <?= $active === 'kategori' ? 'active' : ''; ?>" href="kategori.php">
            <div class="sb-nav-link-icon"><i class="fas fa-tags"></i></div>
            Kategori
        </a>

        <div class="sb-sidenav-menu-heading">Transaksi</div>
        <a class="nav-link <?= $active === 'masuk' ? 'active' : ''; ?>" href="masuk.php">
            <div class="sb-nav-link-icon"><i class="fas fa-arrow-circle-down"></i></div>
            Barang Masuk
        </a>
        <a class="nav-link <?= $active === 'keluar' ? 'active' : ''; ?>" href="keluar.php">
            <div class="sb-nav-link-icon"><i class="fas fa-arrow-circle-up"></i></div>
            Barang Keluar
        </a>

        <div class="sb-sidenav-menu-heading">Laporan</div>
        <a class="nav-link <?= $active === 'laporan' ? 'active' : ''; ?>" href="laporan.php">
            <div class="sb-nav-link-icon"><i class="fas fa-file-alt"></i></div>
            Laporan Inventaris
        </a>

        <div class="sb-sidenav-menu-heading">Akun</div>
        <a class="nav-link" href="logout.php">
            <div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>
            Logout
        </a>
    </div>
</div>
