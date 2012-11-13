<?php
/*
Plugin Name: WP-review-publish
Plugin URI: https://github.com/digibib/wp-review-publish
Description: Push posts as bookreviews to Deichman's RDF-store
Version: 0.1
Author: Petter Goksøyr Åsen
Author URI: https://github.com/boutros
License: GPLv2
*/

add_action( 'init', 'create_bookreview_type' );
register_activation_hook( __FILE__, 'set_missing_key');
add_action( 'admin_init', 'deichman_admin_init' );
add_action( 'admin_menu', 'deichman_settings_menu' );

function create_bookreview_type() {
	register_post_type( 'book_reviews',
		array(
			'labels' => array(
				'name' => 'Bokanbefalinger',
				'singular_name' => 'Bokanbefaling',
				'add_new' => 'Legg til',
				'add_new_item' => 'Skriv bokanbefaling',
				'edit' => 'Rediger',
				'edit_item' => 'Rediger bokanbefaling',
				'new_item' => 'Ny bokanbefaling',
				'view' => 'Vis',
				'view_item' => 'Vis bokanbefaling',
				'search_items' => 'Søk i bokanbefalinger',
				'not_found' => 'Fant ingen bokanbefalinger',
				'not_found_in_trash' => 'Ingen bokanbefalinger i søppelbøtta',
				'parent' => 'Overordnet bokanbefaling'
			),
			'public' => true,
			'menu_position' => 20,
			'supports' =>
				array( 'title', 'editor', 'comments',
					'thumbnail', 'custom-fields' ),
			'taxonomies' => array( '' ),
			'has_archive' => true
			)
		);

}

function set_missing_key() {
	if ( get_option( 'deichman_api_key') == false) {
		add_option ( 'deichman_api_key', 'kontakt Deichmanske bibliotek for API-nøkkel');
	}
}

function deichman_admin_init() {
	add_action( 'admin_post_save_deichman_options', 'process_deichman_options');
}

function process_deichman_options() {
	if ( !current_user_can( 'manage_options') )
		wp_die( 'Mangler rettigheter');

	check_admin_referer( 'deichman' );

	if ( isset( $_POST['deichman_api_key'] ) ) {
		$key = sanitize_text_field( $_POST['deichman_api_key'] );
		update_option ( 'deichman_api_key', $key );
	}

	wp_redirect ( add_query_arg ( array( 'page' => 'wp-review-publish', 'message' => '1' ),
		            admin_url( 'options-general.php' ) ) );
	exit;
}

function deichman_settings_menu() {
	add_options_page( 'Data.deichman.no konfigurasjon',
	 				  'data.deichman.no', 'manage_options',
	 				  'wp-review-publish', 'wp_review_publish_config_page');
}

function wp_review_publish_config_page() {
	$key = get_option( 'deichman_api_key' );
	?>
	<div id="deichman-general" class="wrap">
		<h2>data.deichman.no API-nøkkel</h2>

		<?php if ( isset( $_GET['message'] )
		&& $_GET['message'] == '1' ) { ?>
		<div id='message' class='updated fade'><p><strong>Endring lagret.</strong></p></div>
		<?php } ?>

		<form method="post" action="admin-post.php">
			<input type="hidden" name="action" value="save_deichman_options" />
			<?php wp_nonce_field( 'deichman' ); ?>
			API-nøkkel: <input type="text" name="deichman_api_key" value="<?php echo $key; ?>"/>
			<br />
			<input type="submit" value="Lagre" class="button-primary"/>
		</form>
	</div>
<?php
}

?>