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
$sysconf['template']['theme'] = 'sonic';

/* ===== DEFAULTS (guarded: nilai dari Kustomisasi/DB selalu menang) ===== */
$sonic_defaults = [
    'sonic_neon_mode' => true,
    'sonic_particles' => true,
    'sonic_popular_collection' => 1,
    'sonic_new_collection' => 1,
    'sonic_top_reader' => 1,
    'sonic_map' => false,
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
    'sonic_extra_menus' => '',
    'sonic_extra_menu_label' => 'Lainnya',
];
foreach ($sonic_defaults as $k => $v) {
    if (!isset($sysconf['template'][$k])) $sysconf['template'][$k] = $v;
}

$sysconf['visitor_log_voice'] = 1;

/* ===== SLiMS CUSTOMIZER SCHEMA (wajib agar tombol Kustomisasi hidup) ===== */
$sysconf['template']['option']['sonic'] = [
    'subname'   => ['dbfield'=>'sonic_library_subname','label'=>'Library Subname','type'=>'text','default'=>'Hyperdrive Library System','width'=>80,'max'=>120],
    'about'     => ['dbfield'=>'sonic_footer_about_us','label'=>'Footer About Us','type'=>'longtext','default'=>'Perpustakaan digital dengan akses kecepatan cahaya.','width'=>90,'max'=>500],
    'email'     => ['dbfield'=>'sonic_contact_email','label'=>'Contact Email','type'=>'text','default'=>'library@example.com','width'=>60,'max'=>120],
    'fb'        => ['dbfield'=>'sonic_fb_link','label'=>'Facebook URL','type'=>'text','default'=>'','width'=>80,'max'=>250],
    'tw'        => ['dbfield'=>'sonic_twitter_link','label'=>'Twitter / X URL','type'=>'text','default'=>'','width'=>80,'max'=>250],
    'ig'        => ['dbfield'=>'sonic_instagram_link','label'=>'Instagram URL','type'=>'text','default'=>'','width'=>80,'max'=>250],
    'yt'        => ['dbfield'=>'sonic_youtube_link','label'=>'YouTube URL','type'=>'text','default'=>'','width'=>80,'max'=>250],
    'tg'        => ['dbfield'=>'sonic_telegram_link','label'=>'Telegram URL','type'=>'text','default'=>'','width'=>80,'max'=>250],
    'menus'     => ['dbfield'=>'sonic_extra_menus','label'=>'Extra Navbar Menus — separate with COMMA ( , ); optional Label|URL','type'=>'longtext','default'=>'','width'=>90,'max'=>2000],
    'menulabel' => ['dbfield'=>'sonic_extra_menu_label','label'=>'Extra Menu Dropdown Label','type'=>'text','default'=>'Lainnya','width'=>50,'max'=>40],
    'popular'   => ['dbfield'=>'sonic_popular_collection','label'=>'Show Popular Collection','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'newcoll'   => ['dbfield'=>'sonic_new_collection','label'=>'Show New Collection','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'reader'    => ['dbfield'=>'sonic_top_reader','label'=>'Show Top Reader','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'poplimit'  => ['dbfield'=>'sonic_popular_collection_limit','label'=>'Popular Limit','type'=>'numeric','default'=>10,'width'=>30,'max'=>3],
    'newlimit'  => ['dbfield'=>'sonic_new_collection_limit','label'=>'New Limit','type'=>'numeric','default'=>10,'width'=>30,'max'=>3],
    'map'      => ['dbfield'=>'sonic_map','label'=>'Show Library Map','type'=>'choice','default'=>1,'data'=>[['1','Yes'],['0','No']]],
    'maplink'  => ['dbfield'=>'sonic_map_link','label'=>'Map Link / Address (Google Maps)','type'=>'text','default'=>'','width'=>90,'max'=>300],
    'mapdesc'  => ['dbfield'=>'sonic_map_desc','label'=>'Map Description','type'=>'longtext','default'=>'','width'=>90,'max'=>300],
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