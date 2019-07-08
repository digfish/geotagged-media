<?php
// @digfish: the form to configure Geotagged media plugin

//d($gtm_options);
?>
<div class="wrap">
	<h1>Geotagged Media Plugin options</h1>

		<p>
		Fill the fields with the configuration for Geotagged Media Plugin
		</p>

		<form method="post" action="options-general.php?page=gtm-admin-options">
            <p><input type="checkbox"  name="gtm_options[geocode_on_upload]" value="true"><label for="gtm_options[geocode_on_upload]">Assign names automtically to photos on upload !?</label></p>
            <p><input type="checkbox" name="gtm_options[add_metadata_column]" value="true"><label>Add metadata column to media library?</label></p>
            <p><input type="checkbox" name="gtm_options[media_metadata_gps_details]" value="true"><label>Show GPS details on Media Attachment details !?</label></p>
            <p><input type="checkbox" name="gtm_options[media_show_edit_exif_form]" value="true"><label>Show Edit EXIF fields form !? </label></p>
            <p><input type="checkbox" name="gtm_options[add_dashboard_geotagged_media_option]" value="true"><label>Add " Geotagged Media" item to the the dashboard side menu ?</label></p>

			<?php echo submit_button() ?>
		</form>

</div>
<script type="text/javascript">
    jQuery(document).ready(function ($) {

            $.get(
                ajaxurl + "?action=gtm_get_options_values",
            {}).success(
        function (response) {
            console.log("GTM options",response);
            gtm_options = response;
            $.each(gtm_options, function(option_name,option_value) {
                console.log(option_name,option_value);
                if (option_value == 'true') {
                    $("[type=checkbox][name=gtm_options\\[" + option_name + "\\]]").attr('checked', 'yes');
                }
            });
        });

    });
</script>