<?php
// mulai session_start() untuk keperluan proses data yang membutuhkan data session
session_start();
date_default_timezone_set('Asia/Jakarta');
$sekarang = date("Y-m-d H:i:s");

// require koneksi ke database, koneksi ke database dengan $conn
require 'database.php';

// initiate string error
$error = "";

// jika submit dengan value="daftar", maka jalankan
if($_POST['perlu']=="daftar"){
    // tangkap data POST dari formulir pendaftaran
    $email = $_POST['email'];
    $password = $_POST['password'];
    $username = $_POST['username'];
    // cek apakah inputan user ada yang kosong atau tidak
    if(empty($email || $password || $username)){
        $error = "Mohon isi dengan lengkap";
        header("location: registrasi.php?error=$error");
        exit;
    }else{
        $passwordMd5 = md5($password);
        //cek apakah email atau username sudah ada di database atau belum
        $cekEmail = mysqli_query($conn, "SELECT * FROM akun WHERE email='$email' OR username='$username'");
        if($rowEmail = mysqli_num_rows($cekEmail)>0){
         $error = "Email / username tidak tersedia";
         header("location: registrasi.php?error=$error");
         exit;
        }

         // masukkan ke database
        $insertRegistrasi = mysqli_query($conn, "INSERT INTO akun (email,password,username) VALUES ('$email','$passwordMd5','$username')");
        // berhasil, arahkan ke form login
        header("location: registrasi.php");
        exit;
    }
}

// jika submit dengan value="login", maka jalankan
if ($_POST['perlu']=="login"){
    session_start();
    if(empty($_POST['email']) || empty($_POST['password'])){
        $error = "ISI EMAIL ATAU PASSWORD DENGAN LENGKAP";
        header("location: login.php?error=$error");
        exit;
    }else{
        $email = $_POST['email'];
        $password = md5($_POST['password']);
        // cek data di table akun
        $queryAkun = mysqli_query($conn, "SELECT * FROM akun WHERE email='$email' AND password='$password'");
        $cekAkun = mysqli_num_rows($queryAkun);
        $akun = mysqli_fetch_assoc($queryAkun);
        if($cekAkun<1 || $cekAkun>1){
            $error = "Data tidak ditemukan atau error";
            header("location: login.php?error=$error");
            exit;
        }else{
            $_SESSION['pembeli'] = TRUE;
            $_SESSION['id_pembeli'] = $akun['id'];
            header("location: cart.php");
            exit;
        }
    }
    $error = "error";
    header("location: login.php?error=$error");
    exit;
}

// jika post perlu adalah tambahCart
if($_POST['perlu']=="tambahCart"){
    $id_produk = $_POST['id_produk'];
    $kuantitas = $_POST['kuantitas'];
    $id_pembeli = $_POST['id_pembeli'];
    date_default_timezone_set('Asia/Jakarta');
    $sekarang = date("Y-m-d H:i:s");

    if(empty($id_produk) || empty($kuantitas) || empty($id_pembeli)){
        header('location: index.php');
        exit;
    }else{
    // cek apakah masih ada produknya atau enggak
    $cek_stok = mysqli_query($conn, "SELECT * FROM produk WHERE id='$id_produk'");
    $stok = mysqli_fetch_assoc($cek_stok);
    if($stok['stok']<1){
        $error = "Stok habis!";
        header("location: index.php?error=$error");
        exit;
    } else{
            // kurangi stok
            $kurang = mysqli_query($conn, "UPDATE produk SET stok=(stok-$kuantitas) WHERE id='$id_produk'");
            
            // cek apakah sudah ada datanya belum di akun dan barang yang sama di keranjang
             $cek_keranjang = mysqli_query($conn, "SELECT * FROM keranjang WHERE id_produk='$id_produk' AND id_akun='1' ");
             if($keranjang = mysqli_num_rows($cek_keranjang)>0){
                $input = mysqli_query($conn, "UPDATE keranjang SET kuantitas =(kuantitas+$kuantitas) WHERE id_produk='$id_produk' AND id_akun='1'");
            }else{
                // tidak ada, buat baru
                $input = mysqli_query($conn, "INSERT INTO keranjang (id_produk, id_akun, kuantitas) VALUES ('$id_produk', '$id_pembeli', '$kuantitas')");
             }
            if(!$input){
                $error = "Gagal tambah ke keranjang";
                header("location: index.php?error=$error");
            }else{
                $success = "berhasil tambah ke keranjang";
                header("location: cart.php?success=$success");
            }
        }
    }
}

if($_POST['perlu']=="ubahBanyak"){
    if(empty($_POST['kuantitas']) || empty($_POST['id_produk']) || !isset($_SESSION['id_pembeli'])){
        $error = "Gagal mengubah";
        header("location: cart.php?error=$error");
    }
    $id_pembeli = $_SESSION['id_pembeli'];
    $kuantitas = $_POST['kuantitas'];
    $id_produk = $_POST['id_produk'];
    $queryProduk = mysqli_query($conn, "SELECT * FROM keranjang WHERE id_akun='$id_pembeli' AND id_produk='$id_produk'");
    $produk = mysqli_fetch_assoc($queryProduk);
    $kuantitasAwal = $produk['kuantitas'];
    // cek apakah kuantitas yang dimasukkan lebih kecil atau lebih besar
    if($kuantitasAwal > $kuantitas){ // kuantitas baru lebih kecil dari kuantitas sebelumnya, maka ubah stok dengan selisih perubahan
        $selisih = $kuantitasAwal - $kuantitas;
        $updateStok = mysqli_query($conn, "UPDATE produk SET stok = stok+$selisih WHERE id='$id_produk'");
        $updateKeranjang = mysqli_query($conn, "UPDATE keranjang SET kuantitas = $kuantitas WHERE id_akun='$id_pembeli' AND id_produk='$id_produk'");
        $success = "Berhasil mengubah";
        header("location: cart.php?success=$success");
        exit;
    }
    if($kuantitasAwal < $kuantitas){ // kuantitas baru lebih besar dari kuantitas sebelumnya, maka kurangi stok dengan selisih perubahan
        $selisih = $kuantitas - $kuantitasAwal;
        $updateStok = mysqli_query($conn, "UPDATE produk SET stok = stok-$selisih WHERE id='$id_produk'");
        $updateKeranjang = mysqli_query($conn, "UPDATE keranjang SET kuantitas ='$kuantitas' WHERE id_akun='$id_pembeli' AND id_produk='$id_produk'");
        $success = "Berhasil mengubah";
        header("location: cart.php?success=$success");
        exit;
    }

}

if($_POST['perlu']=="hapusCart"){
    if(empty($_POST['kuantitas']) || empty($_POST['id_produk']) || !isset($_SESSION['id_pembeli'])){
        $error = "Gagal menghapus";
        header("location: cart.php?error=$error");
    }
    $kuantitas = $_POST['kuantitas'];
    $id_produk = $_POST['id_produk'];
    $id_pembeli = $_SESSION['id_pembeli'];
    $updateStok = mysqli_query($conn, "UPDATE produk SET stok=stok+$kuantitas WHERE id='$id_produk'");
    $hapusKeranjang = mysqli_query($conn, "DELETE FROM keranjang WHERE id_akun='$id_pembeli' AND id_produk='$id_produk'");
    $success = "Berhasil menghapus";
    header("location: cart.php?success=$success");
}

if($_POST['perlu']=='beli'){
    if(empty($_SESSION['id_pembeli']) || empty($_POST['kota']) || empty($_POST['provinsi']) || empty($_POST['alamatLengkap']) || empty($_POST['kodePos']) || empty($_POST['nohp']) || empty($_POST['ongkir'])){
        $error = "oops! Sepertinya kamu belum mengisi dengan lengkap";
        header("location: cart.php?error=$error");
        exit;
    }else{
        $id_pembeli = $_SESSION['id_pembeli'];
        $kota = $_POST['kota'];
        $provinsi = $_POST['provinsi'];
        $alamatLengkap = $_POST['alamatLengkap'];
        $kodePos = $_POST['kodePos'];
        $nohp = $_POST['nohp'];
        $ongkir = $_POST['ongkir'];
        // cek apakah ada produk di keranjang atau tidak
        $cekData =  mysqli_query($conn, "SELECT * FROM keranjang WHERE id_akun='$id_pembeli'");
        // jika tidak ada, arahkan ke cart tanpa ada proses apapun
        if($data = mysqli_num_rows($cekData)<1){
            $error = "oops! tidak ada data untuk diproses nih, yuk belanja dulu!";
            header("location: cart.php?error=$error");
            exit;
        }else{
            // ada data di keranjang
            // buat kode invoice dengan kombinasi INV.datetime/id_akun
            $invoice = "INV$sekarang/$id_pembeli";
            // insert invoice ke status
            $status = mysqli_query($conn, "INSERT INTO status (invoice, status, resi, ongkir) VALUES ('$invoice', '0', '0','$ongkir')");

            // insert alamat sesuai dengan invoice
            $alamat = mysqli_query($conn, "INSERT INTO alamat (id_akun,kabupaten,provinsi,alamat,kode_pos,invoice,nohp) VALUES ('$id_pembeli','$kota','$provinsi','$alamatLengkap','$kodePos','$invoice','$nohp')");
    
            // fetch semua data di keranjang dari produk
            $cekKeranjang = mysqli_query($conn, "SELECT * FROM keranjang WHERE id_akun='$id_pembeli'");
            while($keranjang = mysqli_fetch_assoc($cekKeranjang)){
                $id_produk = $keranjang['id_produk'];
                $cekProduk = mysqli_query($conn, "SELECT * FROM produk WHERE id='$id_produk' AND status!=0");
                $produk = mysqli_fetch_assoc($cekProduk);
                // assign variable dari hasil fetch, biar bisa masuk ke query
                $harga = $produk['harga'];
                $kuantitas = $keranjang['kuantitas'];
                $total = $harga*$kuantitas;
                // insert masing-masing produk dengan 1 invoice yang sama ke transaksi
                $insert = mysqli_query($conn, "INSERT INTO transaksi (invoice, id_akun, id_produk, harga, kuantitas, total) VALUES ('$invoice','$id_pembeli','$id_produk','$harga','$kuantitas','$total')");
            }
            // hapus data di keranjang
            $hapus = mysqli_query($conn, "DELETE FROM keranjang WHERE id_akun = '$id_pembeli'");
    
            header("location: pembelian.php");
        }
    }
}

?>
