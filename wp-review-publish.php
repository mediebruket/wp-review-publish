<?php
/*
Plugin Name: WP-review-publish
Plugin URI: https://github.com/digibib/wp-review-publish
Description: Publisér bokanbefalinger til Decihmans RDF-base direkte fra Wordpress
Version: 0.2.1
Author: Petter Goksøyr Åsen
Author URI: https://github.com/boutros
License: GPLv3
*/

add_action( 'init', 'create_bookreview_type' );
add_action( 'admin_init', 'book_reviews_admin_init' );
add_action( 'admin_menu', 'book_reviews_settings_menu' );
add_action( 'save_post', 'process_book_review_fields', 10, 2 );
add_filter( 'pre_get_posts', 'show_book_reviews_as_posts' );
add_action( 'wp_trash_post', 'remove_rdf' );
add_action( 'untrash_post', 'remove_uri' );
add_action( 'admin_notices', 'my_admin_notices' );
add_action( 'save_post', 'remove_rdf_if_draft' );
add_filter( 'manage_edit-book_reviews_columns', 'my_columns' );
add_action('admin_head-post.php','validate_metadata');
add_action('admin_head-post-new.php','validate_metadata');

if (!session_id())
  session_start();

// Display custom messages to inform user if successfull push to
// deichman's rdfstore or not
function my_admin_notices(){
  if(!empty($_SESSION['my_admin_notices'])) {
	print  $_SESSION['my_admin_notices'];
	unset ($_SESSION['my_admin_notices']);
  }
}

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
				array( 'author', 'title', 'editor', 'comments','thumbnail'),
			'taxonomies' => array( '' ),
			'has_archive' => true,
			'rewrite' => array( 'slug' => 'bokomtaler')
			)
		);
	add_theme_support( 'post-thumbnails', array( 'post', 'page', 'book_reviews' ) );
}

function book_reviews_admin_init() {
	if ( get_option( 'deichman_api_key') == false) {
		add_option( 'deichman_api_key');
	}
	// allow tagging and categories for book reviews
	register_taxonomy_for_object_type('category', 'book_reviews');
	register_taxonomy_for_object_type('post_tag', 'book_reviews');

	add_action( 'admin_post_save_book_reviews_options', 'save_book_reviews_options');
	add_meta_box ( 'book_reviews_metadata',
								 'Metadata',
								 'display_book_review_metadata_box',
								 'book_reviews', 'normal', 'high');
}

function display_book_review_metadata_box ( $book_review ) {
	$book_isbn = esc_html( get_post_meta( $book_review->ID, 'book_isbn', true ) );
	$review_teaser = esc_html( get_post_meta( $book_review->ID, 'review_teaser', true ) );
	$review_audience = esc_html( get_post_meta( $book_review->ID, 'review_audience', true ) );
	$review_reviewer = esc_html( get_post_meta( $book_review->ID, 'review_reviewer', true ) );
	$review_reviewer_email = esc_html( get_post_meta( $book_review->ID, 'review_reviewer_email', true ) );
	$review_uri = esc_html( get_post_meta( $book_review->ID, 'review_uri', true ) );
	?>
	<p style="color:red"><strong>Alle felt er obligatoriske</strong></p>
	<table>
		<tr>
			<td style="width: 100%">ISBN<span style="color:red">*</span></td>
			<td><input id="metadata-isbn" type="text" size="80" name="book_review_book_isbn"
				value="<?php echo $book_isbn; ?>" /></td>
		</tr>
		<tr>
			<td style="width: 100%">Teaser<span style="color:red">*</span></td>
			<td>
				<textarea id="metadata-teaser" name="review_teaser" rows="5" cols="79"><?php echo $review_teaser; ?></textarea>
			</td>
		</tr>
		<tr>
			<td style="width: 100%">Målgruppe(r)<span style="color:red">*</span> for <em>anbefalingen</em>, ikke for boka:</td>
			<td><div id="metadata-audience" style="border: 1px solid #dedede; padding: 4px">
				<fieldset>
					<input id="a1" <?php if ( preg_match("/barn/", $review_audience)) echo 'checked="checked"'; ?> class="audiences" name="audience[]" type="checkbox" value="barn">
					<label for="a1" class="checklabel">barn</label>
					<input id="a2" <?php if ( preg_match("/ungdom/", $review_audience)) echo 'checked="checked"'; ?>class="audiences" name="audience[]" type="checkbox" value="ungdom">
					<label for="a2" class="checklabel">ungdom</label>
					<input id="a3" <?php if ( preg_match("/voksen/", $review_audience)) echo 'checked="checked"'; ?>class="audiences" name="audience[]" type="checkbox" value="voksen">
					<label for="a3" class="checklabel">voksen</label>
				</fieldset>
				</div>
			</td>
		</tr>
		<tr>
			<td style="width: 100%">Anmelders epost<span style="color:red">*</span></td>
			<td><input id="metadata-email" type="text" size="80" name="review_reviewer_email"
				value="<?php echo $review_reviewer_email; ?>" /></td>
		</tr>
		<tr>
			<td style="width: 100%">Anmelders navn<span style="color:red">*</span></td>
			<td><input id="metadata-name" type="text" size="80" name="review_reviewer"
				value="<?php echo $review_reviewer; ?>" /></td>
		</tr>
		<tr>
			<td style="width: 100%">URI (fylles ut automatisk etter publisering)</td>
			<td><input type="text" disabled="true" size="80" name="review_uri"
				value="<?php echo $review_uri; ?>" /></td>
		</tr>
	</table>
<?php
}

function validate_metadata() {
global $post;
if ( is_admin() && $post->post_type == 'book_reviews' ){
	?>
	<script language="javascript" type="text/javascript">
		jQuery(document).ready(function() {
			jQuery('#publish').on('click', function(e) {
				var inputs = ["title", "metadata-isbn", "metadata-name", "metadata-teaser", "metadata-email"];
				var valid = true;
				inputs.forEach(function( i ) {
					var el = document.getElementById(i);
					if ( !el.value  ) {
						valid = false;
						el.style.borderColor = "red";
					} else {
						el.style.borderColor = "#dedede";
					}

				});
				var audienceMissing = true;
				jQuery("#"+"metadata-audience").find("input").each(function ( i ) {
					if ( this.checked == true ) {
						audienceMissing = false;
					}
				});
				if ( audienceMissing ) {
					document.getElementById("metadata-audience").style.borderColor = "red";
					valid = false;
				}
				if ( !valid ) {
					jQuery('.temp-error').remove();
					jQuery('#publish').parent().parent().prepend("<div class='error temp-error'>Fyll ut alle obligatoriske felt!</div>");
					e.preventDefault();
				}
			});
		});
	</script>
	<?php
	}
}

function process_book_review_fields( $book_review_id, $book_review ) {
	// Check post type for book reviews
	if ( $book_review->post_type != 'book_reviews' )
		return;

	// Store data in meta table if present in post data
	if ( isset( $_POST['book_review_book_isbn'] ) ) {
		update_post_meta( $book_review_id, 'book_isbn', $_POST['book_review_book_isbn'] );
	}
	if ( isset( $_POST['review_teaser'] ) ) {
		update_post_meta( $book_review_id, 'review_teaser', $_POST['review_teaser'] );
	}
	if ( isset( $_POST['audience'] ) ) {
		$s= join("|", $_POST['audience']);
		update_post_meta( $book_review_id, 'review_audience', $s );
	} else {
		update_post_meta( $book_review_id, 'review_audience', "" );
	}
	if ( isset( $_POST['review_reviewer'] ) ) {
		update_post_meta( $book_review_id, 'review_reviewer', $_POST['review_reviewer'] );
	}
	if ( isset( $_POST['review_reviewer_email'] ) ) {
		update_post_meta( $book_review_id, 'review_reviewer_email', $_POST['review_reviewer_email'] );
	}
	if ( isset( $_POST['review_audience'] ) && $_POST['review_audience'] != '0') {
		update_post_meta( $book_review_id, 'review_audience', $_POST['review_audience'] );
	}

	// Don't do continue if post is draft
	if ( $book_review->post_status != "publish" )
		return;

	// set up HTTP request for push data.deichman.no
	if( !class_exists( 'WP_Http' ) ) {
		include_once( ABSPATH . WPINC. '/class-http.php' );
	}
	$request = new WP_Http;
	$url = 'http://marc2rdf.deichman.no/api/reviews';

	// Check if all parameters are present:
	// required: text, teaser, author, (review)title, audience
	// optional: reviewer
	$cleaned_text = preg_replace('~^\s*(.*?)\s*$~sm', '<p>$1</p>', $book_review->post_content);
	$body = array (
		"published" => true,
		"title" => $book_review->post_title,
		"text"  => $cleaned_text,
		"teaser" => get_post_meta( $book_review_id, 'review_teaser', true ),
		"api_key" => get_option( 'deichman_api_key' ),
		"reviewer" => get_post_meta( $book_review_id, 'review_reviewer_email', true )
		);

	$audience = get_post_meta( $book_review_id, 'review_audience', true );
	if ( $audience != "0" && !empty($audience) ) {
		$body["audience"] = $audience;
	}

	$reviewer = get_post_meta( $book_review_id, 'review_reviewer', true );
	if ( $reviewer != "0" && !empty($reviewer) ) {
		$body["reviewer_name"] = $reviewer;
	}

	$uri = get_post_meta( $book_review_id, 'review_uri', true );
	// If review is published not before
	if ( empty($uri) ) {
		// perform POST
		$body["isbn"] = get_post_meta( $book_review_id, 'book_isbn', true );
		$body = json_encode( $body );
		$result = $request->request( $url,
									 array( 'method' => 'POST', 'body' => $body ) );
		if ( $result["response"]["code"] != 201 ) {
			 $_SESSION['my_admin_notices'] .= '<div class="error"><p>Bokanbefaling push feilet fordi:</p><p>'. $result["body"] .'</p></div>';
			 return false;
		} else {
			$_SESSION['my_admin_notices'] .= '<div class="updated"><p>Bokanbefaling pushet til anbefalinger.deichman.no</p></div>';
		}

		$json = json_decode( $result["body"], true );

		// If success, save uri to review metadata
		if ( $json["works"][0]["reviews"][0]["uri"] != "" )
			update_post_meta( $book_review_id, 'review_uri', $json["works"][0]["reviews"][0]["uri"] );

	// else if review is updating an allready published review
	} else {
		$body["uri"] = $uri;
		// perform PUT
		$body = json_encode( $body );
		$result = $request->request( $url,
									 array( 'method' => 'PUT', 'body' => $body ) );
		if ( $result["response"]["code"] != 200 ) {
			$_SESSION['my_admin_notices'] .= '<div class="error"><p>Bokanbefaling opdatering feilet fordi:</p><p>'. $result["body"] .'</p></div>';
		} else {
			$_SESSION['my_admin_notices'] .= '<div class="updated"><p>Bokanbefaling oppdatert på anbefalinger.deichman.no</p></div>';
		}
	}

}

function remove_rdf ( $id ) {
	// Return if uri not present
	$uri = get_post_meta( $id, 'review_uri', true );

	if ( empty( $uri ) )
		return;

	$url = 'http://marc2rdf.deichman.no/api/reviews';
	$post_data = array (
		"uri" => $uri,
		"api_key" => get_option( 'deichman_api_key' )
		);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
	// adding the post variables to the request
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	$result = curl_exec($ch);
	curl_close($ch);

	if ( $result == FALSE ) {
			$_SESSION['my_admin_notices'] .= '<div class="error"><p>Bokanbefaling sletting feilet fordi:</p><p>'. curl_error($ch) .'</p></div>';
		} else {
			$_SESSION['my_admin_notices'] .= '<div class="updated"><p>Bokanbefaling fjernet fra anbefalinger.deichman.no</p></div>';
	}
}

function remove_rdf_if_draft ( $id ) {
	$book_review = get_post( $id );

	if ( $book_review->post_status == 'draft' || $book_review->post_status == 'pending' ) {
		remove_rdf( $id );
		remove_uri( $id );
	}
}

function remove_uri ( $id) {
	// delete uri, in case post is restored from trash
	delete_post_meta( $id, 'review_uri');
}

function save_book_reviews_options() {
	if ( !current_user_can( 'manage_options') )
		wp_die( 'Mangler rettigheter' );

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
			<input type="hidden" name="action" value="save_book_reviews_options" />
			<?php wp_nonce_field( 'deichman' ); ?>
			API-nøkkel: <input type="text" name="deichman_api_key" value="<?php echo $key; ?>"/>
			<input type="submit" value="Lagre" class="button-primary"/>
		</form>
		<p>Har du ikke API-nøkkel, får du denne ved å kontakte Deichmanske bibliotek</p>
	</div>
<?php
}

function show_book_reviews_as_posts ( $query ) {
	if( ( is_home() && $query->is_main_query() ) || is_feed() || is_category() || is_tag() ){
		if ( is_category() || is_tag() ) {
			$post_types = array('nav_menu_item', 'post', 'book_reviews');
		} else {
			$post_types = array('post', 'book_reviews');
		}

		$query->set ( 'post_type', $post_types);
	}
	return $query;
}

function my_columns( $columns ) {
	$columns['author'] = 'Forfatter';
	unset( $columns['comments'] );
	return $columns;
}

?>