// adds how many times the product been sold in a custom column
add_filter( 'manage_edit-product_columns', 'droid_add_total_sales_to_product_list', 20 );
add_action( 'manage_posts_custom_column', 'droid_display_total_sales_in_product_list' );
add_filter('manage_edit-product_sortable_columns', 'droid_sort_total_sales_product_list');
add_action( 'pre_get_posts', 'droid_total_sales_query_product_list' );

function droid_add_total_sales_to_product_list( $col_th ) {
	return wp_parse_args( array( 'total_sales' => 'Total Sales' ), $col_th );
}

function droid_display_total_sales_in_product_list( $column_id ) {
	if( $column_id  == 'total_sales' )
		echo get_post_meta( get_the_ID(), 'total_sales', true );
}
 
function droid_sort_total_sales_product_list( $a ){
	return wp_parse_args( array( 'total_sales' => 'by_total_sales' ), $a );
}

function droid_total_sales_query_product_list( $query ) {
	if( !is_admin() || empty( $_GET['orderby']) || empty( $_GET['order'] ) )
		return;

	if( $_GET['orderby'] == 'by_total_sales' ) {
		$query->set('meta_key', 'total_sales' );
		$query->set('orderby', 'meta_value_num');
		$query->set('order', $_GET['order'] );
	}
	return $query;
}

// adds the purchase price and the profit margin
add_action('woocommerce_product_options_general_product_data', 'droid_add_product_purchase_price_field');
add_action('woocommerce_process_product_meta', 'droid_save_product_purchase_price_field', 10, 2);
add_action('woocommerce_product_after_variable_attributes', 'droid_add_variable_product_purchase_price_field', 10, 3);
add_action('woocommerce_save_product_variation', 'droid_save_variable_product_purchase_price_field', 10, 2);

function droid_add_product_purchase_price_field() {
	$currency = get_woocommerce_currency_symbol();
	woocommerce_wp_text_input(
		  array(
			  'id' => '_purchase_price',
			  'class' => '',
			  'wrapper_class' => 'pricing show_if_simple show_if_external',
			  'label' => __("Purchase Price", 'products-purchase-price-for-woocommerce') . " ($currency)",
			  'data_type' => 'price',
			  'desc_tip' => true,
			  'description' => __('Purchase cost, e.g: 79', 'products-purchase-price-for-woocommerce'),
		  )
	);
}

function droid_save_product_purchase_price_field($post_id, $post) {
	if (isset($_POST['_purchase_price'])) {
		$purchase_price = ($_POST['_purchase_price'] === '' ) ? '' : wc_format_decimal($_POST['_purchase_price']);
		update_post_meta($post_id, '_purchase_price', $purchase_price);
	}
}

function droid_add_variable_product_purchase_price_field($loop, $variation_data, $variation) {
	$currency = get_woocommerce_currency_symbol();
	woocommerce_wp_text_input(array(
		'id' => 'variable_purchase_price[' . $loop . ']',
		'wrapper_class' => 'form-row form-row-first',
		'label' => __("Purchase Price", 'products-purchase-price-for-woocommerce') . " ($currency)",
		'placeholder' => 'Purchase cost, e.g: 79',
		'data_type' => 'price',
		'desc_tip' => false,
		'value' => get_post_meta($variation->ID, '_purchase_price', true)
	));
}

function droid_save_variable_product_purchase_price_field($variation_id, $i) {
	if (isset($_POST['variable_purchase_price'][$i])) {
		$purchase_price = ($_POST['variable_purchase_price'][$i] === '' ) ? '' : wc_format_decimal($_POST['variable_purchase_price'][$i]);
		update_post_meta($variation_id, '_purchase_price', $purchase_price);
	}
}

add_filter( 'manage_edit-product_columns', 'droid_add_purchase_price_product_column', 11);
function droid_add_purchase_price_product_column( $columns )
{
	$columns['_purchase_price'] = __( 'Cost','woocommerce');
	return $columns;
}

add_action( 'manage_product_posts_custom_column' , 'droid_display_purchase_price_in_column', 10, 2 );
function droid_display_purchase_price_in_column( $column, $product_id )
{
    global $post;
    $purchase_price = get_post_meta( $product_id, '_purchase_price', true );
    switch ( $column )
    {
        case '_purchase_price' :
            echo get_woocommerce_currency_symbol() . $purchase_price;
            break;
    }
}

add_filter( 'manage_edit-product_columns', 'droid_add_net_winnings_product_column', 11);
function droid_add_net_winnings_product_column($columns)
{
   $columns['net_winnings'] = __( 'Profit','woocommerce');
   return $columns;
}

add_action( 'manage_product_posts_custom_column' , 'droid_net_winnings_display', 10, 2 );
function droid_net_winnings_display( $column, $product_id ){
    if( $column  == 'net_winnings' ) {
        global $product;

        if( ! $product && ! is_object($product) ){
            $product = wc_get_product( $product_id );
        }

        $purchase_price = (float) $product->get_meta('_purchase_price' );
        $regular_price  = (float) $product->get_regular_price();
        $net_winning    = $purchase_price != 0 ? $regular_price - $purchase_price : 0;
        echo wc_price($net_winning);
    }
}

add_filter( "manage_edit-product_sortable_columns", 'droid_make_purchase_price_column_sortable' );
function droid_make_purchase_price_column_sortable( $columns )
{
    $custom = array(
        '_purchase_price'    => 'Cost',
        'net_winnings'    => 'Profit',
	);
    return wp_parse_args( $custom, $columns );
}
