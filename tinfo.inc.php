<?php
/**
 * @Created  : SONIC Template
 * @File name: tinfo.inc.php (FINAL — customizable via SLiMS Theme panel)
 */

$sysconf['index']['type'] = array(
    array('index', 'Index'),
    array('title', 'Title'),
    array('author', 'Author'),
    array('subject', 'Subject'),
    array('isbn', 'ISBN/ISSN'),
    array('location', 'Location'),
    array('colltype', 'Collection Type'),
    array('gmd', 'GMD'),
    array('publishyear', 'Publish Year')
);

$sysconf['template']['base'] = 'php';
$sysconf['template']['theme'] = basename(__DIR__);

/* ===== DEFAULTS (guarded: nilai dari Kustomisasi/DB selalu menang) ===== */
$sonic_defaults = [
    'sonic_profile_vision' => '',
    'sonic_profile_mission' => '',
    'sonic_profile_year' => '',
    'sonic_profile_accreditation' => '',
    'sonic_welcome_text' => 'SELAMAT DATANG DI LAYANAN OPAC PERPUSTAKAAN',
    'sonic_particle' => 'books',
    'sonic_walker' => 'person',
    'sonic_popular_collection' => 1,
    'sonic_new_collection' => 1,
    'sonic_top_reader' => 1,
    'sonic_map' => 1,
    'sonic_map_link' => '',
    'sonic_map_desc' => '',
    'sonic_fb_link' => '',
    'sonic_twitter_link' => '',
    'sonic_youtube_link' => '',
    'sonic_instagram_link' => '',
    'sonic_telegram_link' => '',
    'sonic_contact_email' => 'library@example.com',
    'sonic_footer_about_us' => 'Perpustakaan digital dengan akses kecepatan cahaya.',
    'sonic_library_subname' => 'Hyperdrive Library System',
    'sonic_popular_collection_limit' => 10,
    'sonic_new_collection_limit' => 10,
    'sonic_trending_collection' => 1,
    'sonic_trending_collection_limit' => 10,
    'sonic_top_visitor' => 1,
    'sonic_top_visitor_limit' => 6,
    'sonic_extra_menus' => '',
    'sonic_extra_menu_label' => 'Lainnya',
    'sonic_instant_rich' => 1,
    'sonic_dym_fuzzy' => 1,
    'sonic_virtual_shelf' => 1,
    'sonic_shelf_limit' => 8,
    'sonic_coloan' => 1,
    'sonic_coloan_limit' => 8,
    'sonic_year_slider' => 1,
    'sonic_kiosk_qr' => 1,
    'sonic_member_card_qr' => 1,
    'sonic_fines' => 1,
    'sonic_reading_quest' => 1,
    'sonic_wish_reserve' => 1,
    'sonic_theme_mode' => 'auto',
    'sonic_a11y_toolbar' => 1,
    'sonic_public_stats' => 1,
    'sonic_api_rate' => 1,
];
foreach ($sonic_defaults as $k => $v) {
    if (!isset($sysconf['template'][$k])) $sysconf['template'][$k] = $v;
}

$sysconf['visitor_log_voice'] = 1;
$sysconf['template']['sonic_kiosk_idle'] = $sysconf['template']['sonic_kiosk_idle'] ?? 20;
$sysconf['template']['sonic_kiosk_quotes'] = $sysconf['template']['sonic_kiosk_quotes'] ?? '';

/* ===== SLiMS CUSTOMIZER SCHEMA (wajib agar tombol Kustomisasi hidup) ===== */
$sysconf['template']['option'][basename(__DIR__)] = [
    'pvision' => ['dbfield'=>'sonic_profile_vision','label'=>'Profile: Vision','type'=>'longtext','default'=>'','width'=>90,'max'=>600],
    'pmission'=> ['dbfield'=>'sonic_profile_mission','label'=>'Profile: Mission — separate each point with || or number them (1. 2. 3.)','type'=>'longtext','default'=>'','width'=>90,'max'=>1000],
    'pyear'   => ['dbfield'=>'sonic_profile_year','label'=>'Profile: Founding Year','type'=>'text','default'=>'','width'=>40,'max'=>20],
    'paccred' => ['dbfield'=>'sonic_profile_accreditation','label'=>'Profile: Accreditation Status','type'=>'text','default'=>'','width'=>60,'max'=>80],
    'subname'   => ['dbfield'=>'sonic_library_subname','label'=>'Library Subname','type'=>'text','default'=>'Hyperdrive Library System','width'=>80,'max'=>120],
    'about'     => ['dbfield'=>'sonic_footer_about_us','label'=>'Footer About Us','type'=>'longtext','default'=>'Perpustakaan digital dengan akses kecepatan cahaya.','width'=>90,'max'=>500],
    'welcome'  => ['dbfield'=>'sonic_welcome_text','label'=>'Hero Welcome Text (space-separated words)','type'=>'longtext','default'=>'SELAMAT DATANG DI LAYANAN OPAC PERPUSTAKAAN','width'=>90,'max'=>200],
    'particle' => ['dbfield'=>'sonic_particle','label'=>'Background Particle Style','type'=>'dropdown','default'=>'books','data'=>[['books','Floating Book Pages'],['stars','Twinkling Stars'],['bolts','Lightning Bolts'],['mixed','Mixed Cosmos'],['none','Disabled']]],
    'walker'   => ['dbfield'=>'sonic_walker','label'=>'Hero Walker Animation','type'=>'dropdown','default'=>'person','data'=>[['person','Neon Person'],['robot','Neon Robot'],['astronaut','Astronaut'],['none','Disabled']]],
    'email'     => ['dbfield'=>'sonic_contact_email','label'=>'Contact Email','type'=>'text','default'=>'library@example.com','width'=>60,'max'=>120],
    'fb'        => ['dbfield'=>'sonic_fb_link','label'=>'Facebook URL','type'=>'text','default'=>'','width'=>80,'max'=>250],
    'tw'        => ['dbfield'=>'sonic_twitter_link','label'=>'Twitter / X URL','type'=>'text','default'=>'','width'=>80,'max'=>250],
    'ig'        => ['dbfield'=>'sonic_instagram_link','label'=>'Instagram URL','type'=>'text','default'=>'','width'=>80,'max'=>250],
    'yt'        => ['dbfield'=>'sonic_youtube_link','label'=>'YouTube URL','type'=>'text','default'=>'','width'=>80,'max'=>250],
    'tg'        => ['dbfield'=>'sonic_telegram_link','label'=>'Telegram URL','type'=>'text','default'=>'','width'=>80,'max'=>250],
    'menus'     => ['dbfield'=>'sonic_extra_menus','label'=>'Extra Navbar Menus — separate with COMMA or NEWLINE; optional Label|URL','type'=>'longtext','default'=>'','width'=>90,'max'=>2000],
    'menulabel' => ['dbfield'=>'sonic_extra_menu_label','label'=>'Extra Menu Dropdown Label','type'=>'text','default'=>'Lainnya','width'=>50,'max'=>40],
    'popular'   => ['dbfield'=>'sonic_popular_collection','label'=>'Show Popular Collection','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'newcoll'   => ['dbfield'=>'sonic_new_collection','label'=>'Show New Collection','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'reader'    => ['dbfield'=>'sonic_top_reader','label'=>'Show Top Reader','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'poplimit'  => ['dbfield'=>'sonic_popular_collection_limit','label'=>'Popular Limit','type'=>'numeric','default'=>10,'width'=>30,'max'=>3],
    'newlimit'  => ['dbfield'=>'sonic_new_collection_limit','label'=>'New Limit','type'=>'numeric','default'=>10,'width'=>30,'max'=>3],
    'trending'  => ['dbfield'=>'sonic_trending_collection','label'=>'Show Trending 30 Days','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'trendlimit'=> ['dbfield'=>'sonic_trending_collection_limit','label'=>'Trending Limit','type'=>'numeric','default'=>10,'width'=>30,'max'=>3],
    'topvisitor'=> ['dbfield'=>'sonic_top_visitor','label'=>'Show Top Visitors','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'visitlimit'=> ['dbfield'=>'sonic_top_visitor_limit','label'=>'Top Visitors Limit','type'=>'numeric','default'=>6,'width'=>30,'max'=>3],
    'map'      => ['dbfield'=>'sonic_map','label'=>'Show Library Map','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'maplink'  => ['dbfield'=>'sonic_map_link','label'=>'Map Link / Address (Google Maps)','type'=>'text','default'=>'','width'=>90,'max'=>300],
    'mapdesc'  => ['dbfield'=>'sonic_map_desc','label'=>'Map Description','type'=>'longtext','default'=>'','width'=>90,'max'=>300],
    'kidle'   => ['dbfield'=>'sonic_kiosk_idle','label'=>'Kiosk: Idle reset (seconds)','type'=>'numeric','default'=>20,'width'=>30,'max'=>3],
    'kquotes' => ['dbfield'=>'sonic_kiosk_quotes','label'=>'Kiosk: Quotes — separate with ||','type'=>'longtext','default'=>'','width'=>90,'max'=>1000],
    'instant'   => ['dbfield'=>'sonic_instant_rich','label'=>'Discovery: Instant Search Rich (cover+pengarang di dropdown)','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'dymfuzzy'  => ['dbfield'=>'sonic_dym_fuzzy','label'=>'Discovery: Did-you-mean Fuzzy (index_words)','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'vshelf'    => ['dbfield'=>'sonic_virtual_shelf','label'=>'Discovery: Virtual Shelf (tetangga rak)','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'shelflimit'=> ['dbfield'=>'sonic_shelf_limit','label'=>'Virtual Shelf Limit (per sisi)','type'=>'numeric','default'=>8,'width'=>30,'max'=>2],
    'coloan'    => ['dbfield'=>'sonic_coloan','label'=>'Discovery: Co-loan (peminjam juga meminjam)','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'coloanlimit'=> ['dbfield'=>'sonic_coloan_limit','label'=>'Co-loan Limit','type'=>'numeric','default'=>8,'width'=>30,'max'=>2],
    'yearslider'=> ['dbfield'=>'sonic_year_slider','label'=>'Discovery: Year Range Slider','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'kioskqr'   => ['dbfield'=>'sonic_kiosk_qr','label'=>'Kiosk: Scan QR Kartu Anggota (html5-qrcode)','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'cardqr'    => ['dbfield'=>'sonic_member_card_qr','label'=>'Member: Kartu Digital ber-QR','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'fines'     => ['dbfield'=>'sonic_fines','label'=>'Member: Denda & Jatuh Tempo Real-time','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'quest'     => ['dbfield'=>'sonic_reading_quest','label'=>'Member: Reading Quest (streak & target tahunan)','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'wishreserve'=> ['dbfield'=>'sonic_wish_reserve','label'=>'Member: Wishlist → Reservasi 1 Klik','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'thememode' => ['dbfield'=>'sonic_theme_mode','label'=>'Platform: Default theme mode','type'=>'dropdown','default'=>'auto','data'=>[['auto','Auto (follow OS)'],['dark','Dark'],['light','Light']]],
    'a11y'      => ['dbfield'=>'sonic_a11y_toolbar','label'=>'Platform: Accessibility toolbar (theme + font)','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'pubstats'  => ['dbfield'=>'sonic_public_stats','label'=>'Platform: Public statistics page (?stats=1)','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'apirate'   => ['dbfield'=>'sonic_api_rate','label'=>'Platform: API rate-limit','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
];

/* ===== Visitor form (tetap) ===== */
$sysconf['visitor_form']['name'] = ['dbfield'=>'member_name','label'=>_('Name'),'type'=>'text'];
$sysconf['visitor_form']['institution'] = ['dbfield'=>'member_institution','label'=>_('Institution'),'type'=>'text'];
$sysconf['visitor_form']['id'] = ['dbfield'=>'member_id','label'=>_('Member ID'),'type'=>'text'];
$sysconf['visitor_form']['purpose'] = [
    'dbfield'=>'purpose','label'=>_('Visit Purpose'),'type'=>'dropdown','default'=>'reading',
    'data'=>[['reading',_('Read Collection')],['borrow',_('Borrow Collection')],['return',_('Return Collection')],['research',_('Research')],['other',_('Other')]]
];
$sysconf['visitor_form']['search'] = ['dbfield'=>'search','label'=>_('Search Collection'),'type'=>'text'];