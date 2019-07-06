<?php



function gtm_composer_exec( $options ) {
	$composer_location = __DIR__ . '/composer.phar';
	chdir( __DIR__ );
	header( "Content-type: text/plain" );
	$full_cmdline = "/usr/bin/php $composer_location $options";
	//$full_cmdline = "php /usr/bin/composer $options";
	//debug( $full_cmdline );
	$buffer    = "'";
	$exit_code = PHP_INT_MIN;
	putenv( "COMPOSER_HOME=" . getcwd() );
	ob_start();
	system( $full_cmdline . " 2>&1", $exit_code );
	//$buffer .= join("\n",$r_out);
	$buffer .= ob_get_clean();
	//debug( 'exit_code', $exit_code );
	$buffer .= "'";
	unlink( __DIR__ . '/.htaccess' );

	return $buffer;
}

function gtm_install_deps() {
	$out = gtm_composer_exec( "update" );
	//debug( 'composer_output', $out );
	//echo $out;
}


