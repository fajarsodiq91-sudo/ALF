<?php
include 'koneksi.php';
session_start();

// Proteksi Halaman: Hanya Master dan Editor yang boleh masuk
if (!isset($_SESSION['login']) || ($_SESSION['role'] !== 'master' && $_SESSION['role'] !== 'editor')) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['simpan'])) {
    $nis          = $_POST['nis'];
    $nama_santri  = $_POST['nama_santri'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir= $_POST['tanggal_lahir'];

    $insert = mysqli_query($koneksi, "INSERT INTO santri (nis, nama_santri, tempat_lahir, tanggal_lahir) VALUES ('$nis', '$nama_santri', '$tempat_lahir', '$tanggal_lahir')");

    if ($insert) {
        header("Location: index.php");
    } else {
        echo "<script>alert('Gagal menambah data!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Tambah Santri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4 shadow">
                <h3>Tambah Data Santri secara Manual</h3>
                <form action="" method="POST" class="mt-4">
                    <div class="mb-3">
                        <label class="form-label">NIS</label>
                        <input type="text" name="nis" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Santri</label>
                        <input type="text" name="nama_santri" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control">
                    </div>
                    <button type="submit" name="simpan" class="btn btn-success">Simpan Data</button>
                    <a href="index.php" class="btn btn-secondary">Kembali</a>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>