<?php
/**
 * SONIC SECURITY HELPERS (BATCH 2) — sumber tunggal, load-order safe.
 * Di-require paling awal oleh biblio_list_template.php, detail_template.php,
 * visitor_template.php. classic.php (Batch 1) memakai guard yang sama sehingga
 * tidak ada redeclare — versi kuat selalu menang.
 */
if (!defined('INDEX_AUTH')) { die("can not access this file directly"); }

if (!function_exists('sonic_cache')) {
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
    function sonic_host() {
        $h = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        return preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $h);
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