<?php
# @Filename: _navbar.php (SONIC — FINAL: navbar lega, Informasi+Berita+Statistik di dropdown Lainnya)

/* Menu utama dipangkas agar navbar lega (Informasi & Berita pindah ke "Lainnya") */
$main_menus = [
    'home'      => ['text' => __('Home'),        'url' => 'index.php',                      'icon' => 'fas fa-home',        'p' => ''],
    'profil'    => ['text' => sonic_t('Library Profile'), 'url' => 'index.php?p=libinfo&sec=profil', 'icon' => 'fas fa-landmark', 'p' => 'libinfo', 'sec' => 'profil'],
    'librarian' => ['text' => __('Librarian'),   'url' => 'index.php?p=librarian',          'icon' => 'fas fa-user-shield', 'p' => 'librarian'],
];

$cur_p = $_GET['p'] ?? '';
$cur_s = $_GET['sec'] ?? '';

/* ===== Extra menus (dropdown "Lainnya") ===== */
$sonic_extra_menus = [];
$_sonic_icon_map = [
    'services'=>'fas fa-concierge-bell','journals'=>'fas fa-book-open','libinfo'=>'fas fa-info-circle',
    'news'=>'fas fa-newspaper','help'=>'fas fa-question-circle','librarian'=>'fas fa-user-shield',
    'member'=>'fas fa-user-astronaut','visitor'=>'fas fa-clipboard-user','stats'=>'fas fa-chart-line',
];
/* Informasi & Berita masuk dropdown (sec '' = halaman libinfo umum, bukan profil) */
$sonic_extra_menus[] = ['text' => __('Information'), 'url' => 'index.php?p=libinfo', 'icon' => 'fas fa-info-circle', 'p' => 'libinfo', 'sec' => ''];
$sonic_extra_menus[] = ['text' => __('News'),        'url' => 'index.php?p=news',    'icon' => 'fas fa-newspaper',   'p' => 'news'];

foreach (preg_split('/\r\n|\r|\n|,/', (string)($sysconf['template']['sonic_extra_menus'] ?? '')) as $_line) {
    $_line = trim($_line);
    if ($_line === '') continue;
    if (strpos($_line, '|') !== false) { list($_lb, $_url) = array_map('trim', explode('|', $_line, 2)); }
    else { $_url = $_line; $_lb = ''; }
    if ($_url === '') continue;
    if (function_exists('sonic_http_url')) { $_url = sonic_http_url($_url); if ($_url === '') continue; }
    $_q = parse_url($_url, PHP_URL_QUERY); $_p = '';
    if ($_q) { parse_str($_q, $_qq); $_p = $_qq['p'] ?? ''; }
    if ($_lb === '') {
        $_lb = ($_p !== '') ? ucwords(str_replace(['_','-'], ' ', $_p))
                            : ucwords(str_replace(['_','-'], ' ', basename(parse_url($_url, PHP_URL_PATH) ?: 'Menu')));
        if ($_lb === '' || stripos($_lb, 'index.php') === 0) $_lb = 'Menu';
    }
    $sonic_extra_menus[] = ['text'=>$_lb, 'url'=>$_url, 'icon'=>$_sonic_icon_map[$_p] ?? 'fas fa-link', 'p'=>$_p];
}
$sonic_extra_menus[] = ['text' => __('Help'), 'url' => 'index.php?p=help', 'icon' => 'fas fa-question-circle', 'p' => 'help'];
if (!empty($sonic_stats_on)) {
    $sonic_extra_menus[] = ['text' => sonic_t('Statistics'), 'url' => 'index.php?stats=1', 'icon' => 'fas fa-chart-line', 'p' => 'stats', 'is_stats' => true];
}

/* Deteksi aktif untuk item dropdown (dukung sec & stats) */
$sonic_extra_active = function ($em) use ($cur_p, $cur_s) {
    if (!empty($em['is_stats'])) return isset($_GET['stats']);
    if (($em['p'] ?? '') === '') return false;
    if ($cur_p !== $em['p']) return false;
    if (array_key_exists('sec', $em)) return $cur_s === $em['sec'];
    return true;
};
$sonic_group_label = $sysconf['template']['sonic_extra_menu_label'] ?? __('More');
$sonic_group_active = '';
foreach ($sonic_extra_menus as $em) { if ($sonic_extra_active($em)) { $sonic_group_active = 'active'; break; } }
?>
<div class="sonic-progress" id="sonicProgress"></div>

<nav class="sonic-navbar" id="sonicNavbar">
    <div class="sonic-navbar-inner">
        <a class="sonic-brand" href="index.php">
            <?php
            if(isset($sysconf['logo_image']) && $sysconf['logo_image'] != '' && $imagesDisk->isExists($path = 'default/'.$sysconf['logo_image'])){
                echo '<img src="'.SWB . 'lib/minigalnano/createthumb.php?filename=images/' . $path.'&width=350" alt="logo">';
            } elseif (file_exists(__DIR__ . '/../assets/images/logo-big.png')) {
                echo '<img src="'.assets('images/logo-big.png').'" alt="logo">';
            }
            ?>
            <div class="sonic-brand-text">
                <h1 title="<?php echo htmlspecialchars($sysconf['library_name']); ?>"><?php echo htmlspecialchars($sysconf['library_name'], ENT_QUOTES); ?></h1>
                <?php if (!empty($sysconf['template']['sonic_library_subname'])) : ?>
                <h2 title="<?php echo htmlspecialchars($sysconf['template']['sonic_library_subname']); ?>"><?php echo htmlspecialchars($sysconf['template']['sonic_library_subname'], ENT_QUOTES); ?></h2>
                <?php endif; ?>
            </div>
        </a>

        <button class="sonic-burger" id="sonicBurger" aria-label="Menu"><i class="fas fa-bars"></i></button>

        <ul class="sonic-nav" id="sonicNav">
            <?php foreach ($main_menus as $key => $main_menu):
                $mp = $main_menu['p'] ?? $key;
                $ms = $main_menu['sec'] ?? null;
                if ($key === 'home')            $active = ($cur_p === '');
                elseif ($ms === null)           $active = ($cur_p === $mp);
                else                            $active = ($cur_p === $mp && $cur_s === $ms);
            ?>
            <li class="<?= $active ? 'active' : '' ?> sonic-nav-item">
                <a href="<?= $main_menu['url'] ?>" title="<?= htmlspecialchars($main_menu['text']) ?>"><i class="<?= $main_menu['icon'] ?>"></i><span class="sonic-nav-label"><?= $main_menu['text'] ?></span></a>
            </li>
            <?php endforeach; ?>

            <?php if (count($sonic_extra_menus) > 0): ?>
            <li class="dropdown sonic-nav-group <?= $sonic_group_active ?>">
                <a class="nav-link dropdown-toggle" href="#" id="sonicExtraMenuBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="<?= htmlspecialchars($sonic_group_label) ?>">
                <i class="fas fa-layer-group"></i><span class="sonic-nav-label"><?= htmlspecialchars(sonic_t($sonic_group_label)) ?></span><i class="fas fa-chevron-down sonic-caret"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right sonic-group-menu" aria-labelledby="sonicExtraMenuBtn">
                    <?php foreach ($sonic_extra_menus as $em): $em_active = $sonic_extra_active($em) ? 'active' : ''; ?>
                    <a class="dropdown-item <?= $em_active ?>" href="<?= htmlspecialchars($em['url']) ?>" title="<?= htmlspecialchars($em['text']) ?>">
                        <i class="<?= $em['icon'] ?>"></i><span class="sonic-nav-label"><?= htmlspecialchars($em['text']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </li>
            <?php endif; ?>

            <?php $menu_member_active = isset($_GET['p']) && $_GET['p'] === 'member' ? 'active' : ''; ?>

            <?php if ($is_login): ?>
                <li class="<?= $menu_member_active ?> sonic-nav-protected">
                    <a href="index.php?p=member&sec=title_basket" title="<?= __('Basket') ?>">
                        <i class="fas fa-shopping-basket"></i>
                        <span class="sonic-badge-count" id="count-basket"><?= count($_SESSION['m_mark_biblio'] ?? []) ?></span>
                    </a>
                </li>
                <li class="<?= $menu_member_active ?> sonic-nav-protected">
                    <a href="index.php?p=member" class="sonic-user-btn" title="<?= htmlspecialchars($_SESSION['m_name'] ?? '') ?>">
                        <i class="fas fa-user-circle"></i> <span class="sonic-nav-label"><?= htmlspecialchars($_SESSION['m_name'] ?? '') ?></span>
                    </a>
                </li>
                <li class="sonic-nav-protected">
                    <a href="index.php?p=member&logout=1" title="<?= __('Logout') ?>"><i class="fas fa-sign-out-alt"></i></a>
                </li>
            <?php else: ?>
                <li class="<?= $menu_member_active ?> sonic-nav-protected">
                    <a href="index.php?p=member" class="sonic-user-btn">
                        <i class="fas fa-user-astronaut"></i> <span class="sonic-nav-label"><?= __('Member Area') ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (!empty($sonic_a11y_on)): ?>
            <li class="sonic-nav-protected sonic-a11y-bar">
                <button type="button" class="sonic-a11y-btn" id="sonicThemeBtn" aria-label="<?= __('Toggle color theme') ?>" title="<?= __('Theme: auto/dark/light') ?>"><i class="fas fa-adjust"></i></button>
                <button type="button" class="sonic-a11y-btn" id="sonicFontMinus" aria-label="<?= __('Decrease font size') ?>">A−</button>
                <button type="button" class="sonic-a11y-btn" id="sonicFontReset" aria-label="<?= __('Reset font size') ?>">A</button>
                <button type="button" class="sonic-a11y-btn" id="sonicFontPlus" aria-label="<?= __('Increase font size') ?>">A+</button>
            </li>
            <?php endif; ?>

            <?php
            $langstr = '';
            $current_lang = ['name' => 'EN', 'code' => 'en'];
            $select_lang = $_COOKIE['select_lang'] ?? $sysconf['default_lang'];
            $sonic_raw_qs = filter_input(INPUT_SERVER, 'QUERY_STRING', FILTER_UNSAFE_RAW);
            if (!is_string($sonic_raw_qs) || $sonic_raw_qs === '') $sonic_raw_qs = stripslashes((string)($_SERVER['QUERY_STRING'] ?? ''));
            $sonic_lang_qs = [];
            if ($sonic_raw_qs !== '') parse_str($sonic_raw_qs, $sonic_lang_qs);
            unset($sonic_lang_qs['select_lang']);
            $sonic_lang_prefix = 'index.php?' . (empty($sonic_lang_qs) ? '' : http_build_query($sonic_lang_qs) . '&');
            foreach ($available_languages ?? [] as $lang_index) {
                $lang_code = $lang_index[0];
                $lang_name = $lang_index[1];
                $code_arr = explode('_', $lang_code);
                $code_flag = strtolower($code_arr[1] ?? 'en');
                if ($lang_code == $select_lang) $current_lang = ['name' => $lang_name, 'code' => $code_flag];
                $langstr .= '<a class="dropdown-item" href="' . htmlspecialchars($sonic_lang_prefix . 'select_lang=' . urlencode($lang_code), ENT_QUOTES) . '"><span class="flag-icon flag-icon-' . htmlspecialchars($code_flag, ENT_QUOTES) . '" style="border-radius:2px;margin-right:.4rem;"></span>' . htmlspecialchars($lang_name, ENT_QUOTES) . '</a>';
            }
            ?>
            <li class="dropdown sonic-nav-protected">
                <a class="nav-link dropdown-toggle" href="#" id="languageMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color:var(--sonic-text);">
                    <i class="fas fa-globe" style="color:var(--sonic-neon);"></i>
                    <span class="flag-icon flag-icon-<?= htmlspecialchars($current_lang['code'], ENT_QUOTES) ?>" style="border-radius:2px;"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="languageMenuButton">
                    <h6 class="dropdown-header"><?= __('Select Language') ?>:</h6>
                    <?= $langstr ?>
                </div>
            </li>
        </ul>
    </div>
</nav>