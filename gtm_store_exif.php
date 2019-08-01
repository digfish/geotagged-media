<?php

include_once "vendor/autoload.php";

use lsolesen\pel\PelEntryAscii;
use lsolesen\pel\PelEntryByte;
use lsolesen\pel\PelEntryRational;
use lsolesen\pel\PelEntryUserComment;
use lsolesen\pel\PelExif;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTag;
use lsolesen\pel\PelTiff;


function gtm_split_coord_tokens($coord)
{
    return preg_split("/\//", $coord);
}

function gtm_store_exif($media_id)
{

    $postmd = wp_get_attachment_metadata($media_id);

//var_dump($postmd);
    $imagemd = $postmd['image_meta'];
//var_dump($imagemd);
    $upload_dir = wp_get_upload_dir();

    $imagefilename = $upload_dir['basedir'] . "/" . $postmd['file'];

    $latitude = $imagemd['latitude']; // coordinates is an array of three eleemnts
    $longitude = $imagemd['longitude'];

    $jpeg = new PelJpeg($imagefilename);

    $exif = $jpeg->getExif();
 //   if (!$exif) { // there is no EXIF, so we'll create one!

        $exif = new PelExif();
        $jpeg->setExif($exif);
        $tiff_data = new PelTiff();
        $exif->setTiff($tiff_data);

        $ifd0 = new PelIfd(PelIfd::IFD0);
        $tiff_data->setIfd($ifd0);


        $geo_ifd = new PelIfd(PelIfd::GPS);
        $ifd0->addSubIfd($geo_ifd);

        $exif_ifd = new PelIfd(PelIfd::EXIF);
        $exif_ifd->addEntry(new PelEntryUserComment("comment"));
        $ifd0->addSubIfd($exif_ifd);

        $inter_ifd = new PelIfd(PelIfd::INTEROPERABILITY);
        $ifd0->addSubIfd($inter_ifd);

        $ifd0->addEntry(new PelEntryAscii(PelTag::MODEL, 'Geotagged Media'));
        $ifd0->addEntry(new PelEntryAscii(PelTag::DATE_TIME, date('Y-m-d H:i:s')));
        $ifd0->addEntry(new PelEntryAscii(PelTag::IMAGE_DESCRIPTION, "Image geotagged manually by Geotagged Media Wordpress Plugin"));
        $geo_ifd->addEntry(new PelEntryByte(PelTag::GPS_VERSION_ID, 2, 2, 0, 0));


        list ($degrees, $minutes, $seconds) = array_map(
            function ($coord) {
                return gtm_split_coord_tokens($coord);
            },
            $latitude
        );


        $geo_ifd->addEntry(new PelEntryRational(PelTag::GPS_LATITUDE, $degrees, $minutes, $seconds));
        $geo_ifd->addEntry(new PelEntryAscii(PelTag::GPS_LATITUDE_REF, ($latitude < 0) ? "S" : "N"));

        d($longitude);


        list ($degrees, $minutes, $seconds) = array_map(
            function ($coord) {
                return gtm_split_coord_tokens($coord);
            },
            $longitude
        );

        $geo_ifd->addEntry(new PelEntryRational(PelTag::GPS_LONGITUDE, $degrees, $minutes, $seconds));
        $geo_ifd->addEntry(new PelEntryAscii(PelTag::GPS_LONGITUDE_REF, ($longitude < 0) ? "W" : "E"));

        $filename_no_ext = substr($imagefilename, 0, strripos($imagefilename, "."));
        //$new_geotagged_filename = "$filename_no_ext.tagged.jpg";
        file_put_contents($imagefilename, $jpeg->getBytes());
        echo "New geotagged file is $imagefilename";

   /* } else { // file already has exif, update it!

        echo "<h3>The image already has geotags!</h3>";
    }*/

    echo "<h3>Click here to <A href='post.php?post=$media_id&action=edit'>return to the edit media</A></h3>";
}


