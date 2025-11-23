<?php
/**
 * Plugin Name:       RB Thumbnail Columns
 * Plugin URI:        https://github.com/BashirRased/wp-plugin-rb-thumbnail-columns
 * Description:       Adds a thumbnail column to post lists.
 * Version:           1.0.1
 * Requires at least: 6.4
 * Tested up to:      6.7
 * Requires PHP:      7.4
 * PHP Version:       8.2
 * Author:            Bashir Rased
 * Author URI:        https://bashir-rased.com/
 * Text Domain:       rb-thumbnail-columns
 * Domain Path:       /languages
 *
 * @package RB_Plugins
 * @subpackage RB_Thumbnail_Columns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load plugin textdomain.
 */
function rbtc_textdomain() {
	load_plugin_textdomain( 'rb-thumbnail-columns', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'rbtc_textdomain' );

/**
 * Add GitHub link under plugin row.
 *
 * @param array  $links  Existing plugin links.
 * @param string $plugin Plugin file name.
 */
function rbtc_plugin_row_meta( $links, $plugin ) {
	if ( plugin_basename( __FILE__ ) === $plugin ) {
		$links[] = sprintf(
			'<a href="%s" style="color:#b32d2e;">%s</a>',
			esc_url( 'https://github.com/BashirRased/wp-plugin-rb-thumbnail-columns' ),
			esc_html__( 'Fork on Github', 'rb-thumbnail-columns' )
		);
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'rbtc_plugin_row_meta', 10, 2 );

/**
 * Add thumbnail column to posts, pages, products.
 *
 * @param array $columns Existing columns.
 */
function rbtc_add_custom_columns( $columns ) {
	$columns['thumbnail'] = esc_html__( 'Thumbnail Image', 'rb-thumbnail-columns' );
	return $columns;
}
add_filter( 'manage_post_posts_columns', 'rbtc_add_custom_columns', 20 );
add_filter( 'manage_page_posts_columns', 'rbtc_add_custom_columns', 10 );
add_filter( 'manage_product_posts_columns', 'rbtc_add_custom_columns', 20 );

/**
 * Render thumbnail value in column.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function rbtc_custom_columns_value( $column, $post_id ) {
	if ( 'thumbnail' === $column ) {
		$thumbnail = get_the_post_thumbnail( $post_id, array( 100, 100 ) );
		echo wp_kses_post( $thumbnail );
	}
}
add_action( 'manage_posts_custom_column', 'rbtc_custom_columns_value', 10, 2 );
add_action( 'manage_pages_custom_column', 'rbtc_custom_columns_value', 10, 2 );

/**
 * Make thumbnail column sortable.
 *
 * @param array $columns Existing sortable columns.
 */
function rbtc_sortable_column( $columns ) {
	$columns['thumbnail'] = '_thumbnail_id';
	return $columns;
}
add_filter( 'manage_edit-post_sortable_columns', 'rbtc_sortable_column' );

/**
 * Add dropdown filter for thumbnail posts.
 */
function rbtc_filter_column() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$filter_value = isset( $_GET['rbtc'] ) ? absint( wp_unslash( $_GET['rbtc'] ) ) : 0;

	$values = array(
		'0' => esc_html__( 'All Posts', 'rb-thumbnail-columns' ),
		'1' => esc_html__( 'Thumbnail Posts', 'rb-thumbnail-columns' ),
		'2' => esc_html__( 'No Thumbnail Posts', 'rb-thumbnail-columns' ),
	);
	?>
	<select name="rbtc">
		<?php foreach ( $values as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filter_value, $key ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}
add_action( 'restrict_manage_posts', 'rbtc_filter_column' );

/**
 * Apply filter/sorting to WP_Query.
 *
 * @param WP_Query $query The query object.
 */
function rbtc_filter_data( $query ) {

	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$filter_value = isset( $_GET['rbtc'] ) ? absint( wp_unslash( $_GET['rbtc'] ) ) : 0;

	// Thumbnail filters.
	if ( 1 === $filter_value ) {
		$query->set(
			'meta_query',
			array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			)
		);
	} elseif ( 2 === $filter_value ) {
		$query->set(
			'meta_query',
			array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'NOT EXISTS',
				),
			)
		);
	}

	// Fix sorting.
	$orderby = $query->get( 'orderby' );
	if ( 'rbtc_post_view' === $orderby ) {
		$query->set( 'meta_key', 'rbtc_post_view' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'rbtc_filter_data' );
