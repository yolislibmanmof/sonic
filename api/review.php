<?php
/**
 * SONIC OPAC — Rating & Ulasan pemustaka (mandiri, tanpa mengubah core).
 * GET  ?biblio_id=X        → {avg,count,items}
 * POST action=add          → simpan (wajib login member + CSRF)
 */
define('INDEX_AUTH', '1');
require_once '../../../sysconfig.inc.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $dbs->query("CREATE TABLE IF NOT EXISTS sonic_review (
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        biblio_id INT NOT NULL,
        member_id VARCHAR(20) NOT NULL,
        rating TINYINT NOT NULL DEFAULT 5,
        review TEXT,
        created_at DATETIME NOT NULL,
        INDEX(biblio_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {}

$biblio_id = (int)($_REQUEST['biblio_id'] ?? 0);
if ($biblio_id <= 0) { echo json_encode(['avg'=>0,'count'=>0,'items'=>[]]); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $logged = class_exists('utility') && utility::isMemberLogin();
    if (!$logged) { http_response_code(401); echo json_encode(['status'=>false,'message'=>'Login dulu untuk menulis ulasan']); exit; }
    if (class_exists('\Volnix\CSRF\CSRF') && !\Volnix\CSRF\CSRF::validate($_POST)) { http_response_code(403); echo json_encode(['status'=>false,'message'=>'Token tidak valid']); exit; }
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $review = mb_substr(trim(strip_tags((string)($_POST['review'] ?? ''))), 0, 1000);
    $mid = $dbs->real_escape_string($_SESSION['mid']);
    try {
        $stmt = $dbs->prepare("INSERT INTO sonic_review (biblio_id, member_id, rating, review, created_at) VALUES (?,?,?,?,NOW())");
        $stmt->bind_param('isis', $biblio_id, $mid, $rating, $review);
        $stmt->execute();
        echo json_encode(['status'=>true,'message'=>'Ulasan tersimpan. Terima kasih!']);
    } catch (Throwable $e) { echo json_encode(['status'=>false,'message'=>'Gagal menyimpan ulasan']); }
    exit;
}

$out = ['avg'=>0,'count'=>0,'items'=>[]];
try {
    $q = $dbs->query("SELECT AVG(rating) a, COUNT(*) c FROM sonic_review WHERE biblio_id={$biblio_id}");
    if ($q && ($r = $q->fetch_assoc())) { $out['avg'] = round((float)$r['a'], 1); $out['count'] = (int)$r['c']; }
    $q = $dbs->query("SELECT r.rating, r.review, r.created_at, m.member_name FROM sonic_review r LEFT JOIN member m ON r.member_id=m.member_id WHERE r.biblio_id={$biblio_id} ORDER BY r.created_at DESC LIMIT 20");
    if ($q) while ($r = $q->fetch_assoc()) $out['items'][] = $r;
} catch (Throwable $e) {}
echo json_encode($out);