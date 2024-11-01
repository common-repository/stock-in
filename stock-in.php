<?php
/**
 * @package Stock in & out
 * @version 1.0.4
 */
/*
Plugin Name: Stock in & out
Plugin URI: http://gtptc.com
Description: Stock in Plugin for Managing Stock & Keep Records of Each Update of Stock
Version: 1.0.4
Author: Kasi Raviteja
Author URI: http://gtptc.com/
License: GPLv3 or later
*/

/*
Copyright (C) 2018  Kasi Raviteja (email : burugupallikasiraviteja@gmail.com)
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
    exit;

if ( ! class_exists( 'stock_in' ) ) :

	/**
	 * stock_in final class.
	 *
	 * @class stock_in
	 * @version	1.0
	 */
	final class Stock_in {

		private static $instance;
		public $options;
		public $defaults = array(
			 'general'	 => array(
				'exclude_ips'			 => array(),
				'restrict_edit_views'	 => false,
				'deactivation_delete'	 => false,
				'cron_run'				 => true,
				'cron_update'			 => true
			), 

			'version'	 => '1.0'
		);
		
		/**
		 * Disable object clone.
		 */
		private function __clone() {
			
		}

		/**
		 * Disable unserializing of the class.
		 */
		private function __wakeup() {
			
		}

		/**
		 * Main plugin instance,
		 * Insures that only one instance of the plugin exists in memory at one time.
		 * 
		 * @return object
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Stock_in ) ) {
				self::$instance = new Stock_in;
				self::$instance->define_constants();


				self::$instance->includes();
				self::$instance->settings = new Stock_in_Settings();
			}
			return self::$instance;
		}

		/**
		 * Setup plugin constants.
		 *
		 * @return void
		 */
		private function define_constants() {
			define( 'STOCK_IN_URL', plugins_url( '', __FILE__ ) );
			define( 'STOCK_IN_PATH', plugin_dir_path( __FILE__ ) );
			define( 'STOCK_IN_REL_PATH', dirname( plugin_basename( __FILE__ ) ) . '/' );
		}

		/**
		 * Include required files.
		 *
		 * @return void
		 */
		private function includes() {
			include_once( STOCK_IN_PATH . 'includes/settings.php' );
			
		 }

		/**
		 * Class constructor.
		 * 
		 * @return void
		 */
		public function __construct() {
			register_activation_hook( __FILE__, array( $this, 'activation' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );

			// settings
/* 			$this->options = array(
				'general'	 => array_merge( $this->defaults['general'], get_option( 'stock_in_settings_general', $this->defaults['general'] ) ),
				//'display'	 => array_merge( $this->defaults['display'], get_option( 'stock_in_settings_display', $this->defaults['display'] ) )
			); */

			
		}

		/**
		 * Plugin activation function.
		 */
		public function activation() {
			global $wpdb, $charset_collate;

			// required for dbdelta
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			// create stock_in_views table
			dbDelta( '
		CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'stock_in_history (
		    id bigint unsigned AUTO_INCREMENT NOT NULL,
		    product_id bigint unsigned NOT NULL,
		    sku varchar(100),
			existing_qty bigint unsigned NOT NULL,
			added_qty bigint unsigned NOT NULL,
			reduced_qty bigint unsigned NOT NULL,
			stock_after_update bigint unsigned NOT NULL,
		    date datetime NOT NULL,
			ip varchar(100) NOT NULL,
			type ENUM("stock_in","stock_out") NOT NULL DEFAULT "stock_in",
		    PRIMARY KEY  (id),
		    UNIQUE INDEX id_postid (id, product_id) USING BTREE,
		    INDEX ip_postid_dt (ip, product_id, date) USING BTREE
		) ' . $charset_collate . ';'
			);
			
			
			

update_option(MY_DB_VERSION, $db_version);
			/* create stock_in_views table
			dbDelta( '
		CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'stock_in_payouts (
		    id bigint unsigned AUTO_INCREMENT NOT NULL,
		    user_id bigint unsigned NOT NULL,
		    username varchar(255) NOT NULL,
		    processor enum("paypal", "Bank") NOT NULL DEFAULT "paypal",
		    payingto varchar(255)  NULL,
		    amount decimal(16,2) NOT NULL,
		    status enum("paid", "pending") NOT NULL DEFAULT "pending",
		    dt varchar(10) NULL,
		    stime varchar(100) NULL,
		    ip varchar(100) NULL,
		    PRIMARY KEY  (id, user_id)
		) ' . $charset_collate . ';'
			);
*/
			// add default options
			add_option( 'stock_in_settings_general', $this->defaults['general'], '', 'no' );
			add_option( 'stock_in_settings_display', $this->defaults['display'], '', 'no' );
			add_option( 'stock_in_version', $this->defaults['version'], '', 'no' );

			// schedule cache flush
			$this->schedule_cache_flush();
		}

		/**
		 * Plugin deactivation function.
		 */
		public function deactivation() {
			// delete default options
			if ( $this->options['general']['deactivation_delete'] ) {
				delete_option( 'stock_in_general' );
				delete_option( 'stock_in_history' );

				global $wpdb;

				// delete table from database
				$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'stock_in_views' );
			}

			// remove schedule
			wp_clear_scheduled_hook( 'stock_in_reset_counts' );
			remove_action( 'stock_in_reset_counts', array( stock_in()->cron, 'reset_counts' ) );

			$this->remove_cache_flush();
		}

		/**
		 * Schedule cache flushing if it's not already scheduled.
		 * 
		 * @param bool $forced
		 */
		public function schedule_cache_flush( $forced = true ) {
			if ( $forced || ! wp_next_scheduled( 'stock_in_flush_cached_counts' ) ) {
				wp_schedule_event( time(), 'stock_in_flush_interval', 'stock_in_flush_cached_counts' );
			}
		}

		/**
		 * Remove scheduled cache flush and the corresponding action.
		 */
		public function remove_cache_flush() {
			wp_clear_scheduled_hook( 'stock_in_flush_cached_counts' );
			remove_action( 'stock_in_flush_cached_counts', array( stock_in()->cron, 'flush_cached_counts' ) );
		}


		

	}

endif; // end if class_exists check

/**
 * Initialise stock_in.
 * 
 * @return object
 */
function stock_in() {
	static $instance;

	// first call to instance() initializes the plugin
	if ( $instance === null || ! ( $instance instanceof stock_in ) )
		$instance = stock_in::instance();

	return $instance;
}

stock_in();

//ADDING VIEWS WHEN POST VISITS
function add_stock_in_views() {
	   if(is_single()) {
		global $wpdb,$post;
		$post_id=$post->ID;
		$post_author=$post->post_author;

$stock_in_views=$wpdb->prefix.'stock_in_views';
$ip=$_SERVER['REMOTE_ADDR'];$dt=date('d-m-Y');
$postviews= $wpdb->get_var("SELECT COUNT(*) FROM $stock_in_views WHERE postid = $post_id AND dt = '$dt' AND ip='$ip'");
 if($postviews==0){
$wpdb->insert( 
	$stock_in_views, 
	array( 
	    'ip'=>$ip,
		'postid'=>$post_id,
		'dt'=>$dt,
	)
);
$current_views = get_user_meta($post_author, "current_monetize_views",true);
    if(!isset($current_views) OR empty($current_views) OR !is_numeric($current_views) ) {
         $current_views = 0;
      }
      $new_views = $current_views + 1;
       update_user_meta($post_author, "current_monetize_views", $new_views);
	   
	$total_views = get_user_meta($post_author, "total_monetize_views",true);
	if(!isset($total_views) OR empty($total_views) OR !is_numeric($total_views) ) {
         $total_views = 0;
    }
	$new_total_views = $total_views + 1;
    update_user_meta($post_author, "total_monetize_views", $new_total_views);
}
}
	}
//add_action("wp_footer", "add_stock_in_views");	

/*STOCK OUT*/
// run the action 
//Action to validate
//add_action('woocommerce_after_checkout_validation', 'after_checkout_otp_validation');
add_action('woocommerce_checkout_order_processed', 'after_checkout_otp_validation', 10, 1);
//The function
function after_checkout_otp_validation( $order_id ) {
 if ( ! $order_id )
        return;

    // Getting an instance of the order object
    $order = wc_get_order( $order_id );

    if($order->is_paid())
        $paid = 'yes';
    else
        $paid = 'no';

    // iterating through each order items (getting product ID and the product object) 
    // (work for simple and variable products)
    foreach ( $order->get_items() as $item_id => $item ) {

        if( $item['variation_id'] > 0 ){
            $product_id = $item['variation_id']; // variable product
			$product_qty = $item['qty']; // variable product

        } else {
            $product_id = $item['product_id']; // simple product
            $product_qty = $item['qty']; // simple product
        }
	//$purchased_stock = $order->get_item_count();
	$purchased_stock = $product_qty;

		global $wpdb;
		$product_sku = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE post_id=$product_id AND meta_key='_sku'");
		$product_stock = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE post_id=$product_id AND meta_key='_stock'");

    // you can use wc_add_notice with a second parameter as "error" to stop the order from being placed
			if($purchased_stock > 0){
	//DIRECT UPDATE OF STOCK
    //update_post_meta($author_id, "_stock", $new_stock);
	
	//or
	//UPDATING OLD STOCK + NEW STOCK COUNT
	$stock_after_update = $product_stock - $purchased_stock;
	//update_post_meta($product_id, "_stock", $stock_after_update);
	
	$stock_in_history=$wpdb->prefix.'stock_in_history';
$ip=$_SERVER['REMOTE_ADDR'];
$dt=date('d-m-Y');
$stime=date('Y-m-d H:i:s');

//$order_user = $order->get_user();
$order_user = $order_id;
//print_r($order_user);

$wpdb->insert(
	$stock_in_history, 
	array(
		'product_id'=>$product_id,
		'sku'=>$product_sku,
		'existing_qty'=>$product_stock,
		'reduced_qty'=>$purchased_stock,
		'stock_after_update'=>$stock_after_update,
		//'dt'=>$dt,
		'date' => $stime, 
		//'ip'=>$ip,
		'ip'=>$order_user,
		'type'=>'stock_out',
	)
		);
		echo "Success! You updated Stock - PID - $product_id : QTY : $new_stock : Date - $stime : IP - $ip";
			}
	}
			// you can use wc_add_notice with a second parameter as "error" to stop the order from being placed
   /* if (error) {
         wc_add_notice( __( "Incorrect OTP!", 'text-domain' ), 'error' );
    }*/
};


// remove the action 
//remove_action( 'woocommerce_order_status_completed', 'action_woocommerce_order_status_completed', 10, 1 );
?>
