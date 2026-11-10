<?php
# @Filename: _navbar.php (SONIC v2)

$main_menus = [
    'home'      => ['text' => __('Home'),       'url' => 'index.php',            'icon' => 'fas fa-home'],
    'libinfo'   => ['text' => __('Information'),'url' => 'index.php?p=libinfo',  'icon' => 'fas fa-info-circle'],
    'news'      => ['text' => __('News'),       'url' => 'index.php?p=news',     'icon' => 'fas fa-newspaper'],
    'help'      => ['text' => __('Help'),       'url' => 'index.php?p=help',     'icon' => 'fas fa-question-circle'],
    'librarian' => ['text' => __('Librarian'),  'url' => 'index.php?p=librarian','icon' => 'fas fa-user-shield']
];
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
                <h1><?php echo $sysconf['library_name']; ?></h1>
                <?php if (!empty($sysconf['template']['sonic_library_subname'])) : ?>
                    <h2><?php echo $sysconf['library_subname']; ?></h2>
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
            <li class="<?= $active ?>">
                <a href="<?= $main_menu['url'] ?>"><i class="<?= $main_menu['icon'] ?>"></i><?= $main_menu['text'] ?></a>
            </li>
            <?php endforeach; ?>

            <?php $menu_member_active = isset($_GET['p']) && $_GET['p'] === 'member' ? 'active' : ''; ?>

            <?php if ($is_login): ?>
                <li class="<?= $menu_member_active ?>">
                    <a href="index.php?p=member&sec=title_basket" title="<?= __('Basket') ?>">
                        <i class="fas fa-shopping-basket"></i>
                        <span class="sonic-badge-count"><?= count($_SESSION['m_mark_biblio'] ?? []) ?></span>
                    </a>
                </li>
                <li class="<?= $menu_member_active ?>">
                    <a href="index.php?p=member" class="sonic-user-btn">
                        <i class="fas fa-user-circle"></i> <?= $_SESSION['m_name'] ?>
                    </a>
                </li>
                <li>
                    <a href="index.php?p=member&logout=1" title="<?= __('Logout') ?>">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </li>
            <?php else: ?>
                <li class="<?= $menu_member_active ?>">
                    <a href="index.php?p=member" class="sonic-user-btn">
                        <i class="fas fa-user-astronaut"></i> <?= __('Member Area') ?>
                    </a>
                </li>
            <?php endif; ?>

                       <?php
            // Language selector (fungsi bawaan OPAC)
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
            <li class="dropdown">
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