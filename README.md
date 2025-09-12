# 🕌 Plugin Jadwal Sholat WordPress
Plugin WordPress untuk menampilkan jadwal sholat dengan data dari api.myquran.com. Plugin ini menyediakan tampilan yang responsive dan beberapa pilihan tema desain.

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue?style=flat-square&logo=wordpress)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-GPL%20v2%2B-green?style=flat-square)

## ✨ Fitur
✅ Menampilkan jadwal sholat untuk hari ini
✅ Pilihan kota dari seluruh Indonesia (api.myquran.com)
✅ 4 tema desain berbeda: Modern, Islamic, Minimal, dan Dark Mode
✅ Countdown otomatis menuju sholat berikutnya
✅ Auto-refresh setiap 1 menit
✅ Tampilan responsive yang bekerja di semua perangkat
✅ Admin panel untuk pengaturan default
✅ Shortcode mudah digunakan
✅ Caching data untuk performa optimal

## 📦 Instalasi

### Metode 1: Upload via WordPress Admin
1. Download file ZIP dari repository ini.  
2. Login ke dashboard WordPress Anda.  
3. Navigasi ke **Plugins → Add New**.  
4. Klik **Upload Plugin** dan pilih file ZIP.  
5. Klik **Install Now** lalu **Activate Plugin**.  

### Metode 2: Manual Upload via FTP
1. Download atau clone repository ini.  
2. Extract folder `jadwal-sholat-plugin-wordpress-main`.  
3. Upload folder ke direktori `/wp-content/plugins/` menggunakan FTP/SFTP.  
4. Login ke dashboard WordPress → **Plugins**.  
5. Aktifkan plugin.  

### Metode 3: Git Clone
```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/muhammadhamdani/jadwal-sholat-plugin-wordpress.git
```

## ⚙️ Konfigurasi

Setelah plugin aktif, buka Settings → Jadwal Sholat untuk mengatur:
Kota Default – Pilih kota default yang akan ditampilkan.
Tema Desain – Pilih antara Modern, Islamic, Minimal, atau Dark Mode

## 🚀 Penggunaan

### Shortcode Dasar
```[jadwal_sholat]```

### Shortcode dengan Parameter
```[jadwal_sholat kota="1101" tema="islamic"]```

Parameter yang Tersedia
kota → ID kota (default: sesuai pengaturan admin).
tema → modern | islamic | minimal | dark (default: sesuai pengaturan admin).

Contoh Penggunaan dalam Template

```<?php echo do_shortcode('[jadwal_sholat kota="1101" tema="dark"]'); ?>```

## 🎨 Tema Desain

Plugin ini menyediakan 4 pilihan tema desain:
Modern 🌈 – Default dengan gradien biru dan ungu.
Islamic ☪️ – Nuansa hijau Islami yang menenangkan.
Minimal ⚪ – Warna netral dan desain bersih.
Dark Mode 🌙 – Tampilan gelap untuk malam hari.

## 🔌 API yang Digunakan

Plugin ini menggunakan api.quran.com:
Daftar kota → /sholat/kota/semua
Jadwal sholat → /sholat/jadwal/{kota_id}/{tanggal}
⏱ Data akan di-cache selama 24 jam untuk performa optimal.

## 📱 Tampilan Responsive

Plugin didesain untuk bekerja di semua perangkat:
📱 Mobile – Tampilan optimal di smartphone
📟 Tablet – Layout yang nyaman di tablet
💻 Desktop – Tampilan penuh di desktop

## 📜 Lisensi

Plugin ini dirilis di bawah lisensi GPL v2+.
Anda bebas menggunakan, memodifikasi, dan mendistribusikan sesuai lisensi.
