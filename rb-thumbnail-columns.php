<?php
/**
 * Plugin Name:       RB Thumbnail Columns
 * Plugin URI:        https://github.com/BashirRased/wp-plugin-rb-thumbnail-columns
 * Description:       RB Thumbnail Columns plugin use for your posts visit count.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Tested up to: 6.5
 * Requires PHP: 7.0
 * Author:            Bashir Rased
 * Author URI:        https://profiles.wordpress.org/bashirrased2017/
 * Text Domain:       rb-thumbnail-columns
 * Domain Path: 	  /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * 
 */


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin Text domain loaded
function rbtc_textdomain() {
    load_plugin_textdomain('rb-thumbnail-columns', false, dirname(plugin_basename(__FILE__)).'/languages'); 
}
add_action('plugins_loaded', 'rbtc_textdomain');

// Github Page Link
add_filter('plugin_row_meta', function ($links, $plugin) {
	if (plugin_basename(__FILE__) == $plugin) {
		$link = sprintf("<a href='%s' style='color:#b32d2e;'>%s</a>", esc_url('https://github.com/BashirRased/wp-plugin-rb-thumbnail-columns'), __('Fork on Github', 'rb-thumbnail-columns'));
		array_push($links, $link);
	}
	return $links;
}, 10, 2);

// Add Post Columns
function rbtc_add_custom_columns( $columns ) {    
   $columns['thumbnail']  = __('Thumbnail Image','rb-thumbnail-columns');    
   return $columns;
}
add_filter('manage_post_posts_columns', 'rbtc_add_custom_columns', 20, 1);
add_filter('manage_page_posts_columns', 'rbtc_add_custom_columns', 10, 1);
add_filter('manage_product_posts_columns', 'rbtc_add_custom_columns', 20, 1);


// Add Post Columns Value
function rbtc_custom_columns_value( $column, $post_id ) {
   if ($column == 'thumbnail'){

	$thumbnail = get_the_post_thumbnail( get_the_ID(), array( 100, 100 ) );

    echo wp_kses_post($thumbnail); 
   }
}
add_action('manage_posts_custom_column' , 'rbtc_custom_columns_value', 10, 2);
add_action('manage_pages_custom_column' , 'rbtc_custom_columns_value', 10, 2);

// Add Custom Post Columns Sortable
function rbtc_sortable_column( $columns ) {
	$columns['thumbnail'] = '_thumbnail_id';
	return $columns;
}
add_filter('manage_edit-post_sortable_columns', 'rbtc_sortable_column');

// Add Custom Post Columns Filter
function rbtc_filter_column() {
	
	$filter_value = isset( $_GET['rbtc'] ) ? absint($_GET['rbtc']) : '';
	$values       = array(
		'0' => __('All Posts', 'rb-thumbnail-columns'),
		'1' => __('Thumbnail Posts', 'rb-thumbnail-columns'),
		'2' => __('No Thumbnail Posts', 'rb-thumbnail-columns'),
	);
	?>
    <select name="<?php echo esc_attr('rbtc'); ?>">
		<?php
		foreach ( $values as $key => $value ) {
			printf( 
				"<option value='%s' %s>%s</option>", 
				esc_attr($key),
				$key == $filter_value ? "selected = 'selected'" : '',
				esc_html($value, 'rb-thumbnail-columns')
			);
		}
		?>
    </select>
	<?php
}
add_action('restrict_manage_posts', 'rbtc_filter_column');

// Add Custom Post Columns Filter Value
function rbtc_filter_data($rbtc_query) {
	if(!is_admin()){
		return;
	}
	$filter_value = isset( $_GET['rbtc'] ) ? absint($_GET['rbtc']) : '';

	if ( '1' == $filter_value ) {
		$rbtc_query->set( 'meta_query', array(
			array(
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS'
			)
		) );
	} else if ( '2' == $filter_value ) {
		$rbtc_query->set( 'meta_query', array(
			array(
				'key'     => '_thumbnail_id',
				'compare' => 'NOT EXISTS'
			)
		) );
	}

	$rbtc_orderby = $rbtc_query->get('orderby');
	if('rbtc_post_view' === $rbtc_orderby){
		$rbtc_query->set('meta_key','rbtc_post_view');
		$rbtc_query->set('orberby','meta_value_num');
	}

}
add_action('pre_get_posts', 'rbtc_filter_data');