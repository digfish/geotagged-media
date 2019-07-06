function wp_media_link(link_text, image_post_id) {
    return "<A href='/wp-admin/upload.php?item=" + image_post_id + "&mode=grid' target='_blank'>" + link_text + "</A>";
}

function gtm_action_link(action, text, params) {
    pkv = [];
    for (key in params) {
        val = params[key];
        pkv.push(key + '=' + encodeURIComponent(val));
    }
    qstr = pkv.join('&');
    return "<A href='/wp-admin/upload.php?page=gtm&action=" + action + (qstr ? ("&" + qstr) : "") + "' target='_blank'>" + text + "</A>";
}