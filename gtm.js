function wp_media_link(link_text, image_post_id) {
    return "<A href='/wp-admin/upload.php?item=" + image_post_id + "&mode=grid' target='_blank'>" + link_text + "</A>";
}

function wp_media_url(image_post_id) {
    return "/wp-admin/upload.php?item=" + image_post_id + "&mode=grid";
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

function mst_render(id,vars) {
    var tmpl_script = jQuery('head').find(id).html();
    //console.log(tmpl_script);
    return Mustache.render(tmpl_script, vars);
}

function mst_render_html(id,vars){
    var tmpl_script = $('head').find(id).html();
    console.log(tmpl_script);
    return Mustache.to_html(tmpl_script, vars);
}

function isAdmin() {
    return location.href.indexOf('wp-admin') > -1;
}