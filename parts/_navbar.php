<?php
# @Filename: _navbar.php (SONIC v5 — extra menus grouped into dropdown)

$main_menus = [
    'home'      => ['text' => __('Home'),       'url' => 'index.php',            'icon' => 'fas fa-home'],
    'libinfo'   => ['text' => __('Information'),'url' => 'index.php?p=libinfo',  'icon' => 'fas fa-info-circle'],
    'news'      => ['text' => __('News'),       'url' => 'index.php?p=news',     'icon' => 'fas fa-newspaper'],
    'help'      => ['text' => __('Help'),       'url' => 'index.php?p=help',     'icon' => 'fas fa-question-circle'],
    'librarian' => ['text' => __('Librarian'),  'url' => 'index.php?p=librarian','icon' => 'fas fa-user-shield']
];

/* ===== Extra menus dari Kustomisasi ===== */
$sonic_extra_menus = [];
$_sonic_icon_map = [
    'services'=>'fas fa-concierge-bell','journals'=>'fas fa-book-open','libinfo'=>'fas fa-info-circle',
    'news'=>'fas fa-newspaper','help'=>'fas fa-question-circle','librarian'=>'fas fa-user-shield',
    'member'=>'fas fa-user-astronaut','visitor'=>'fas fa-clipboard-user',
];
$_sonic_raw_menus = (string)($sysconf['template']['sonic_extra_menus'] ?? '');
/* Recovery: core SLiMS menghapus newline → pisahkan kembali sebelum tiap index.php? yang menempel */
$_sonic_raw_menus = preg_replace('/(?<![|,\s;])(index\.php\?)/i', ',$1', $_sonic_raw_menus);
$_sonic_raw_menus = ltrim($_sonic_raw_menus, ",; \t");
foreach (preg_split('/[,;\r\n]+/', $_sonic_raw_menus) as $_line) {
    $_line = trim($_line);
    if ($_line === '') continue;
    if (strpos($_line, '|') !== false) { list($_lb, $_url) = array_map('trim', explode('|', $_line, 2)); }
    else { $_url = $_line; $_lb = ''; }
    if ($_url === '') continue;
    $_q = parse_url($_url, PHP_URL_QUERY); $_p = '';
    if ($_q) { parse_str($_q, $_qq); $_p = $_qq['p'] ?? ''; }
    if ($_lb === '') {
        $_lb = ($_p !== '') ? ucwords(str_replace(['_','-'], ' ', $_p))
                            : ucwords(str_replace(['_','-'], ' ', basename(parse_url($_url, PHP_URL_PATH) ?: 'Menu')));
        if ($_lb === '' || stripos($_lb, 'index.php') === 0) $_lb = 'Menu';
    }
    $sonic_extra_menus[] = ['text'=>$_lb, 'url'=>$_url, 'icon'=>$_sonic_icon_map[$_p] ?? 'fas fa-link', 'p'=>$_p];
}
$sonic_group_label = $sysconf['template']['sonic_extra_menu_label'] ?? __('More');
$sonic_group_active = '';
foreach ($sonic_extra_menus as $em) {
    if (isset($_GET['p']) && $em['p'] !== '' && $em['p'] === $_GET['p']) { $sonic_group_active = 'active'; break; }
}
?>
<div class="sonic-progress" id="sonicProgress"></div>

<nav class="sonic-navbar" id="sonicNavbar">
    <div class="sonic-navbar-inner">
        <a class="sonic-brand" href="index.php">
            <span class="sonic-brand-badge"><i class="fas fa-bolt"></i></span>
            <?php
            if(isset($sysconf['logo_image']) && $sysconf['logo_image'] != '' && $imagesDisk->isExists($path = 'default/'.$sysconf['logo_image'])){
                echo '<img src="'.SWB . 'lib/minigalnano/createthumb.php?filename=images/' . $path.'&width=350" alt="logo">';
            } elseif (file_exists(__DIR__ . '/../assets/images/logo.png')) {
                echo '<img src="'.assets('images/logo.png').'" alt="logo">';
            }
            ?>
            <div class="sonic-brand-text">
                <h1 title="<?php echo htmlspecialchars($sysconf['library_name']); ?>"><?php echo $sysconf['library_name']; ?></h1>
                <?php if (!empty($sysconf['template']['sonic_library_subname'])) : ?>
                    <h2 title="<?php echo htmlspecialchars($sysconf['template']['sonic_library_subname']); ?>"><?php echo $sysconf['template']['sonic_library_subname']; ?></h2>
                <?php endif; ?>
            </div>
        </a>

        <button class="sonic-burger" id="sonicBurger" aria-label="Menu"><i class="fas fa-bars"></i></button>

        <ul class="sonic-nav" id="sonicNav">
            <?php foreach ($main_menus as $key => $main_menu):
                $active = '';
                if (isset($_GET['p']) && $key === $_GET['p']) $active = 'active';
                elseif (!isset($_GET['p']) && $key === 'home') $active = 'active';
            ?>
            <li class="<?= $active ?> sonic-nav-item">
                <a href="<?= $main_menu['url'] ?>" title="<?= htmlspecialchars($main_menu['text']) ?>"><i class="<?= $main_menu['icon'] ?>"></i><span class="sonic-nav-label"><?= $main_menu['text'] ?></span></a>
            </li>
            <?php endforeach; ?>

            <?php if (count($sonic_extra_menus) > 0): ?>
            <li class="dropdown sonic-nav-group <?= $sonic_group_active ?>">
                <a class="nav-link dropdown-toggle" href="#" id="sonicExtraMenuBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="<?= htmlspecialchars($sonic_group_label) ?>">
                    <i class="fas fa-layer-group"></i><span class="sonic-nav-label"><?= htmlspecialchars($sonic_group_label) ?></span><i class="fas fa-chevron-down sonic-caret"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right sonic-group-menu" aria-labelledby="sonicExtraMenuBtn">
                    <?php foreach ($sonic_extra_menus as $em):
                        $em_active = (isset($_GET['p']) && $em['p'] !== '' && $em['p'] === $_GET['p']) ? 'active' : '';
                    ?>
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
                        <span class="sonic-badge-count"><?= count($_SESSION['m_mark_biblio'] ?? []) ?></span>
                    </a>
                </li>
                <li class="<?= $menu_member_active ?> sonic-nav-protected">
                    <a href="index.php?p=member" class="sonic-user-btn" title="<?= htmlspecialchars($_SESSION['m_name']) ?>">
                        <i class="fas fa-user-circle"></i> <span class="sonic-nav-label"><?= $_SESSION['m_name'] ?></span>
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

            <?php
            $langstr = '';
            $current_lang = ['name' => 'EN', 'code' => 'en'];
            $select_lang = $_COOKIE['select_lang'] ?? $sysconf['default_lang'];
            foreach ($available_languages ?? [] as $lang_index) {
                $lang_code = $lang_index[0];
                $lang_name = $lang_index[1];
                $code_arr = explode('_', $lang_code);
                $code_flag = strtolower($code_arr[1] ?? 'en');
                if ($lang_code == $select_lang) $current_lang = ['name' => $lang_name, 'code' => $code_flag];
                $langstr .= '<a class="dropdown-item" href="index.php?select_lang=' . $lang_code . '"><span class="flag-icon flag-icon-' . $code_flag . '" style="border-radius:2px;margin-right:.4rem;"></span>' . $lang_name . '</a>';
            }
            ?>
            <li class="dropdown sonic-nav-protected">
                <a class="nav-link dropdown-toggle" href="#" id="languageMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color:var(--sonic-text);">
                    <i class="fas fa-globe" style="color:var(--sonic-neon);"></i>
                    <span class="flag-icon flag-icon-<?= $current_lang['code'] ?>" style="border-radius:2px;"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="languageMenuButton">
                    <h6 class="dropdown-header"><?= __('Select Language') ?>:</h6>
                    <?= $langstr ?>
                </div>
            </li>
        </ul>
    </div>
</nav>