<?php
/**
 * @File name: classic.php (SONIC — BATCH 1: sanitizer kuat, cache, guard query)
 */
if (!defined('INDEX_AUTH')) { die("can not access this file directly"); }
elseif (INDEX_AUTH != 1) { die("can not access this file directly"); }

define('CURRENT_TEMPLATE_DIR', $sysconf['template']['dir'] . '/' . basename(__DIR__) . '/');

$is_login = utility::isMemberLogin();
$member_image_name = isset($_SESSION['m_image']) ? $_SESSION['m_image'] : 'person.png';
$member_image_path = getImagePath($sysconf, $member_image_name, 'persons');

function assets($path = '') { return CURRENT_TEMPLATE_DIR . 'assets/' . $path; }

/* ===== SONIC HELPERS ===== */
if (!function_exists('sonic_cache')) {
    // Cache file ringan (TTL detik) + memo per-request. Aman untuk multi-instance.
    function sonic_cache($key, $ttl, $callback) {
        static $mem = [];
        if (array_key_exists($key, $mem)) return $mem[$key];
        $dir = sys_get_temp_dir() . '/sonic_cache';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/' . md5((defined('SB') ? SB : '') . $key) . '.json';
        if (is_file($file) && (time() - (int)@filemtime($file)) < $ttl) {
            $d = json_decode((string)@file_get_contents($file), true);
            if ($d !== null) return $mem[$key] = $d;
        }
        $data = $callback();
        if ($data !== null) {
            @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
            $mem[$key] = $data;
        }
        return $data;
    }
}
if (!function_exists('sonic_http_url')) {
    // Validasi skema URL (tolak javascript:/data:/vbscript:). URL relatif diizinkan.
    function sonic_http_url($url) {
        $url = trim((string)$url);
        if ($url === '') return '';
        $probe = preg_replace('/[\x00-\x20]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (preg_match('#^(javascript|data|vbscript|file):#i', $probe)) return '';
        if (preg_match('#^(https?://|mailto:|tel:|/|\./|index\.php|\#)#i', $probe)) return $url;
        return '';
    }
}
if (!function_exists('sonic_host')) {
    // Host bersih untuk meta/OG/JSON-LD (anti host-header injection).
    function sonic_host() {
        $h = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        return preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $h);
    }
}
if (!function_exists('sonic_common_list')) {
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
    /**
     * Sanitasi HTML whitelist. Prioritas: HTMLPurifier bawaan SLiMS 9.8.
     * Fallback: strip_tags whitelist + whitelist atribut + validasi skema URL
     * (tahan <svg/onload>, tag tak tertutup, javascript: tanpa kutip / berentitas).
     */
    function sonic_kses($html) {
        $html = (string)$html;
        if (trim($html) === '') return '';
        if (!class_exists('HTMLPurifier') && defined('LIB') && is_file(LIB . 'ezyang/htmlpurifier/library/HTMLPurifier.auto.php')) {
            require_once LIB . 'ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
        }
        if (class_exists('HTMLPurifier')) {
            static $purifier = null;
            try {
                if ($purifier === null) {
                    $cfg = HTMLPurifier_Config::createDefault();
                    $cfg->set('Core.Encoding', 'UTF-8');
                    $cfg->set('Cache.SerializerPath', sys_get_temp_dir());
                    $cfg->set('HTML.Allowed', 'p,br,b,strong,i,em,u,s,ul,ol,li,dl,dt,dd,blockquote,a[href|title|target|rel],img[src|alt|title|width|height],table[border|cellpadding|cellspacing],thead,tbody,tr,td[colspan|rowspan],th[colspan|rowspan],span,h1,h2,h3,h4,h5,h6,pre,code,sub,sup,hr,figure,figcaption');
                    $cfg->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
                    $cfg->set('HTML.TargetBlank', true);
                    $cfg->set('HTML.Nofollow', true);
                    $purifier = new HTMLPurifier($cfg);
                }
                return $purifier->purify($html);
            } catch (Throwable $e) { /* jatuh ke fallback */ }
        }
        // Fallback tanpa Purifier
        $html = preg_replace('#<!--.*?-->#s', '', $html);
        $html = preg_replace('#<(script|style|iframe|object|embed|form|link|meta|base)\b[^>]*>.*?</\1\s*>#is', '', $html);
        $html = preg_replace('#<(script|style|iframe|object|embed|form|link|meta|base)\b[^>]*>.*$#is', '', $html);
        $allow = '<p><br><b><strong><i><em><u><s><ul><ol><li><dl><dt><dd><blockquote><a><img><table><thead><tbody><tr><td><th><span><h1><h2><h3><h4><h5><h6><pre><code><sub><sup><hr><figure><figcaption>';
        $html = strip_tags($html, $allow);
        $attrs = [
            'a' => ['href','title','target','rel'], 'img' => ['src','alt','title','width','height'],
            'table' => ['border','cellpadding','cellspacing'], 'td' => ['colspan','rowspan'], 'th' => ['colspan','rowspan'],
        ];
        $html = preg_replace_callback('#<\s*([a-zA-Z][a-zA-Z0-9]*)\b([^>]*?)(/?)>#s', function ($m) use ($attrs) {
            $tag = strtolower($m[1]);
            $ok = $attrs[$tag] ?? [];
            $out = '';
            if (preg_match_all('#([a-zA-Z-]+)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))#', $m[2], $am, PREG_SET_ORDER)) {
                foreach ($am as $a) {
                    $name = strtolower($a[1]);
                    if (!in_array($name, $ok, true)) continue;
                    $val = (($a[3] ?? '') !== '') ? $a[3] : ((($a[4] ?? '') !== '') ? $a[4] : ($a[5] ?? ''));
                    if ($name === 'href' || $name === 'src') {
                        $v = preg_replace('/[\x00-\x20]+/', '', html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        if (!preg_match('#^(https?://|mailto:|tel:|/|\./|\#)#i', $v)) continue;
                    }
                    if ($name === 'target') $val = '_blank';
                    if ($name === 'rel') $val = 'noopener noreferrer';
                    $out .= ' ' . $name . '="' . htmlspecialchars($val, ENT_QUOTES) . '"';
                }
            }
            if ($tag === 'a' && strpos($out, 'target=') !== false && strpos($out, 'rel=') === false) $out .= ' rel="noopener noreferrer"';
            return '<' . $tag . $out . ($m[3] === '/' ? ' /' : '') . '>';
        }, $html);
        return $html;
    }
}
if (!function_exists('sonic_asset_ver')) {
    function sonic_asset_ver($rel) {
        static $cache = [];
        if (isset($cache[$rel])) return $cache[$rel];
        $path = __DIR__ . '/assets/' . ltrim($rel, '/');
        return $cache[$rel] = (is_file($path) ? (string)filemtime($path) : '1');
    }
}

function getPopularBiblio($dbs, $limit = 5) {
    $limit = max(1, (int)$limit);
    return sonic_cache("popular_biblio_{$limit}", 300, function () use ($dbs, $limit) {
        $return = [];
        try {
            $sql = "SELECT b.biblio_id, b.title, b.image, COUNT(*) AS total
                    FROM loan AS l
                    LEFT JOIN item AS i ON l.item_code=i.item_code
                    LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
                    WHERE b.title IS NOT NULL
                    GROUP BY b.biblio_id ORDER BY total DESC LIMIT {$limit}";
            $query = $dbs->query($sql);
            if ($query) while ($data = $query->fetch_assoc()) { $return[] = $data; }
            $n = $query ? $query->num_rows : 0;
            if ($n < $limit) {
                $need = $limit - $n;
                $exclude = array_map(fn($r) => (int)$r['biblio_id'], $return);
                $where = $exclude ? 'WHERE biblio_id NOT IN (' . implode(',', $exclude) . ')' : '';
                $q2 = $dbs->query("SELECT biblio_id, title, image FROM biblio {$where} ORDER BY last_update DESC LIMIT {$need}");
                if ($q2) while ($data = $q2->fetch_assoc()) { $return[] = $data; }
            }
        } catch (Throwable $e) {}
        return $return;
    });
}

function getPopularTopic($dbs, $limit = 5) {
    $limit = max(1, (int)$limit);
    return sonic_cache("popular_topic_{$limit}", 600, function () use ($dbs, $limit) {
        $return = [];
        try {
            $q = $dbs->query("SELECT mt.topic, COUNT(*) AS total FROM loan AS l
                LEFT JOIN item AS i ON l.item_code=i.item_code
                LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
                LEFT JOIN biblio_topic AS bt ON i.biblio_id=bt.biblio_id
                LEFT JOIN mst_topic AS mt ON bt.topic_id=mt.topic_id
                WHERE mt.topic IS NOT NULL GROUP BY bt.topic_id ORDER BY total DESC LIMIT {$limit}");
            if ($q) while ($r = $q->fetch_row()) { $return[] = $r[0]; }
            $n = $q ? $q->num_rows : 0;
            if ($n < $limit) {
                $need = $limit - $n;
                $q2 = $dbs->query("SELECT mt.topic, COUNT(*) AS total FROM biblio_topic AS bt
                    LEFT JOIN mst_topic AS mt ON bt.topic_id=mt.topic_id
                    WHERE mt.topic IS NOT NULL GROUP BY bt.topic_id ORDER BY total DESC LIMIT {$need}");
                if ($q2) while ($r = $q2->fetch_row()) { $return[] = $r[0]; }
            }
        } catch (Throwable $e) {}
        return $return;
    });
}

function getLatestBiblio($dbs, $limit = 5) {
    $limit = max(1, (int)$limit);
    return sonic_cache("latest_biblio_{$limit}", 300, function () use ($dbs, $limit) {
        $return = [];
        try {
            $q = $dbs->query("SELECT biblio_id, title, image FROM biblio ORDER BY last_update DESC LIMIT {$limit}");
            if ($q) while ($r = $q->fetch_assoc()) { $return[] = $r; }
        } catch (Throwable $e) {}
        return $return;
    });
}

function getRandomBiblio($dbs, $limit = 5) {
    $limit = max(1, (int)$limit);
    $return = [];
    try {
        // FIX: GROUP BY biblio (bukan biblio_author) + hindari ORDER BY RAND() tabel besar.
        $c = $dbs->query('SELECT COUNT(*) FROM biblio');
        $total = $c ? (int)$c->fetch_row()[0] : 0;
        if ($total > 0) {
            $offset = ($total > $limit) ? random_int(0, $total - $limit) : 0;
            $q = $dbs->query("SELECT b.biblio_id, b.title, b.image,
                    GROUP_CONCAT(ma.author_name SEPARATOR ' - ') AS author
                FROM biblio b
                LEFT JOIN biblio_author ba ON b.biblio_id=ba.biblio_id
                LEFT JOIN mst_author ma ON ba.author_id=ma.author_id
                GROUP BY b.biblio_id LIMIT {$offset}, {$limit}");
            if ($q) while ($r = $q->fetch_assoc()) { $return[] = $r; }
        }
    } catch (Throwable $e) {}
    return $return;
}

function getLatestTopic($dbs, $limit = 5) {
    $limit = max(1, (int)$limit);
    return sonic_cache("latest_topic_{$limit}", 600, function () use ($dbs, $limit) {
        $return = [];
        try {
            $q = $dbs->query("SELECT mt.topic FROM biblio_topic AS bt
                LEFT JOIN biblio AS b ON bt.biblio_id=b.biblio_id
                LEFT JOIN mst_topic AS mt ON mt.topic_id=bt.topic_id
                WHERE mt.topic IS NOT NULL GROUP BY bt.topic_id
                ORDER BY max(b.last_update) DESC LIMIT {$limit}");
            if ($q) while ($r = $q->fetch_row()) { $return[] = $r[0]; }
        } catch (Throwable $e) {}
        return $return;
    });
}

function getTopic($dbs, $biblio_id) {
    $biblio_id = (int)$biblio_id;
    $return = [];
    try {
        $q = $dbs->query("SELECT topic FROM biblio_topic AS bt JOIN mst_topic AS mt ON bt.topic_id=mt.topic_id WHERE bt.biblio_id={$biblio_id}");
        if ($q) while ($r = $q->fetch_row()) { $return[] = $r[0]; }
    } catch (Throwable $e) {}
    return $return;
}

function getActiveMembers($dbs, $year, $limit = 3) {
    $year = preg_replace('/\D/', '', (string)$year);
    $limit = max(1, (int)$limit);
    return sonic_cache("active_members_{$year}_{$limit}", 600, function () use ($dbs, $year, $limit) {
        $return = [];
        try {
            $q = $dbs->query("SELECT m.member_name, mm.member_type_name, m.member_image, COUNT(*) AS total,
                    GROUP_CONCAT(i.biblio_id SEPARATOR ';') AS biblio_id
                FROM loan AS l LEFT JOIN member AS m ON l.member_id=m.member_id
                LEFT JOIN mst_member_type AS mm ON m.member_type_id=mm.member_type_id
                LEFT JOIN item As i ON l.item_code=i.item_code
                WHERE l.loan_date LIKE '{$year}-%' AND m.member_name IS NOT NULL
                GROUP BY m.member_id ORDER BY total DESC LIMIT {$limit}");
            if ($q) while ($data = $q->fetch_assoc()) {
                $title = array_unique(explode(';', (string)$data['biblio_id']));
                $return[] = [
                    'name' => $data['member_name'], 'type' => $data['member_type_name'],
                    'image' => $data['member_image'], 'total' => $data['total'],
                    'total_title' => count($title), 'order' => $data['total'] + count($title),
                ];
            }
            usort($return, fn($a, $b) => $b['order'] <=> $a['order']);
        } catch (Throwable $e) {}
        return $return;
    });
}

function getTrendingBiblio($dbs, $limit = 10) {
    $limit = max(1, (int)$limit);
    return sonic_cache("trending_biblio_{$limit}", 300, function () use ($dbs, $limit) {
        $return = [];
        try {
            $q = $dbs->query("SELECT b.biblio_id, b.title, b.image, COUNT(*) AS total
                FROM loan l
                INNER JOIN item i ON l.item_code=i.item_code
                INNER JOIN biblio b ON i.biblio_id=b.biblio_id
                WHERE l.loan_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND b.title IS NOT NULL
                GROUP BY b.biblio_id ORDER BY total DESC, b.last_update DESC LIMIT {$limit}");
            if ($q) while ($r = $q->fetch_assoc()) $return[] = $r;
        } catch (Throwable $e) {}
        return $return;
    });
}

function getTopVisitors($dbs, $limit = 6) {
    $limit = max(1, (int)$limit);
    return sonic_cache("top_visitors_{$limit}", 600, function () use ($dbs, $limit) {
        $return = [];
        try {
            $vt = null;
            foreach (['visitor_count', 'visitor'] as $cand) { $r = $dbs->query("SHOW TABLES LIKE '" . $cand . "'"); if ($r && $r->num_rows) { $vt = $cand; break; } }
            if (!$vt) return $return;
            $vc = 'checkin_date';
            $cr = $dbs->query("SHOW COLUMNS FROM `" . $vt . "`");
            if ($cr) { while ($c = $cr->fetch_assoc()) { $f = strtolower($c['Field']); if (in_array($f, ['checkin_date','visitor_date','date','input_date','created_at'])) { $vc = $c['Field']; break; } } }
            $base = "SELECT v.member_id, m.member_name, m.member_image, mm.member_type_name, COUNT(*) AS total
                FROM `{$vt}` v
                INNER JOIN member m ON v.member_id=m.member_id
                LEFT JOIN mst_member_type mm ON m.member_type_id=mm.member_type_id
                WHERE v.member_id IS NOT NULL AND v.member_id <> ''";
            $grp = " GROUP BY v.member_id, m.member_name, m.member_image, mm.member_type_name ORDER BY total DESC LIMIT {$limit}";
            $q = $dbs->query($base . " AND v.`{$vc}` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)" . $grp);
            if ($q) while ($r = $q->fetch_assoc()) $return[] = $r;
            if (!$return) { $q2 = $dbs->query($base . $grp); if ($q2) while ($r = $q2->fetch_assoc()) $return[] = $r; }
        } catch (Throwable $e) {}
        return $return;
    });
}

function getImagePath($sysconf, $image, $path = 'docs') {
    $image = urlencode($image);
    $images_loc = 'images/' . $path . '/' . $image;
    $img_status = pathinfo('images/' . $path . '/' . $image);
    if (isset($img_status['extension'])) {
        return './lib/minigalnano/createthumb.php?filename=' . urlencode($images_loc) . '&width=120';
    }
    return './lib/minigalnano/createthumb.php?filename=images/default/image.png&width=120';
}

function truncate($text, $length) {
    $length = abs((int)$length);
    if (function_exists('mb_strlen')) {
        if (mb_strlen($text) > $length) return rtrim(mb_substr($text, 0, $length)) . '...';
        return $text;
    }
    if (strlen($text) > $length) {
        $text = preg_replace("/^(.{1,$length})(\s.*|$)/s", '\1...', $text);
    }
    return $text;
}

function getQuery($key, $optional = '') {
    // FIX: tanpa escape_sql untuk keperluan TAMPILAN (escaping dilakukan saat echo).
    return isset($_GET[$key]) ? utility::filterData($key, 'get', false, true, true) : $optional;
}

/* ===== SONIC i18n: terjemahan string kustom template ===== */
if (!function_exists('sonic_t')) {
    function sonic_t($en) {
        static $map = [
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
            'Trending in the last 30 days' => 'Trending 30 hari terakhir',
            'Most borrowed this month'     => 'Paling banyak dipinjam 30 hari terakhir',
            'borrowed (30d)'               => 'dipinjam (30 hari)',
            'Most frequent visitors'       => 'Anggota terbanyak mengunjungi',
            'Our most active visitors'     => 'Anggota paling rajin berkunjung',
            'visits'                       => 'kunjungan',
            'Recently viewed'              => 'Terakhir dilihat',
            'Your reading history on this device' => 'Riwayat baca Anda di perangkat ini',
            'Clear history'                => 'Hapus riwayat',
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
            'Statistics' => 'Statistik',
            'Library Statistics' => 'Statistik Perpustakaan',
            'Live overview of our collection and community' => 'Gambaran langsung koleksi dan komunitas kami',
            'Public statistics are disabled' => 'Statistik publik dinonaktifkan',
            'Titles' => 'Judul',
            'Items' => 'Eksemplar',
            'Total Loans' => 'Total Pinjaman',
            'Loans This Month' => 'Pinjaman Bulan Ini',
            'Visits' => 'Kunjungan',
            'Loans — last 6 months' => 'Pinjaman — 6 bulan terakhir',
            'Collection by GMD' => 'Koleksi berdasarkan GMD',
            'Popular' => 'Populer',
            'Trending 30 days' => 'Trending 30 hari',
            'Top Visitors' => 'Pengunjung Teratas',
            'More' => 'Lainnya',
        ];
        static $reverse = ['Lainnya' => 'More'];
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