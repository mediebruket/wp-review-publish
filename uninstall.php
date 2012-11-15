<?php

	if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit;

	if ( get_option( 'decihman_api_key' ) != false ) {
		delete_option( 'ch2pho_options' );
	}

?>