<?php
/**
 * Template for Biblio List (SONIC Edition — FASE 1: escaping + bookmark DB + share benar)
 */
use SLiMS\Url;

$label_cache = array();

function biblio_list_format($dbs, $biblio_detail, $n, $settings = array(), &$return_back = array()) {
    global $label_cache, $sysconf;

    $output = '';
    $title = $biblio_detail['title'];
    $title_esc = htmlspecialchars(stripslashes($title), ENT_QUOTES);
    $biblio_id = (int)$biblio_detail['biblio_id'];
    $detail_url = SWB.'index.php?p=show_detail&id='.$biblio_id.'&keywords='.urlencode($settings['keywords'] ?? '');
    $cite_url = SWB.'index.php?p=cite&id='.$biblio_id.'&keywords='.urlencode($settings['keywords'] ?? '');

    $images_loc = 'images/docs/'.$biblio_detail['image'];
    if ($biblio_detail['image'] == '' || $biblio_detail['image'] == NULL) { $images_loc = 'images/default/image.png'; }
    $thumb_url = './lib/minigalnano/createthumb.php?filename='.urlencode($images_loc).'&width=240';

    $notes = getNotes($dbs, $biblio_id);
    $custom_field = '';
    $i = 0;
    $expand = true;

    if ($settings['enable_custom_frontpage'] AND $settings['custom_fields']) {
        $custom_field = '<dl>';
        foreach ($settings['custom_fields'] as $field => $field_opts) {
            if ($field_opts[0] == 1) {
                $field_value = (trim($biblio_detail[$field] ?? '') !== '' ? htmlspecialchars($biblio_detail[$field]) : '-');
                $custom_field .= '<dt style="color: var(--sonic-muted); font-size: 0.8rem;">'.$field_opts[1].'</dt><dd>'.$field_value.'</dd>';
                $i++;
            }
        }
        $custom_field .= '</dl>';
    }

    if (empty($notes)) { $notes = $custom_field; $expand = false; }

    $availability = getAvailability($dbs, $biblio_id, $sysconf);
    $class_avail = ($availability > 0) ? '' : 'unavailable';

    $_authors = isset($biblio_detail['author']) ? $biblio_detail['author'] : biblio_list_model::getAuthors($dbs, $biblio_id, true);
    $_authors_string = '';
    if ($_authors) {
        if (!is_array($_authors)) $_authors = explode('-', $_authors);
        foreach ($_authors as $a) {
            $a = trim($a);
            $_authors_string .= '<a href="index.php?author='.urlencode($a).'&search=Search" class="sonic-tag" style="padding: 0.2rem 0.6rem; font-size: 0.75rem;">'.htmlspecialchars($a).'</a>';
        }
    }

    if (($_POST['view'] ?? $_SESSION['LIST_VIEW'] ?? 'list') === 'list'):
        $output .= '<div class="sonic-list-card">';
        $output .= '<div class="sonic-list-cover"><img loading="lazy" src="'.$thumb_url.'" alt="cover"></div>';
        $output .= '<div class="sonic-list-content">';
        $output .= '<h4 class="sonic-list-title"><a href="'.$detail_url.'">'.addEllipsis($title_esc, 80).'</a></h4>';
        $output .= createButton($biblio_id, $title);
        $output .= '<div class="sonic-list-meta"><span><i class="far fa-calendar-alt"></i>'.htmlspecialchars($biblio_detail['publish_year'] ?? '—').'</span><span><i class="fas fa-map-marker-alt"></i>'.htmlspecialchars($biblio_detail['call_number'] ?? '—').'</span><span><i class="fas fa-box"></i><span style="color:'.($availability > 0 ? 'var(--sonic-success)' : 'var(--sonic-danger)').';">'.($availability > 0 ? $availability.' '.__('available') : __('Unavailable')).'</span></span></div>';
        $output .= '<p class="sonic-list-notes">'.$notes.'</p>';
        if ($i > 0 && $expand) {
            $output .= '<div id="expand-'.$biblio_id.'" class="collapse py-2">'.$custom_field.'</div>';
            $output .= '<a class="sonic-mini-btn" data-toggle="collapse" href="#expand-'.$biblio_id.'">⬇️ '.__('Show more').'</a>';
        }
        $output .= '</div>';

        $output .= '<div class="sonic-list-actions">';
        $output .= '<div style="text-align: center; padding: 0.8rem; background: rgba(0,240,255,0.1); border-radius: 10px; border: 1px solid var(--glass-border);">';
        $output .= '<div style="color: var(--sonic-muted); font-size: 0.7rem; text-transform: uppercase;">'.__('Availability').'</div>';
        $output .= '<div style="font-size: 1.5rem; font-weight: 900; color: '.($availability > 0 ? 'var(--sonic-success)' : 'var(--sonic-danger)').';">'.$availability.'</div>';
        $output .= '</div>';
        $output .= '<a class="sonic-mini-btn add-to-chart-button" data-biblio="'.$biblio_id.'" href="#" style="justify-content: center; padding: 0.5rem;">➕ '.__('Add to basket').'</a>';
        $output .= '<a class="sonic-mini-btn sonic-marc-trigger" href="'.$detail_url.'&MARC=true" style="justify-content: center;">💾 MARC</a>';
        $output .= '<a class="sonic-mini-btn openPopUp citationLink" href="'.$cite_url.'" style="justify-content: center;">📋 '.__('Cite').'</a>';
        $output .= '<a class="sonic-mini-btn" href="'.$detail_url.'" style="justify-content: center; background: var(--sonic-neon); color: #000; border-color: var(--sonic-neon);">⚡ '.__('View Detail').'</a>';
        $output .= '</div>';
        $output .= '</div>';
    else:
        $output .= '<a href="'.$detail_url.'" class="sonic-book">';
        $output .= '<div class="sonic-book-cover">';
        $output .= '<img loading="lazy" src="'.$thumb_url.'" alt="cover">';
        $output .= '<span class="sonic-avail-badge '.$class_avail.'">'.($availability > 0 ? $availability.' '.__('available') : __('Unavailable')).'</span>';
        $output .= '</div>';
        $output .= '<h4 class="sonic-book-title">'.addEllipsis($title_esc, 60).'</h4>';
        $output .= '<p class="sonic-book-year"><i class="far fa-calendar-alt"></i>'.htmlspecialchars($biblio_detail['publish_year'] ?? '—').' &middot; '.htmlspecialchars($biblio_detail['call_number'] ?? '—').'</p>';
        $output .= '<div class="sonic-book-actions"><span class="sonic-mini-btn" style="pointer-events: none;">⚡ '.__('View').'</span></div>';
        $output .= '</a>';
    endif;

    return $output;
}

function getNotes($dbs, $biblio_id) {
    $query = $dbs->query('SELECT notes FROM biblio WHERE biblio_id = ' . (int)$biblio_id);
    $data = $query->fetch_row();
    return sonic_kses(addEllipsis($data[0] ?? '', 400));//
}

function addEllipsis($string, $length, $end = '…') {
    $string = (string)$string;
    if (function_exists('mb_strlen')) {
        if (mb_strlen($string) > $length) { return mb_substr($string, 0, $length - mb_strlen($end)) . $end; }
        return $string;
    }
    if (strlen($string) > $length) { return substr($string, 0, $length - strlen($end)) . $end; }
    return $string;
}

function getAvailability($dbs, $biblio_id, $sysconf) {
    $biblio_id = (int)$biblio_id;
    // Satu query (dua subquery) — kurangi round-trip N+1.
    $q = $dbs->query("SELECT (SELECT COUNT(*) FROM item WHERE biblio_id={$biblio_id}) - (SELECT COUNT(*) FROM loan AS l INNER JOIN item AS i ON l.item_code=i.item_code WHERE l.is_lent=1 AND l.is_return=0 AND i.biblio_id={$biblio_id}) AS avail");
    if ($q && ($r = $q->fetch_row())) return max(0, (int)$r[0]);
    return 0;
}

function createButton(int $biblio_id, string $title) {
    $commentUrlCondition = (utility::isMemberLogin() ?
        Url::getSlimsBaseUri('?p=show_detail&id=' . $biblio_id . '#comment') :
        Url::getSlimsBaseUri('?p=member&destination=' . Url::getSlimsBaseUri('?p=show_detail&id=' . $biblio_id . '#comment')->encode()));

    $bookmarked = function_exists('sonic_is_bookmarked') ? sonic_is_bookmarked($biblio_id) : isset($_SESSION['bookmark'][$biblio_id]);
    $comment = __('Comment');
    $bookmark = $bookmarked ? __('Bookmarked') : __('Bookmark');
    $share = __('Share');
    $setBookmarked = $bookmarked ? 'active' : '';
    $title_attr = htmlspecialchars($title, ENT_QUOTES);
    $label_on = htmlspecialchars(__('Bookmarked'), ENT_QUOTES);
    $label_off = htmlspecialchars(__('Bookmark'), ENT_QUOTES);

    // PENTING: tanpa data-toggle="modal" agar tidak bentrok Bootstrap (pakai handler jQuery SONIC).
    return <<<HTML
    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; margin: 0.5rem 0;">
        <a href="{$commentUrlCondition}" class="sonic-mini-btn">💬 {$comment}</a>
        <a href="javascript:void(0)" data-id="{$biblio_id}" data-label-on="{$label_on}" data-label-off="{$label_off}" class="sonic-mini-btn bookMarkBook {$setBookmarked}">🔖 <span id="label-{$biblio_id}">{$bookmark}</span></a>
        <a href="javascript:void(0)" data-id="{$biblio_id}" data-title="{$title_attr}" data-target="#mediaSocialModal" class="sonic-mini-btn">📤 {$share}</a>
    </div>
    HTML;
}