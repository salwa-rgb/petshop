<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Saya</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="header-container">
            <a href="#rumah" class="logo">PETSHOP ME<span>.</span></a>
            
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="pemilik.php">Data Pemilik</a></li>
                    <li><a href="petugas.php">Data Petugas</a></li>
                    <li><a href="penitipan.php">Data Penitipan</a></li>
                    <li><a href="kandang_hewan.php">Data Kandang Hewan</a></li>
                    <li><a href="pembayaran.php">Data Pembayaran</a></li>
                    <li><a href="layanan.php">Data Layanan</a></li>
                    <li><a href="detail_layanan.php">Data Detail Layanan</a></li>
                    <li><a href="hewan.php">Data Hewan</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section id="rumah">
            <div class="bg-circle"></div> 
            <div class="hero-container">
                <div class="hero-text">
                    <h1>Hallo, Selamat datang kembali 👋</h1>
                    <h2>Sebagai member, kamu berhak menikmati promo spesial, harga khusus layanan, dan informasi terbaru seputar produk serta tips perawatan hewan. Kami siap membantu memastikan peliharaanmu selalu sehat, bersih, dan bahagia.</h2>
                    <p>Silakan jelajahi menu di atas dan pilih layanan yang kamu butuhkan. Jika ada pertanyaan, tim kami siap membantu kapan saja.</p>
                    <a href="#tentang" class="btn-hero">Profile</a>
                    <a href="#foto" class="btn-hero">Galeri</a>
                </div>
                
                <div class="hero-image">
                    <img src="asset/poto1.jpg" alt="Foto Utama">
                </div>
            </div>
        </section>

        <section id="tentang">
           <div class="about-container">
                <div class="about-image">
                    <img src="asset/poto2.jpg" alt="Foto Salwa">
                </div>

                <div class="about-text">
                    <h2>PETSHOP ME</h2>
                    <p>Petshop Me adalah petshop yang melayani kebutuhan hewan peliharaan secara menyeluruh, mulai dari perawatan, kesehatan, penitipan, sampai perlengkapan sehari-hari. Kami percaya hewan peliharaan adalah bagian dari keluarga, sehingga setiap layanan kami berikan dengan penuh kasih sayang, kebersihan, dan tanggung jawab.</p>
                    <p>Kami berkomitmen menjadi tempat yang nyaman dan dipercaya bagi para pemilik hewan, dan kini hadir juga secara online agar member lebih mudah. ✨</p>
                </div>
            </div>
        </section>


       <section id="foto">
            <div class="foto-container">
                <div class="foto-header">
                    <h2>Pelayanan Petshop</h2>
                    <p>Pilih layanan yang akan kamu berikan ke peliharaan</p>
                </div>
                
                <ul class="foto-grid">
                    <li>
                        <figure class="foto-card">
                            <div class="foto-wrapper">
                                <img src="asset/grooming.jpg" alt="Grooming"/>
                            </div>
                            <figcaption>
                                <h3>1. Grooming</h3>
                                <p>Grooming peliharaan.</p>
                            </figcaption>
                        </figure>
                    </li>
                    <li>
                        <figure class="foto-card">
                            <div class="foto-wrapper">
                                <img src="asset/mandi.jpg" alt="Mandiin"/>
                            </div>
                            <figcaption>
                                <h3>2. Mandi</h3>  
                                <p>Memandikan peliharaan.</p>
                            </figcaption>                    
                        </figure>
                    </li>
                    <li>
                        <figure class="foto-card">
                            <div class="foto-wrapper">
                                <img src="asset/vitamin.jpg" alt="Pemberian Vitamin"/>
                            </div>
                            <figcaption>
                                <h3>3. Vitamin Hewan</h3>
                                <p>Pemberian Vitamin Peliharaan.</p>
                            </figcaption>
                        </figure>
                    </li>
                </ul>
            </div>
        </section>


    <footer>
        <p>2026 sha Website. All rights reserved.</p>
        <p>
            <a href="https://www.instagram.com/salwahanning?igsh=eXQ4OGFvejlybm9q" target="_blank">Instagram</a>
            <a href="https://www.tiktok.com/@random.salwa?_r=1&_t=ZS-979Bhj3TxcC" target="_blank">Tiktok</a>
        </p>
        <p>Contact: <a href="mailto:2559201015@umko.ac.id">2559201015@umko.ac.id</a></p>
     </footer>
</body>
</html>