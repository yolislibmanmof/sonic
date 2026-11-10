<?php
/**
 * @Created  : SONIC Template
 * @File name: tinfo.inc.php
 */

$sysconf['index']['type'] = array(
    array('index', 'Index'),
    array('title', 'Title'),
    array('author', 'Author'),
    array('subject', 'Subject'),
    array('isbn', 'ISBN/ISSN'),
    array('location', 'Location'),
    array('colltype', 'Collection Type'),
    array('colltype', 'Collection Type'),
    array('gmd', 'GMD'),
    array('publishyear', 'Publish Year')
);

$sysconf['template']['base'] = 'php';
$sysconf['template']['theme'] = 'sonic';

// Konfigurasi kustom SONIC
$sysconf['template']['sonic_neon_mode'] = true;
$sysconf['template']['sonic_particles'] = true;

// Konfigurasi classic (di-rename jadi sonic agar compatible)
$sysconf['template']['sonic_popular_collection'] = true;
$sysconf['template']['sonic_new_collection'] = true;
$sysconf['template']['sonic_top_reader'] = true;
$sysconf['template']['sonic_map'] = false;
$sysconf['template']['sonic_map_link'] = '';
$sysconf['template']['sonic_map_desc'] = '';
$sysconf['template']['sonic_fb_link'] = '';
$sysconf['template']['sonic_twitter_link'] = '';
$sysconf['template']['sonic_youtube_link'] = '';
$sysconf['template']['sonic_instagram_link'] = '';
$sysconf['template']['sonic_footer_about_us'] = 'Perpustakaan digital dengan akses kecepatan cahaya.';
$sysconf['template']['sonic_library_subname'] = 'Hyperdrive Library System';
$sysconf['template']['sonic_popular_collection_limit'] = 10;
$sysconf['template']['sonic_new_collection_limit'] = 10;

$sysconf['visitor_log_voice'] = 1;

// Konfigurasi form fields
$sysconf['visitor_form']['name'] = [
    'dbfield' => 'member_name',
    'label' => _('Name'),
    'type' => 'text',
];
$sysconf['visitor_form']['institution'] = [
    'dbfield' => 'member_institution',
    'label' => _('Institution'),
    'type' => 'text',
];
$sysconf['visitor_form']['id'] = [
    'dbfield' => 'member_id',
    'label' => _('Member ID'),
    'type' => 'text',
];
$sysconf['visitor_form']['purpose'] = [
    'dbfield' => 'purpose',
    'label' => _('Visit Purpose'),
    'type' => 'dropdown',
    'default' => 'reading',
    'data' => [
        ['reading', _('Read Collection')],
        ['borrow', _('Borrow Collection')],
        ['return', _('Return Collection')],
        ['research', _('Research')],
        ['other', _('Other')]
    ]
];
$sysconf['visitor_form']['search'] = [
    'dbfield' => 'search',
    'label' => _('Search Collection'),
    'type' => 'text',
];