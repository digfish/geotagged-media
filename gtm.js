function wp_media_link(link_text, image_post_id) {
    return "<A href='" + wp_media_url(image_post_id) + "' target='_blank'>" + link_text + "</A>";
}

function wp_media_url(image_post_id) {
    // return "/wp-admin/upload.php?item=" + image_post_id + "&mode=grid";

    return "/wp-admin/post.php?post=" + image_post_id + "&action=edit";
}

function gtm_action_link(action, text, params) {
    pkv = [];
    for (key in params) {
        val = params[key];
        pkv.push(key + '=' + encodeURIComponent(val));
    }
    qstr = pkv.join('&');


    //   return "<A href='/wp-admin/post.php?&action=' + action + (qstr ? ("&" + qstr) : "") + "' target='_blank'>" + text + "</A>";

    return "<A href='/wp-admin/upload.php?page=gtm&action=" + action + (qstr ? ("&" + qstr) : "") + "' target='_blank'>" + text + "</A>";
}

function mst_render(id,vars) {
    var tmpl_script = jQuery('head').find(id).html();

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

function gtmOverlayModalIframe(url) {
    $('body').append(mst_render('#mst_overlay_modal_iframe', {url: url}));
    _overlayModaljQueryUIdialog('#overlay_modal');
}

function _overlayModaljQueryUIdialog(selector) {
    jQuery(selector).dialog({
            width: 0.95 * document.body.clientWidth,
            height: document.body.clientHeight,
            modal: true
        }
    );
}

function gtmOverlayModal(html) {
    jQuery('body').append(mst_render('#mst_overlay_modal', {html: html}));
    _overlayModaljQueryUIdialog('#overlay_modal');
}


function initMustacheTemplates() {
    jQuery.get("/wp-content/plugins/geotagged-media/gtm.mst", {}).success(function (response) {
        console.log("Mustache templates file loaded!");
        mustache_tmpl = jQuery.parseHTML(response, document, true);
        jQuery('head').append(mustache_tmpl);
    });
}

function gtmOverlayModalUrl(url) {
    jQuery.get(url).success(function (resp) {
        console.log('gtm_html_url response', resp);
        gtmOverlayModal(resp);
    });
}

function initDismissableButtonAction() {

    jQuery(document).on('click', '#gtm_activation_notice .notice-dismiss', function (evt) {
        console.log('Clicked dismiss!');
        jQuery.get(ajaxurl + "?action=dismiss_activation_notice")
            .success(function (response) {
                console.log('dismisson_activation_notice', response);
            });

    });
}

function isMapDrawn() {
    return jQuery('canvas').length > 0;
}