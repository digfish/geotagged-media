<?php
// @digfish: the form to configure Geotagged media plugin

?>
<style type="text/css">
    #geocode_on_upload_details {
        display: none;
    }
</style>
<div id="mst_cntx" style="display: none">

</div>
<div class="wrap">
    <h1>Geotagged Media Plugin options</h1>

    <p>
        Fill the fields with the configuration for Geotagged Media Plugin
    </p>

    <form name="gtm_settings_form" method="post" action="options-general.php?page=gtm-admin-options">
        <p><input type="checkbox" name="gtm_options[geocode_on_upload]" value="true"><label
                    for="gtm_options[geocode_on_upload]">Assign names automtically to photos on upload !?</label></p>
        <div id="composer_init_section">
            <p id="geocode_on_upload_details">In order to proceed with this option it's required to download the
                Composer
                package manager
                <button class="button" id="btn_download_composer">Click here to download</button>
            <P id='download_composer_response' style="display: none;"></P>
            </p></div>
        <p><input type="checkbox" name="gtm_options[add_metadata_column]" value="true"><label>Add metadata column to
                media library?</label></p>
        <p><input type="checkbox" name="gtm_options[media_metadata_gps_details]" value="true"><label>Show GPS details on
                Media Attachment details !?</label></p>
        <p><input type="checkbox" name="gtm_options[media_show_edit_exif_form]" value="true"><label>Show Edit EXIF
                fields form !? </label></p>
        <p><input type="checkbox" name="gtm_options[add_dashboard_geotagged_media_option]" value="true"><label>Add "
                Geotagged Media" item to the the dashboard side menu ?</label></p>
        <p><label><input type="checkbox" name="gtm_options[media_library_gtm_filters]" value="true">Add filters on media
                library to allow to select geotagged and not geotagged media ?</label></p>

        <p><label class="gtm input-label">BingMaps Key</label><input type="text" name="gtm_options[key_bingmaps]"
                                                                     class="gtm-input"
                                                                     value="<?php echo $gtm_options['key_bingmaps'] ?>">
        </p>

        <p><label class="gtm input-label"> ThunderForest Key</label><input class="gtm-input" type="text"
                                                                           name="gtm_options[key_thunderforest]"
                                                                           value="<?php echo $gtm_options['key_thunderforest'] ?>">

        </p>

        <p><label class="gtm input-label"> MapBox Key</label><input class="gtm-input" type="text" style="width:700px"
                                                                    name="gtm_options[key_mapbox]"
                                                                    value="<?php echo $gtm_options['key_mapbox'] ?>">

        </p>


		<?php echo submit_button() ?>
    </form>

</div>
<script type="text/javascript">



    function jquiModal(dialog_id,msg) {
         jQuery('body').append("<DIV id='"+ dialog_id + "'><img src='/wp-includes/images/spinner-2x.gif'>" + msg + "</DIV");
        $jquim = jQuery('#'+dialog_id).dialog();
        console.log('jquim',$jquim);
        return $jquim;
    }

    function jquiClose($jquim) {

        jQuery('.ui-dialog button').trigger('click');
        jQuery('.ui-dialog').remove();
    }



    jQuery(document).ready(function ($) {

        $jquim = jquiModal('gtmModalWaitSettings',"<H4>Please wait while the option settings are loaded...</H4>");

        var mustache_tmpl = "";
        // load mustache js templates
        $.get("<?php echo plugin_dir_url( __FILE__ ) ?>/gtm.mst", {}).success(function (response) {
            console.log("Mustache templates file loaded!");
            mustache_tmpl = jQuery.parseHTML(response, document, true);
            $('head').append(mustache_tmpl);

        });

        $.get(
            ajaxurl + "?action=gtm_get_options_values",
            {}).success(
            function (response) {
                jquiClose($jquim);                
                //               console.log("GTM options", response);
                gtm_options = response;
                $.each(gtm_options, function (option_name, option_value) {
                    //                   console.log(option_name, option_value);
                    if (option_value == 'true') {
                        $("[type=checkbox][name=gtm_options\\[" + option_name + "\\]]").attr('checked', 'yes');
                    }
                });
            });


        $('#btn_download_composer').on('click', function (evt) {
            evt.preventDefault();
            jquim = jquiModal("gtmModalWaitDwnldComposer","<H4>Downloading composer, wait...</H4>");
            $.get(
                ajaxurl + "?action=gtm_download_composer", {}).success(function (response) {
                jquiClose(jquim);
                console.log(response);
                $('#download_composer_response').html(mst_render('#mst_simple_paragraph', {'text': response}));
                $('#download_composer_response').show();
                //$('#composer_init_section').trigger('init_vendor');
                $('#composer_init_section').triggerHandler('click');
            });

        });


        $('#composer_init_section').on('click', '#btn_composer_init_vendor', function (evt) {
            //          console.log('clicked',this)
            evt.preventDefault();
            jquim = jquiModal("gtmModalWiatDwnldDpendencis","<H4>Downloading dependencies, wait...</H4>");
            $('#btn_composer_init_vendor').parent().append(mst_render('#mst_simple_paragraph', {'text': "Downloading dependencies . . . Please wait . . ."}));
            $.get(ajaxurl + "?action=gtm_install_deps", {}).success(function (response) {
                //              console.log(response);
                jquiClose(jquim);
                $('#btn_composer_init_vendor').parent().append(mst_render('#mst_textarea_console', {'text': response}));

                $('#btn_composer_init_vendor').parent().append(mst_render('#mst_simple_paragraph', {'text': "Dependencies installed with success!"}));
            });

        });


        $('[name=gtm_options\\[geocode_on_upload\\]]').on('click', function (evt) {
            $this = $(this);
            if ($this.attr('checked')) {
                //           console.log('Is checked!');
                $('#geocode_on_upload_details').show();
            } else {
                //             console.log('Is not checked!');
                $('#geocode_on_upload_details').hide();

            }
        })
    });
</script>