# ⚡ SONIC OPAC — Hyperdrive Library System

Template OPAC **SLiMS 9.8 (Bulian)** bertema *neon-cyberpunk* yang cepat, aman, dan kaya fitur.
Dibangun di atas kontrak resmi SLiMS `v9.8.0` — **tanpa memodifikasi core**, semua fitur bawaan tetap utuh, ditambah lapisan keamanan, performa, dan fitur premium.

> Status: **Produksi** · PHP 8.2 teruji · Bundle 31→2 request · Slider WebP ±69% lebih ringan · FA 5.15.4

---

## Daftar Isi
1. [Tentang](#1-tentang)
2. [Fitur](#2-fitur)
3. [Persyaratan](#3-persyaratan)
4. [Struktur Folder](#4-struktur-folder)
5. [Instalasi](#5-instalasi)
6. [Build Tool](#6-build-tool)
7. [Kustomisasi Tema](#7-kustomisasi-tema)
8. [Cara Kerja Fitur Premium](#8-cara-kerja-fitur-premium)
9. [Endpoint API](#9-endpoint-api)
10. [Keamanan](#10-keamanan)
11. [Performa](#11-performa)
12. [Alur Pengembangan](#12-alur-pengembangan)
13. [Troubleshooting](#13-troubleshooting)
14. [Changelog](#14-changelog)
15. [Kredit & Lisensi](#15-kredit--lisensi)
16. [FAQ](#16-faq)

---

## 1. Tentang
SONIC adalah template OPAC untuk **SLiMS 9.8** yang mempertahankan seluruh fungsi bawaan SLiMS (pencarian, facet, detail, anggota, kiosk, sitasi, MARC, dll.) sambil menambahkan:
- **Lapisan keamanan** (sanitasi HTMLPurifier, escaping meta, CSRF fail-closed, header keamanan).
- **Lapisan performa** (cache file, bundling CSS/JS, WebP, flag-icon mandiri).
- **Fitur premium** (Terakhir Dilihat, Mungkin maksud Anda…, Trending 30 Hari, Anggota terbanyak mengunjungi, Font Awesome 5.15.4).

Semua perubahan bersifat **additif** — file lama tidak dihapus, fitur lama tidak dikurangi.

---

## 2. Fitur

### 2.1 Fitur inti SLiMS (dipertahankan penuh)
| Area | Fitur |
|---|---|
| Pencarian | Simple & Advanced, multi-facet (GMD/Lokasi/Koleksi) + sort, tampilan list/grid |
| Detail | Buku 3D interaktif, tab (Deskripsi/Detail/Sitasi/Eksemplar/Digital/Editions/Ulasan/Komentar/Serupa) |
| Sitasi | APA, MLA, Chicago, Turabian + ekspor **RIS / BibTeX / EndNote** |
| MARC | Viewer ISO 2709 (byte-accurate) + unduh `.mrc` |
| Anggota | Login, basket, bookmark, pinjaman, reservasi, denda, dashboard |
| Ulasan | Rating bintang + ulasan (login, CSRF, rotasi token) |
| Kiosk | Visitor log + suara, idle reset, quotes, statistik 7 hari |
| Konten | Berita, Pustakawan, Informasi/Profil, Bantuan, peta |
| Ekspor | RSS, JSON-LD, XML · Saved searches · Autocomplete · Ganti bahasa |

### 2.2 Peningkatan (Batch 1–2)
- Sanitasi `sonic_kses` berbasis **HTMLPurifier** (fallback whitelist aman).
- Escaping penuh meta/OG/JSON-LD + sanitasi *host-header*.
- CSRF ulasan **fail-closed** + rotasi `new_token`; sesi API di-boot benar.
- State facet dibaca via `filter_input` (kebal `quoteFree` core).
- Bookmark toggle benar (`delete_bookmark`), call-number tampil di hasil.
- Cache file `sonic_cache()` (TTL) untuk statistik & query berat.
- **Bundling** 19 CSS + 12 JS → 2 file; **WebP** slider; **flag-icon** mandiri.
- Navbar flex deterministik (anti tumpang-tindih) + sticky; hero *search-first*.
- Slider tahan-banting, pause saat tab hidden, hormati `prefers-reduced-motion`.

### 2.3 Fitur premium (Batch 3)
| Fitur | Keterangan | Toggle Kustomisasi |
|---|---|---|
| 🕐 Terakhir Dilihat | Rak riwayat baca client-side (localStorage), rail snap + navigasi + hapus riwayat, tanggal lokal | otomatis (client-side) |
| 💡 Mungkin maksud Anda… | Saran koreksi saat hasil kosong/typo via `api/suggest.php` | otomatis |
| 📈 Trending 30 Hari | Koleksi paling dipinjam 30 hari terakhir + badge jumlah | `sonic_trending_collection` |
| 🚪 Anggota terbanyak mengunjungi | Peringkat pengunjung (medali emas/perak/perunggu) | `sonic_top_visitor` |
| 🎨 Font Awesome 5.15.4 | Folder baru; folder 5.0.4 lama tetap utuh | otomatis (fallback aman) |

---

## 3. Persyaratan
| Komponen | Versi / Catatan |
|---|---|
| SLiMS | **9.8.0 (Bulian)** — kontrak terverifikasi terhadap tag `v9.8.0` |
| PHP | **8.x** (teruji 8.2.28) |
| Ekstensi PHP | `mysqli`, `mbstring`, `json`, `gd` (untuk WebP), `openssl` (unduh FA) |
| DB | MySQL / MariaDB |
| Vendor SLiMS | utuh (HTMLPurifier, Volnix CSRF, Carbon, Flysystem) |
| Build | PHP CLI; internet **opsional** (hanya untuk `--fa`) |

---

## 4. Struktur Folder
```text
sonic/
├── api/
│   ├── facet-counts.php      # hitung facet (cache 120s)
│   ├── marc.php              # ekspor ISO 2709 (.mrc)
│   ├── review.php            # rating & ulasan (GET/POST, CSRF)
│   └── suggest.php           # autocomplete / did-you-mean
├── assets/
│   ├── css/                  # 20 modular + sonic-premium.css + sonic.bundle.css + flag-icon.min.css
│   ├── flags/                # 1x1 & 4x3 (518 SVG)
│   ├── images/               # slider/, icon/, dewey, logo, slide*.webp
│   ├── js/                   # modular + sonic-premium.js + sonic.bundle.js + vegas.js.php
│   └── plugin/
│       ├── font-awesome/         # 5.0.4 (lama, dipertahankan)
│       ├── font-awesome-5.15.4/  # baru (hasil --fa)
│       └── vegas/
├── citation/                 # apa / mla / chicago / turabian
├── parts/
│   ├── _security.php         # helper bersama (kses/cache/host/url)
│   ├── header.php  footer.php
│   ├── _navbar.php _search-form.php _home.php _result-search.php
│   ├── _member.php _member-dashboard.php _login.php _map.php
│   ├── _modal_advanced.php _modal_social_media.php _modal_topic.php
│   └── _other.php _profile.php
├── tools/
│   └── build_bundles.php     # CLI build (CSS/JS/WebP/flags/FA)
├── biblio_list_template.php  classic.php  detail_template.php
├── index_template.inc.php    login_template.inc.php
├── news_template.php         tinfo.inc.php  visitor_template.php
├── custom_frontpage_record.inc.php
└── preview.png
```

---

## 5. Instalasi
1. **Backup** folder template & database.
2. Salin folder `sonic` ke `template/sonic` (boleh di-rename; template *rename-safe* via `basename(__DIR__)`).
3. Aktifkan: **Admin → System → Theme → tema publik = `sonic`**.
4. (Disarankan) jalankan build agar bundle + WebP + FA aktif:
   ```bat
   cd C:\laragon\www\digilib\template\sonic
   php tools/build_bundles.php --fa
   php tools/build_bundles.php
   ```
5. **Ctrl+F5** di browser.
6. Sesuaikan lewat **Admin → System → Theme → Customize** (lihat §7).

> Tanpa build pun template tetap jalan (mode modular). Build hanya optimasi.

---

## 6. Build Tool
`tools/build_bundles.php` — **CLI only** (ditolak lewat browser demi keamanan). Tidak menyentuh core; file asli tidak dihapus.

| Perintah | Fungsi |
|---|---|
| `php tools/build_bundles.php` | build semua (CSS+JS+WebP+flags+FA) |
| `php tools/build_bundles.php --css` | bundle CSS saja |
| `php tools/build_bundles.php --js` | bundle JS saja |
| `php tools/build_bundles.php --webp` | konversi slide → WebP |
| `php tools/build_bundles.php --flags` | generate `flag-icon.min.css` |
| `php tools/build_bundles.php --fa` | unduh Font Awesome 5.15.4 (folder baru) |
| `php tools/build_bundles.php --clean` | hapus bundle → kembali modular |

**Mode bundle vs modular:** bila `sonic.bundle.css/js` ada, header/footer memakainya; bila dihapus (`--clean`), kembali memuat file modular. **Setiap edit modular wajib rebuild** agar terlihat.

---

## 7. Kustomisasi Tema
Semua opsi tersedia di panel Kustomisasi (nilai DB selalu menang atas default).

| dbfield | Tipe | Default | Keterangan |
|---|---|---|---|
| `sonic_profile_vision` / `_mission` / `_year` / `_accreditation` | longtext/text | – | Profil perpustakaan |
| `sonic_library_subname` | text | Hyperdrive Library System | Sub-nama brand |
| `sonic_footer_about_us` | longtext | – | Tentang di footer |
| `sonic_welcome_text` | longtext | SELAMAT DATANG… | Teks hero (per kata) |
| `sonic_particle` | dropdown | books | books/stars/bolts/mixed/none |
| `sonic_walker` | dropdown | person | person/robot/astronaut/none |
| `sonic_contact_email` | text | library@example.com | Kontak |
| `sonic_fb_link` `_twitter_link` `_instagram_link` `_youtube_link` `_telegram_link` | text | – | Sosial (divalidasi skema) |
| `sonic_extra_menus` / `sonic_extra_menu_label` | longtext/text | – / Lainnya | Menu navbar tambahan (`Label\|URL`) |
| `sonic_popular_collection` / `_limit` | choice/numeric | 1 / 10 | Koleksi populer |
| `sonic_new_collection` / `_limit` | choice/numeric | 1 / 10 | Koleksi terbaru |
| `sonic_top_reader` | choice | 1 | Pembaca teratas |
| `sonic_trending_collection` / `_limit` | choice/numeric | 1 / 10 | **Trending 30 hari** |
| `sonic_top_visitor` / `_limit` | choice/numeric | 1 / 6 | **Anggota terbanyak mengunjungi** |
| `sonic_map` / `_link` / `_desc` | choice/text/longtext | 1 | Peta |
| `sonic_kiosk_idle` / `_quotes` | numeric/longtext | 20 / – | Kiosk |

---

## 8. Cara Kerja Fitur Premium
- **Terakhir Dilihat** — `sonic-premium.js` mencatat `{id,title,cover,url,ts}` ke `localStorage['sonicViewed']` saat halaman detail dibuka; di home dirender sebagai rail snap (maks 8), tombol hapus + navigasi, tanggal dilokalkan (`id-ID`/`en-US` dari `SONIC_CFG.lang`). Murni client-side.
- **Mungkin maksud Anda…** — saat hasil kosong & ada `keywords`, memanggil `api/suggest.php`, menampilkan ≤5 saran judul sebagai chip yang menjalankan ulang pencarian.
- **Trending 30 Hari** — `getTrendingBiblio()` menghitung pinjam 30 hari terakhir (cache 300s); section + badge jumlah; toggle di Kustomisasi.
- **Anggota terbanyak mengunjungi** — `getTopVisitors()` membaca `visitor_count`/`visitor` (deteksi kolom tanggal), join `member`, 30 hari terakhir dengan fallback all-time (cache 600s); medali emas/perak/perunggu untuk peringkat 1–3.
- **Font Awesome 5.15.4** — header memuat folder baru bila ada, else folder lama. `--fa` mengunduh css + webfonts; gagal unduh tidak merusak (fallback lama).

---

## 9. Endpoint API
| Endpoint | Method | Output |
|---|---|---|
| `api/suggest.php?q=` | GET | `[{t:title\|author\|subject, v}]` |
| `api/facet-counts.php` | GET | `{gmd,location,colltype}` (cache 120s) |
| `api/marc.php?id=` | GET | ISO 2709 `application/marc` (.mrc) |
| `api/review.php?biblio_id=` | GET | `{avg,count,items}` |
| `api/review.php` (`action=add`) | POST | `{status,message,new_token,token_name}` (login+CSRF) |

---

## 10. Keamanan
- `sonic_kses` = HTMLPurifier (whitelist) + fallback tahan `<svg/onload>`, tag tak tertutup, `javascript:` berentitas.
- Escaping `htmlspecialchars` pada meta/OG/JSON-LD/atribut; sanitasi host-header.
- CSRF ulasan **fail-closed** + rotasi token; sesi member di-boot di API.
- Validasi skema URL (`sonic_http_url`) menolak `javascript:/data:/vbscript:`.
- Header: `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`.
- Query ter-guard `try/catch` + `(int)`/`real_escape_string`; tanpa `ORDER BY RAND()` tabel besar.

---

## 11. Performa
- **Bundling** 31→2 request; cache-bust `filemtime`.
- **WebP** slider ±69% lebih kecil; preload slide pertama.
- **Cache file** `sonic_cache()` (statistik footer, trending, visitor, similar, facet).
- **1 query/baris** hasil (notes+call_number+availability) dengan cache per-request.
- Animasi pause saat tab hidden; hormati `prefers-reduced-motion`.
- `Cache-Control: private, no-cache` (bukan `no-store`) → back/forward cache hidup.

---

## 12. Alur Pengembangan
1. Edit file **modular** (`assets/css/*.css`, `assets/js/*.js`, `parts/*.php`).
2. Rebuild sesuai jenis: `--css` / `--js` (atau penuh).
3. **Ctrl+F5**.
4. Debug tanpa bundle: `--clean`, uji, lalu build lagi.
> Jangan edit `sonic.bundle.*` manual — selalu hasil generate.

---

## 13. Troubleshooting
| Gejala | Penyebab | Solusi |
|---|---|---|
| Perubahan tidak terlihat | bundle stale / cache browser | rebuild `--css`/`--js` + Ctrl+F5 |
| Slider hanya 1 slide | markup & JS tidak sinkron / bundle stale | samakan `_search-form.php`+`sonic.js`, rebuild |
| Kiosk fatal (PHP 8) | `$sonic_last7_max` hilang | pakai `visitor_template.php` final |
| Peringkat teks polos | `sonic-premium.css` belum ter-bundle | rebuild `--css` |
| Tanggal masih angka | `sonic-premium.js` belum ter-bundle | rebuild `--js` |
| "CLI only" di browser | memang ditolak | jalankan via terminal |
| FA gagal unduh | tanpa internet | ulangi `--fa` saat online (fallback lama aman) |
| WebP dilewati | GD tidak ada | aktifkan `extension=gd` |
| Permission denied | folder tak writable | chmod `assets/css`, `assets/js` |
| `php` tidak dikenal | PATH | pakai terminal Laragon / path penuh `php.exe` |
| Path not found | typo folder | pastikan `C:\laragon\www\digilib\template\sonic` |

---

## 14. Changelog
- **Batch 1** — keamanan & bug: kses HTMLPurifier, escaping meta, CSRF+sesi ulasan, state facet `filter_input`, bookmark toggle, cache, guard query, fix kiosk/slider/navbar.
- **Batch 2** — performa: bundling, WebP, flag-icon mandiri, build tool, hero search-first, navbar sticky.
- **Batch 3** — premium: Terakhir Dilihat, Mungkin maksud Anda…, Trending 30 Hari, Anggota terbanyak mengunjungi, FA 5.15.4; refinement rail snap + medali + tanggal lokal.

---

## 15. Kredit & Lisensi
- **SLiMS** (SLiMS Developer Community) — GPL-3.0.
- **SONIC** — template turunan, lisensi mengikuti SLiMS (GPL-3.0).
- Font Awesome (FontAwesome) · flag-icons (lipis) · Vegas (jaywils) · HTMLPurifier (ezyang) · Volnix CSRF · Carbon · Google Fonts (Inter/Orbitron/Rajdhani).
- Aset pihak ketiga tetap pada lisensi masing-masing.

---

## 16. FAQ
**Q: Apakah core SLiMS diubah?**
A: Tidak. Semua di dalam folder template.

**Q: Apakah fitur lama hilang?**
A: Tidak. Semua fitur bawaan dipertahankan; perubahan additif.

**Q: Wajib build?**
A: Tidak (mode modular jalan). Build = optimasi.

**Q: Boleh rename folder?**
A: Boleh — template rename-safe.

**Q: Offline?**
A: Ya, kecuali `--fa` (unduh FA). Setelah FA terunduh, sepenuhnya offline.

---
⚡ **SONIC — perpustakaan digital dengan akses kecepatan cahaya.**