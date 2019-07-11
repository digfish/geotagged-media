<?php
// @digfish: the form to configure Geotagged media plugin

//d($gtm_options);
?>
<style type="text/css">
    #geocode_on_upload_details {
        display: none;
    }
</style>
<div class="wrap">
    <h1>Geotagged Media Plugin options</h1>

    <p>
        Fill the fields with the configuration for Geotagged Media Plugin
    </p>

    <form method="post" action="options-general.php?page=gtm-admin-options">
        <p><input type="checkbox" name="gtm_options[geocode_on_upload]" value="true"><label
                    for="gtm_options[geocode_on_upload]">Assign names automtically to photos on upload !?</label></p>
        <p id="geocode_on_upload_details">In order to proceed with this option it's required to download the Composer
            package manager
            <button class="button" id="btn_download_composer">Click here to download</button>
            <P id='download_composer_response' style="display: none;"></P>
        </p>
        <p><input type="checkbox" name="gtm_options[add_metadata_column]" value="true"><label>Add metadata column to
                media library?</label></p>
        <p><input type="checkbox" name="gtm_options[media_metadata_gps_details]" value="true"><label>Show GPS details on
                Media Attachment details !?</label></p>
        <p><input type="checkbox" name="gtm_options[media_show_edit_exif_form]" value="true"><label>Show Edit EXIF
                fields form !? </label></p>
        <p><input type="checkbox" name="gtm_options[add_dashboard_geotagged_media_option]" value="true"><label>Add "
                Geotagged Media" item to the the dashboard side menu ?</label></p>

		<?php echo submit_button() ?>
    </form>

</div>
<script type="text/javascript">
    jQuery(document).ready(function ($) {

        $.get(
            ajaxurl + "?action=gtm_get_options_values",
            {}).success(
            function (response) {
                console.log("GTM options", response);
                gtm_options = response;
                $.each(gtm_options, function (option_name, option_value) {
                    console.log(option_name, option_value);
                    if (option_value == 'true') {
                        $("[type=checkbox][name=gtm_options\\[" + option_name + "\\]]").attr('checked', 'yes');
                    }
                });
            });

        $('#btn_download_composer').on('click',function(evt) {
            evt.preventDefault();
            $.get(
                ajaxurl + "?action=gtm_download_composer", {}).success(function (response) {
                console.log(response);
                $('#download_composer_response').html(response);
                $('#download_composer_response').show();
                $('#btn_composer_init_vendor').on('click',function(evt) {
                    console.log($(this) + ' clicked!')
                    evt.preventDefault();
                    $('#btn_composer_init_vendor').parent().append("<P>Downloading dependencies</P>");
                    $.get(ajaxurl + "?action=gtm_install_deps", {}).success(function (response) {
                        console.log(response);
                        $('#btn_composer_init_vendor').parent().append("<PRE>"+response+"</PRE>");
                    });

                });
            });

        });



        $('[name=gtm_options\\[geocode_on_upload\\]]').on('click', function (evt) {
            $this = $(this);
            if ($this.attr('checked')) {
                console.log('Is checked!');
                $('#geocode_on_upload_details').show();
            } else {
                console.log('Is not checked!');
                $('#geocode_on_upload_details').hide();

            }
        })
    });
</script>