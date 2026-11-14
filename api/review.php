<?php
/**
 * SONIC OPAC — Rating & Ulasan (BATCH 1 + BATCH 8: rate-limit + ETag GET)
 * GET  ?biblio_id=X  → {avg,count,items}
 * POST action=add    → {status,message,new_token,token_name}
 */
define('INDEX_AUTH', '1');
require_once '../../../sysconfig.inc.php';
require_once LIB . 'member_session.inc.php'; // WAJIB: sesi member

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

/* BATCH 8: rate-limit (POST lebih ketat) + ETag GET (graceful) */
if (is_file(__DIR__ . '/../parts/_platform.php')) {
    require_once __DIR__ . '/../parts/_platform.php';
    if (function_exists('sonic_api_rate')) {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') sonic_api_rate('review_post', 10, 60);
        else sonic_api_rate('review_get', 60, 60);
    }
}

$TOKEN_NAME = class_exists('\Volnix\CSRF\CSRF') ? \Volnix\CSRF\CSRF::getTokenName() : '_csrf_token_645a83a41868941e4692aa31e7235f2';

/* Tabel sekali saja (flag file; CREATE IF NOT EXISTS idempotent) */
try {
    $flag = sys_get_temp_dir() . '/sonic_review_tbl_' . md5(defined('SB') ? SB : __DIR__) . '.flag';
    if (!is_file($flag)) {
        $dbs->query("CREATE TABLE IF NOT EXISTS sonic_review (
            review_id INT AUTO_INCREMENT PRIMARY KEY,
            biblio_id INT NOT NULL,
            member_id VARCHAR(20) NOT NULL,
            rating TINYINT NOT NULL DEFAULT 5,
            review TEXT,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uq_member_biblio (biblio_id, member_id),
            INDEX idx_biblio (biblio_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        @file_put_contents($flag, '1');
    }
} catch (Throwable $e) {}

$biblio_id = (int)($_REQUEST['biblio_id'] ?? 0);
if ($biblio_id <= 0) { echo json_encode(['avg'=>0,'count'=>0,'items'=>[]]); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $out = ['status'=>false,'message'=>'','new_token'=>'','token_name'=>$TOKEN_NAME];

    if (!class_exists('utility') || !utility::isMemberLogin()) {
        http_response_code(401);
        $out['message'] = 'Login dulu untuk menulis ulasan';
        echo json_encode($out); exit;
    }
    if (!class_exists('\Volnix\CSRF\CSRF') || !\Volnix\CSRF\CSRF::validate($_POST)) {
        http_response_code(403);
        $out['message'] = 'Token tidak valid / kedaluwarsa. Muat ulang halaman.';
        $out['new_token'] = class_exists('\Volnix\CSRF\CSRF') ? \Volnix\CSRF\CSRF::getToken() : '';
        echo json_encode($out); exit;
    }
    $out['new_token'] = \Volnix\CSRF\CSRF::getToken();

    $now = time();
    if (isset($_SESSION['sonic_review_last']) && ($now - (int)$_SESSION['sonic_review_last']) < 15) {
        $out['message'] = 'Terlalu cepat. Tunggu beberapa detik lagi.';
        echo json_encode($out); exit;
    }

    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $review = mb_substr(trim(strip_tags((string)($_POST['review'] ?? ''))), 0, 1000);
    $mid = (string)$_SESSION['mid'];

    try {
        $stmt = $dbs->prepare("INSERT INTO sonic_review (biblio_id, member_id, rating, review, created_at)
                               VALUES (?,?,?,?,NOW())
                               ON DUPLICATE KEY UPDATE rating=VALUES(rating), review=VALUES(review), created_at=NOW()");
        $stmt->bind_param('isis', $biblio_id, $mid, $rating, $review);
        $stmt->execute();
        $_SESSION['sonic_review_last'] = $now;
        $out['status'] = true;
        $out['message'] = 'Ulasan tersimpan. Terima kasih!';
    } catch (Throwable $e) {
        $out['message'] = 'Gagal menyimpan ulasan';
    }
    echo json_encode($out); exit;
}

/* GET: ringkasan + 20 ulasan terbaru (prepared) */
$out = ['avg'=>0,'count'=>0,'items'=>[]];
try {
    $stmt = $dbs->prepare("SELECT AVG(rating) a, COUNT(*) c FROM sonic_review WHERE biblio_id=?");
    $stmt->bind_param('i', $biblio_id); $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if ($r) { $out['avg'] = round((float)$r['a'], 1); $out['count'] = (int)$r['c']; }

    $stmt = $dbs->prepare("SELECT r.rating, r.review, r.created_at, m.member_name
                           FROM sonic_review r LEFT JOIN member m ON r.member_id=m.member_id
                           WHERE r.biblio_id=? ORDER BY r.created_at DESC LIMIT 20");
    $stmt->bind_param('i', $biblio_id); $stmt->execute();
    $q = $stmt->get_result();
    while ($row = $q->fetch_assoc()) $out['items'][] = $row;
} catch (Throwable $e) {}
$json = json_encode($out);
if (function_exists('sonic_etag_send')) { sonic_etag_send($json, 'application/json; charset=utf-8', 60); } else { echo $json; }