<?php

include_once "vendor/autoload.php";

use lsolesen\pel\PelJpeg;

function gtm_delete_attachment_metadata( $media_post_id ) {
	global $wpdb;


	/*$wpdb_result = $wpdb->query("DELETE FROM wp_postmeta WHERE  post_id = $media_post_id and meta_key = '_wp_attachment_metadata'");
	*/

	$media_attachment_metadata = wp_get_attachment_metadata( $media_post_id );
	d( 'before', $media_attachment_metadata );
	$image_meta = $media_attachment_metadata['image_meta'];
	unset( $image_meta['latitude'] );
	unset( $image_meta['latitude_ref'] );
	unset( $image_meta['longitude'] );
	unset( $image_meta['longitude_ref'] );
	$media_attachment_metadata['image_meta'] = $image_meta;
	$update_result                           = wp_update_attachment_metadata( $media_post_id, $media_attachment_metadata );
//    gtm_repair_image_meta($media_post_id);

	if ( $update_result > 0 ) {
		echo "The attachment metadata was deleted successfully!";
	}

	d( 'after', $media_attachment_metadata );
}

function gtm_delete_exif( $media_id ) {

	$postmd = wp_get_attachment_metadata( $media_id );


//var_dump($postmd);
	$imagemd = $postmd['image_meta'];
//var_dump($imagemd);
	$upload_dir = wp_get_upload_dir();

	$imagefilename = $upload_dir['basedir'] . "/" . $postmd['file'];

	$latitude  = $imagemd['latitude']; // coordinates is an array of three eleemnts
	$longitude = $imagemd['longitude'];

	$jpeg = new PelJpeg( $imagefilename );

	$jpeg->clearExif();

	$exif = $jpeg->getExif();
	if ( ! $exif ) { // there is no EXIF, so we'll create one!
		echo "<h3 style='color: green'>The EXIF tag was removed from file !</h3>";

	} else { // file already has exif, update it!

		echo "<h3>An error occurred ! The EXIF was not removed.... !</h3>";
	}

}


