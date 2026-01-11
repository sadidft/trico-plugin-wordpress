# 📚 Trico AI Assistant - Dokumentasi Lengkap

Pehh, ini gw lagi ngebahas Trico AI Assistant tapi versi plugin WordPress bukan model ai yang pernah memang krenova itu, ini yg bikin website keren cuma pake satu prompt. Gue susun dokumen ini biar lu bisa install, setup, dan pake dgn santai. Tools ini bisa bantu rakyat kecil bikin bisnis online sendiri tanpa nunggu 19 juta lapangan pekerjaan dari rezim yang lebih sibuk urus sawit, hehe. tapi suwer, ini tools berguna banget soalnya gw bikin all in one pakai konsep Tri-CO atau bisa diartikan Tiga koneksi, B2-Cloudflare-Groq.

---

## 📋 Daftar Isi

1. [Gambaran Umum](#gambaran-umum)
2. [Persyaratan](#persyaratan)
3. [Instalasi](#instalasi)
4. [Konfigurasi](#konfigurasi)
5. [Panduan Penggunaan](#panduan-penggunaan)
6. [Referensi API](#referensi-api)
7. [Pemecahan Masalah](#pemecahan-masalah)
8. [FAQ](#faq)

---

## Gambaran Umum

**Trico AI Assistant** ini plugin AI buat WordPress yang bisa generate website modern dan stunning dari prompt sederhana pake Groq AI, gambar dari Pollinations.ai, dan deploy langsung ke Cloudflare Pages. Praktis banget buat lu yang mau bikin situs tanpa ribet.

### Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| 🤖 Generasi AI | Bikin website lengkap pake Groq AI (model Llama) |
| 🖼️ Gambar AI | Generate gambar unlimited gratis via Pollinations.ai |
| 🎨 Desain Modern | Style glassmorphism, neubrutalism, bento grid |
| 📦 Ekspor Statik | Ekspor jadi HTML/CSS/JS murni |
| 🚀 Deploy CF Pages | Deploy satu klik ke Cloudflare Pages |
| 📊 Synalytics | Dashboard analitik web Cloudflare |
| 🔄 Rotasi API | Dukung sampe 15 API key buat tim |
| 💾 Storage B2 | Integrasi Backblaze B2 buat media |
| 🌐 Whitelabel | Support domain custom full |

Fitur-fitur ini keren buat UMKM Indo yang lagi kena dampak regulasi sawit atau judol, daripada bergantung sama pemerintah yang kadang lambat kayak DPR lagi sidang.

---

## Persyaratan

### Persyaratan Minimal

- **WordPress**: 6.0 ke atas
- **PHP**: 7.4 ke atas
- **MySQL/MariaDB**: 5.7+ atau TiDB
- **Memory**: 256MB (rekomendasi 512MB)

### API Key yang Dibutuhkan

| Layanan | Fungsi | Cara Dapat |
|---------|--------|------------|
| **Groq API** | Generate teks/kode AI | [console.groq.com](https://console.groq.com) |
| **Cloudflare** | Deploy & analitik | [dash.cloudflare.com](https://dash.cloudflare.com) |
| **Backblaze B2** | Storage media (opsional) | [backblaze.com/b2](https://www.backblaze.com/b2) |
| **SadidFT** | Bayar Gw (Wajib) | Bercanda broo.. |

### Izin Token API Cloudflare

Pas bikin token API Cloudflare, kasih izin ini:

```
Account → Cloudflare Pages → Edit
Account → Account Analytics → Read  
Zone → Zone → Read (buat domain custom)
```

Ini penting biar semuanya lancar, jangan sampe kayak .... yang dapet akses tanpa usaha cuma modal bapak, awokawokawok...

---

## Instalasi

### Opsi A: Hugging Face Spaces (Rekomendasi dari Tim Synavy)

1. **Bikin HF Space** pake Docker SDK, gratis bro tenang aja

2. **Tambah Dockerfile**:
```dockerfile
FROM wordpress:latest

# ... (liat Dockerfile di repo)

RUN git clone --depth 1 https://github.com/sadidft/trico-plugin-wordpress.git /tmp/trico && \
    cp -r /tmp/trico/trico-theme /usr/src/wordpress/wp-content/themes/ && \
    cp -r /tmp/trico/trico-ai-assistant /usr/src/wordpress/wp-content/plugins/ && \
    cp -r /tmp/trico/mu-plugins/* /usr/src/wordpress/wp-content/mu-plugins/
```

3. **Set HF Secrets** (liat bagian Konfigurasi)

4. **Rebuild Factory**

### Opsi B: WordPress Standar

1. Download atau clone repo ini
2. Upload `trico-theme` ke `wp-content/themes/`
3. Upload `trico-ai-assistant` ke `wp-content/plugins/`
4. Upload `mu-plugins/synavy-cookie-fix.php` ke `wp-content/mu-plugins/`
5. Aktifin theme dan plugin

### Opsi C: Docker Mandiri

```bash
# Clone repo
git clone https://github.com/sadidft/trico-plugin-wordpress.git
cd trico-plugin-wordpress

# Build dan run
docker build -f Dockerfile-demo -t trico-wordpress .
docker run -d -p 8080:80 \
  -e GROQ_KEY_1=your_groq_key \
  -e CF_API_TOKEN=your_cf_token \
  -e CF_ACCOUNT_ID=your_account_id \
  -e TRICO_DOMAIN=your-domain.com \
  trico-wordpress
```

Instalasi gampang, bro, nggak ribet kaya mau bikin skck di aplikasi isilop.

---

## Konfigurasi

### Variabel Lingkungan / Secrets

#### Wajib

| Variabel | Deskripsi | Contoh |
|----------|-----------|--------|
| `GROQ_KEY_1` | API key Groq pertama | `gsk_abc123...` |
| `CF_API_TOKEN` | Token API Cloudflare | `v1.0-abc123...` |
| `CF_ACCOUNT_ID` | ID akun Cloudflare | `abc123def456...` |
| `TRICO_DOMAIN` | Domain default buat deploy | `pages.lapormaswapres.id` |

#### Opsional - API Key Groq Tambahan

```
GROQ_KEY_2=gsk_...
GROQ_KEY_3=gsk_...
GROQ_KEY_4=gsk_...
GROQ_KEY_5=gsk_...
... sampe GROQ_KEY_15
```

#### Opsional - Storage B2

| Variabel | Deskripsi |
|----------|-----------|
| `B2_KEY_ID` | ID key app B2 |
| `B2_APP_KEY` | Key app B2 |
| `B2_BUCKET_ID` | ID bucket B2 |
| `B2_BUCKET_NAME` | Nama bucket B2 |

#### Buat HF Spaces - Database

| Variabel | Deskripsi |
|----------|-----------|
| `WORDPRESS_DB_HOST` | Host database |
| `WORDPRESS_DB_USER` | Username database |
| `WORDPRESS_DB_PASSWORD` | Password database |
| `WORDPRESS_DB_NAME` | Nama database |
| `WP_DOMAIN` | Domain WordPress |

Konfig ini bikin semuanya aman, jangan sampe bocor kayak data pribadi di tangan budi arie.

---

## Panduan Penggunaan

### Langkah 1: Akses Dashboard Trico

Setelah aktifin, masuk ke: **Admin → Trico AI → Dashboard**

Lo bakal liat:
- Jumlah proyek total
- Status API key
- Proyek terbaru
- Aksi cepat

### Langkah 2: Generate Website Pertama Lo

1. Masuk ke **Trico AI → Generate**

2. Masukin prompt lo. Bikin deskriptif:
   ```
   Buatkan landing page untuk toko roti modern bernama "Roti Masseh".
   Gunakan warna warm (coklat, cream). Style glassmorphism.
   Include: hero section dengan gambar roti, features (fresh, delivery, 
   affordable), testimonial, dan CTA WhatsApp.
   ```

3. Pilih opsi:
   - **CSS Framework**: Tailwind (rekomendasi), Bootstrap, atau Vanilla
   - **Bahasa**: Indonesia atau Inggris

4. Klik **Generate & Save**

5. Tunggu 30-60 detik buat generasi AI

### Langkah 3: Edit di WordPress

Setelah generate:
1. Klik **Edit in WordPress** buat buka Block Editor
2. Ubah konten, gambar, warna sesuka hati
3. Edit native WordPress - drag, drop, ganti teks

### Langkah 4: Deploy ke Cloudflare Pages

1. Masuk ke **Trico AI → Projects**
2. Cari proyek lo
3. Klik **🚀 Deploy**
4. Tunggu deploy (1-2 menit)
5. Situs lu live di `projectname.pages.dev`

### Langkah 5: Domain Custom (Opsional)

1. Masuk ke **Deploy Settings** proyek
2. Masukin subdomain atau domain custom
3. Tambah record CNAME di DNS:
   ```
   CNAME  yoursite  →  projectname.pages.dev
   ```

Pake ini buat bikin situs bisnis jangan buat kritik pemerintah, daripada dibungkam terus lu ilang bre, hehe.

---

## Referensi API

### Model AI yang Dipake

| Model | Fungsi | Kecepatan |
|-------|--------|-----------|
| `llama-3.3-70b-versatile` | Generate halaman full | Lambat, tapi kualitas bagus |
| `llama-3.1-70b-versatile` | Update section | Sedang |
| `llama-3.1-8b-instant` | SEO, edit cepat | Cepat |

### Rotasi API

Trico rotasi API key otomatis:
- Request 1 → Key 1
- Request 2 → Key 2
- Request 3 → Key 3
- ... balik ke Key 1

Kalo key kena limit:
1. Skip ke key selanjutnya
2. Retry request
3. Tandain key limited (reset otomatis)

### Tabel Database

Trico bikin tabel ini (kompatibel TiDB, no foreign keys):

```sql
{prefix}trico_projects   -- Data proyek
{prefix}trico_history    -- Riwayat generasi (max 4 per proyek)
{prefix}trico_b2_files   -- Tracking file B2
```

---

## Pemecahan Masalah

### Masalah Umum

#### "No API keys configured"
**Solusi**: Tambah `GROQ_KEY_1` ke environment/secrets

#### "Failed to connect to database"
**Solusi**: Cek kredensial database dan kompatibilitas TiDB

#### "Cloudflare deployment failed"
**Solusi**: 
1. Verif `CF_API_TOKEN` punya izin bener
2. Cek `CF_ACCOUNT_ID` bener
3. Pastiin nama proyek valid (huruf kecil, alfanumerik, hyphen)

#### "Images not loading"
**Solusi**: 
- URL Pollinations.ai langsung - cek situs allow gambar eksternal
- Buat B2: verif kredensial B2 bener

#### Tabel nggak dibuat
**Solusi**: 
1. Nonaktifin plugin
2. Hapus tabel `trico_*` manual kalo ada
3. Aktifin ulang plugin

### Mode Debug

Tambah ke wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Cek `/wp-content/debug.log` buat error.

Error ini nggak seburuk error di sistem pemilu, bro.

---

## FAQ

**Q: Ini gratis dipake?**
A: Pluginnya gratis (Apache 2.0). Butuh API key sendiri - Groq gratis tier, Cloudflare Pages gratis, Pollinations.ai unlimited gratis.

**Q: Berapa banyak website bisa generate?**
A: Unlimited, dibatesin kuota Groq.

**Q: Bisa pake domain sendiri?**
A: Bisa! Setup CNAME ke proyek Cloudflare Pages.

**Q: Bisa di hosting WordPress biasa?**
A: Bisa, tapi deploy butuh API Cloudflare.

**Q: Bisa edit website generated?**
A: Bisa! Blok native WordPress, editable full.

**Q: Kalau semua API key limit?**
A: Antri request, retry pas available.

---

## Dukungan

- **Issue GitHub**: [github.com/sadidft/trico-plugin-wordpress/issues](https://github.com/sadidft/trico-plugin-wordpress/issues)
- **Dokumentasi**: File ini
- **Lisensi**: Apache 2.0

---

## Kredit

Dibuat dengan ❤️ oleh Tim Synavy

Didukung oleh:
- [Groq](https://groq.com) - Inferensi AI
- [Pollinations.ai](https://pollinations.ai) - Generasi gambar AI
- [Cloudflare Pages](https://pages.cloudflare.com) - Hosting statik
- [WordPress](https://wordpress.org) - Platform CMS
