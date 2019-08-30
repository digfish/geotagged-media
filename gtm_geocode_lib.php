<?php

require_once "vendor/autoload.php";

use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\StatefulGeocoder;
use Http\Adapter\Guzzle6\Client;

/**
 * @param $coord_r assoc array of coordinates with the keys 'lat' and 'long'
 *
 * @return mixed a string with the complete address separated by commas
 * @throws Exception
 */
function gtm_revgeocode($coord_r) {
	$cache_key = GTM_TEXT_DOMAIN .'_'. join(",",$coord_r);
	$revgeocode_cache = get_transient(GTM_TEXT_DOMAIN.'_revgeocode');

	if (!empty($revgeocode_cache[$cache_key]) ) {
		return $revgeocode_cache[$cache_key];
	}

	$httpClient = new Client();
	$provider   = new Nominatim( $httpClient, 'https://nominatim.openstreetmap.org/', 'nominatim-client' );
	$geocoder   = new StatefulGeocoder( $provider, 'en' );

	$geocode_rev_result = NULL;
	try {
		$geocode_rev_result = $geocoder->reverseQuery( ReverseQuery::fromCoordinates( $coord_r['lat'], $coord_r['long'] ) );
	} catch (Exception $ex) {
		$nex = new Exception("Error on retrieving geocoding response!",-2,$ex);
		throw $nex;
	}
	$displayName = $geocode_rev_result->first()->getDisplayName();
	$revgeocode_cache[$cache_key] = $displayName;
	set_transient(GTM_TEXT_DOMAIN.'_revgeocode',$revgeocode_cache, WEEK_IN_SECONDS);
	return $displayName;
}

function gtm_geocode( $geoname ) {

	$httpClient = new Client();
	$provider   = new Nominatim( $httpClient, 'https://nominatim.openstreetmap.org/', 'nominatim-client' );
	$geocoder   = new StatefulGeocoder( $provider, 'en' );

	$geocode_result = $geocoder->geocodeQuery( GeocodeQuery::create( $geoname ) );
	$results        = $geocode_result->all();
//	print_r($results[0]);
	$results = array_map( function ( $result ) {
		return array(
			'name'       => $result->getDisplayName(),
			'latitude'   => $result->getCoordinates()->getLatitude(),
			'longitude'  => $result->getCoordinates()->getLongitude(),
			'streetName' => $result->getStreetName(),
			'locality'   => $result->getLocality(),
			'country'    => $result->getCountry()->getName()
		);
	}, $results );

	return $results;
}
