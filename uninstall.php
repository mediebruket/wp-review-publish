<?php

	if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit;

	if ( get_option( 'decihman_api_key' ) != false ) {
		delete_option( 'deichman_api_key' );
	}

?>