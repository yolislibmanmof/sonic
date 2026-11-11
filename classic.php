<?php
/**
 * @Created  : SONIC Template (fork of classic.php)
 * @File name: classic.php
 */

if (!defined('INDEX_AUTH')) { die("can not access this file directly"); }
elseif (INDEX_AUTH != 1) { die("can not access this file directly"); }

define('CURRENT_TEMPLATE_DIR', $sysconf['template']['dir'] . '/' . $sysconf['template']['theme'] . '/');

$is_login = utility::isMemberLogin();
$member_image_name = isset($_SESSION['m_image']) ? $_SESSION['m_image'] : 'person.png';
$member_image_path = getImagePath($sysconf, $member_image_name, 'persons');

function assets($path = '') { return CURRENT_TEMPLATE_DIR . 'assets/' . $path; }

/* ===== SONIC FASE 1 HELPERS ===== */
if (!function_exists('sonic_common_list')) {
    // Pengganti commonList() yang TIDAK ADA di SLiMS 9.8. Query DB langsung + cache.
    function sonic_common_list($type) {
        global $dbs;
        static $cache = [];
        if (isset($cache[$type])) return $cache[$type];
        $cfg = [
            'gmd'        => ['SELECT gmd_name FROM mst_gmd ORDER BY gmd_name LIMIT 50', __('All GMD/Media')],
            'location'   => ['SELECT location_name FROM mst_location ORDER BY location_name LIMIT 50', __('All Locations')],
            'collection' => ['SELECT coll_type_name FROM mst_coll_type ORDER BY coll_type_name LIMIT 50', __('All Collections')],
            'colltype'   => ['SELECT coll_type_name FROM mst_coll_type ORDER BY coll_type_name LIMIT 50', __('All Collections')],
        ];
        if (!isset($cfg[$type])) return '';
        list($sql, $allLabel) = $cfg[$type];
        $out = '<option value="0">' . $allLabel . '</option>';
        if (isset($dbs) && is_object($dbs)) {
            try {
                $q = $dbs->query($sql);
                if ($q) while ($r = $q->fetch_row()) {
                    $v = htmlspecialchars($r[0], ENT_QUOTES);
                    $out .= '<option value="' . $v . '">' . $v . '</option>';
                }
            } catch (Throwable $e) {}
        }
        return $cache[$type] = $out;
    }
}
if (!function_exists('sonic_is_bookmarked')) {
    // Status bookmark dari DB (biblio_mark) — sumber kebenaran lintas-login.
    function sonic_is_bookmarked($biblio_id) {
        global $dbs;
        static $set = null;
        if (!class_exists('utility') || !utility::isMemberLogin()) return false;
        if ($set === null) {
            $set = [];
            $mid = isset($_SESSION['mid']) ? $dbs->real_escape_string($_SESSION['mid']) : '';
            if ($mid !== '' && isset($dbs) && is_object($dbs)) {
                try {
                    $q = $dbs->query("SELECT biblio_id FROM biblio_mark WHERE member_id='{$mid}'");
                    if ($q) while ($r = $q->fetch_row()) $set[(int)$r[0]] = true;
                } catch (Throwable $e) {}
            }
        }
        return isset($set[(int)$biblio_id]);
    }
}
if (!function_exists('sonic_kses')) {
    // Sanitasi HTML ringan: buang script/iframe & atribut event & javascript: (untuk notes/abstrak).
    function sonic_kses($html) {
        $html = (string)$html;
        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i', '$1=$2#$2', $html);
        return $html;
    }
}
if (!function_exists('sonic_asset_ver')) {
    // Cache-bust berbasis filemtime (bukan detik) agar CSS/JS bisa di-cache browser.
    function sonic_asset_ver($rel) {
        static $cache = [];
        if (isset($cache[$rel])) return $cache[$rel];
        $path = __DIR__ . '/assets/' . ltrim($rel, '/');
        return $cache[$rel] = (is_file($path) ? (string)filemtime($path) : '1');
    }
}

function getPopularBiblio($dbs, $limit = 5) {
    $sql = "SELECT b.biblio_id, b.title, b.image, COUNT(*) AS total
            FROM loan AS l
            LEFT JOIN item AS i ON l.item_code=i.item_code
            LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
            WHERE b.title IS NOT NULL
            GROUP BY b.biblio_id
            ORDER BY total DESC
            LIMIT {$limit}";
    $query = $dbs->query($sql);
    $return = array();
    while ($data = $query->fetch_assoc()) { $return[] = $data; }
    if ($query->num_rows < $limit) {
        $need = $limit - $query->num_rows;
        if ($need < 0) $need = $limit;
        $exclude = array_map(fn($r) => (int)$r['biblio_id'], $return);
        $where = $exclude ? 'WHERE biblio_id NOT IN (' . implode(',', $exclude) . ')' : '';
        $sql = "SELECT biblio_id, title, image FROM biblio {$where} ORDER BY last_update DESC LIMIT {$need}";
        $query = $dbs->query($sql);
        while ($data = $query->fetch_assoc()) { $return[] = $data; }
    }
    return $return;
}

function getPopularTopic($dbs, $limit = 5) {
    $sql = "SELECT mt.topic, COUNT(*) AS total FROM loan AS l
            LEFT JOIN item AS i ON l.item_code=i.item_code
            LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
            LEFT JOIN biblio_topic AS bt ON i.biblio_id=bt.biblio_id
            LEFT JOIN mst_topic AS mt ON bt.topic_id=mt.topic_id
            WHERE mt.topic IS NOT NULL GROUP BY bt.topic_id ORDER BY total DESC LIMIT {$limit}";
    $query = $dbs->query($sql);
    $return = array();
    while ($data = $query->fetch_row()) { $return[] = $data[0]; }
    if ($query->num_rows < $limit) {
        $need = $limit - $query->num_rows;
        if ($need < 0) $need = $limit;
        $sql = "SELECT mt.topic, COUNT(*) AS total FROM biblio_topic AS bt
                LEFT JOIN mst_topic AS mt ON bt.topic_id=mt.topic_id
                WHERE mt.topic IS NOT NULL GROUP BY bt.topic_id ORDER BY total DESC LIMIT {$need}";
        $query = $dbs->query($sql);
        while ($data = $query->fetch_row()) { $return[] = $data[0]; }
    }
    return $return;
}

function getLatestBiblio($dbs, $limit = 5) {
    $sql = "SELECT biblio_id, title, image FROM biblio ORDER BY last_update DESC LIMIT {$limit}";
    $query = $dbs->query($sql);
    $return = array();
    while ($data = $query->fetch_assoc()) { $return[] = $data; }
    return $return;
}

function getRandomBiblio($dbs, $limit = 5) {
    $sql = "SELECT max(biblio.biblio_id) AS biblio_id, max(biblio.title) AS title, max(biblio.image) As image,
            GROUP_CONCAT(mst_author.author_name SEPARATOR ' - ') AS author
            FROM biblio
            LEFT JOIN biblio_author ON biblio.biblio_id=biblio_author.biblio_id
            LEFT JOIN mst_author ON biblio_author.author_id=mst_author.author_id
            GROUP BY biblio_author.biblio_id ORDER BY RAND() LIMIT {$limit}";
    $query = $dbs->query($sql);
    $return = array();
    while ($data = $query->fetch_assoc()) { $return[] = $data; }
    return $return;
}

function getLatestTopic($dbs, $limit = 5) {
    $sql = "SELECT mt.topic FROM biblio_topic AS bt
            LEFT JOIN biblio AS b ON bt.biblio_id=b.biblio_id
            LEFT JOIN mst_topic AS mt ON mt.topic_id=bt.topic_id
            WHERE mt.topic IS NOT NULL GROUP BY bt.topic_id
            ORDER BY max(b.last_update) DESC LIMIT {$limit}";
    $query = $dbs->query($sql);
    $return = array();
    while ($data = $query->fetch_row()) { $return[] = $data[0]; }
    return $return;
}

function getTopic($dbs, $biblio_id) {
    $biblio_id = (int)$biblio_id;
    $query = $dbs->query("SELECT topic FROM biblio_topic AS bt JOIN mst_topic AS mt ON bt.topic_id=mt.topic_id WHERE bt.biblio_id={$biblio_id}");
    $return = array();
    while ($data = $query->fetch_row()) { $return[] = $data[0]; }
    return $return;
}

function getActiveMembers($dbs, $year, $limit = 3) {
    $sql = "SELECT m.member_name, mm.member_type_name, m.member_image, COUNT(*) AS total,
            GROUP_CONCAT(i.biblio_id SEPARATOR ';') AS biblio_id
            FROM loan AS l LEFT JOIN member AS m ON l.member_id=m.member_id
            LEFT JOIN mst_member_type AS mm ON m.member_type_id=mm.member_type_id
            LEFT JOIN item As i ON l.item_code=i.item_code
            WHERE l.loan_date LIKE '{$year}-%' AND m.member_name IS NOT NULL
            GROUP BY m.member_id ORDER BY total DESC LIMIT {$limit}";
    $query = $dbs->query($sql);
    $return = array();
    if ($query) {
        while ($data = $query->fetch_assoc()) {
            $title = array_unique(explode(';', $data['biblio_id']));
            $return[] = array(
                'name' => $data['member_name'],
                'type' => $data['member_type_name'],
                'image' => $data['member_image'],
                'total' => $data['total'],
                'total_title' => count($title),
                'order' => $data['total']+count($title));
        }
    }
    usort($return, function ($a, $b) { return $b['order'] <=> $a['order']; });
    return $return;
}

function getImagePath($sysconf, $image, $path = 'docs') {
    $thumb_url = '';
    $image = urlencode($image);
    $images_loc = 'images/' . $path . '/' . $image;
    $img_status = pathinfo('images/' . $path . '/' . $image);
    if(isset($img_status['extension'])){
        $thumb_url = './lib/minigalnano/createthumb.php?filename=' . urlencode($images_loc) . '&width=120';
    } else {
        $thumb_url = './lib/minigalnano/createthumb.php?filename=images/default/image.png&width=120';
    }
    return $thumb_url;
}

function truncate($text, $length) {
    $length = abs((int)$length);
    if (strlen($text) > $length) {
        $text = preg_replace("/^(.{1,$length})(\s.*|$)/s", '\1...', $text);
    }
    return ($text);
}

function getQuery($key, $optional = '') {
    return isset($_GET[$key]) ? utility::filterData($key, 'get', true, true, true) : $optional;
}

/* ===== SONIC i18n: terjemahan string kustom template ===== */
if (!function_exists('sonic_t')) {
    function sonic_t($en) {
        static $map = [
            // Home
            'Library Information' => 'Informasi Perpustakaan',
            'Library Profile' => 'Profil Perpustakaan',
            'Vision, mission, history and accreditation of our library' => 'Visi, misi, sejarah, dan akreditasi perpustakaan kami',
            'Vision' => 'Visi',
            'Mission' => 'Misi',
            'Founding Year' => 'Tahun Pendirian',
            'Accreditation Status' => 'Status Akreditasi',
            'Profile content is not set yet. Please fill it in Theme Customization.' => 'Konten profil belum diisi. Silakan isi melalui Kustomisasi Tema.',
            'Select the topic you are interested in' => 'Pilih subjek yang menarik bagi Anda',
            'Navigate through knowledge domains'     => 'Jelajahi ranah pengetahuan',
            'Literature'      => 'Kesusastraan',
            'Social Sciences' => 'Ilmu-Ilmu Sosial',
            'Applied Sciences'=> 'Ilmu-Ilmu Terapan',
            'Art & Recreation'=> 'Kesenian, Hiburan, dan Olahraga',
            'see more..'      => 'lihat lainnya..',
            'Popular among our collection'      => 'Populer di antara koleksi kami',
            "Our library's top-borrowed titles" => 'Judul paling banyak dipinjam perpustakaan kami',
            'New collections + updated'         => 'Koleksi baru dan diperbarui',
            'Fresh additions to our database'   => 'Tambahan terbaru ke basis data kami',
            'Top reader of the year'            => 'Pembaca teratas tahun ini',
            'Our most active scholars'          => 'Cendekiawan paling aktif kami',
            'borrowed' => 'dipinjam',
            'updated'  => 'diperbarui',
            // Footer
            'Total Collections' => 'Total Koleksi',
            'Active Members'    => 'Anggota Aktif',
            'Downloads'         => 'Unduhan',
            'Hours Online'      => 'Jam Online',
            'Quick Links'       => 'Tautan Cepat',
            'Our Services'      => 'Layanan Kami',
            'Quick Search'      => 'Pencarian Cepat',
            'Search collections...' => 'Cari koleksi...',
            'Need Help?'        => 'Butuh Bantuan?',
            'Our team is ready to assist you' => 'Tim kami siap membantu Anda',
            'Support SLiMS'     => 'Dukung SLiMS',
            'Contribute'        => 'Berkontribusi',
            'All rights reserved' => 'Hak cipta dilindungi',
            'Powered by'        => 'Didukung oleh',
            'Template'          => 'Templat',
            'Secure'            => 'Aman',
            'Fast'              => 'Cepat',
            'Responsive'        => 'Responsif',
            'Digital Collections' => 'Koleksi Digital',
            'E-Books'           => 'E-Book',
            'Journals'          => 'Jurnal',
            'Thesis & Dissertations' => 'Tesis & Disertasi',
            'Interlibrary Loan' => 'Pinjam Antar Perpustakaan',
            'Reference Service' => 'Layanan Referensi',
            'Digital library with lightning-fast access' => 'Perpustakaan digital dengan akses kecepatan cahaya.',
            // Navbar dropdown "More/Lainnya" (dua arah: EN<->ID)
            'More' => 'Lainnya',
        ];
        static $reverse = [
            'Lainnya' => 'More',
        ];
        $lang = $_COOKIE['select_lang'] ?? ($GLOBALS['sysconf']['default_lang'] ?? 'en_US');
        $is_id = (stripos($lang, 'id') === 0);
        if ($is_id && isset($map[$en])) return $map[$en];
        if (!$is_id && isset($reverse[$en])) return $reverse[$en];
        return __($en);
    }
}

/* ===== SONIC i18n: terjemahan konten libinfo (markup bawaan SLiMS) ===== */
if (!function_exists('sonic_translate_html')) {
    function sonic_translate_html($html) {
        $lang = $_COOKIE['select_lang'] ?? ($GLOBALS['sysconf']['default_lang'] ?? 'en_US');
        if (stripos($lang, 'id') !== 0) return $html;

        /* Label & judul seksi (cocok-persis aman) */
        $map = [
            'Contact Information' => 'Informasi Kontak',
            'Address :'           => 'Alamat :',
            'Phone Number :'      => 'Nomor Telepon :',
            'Fax Number :'        => 'Nomor FAX :',
            'Email :'             => 'Surel :',
            'Opening Hours'       => 'Jam Buka',
            'Monday - Friday :'   => 'Senin - Jumat :',
            'Monday :'            => 'Senin :',
            'Tuesday :'           => 'Selasa :',
            'Wednesday :'         => 'Rabu :',
            'Thursday :'          => 'Kamis :',
            'Friday :'            => 'Jumat :',
            'Saturday :'          => 'Sabtu :',
            'Sunday :'            => 'Minggu :',
            'Open :'              => 'Buka :',
            'Break :'             => 'Istirahat :',
            'Close :'             => 'Tutup :',
            'Collections'         => 'Koleksi',
            'Library Membership'  => 'Keanggotaan Perpustakaan',
        ];
        $html = str_replace(array_keys($map), array_values($map), $html);

        /* Paragraf panjang: regex toleran terhadap variasi teks/whitespace inti SLiMS */
        $html = preg_replace(
            '/We\s+have\s+many\s+types\s+of\s+collections.*?magazines\./is',
            'Kami memiliki berbagai jenis koleksi di perpustakaan kami, mulai dari Fiksi hingga Materi Sains, dari bahan cetak hingga koleksi digital seperti CD-ROM, CD, VCD, dan DVD. Kami juga mengumpulkan terbitan berkala harian seperti surat kabar serta terbitan berkala bulanan seperti majalah.',
            $html
        );
        $html = preg_replace(
            '/To\s+be\s+able\s+to\s+loan\s+our\s+library\s+collections.*?obey\./is',
            'Untuk dapat meminjam koleksi perpustakaan kami, Anda harus terlebih dahulu menjadi anggota perpustakaan. Ada syarat dan ketentuan yang harus Anda patuhi.',
            $html
        );

        return $html;
    }
}