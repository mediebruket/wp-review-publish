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
add_action( 'admin_init', 'book_reviews_admin_init' );
add_action( 'admin_menu', 'book_reviews_settings_menu' );
add_action( 'save_post', 'process_book_review_fields', 10, 2 );
add_filter( 'pre_get_posts', 'show_book_reviews_as_posts');

function create_bookreview_type() {
	register_post_type( 'book_reviews',
		array(
			'labels' => array(
				'name' => 'Bokanbefalinger',
				'singular_name' => 'Bokanbefaling',
				'add_new' => 'Skriv ny',
				'add_new_item' => 'Skriv bokanbefaling',
				'edit' => 'Rediger',
				'edit_item' => 'Rediger bokanbefaling',
				'new_item' => 'Ny bokanbefaling',
				'view' => 'Vis',
				'view_item' => 'Vis bokanbefaling',
				'search_items' => 'Søk i bokanbefalinger',
				'not_found' => 'Fant ingen bokanbefalinger',
				'not_found_in_trash' => 'Ingen bokanbefalinger i søppelkurven',
				'parent' => 'Overordnet bokanbefaling'
			),
			'public' => true,
			'menu_position' => 20,
			'supports' =>
				array( 'title', 'editor', 'comments','thumbnail'),
			'taxonomies' => array( '' ),
			'has_archive' => true
			)
		);

}

function book_reviews_admin_init() {
	if ( get_option( 'deichman_api_key') == false) {
		add_option( 'deichman_api_key');
	}

	add_action( 'admin_post_save_book_reviews_options', 'save_book_reviews_options');
	add_meta_box ( 'book_reviews_metadata',
								 'Metadata',
								 'display_book_review_metadata_box',
								 'book_reviews', 'normal', 'high');
}

function display_book_review_metadata_box ( $book_review ) {
	$book_author = esc_html( get_post_meta( $book_review->ID, 'book_author', true ) );
	$book_isbn = esc_html( get_post_meta( $book_review->ID, 'book_isbn', true ) );
	$review_teaser = esc_html( get_post_meta( $book_review->ID, 'review_teaser', true ) );
	$review_audience = esc_html( get_post_meta( $book_review->ID, 'review_audience', true ) );
	$review_reviewer = esc_html( get_post_meta( $book_review->ID, 'review_reviewer', true ) );
	?>
	<p>Felt merket * er obligatoriske</p>
	<table>
		<tr>
			<td style="width: 100%">Forfatter*</td>
			<td><input type="text" size="80" name="book_review_author_name"
				value="<?php echo $book_author; ?>" /></td>
		</tr>
		<tr>
			<td style="width: 100%">ISBN*</td>
			<td><input type="text" size="80" name="book_review_book_isbn"
				value="<?php echo $book_isbn; ?>" /></td>
		</tr>
		<tr>
			<td style="width: 100%">Teaser*</td>
			<td>
				<textarea  name="review_teaser" rows="5" cols="79"><?php echo $review_teaser; ?></textarea>
			</td>
		</tr>
		<tr>
			<td style="width: 100%">Målgruppe (for anbefalingen)</td>
			<td>
				<select name="review_audience">
						<option value="0" ></option>
						<option value="1" <?php if ( $review_audience == 1 ) echo 'selected="selected"'; ?>>Voksne</option>
						<option value="2" <?php if ( $review_audience == 2 ) echo 'selected="selected"'; ?>>Barn/Ungdom</option>
					</select>
			</td>
		</tr>
		<tr>
			<td style="width: 100%">Anmelder</td>
			<td><input type="text" size="80" name="review_reviewer"
				value="<?php echo $review_reviewer; ?>" /></td>
		</tr>
	</table>
<?php
}

function process_book_review_fields( $book_review_id, $book_review ) {
	// Check post type for book reviews
	if ( $book_review->post_type != 'book_reviews' )
		return;

	// Store data in meta table if present in post data
	if ( isset( $_POST['book_review_author_name'] ) && $_POST['book_review_author_name'] != '' ) {
		update_post_meta( $book_review_id, 'book_author', $_POST['book_review_author_name'] );
	}
	if ( isset( $_POST['book_review_book_isbn'] ) && $_POST['book_review_book_isbn'] != '' ) {
		update_post_meta( $book_review_id, 'book_isbn', $_POST['book_review_book_isbn'] );
	}
	if ( isset( $_POST['review_teaser'] ) && $_POST['review_teaser'] != '' ) {
		update_post_meta( $book_review_id, 'review_teaser', $_POST['review_teaser'] );
	}
	if ( isset( $_POST['review_audience'] ) && $_POST['review_audience'] != '' ) {
		update_post_meta( $book_review_id, 'review_audience', $_POST['review_audience'] );
	}
	if ( isset( $_POST['review_reviewer'] ) && $_POST['review_reviewer'] != '' ) {
		update_post_meta( $book_review_id, 'review_reviewer', $_POST['review_reviewer'] );
	}

	// set up HTTP request for push data.deichman.no
	if( !class_exists( 'WP_Http' ) ) {
		include_once( ABSPATH . WPINC. '/class-http.php' );
	}
	$request = new WP_Http;
	$args = array();

	// Check if all parameters are present:
	// required: text, teaser, author, (review)title,
	// optional: audience, reviewer
	$args["title"] =

	// If review is published not before
	if ( !isset( get_post_meta( $book_review->ID, 'review_uri', true ) )) {
		$args['method'] = 'POST';
		// perform POST
		$result = $request->request( 'http://datatest.deichman.no' , $args );
		// If success, save uri to metadata review_uri

	// else if review is updating an allready published review
	} else {
		$args['method'] = 'PUT';

		// perform PUT
		$result = $request->request( 'http://datatest.deichman.no' , $args );
	}

}

function process_book_reviews_options() {
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

function book_reviews_settings_menu() {
	add_options_page( 'Bokanbefalinger konfigurasjon',
	 				  'Bokanbefalinger', 'manage_options',
	 				  'wp-review-publish', 'book_reviews_config_page');
}

function book_reviews_config_page() {
	$key = get_option( 'deichman_api_key' );
	?>
	<div id="deichman-general" class="wrap">
		<h2>Bokanbefalinger konfigurasjon</h2>

		<?php if ( isset( $_GET['message'] )
		&& $_GET['message'] == '1' ) { ?>
		<div id='message' class='updated fade'><p><strong>Endring lagret.</strong></p></div>
		<?php } ?>

		<form method="post" action="admin-post.php">
			<input type="hidden" name="action" value="save_book_review_options" />
			<?php wp_nonce_field( 'deichman' ); ?>
			API-nøkkel: <input type="text" name="deichman_api_key" value="<?php echo $key; ?>"/>
			<input type="submit" value="Lagre" class="button-primary"/>
		</form>
		<p>Har du ikke API-nøkkel, får du denne ved å kontakte Deichmanske bibliotek</p>
	</div>
<?php
}

function show_book_reviews_as_posts ( $query ) {
	if ( is_home() && $query->is_main_query() )
		$query->set ( 'post_type', array( 'post', 'book_reviews'));
	return $query;
}
?>