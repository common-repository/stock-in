<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Stock_in_Settings class.
 * 
 * @class Stock_in_Settings
 */
class Stock_in_Settings {

	private $tabs;
	

	public function __construct() {
		// actions
		add_action( 'admin_menu', array( $this, 'admin_menu_options' ) );
		add_action( 'after_setup_theme', array( $this, 'load_defaults' ) );
	}

	/**
	 * Load default settings.
	 */
	public function load_defaults() {

		if ( ! is_admin() )
			return;

	
		$this->tabs = array(
			'general'	 => array(
				'name'	 => __( 'Stock in ', 'stock_in' ),
				'key'	 => 'stock_in_general',
				
			),
			'history'	 => array(
				'name'	 => __( 'Stock History', 'stock_in' ),
				'key'	 => 'stock_in_history',
				
			),
			'update'	 => array(
			//	'name'	 => __( 'Stock Update', 'stock_in' ),
				'key'	 => 'stock_in_update',
				
			)
			
		);

	}

	

	/**
	 * Add options page.
	 */
	public function admin_menu_options() {
		add_menu_page(
		__( 'Stock in', 'stock_in' ), __( 'Stock in', 'stock_in' ), 'edit_posts', 'stock_in', array( $this, 'options_page' )
		);
	}

	/**
	 * Options page callback.
	 * 
	 * @return mixed
	 */
	public function options_page() {
		$tab_key = (isset( $_GET['tab'] ) ? $_GET['tab'] : 'general');

		echo '
	    <div class="wrap">' . screen_icon() . '
		<h2>' . __( 'Stock in - Status', 'stock_in' ) . '</h2>
		<h2 class="nav-tab-wrapper">';

		foreach ( $this->tabs as $key => $name ) {
			echo '
		    <a class="nav-tab ' . ($tab_key == $key ? 'nav-tab-active' : '') . '" href="' . esc_url( admin_url( 'admin.php?page=stock_in&tab=' . $key ) ) . '">' . $name['name'] . '</a>';
		}
		echo 		'</h2><div class="stock_in-settings">
		    <div class="df-credits">';
if($this->tabs[$tab_key]['key'] ==='stock_in_general'){
	 global $wpdb;
			$author_id=get_current_user_id();
			
	
	if(!isset( $_GET['sort'] )){
			$sort = 'DESC';
			$sort_link = 'DESC';
		}
			
	if(isset( $_GET['sort'] )){
		if($_GET['sort']=='ASC'){
			$sort = 'ASC';
			$sort_link = 'DESC';
		}else if($_GET['sort']=='DESC'){
			$sort= 'DESC';
			$sort_link= 'ASC';
		}
	}

?>
	<style>
	.widefat th {
    padding: 8px 10px;
	color:#555;
	font-weight:600;
	}
	</style>
	
	<p><form method="post">
	<input type="text" name='srch' id='srch' placeholder="Search by ID, Title" />
	<input type="submit" name='search' id='search' value="Search Product" class="button"/>
	</form>
	<?php 
	if (isset($_POST['search'])){
		$search = $_POST['srch'];
		echo 'Showing Results for "'. $search .'"';
	}
	?>
	</p>
<table class='widefat'>
<tr>
<th><span><a href="admin.php?page=stock_in&tab=general&sort=<?php echo $sort_link ?>">ID</a></span></th>
<th>Product Title</th>
<th>SKU</th>
<th>Current Stock</th>
<!--<th>Status</th>
<th>Date</th>-->
</tr>
<?php
global $wpdb;
$author_id=get_current_user_id();
$stock_in_all_products=$wpdb->prefix.'stock_in_all_products';
//$all_products = $wpdb->get_results("SELECT * FROM $stock_in_all_products	WHERE user_id = $author_id ORDER BY id DESC");

if(isset($_POST['search']))
{
	$search=$_POST['srch'];
	$products_query = "SELECT * FROM wp_posts WHERE post_type='product' AND  post_status='publish' AND (post_title LIKE '%$search%' OR ID LIKE '%$search%')";
}else{
$products_query = "SELECT * FROM wp_posts WHERE post_type='product' and post_status='publish'";
}
$query = $products_query;
 $total_query = "SELECT COUNT(1) FROM (${query}) AS combined_table";
    $total = $wpdb->get_var( $total_query );
    $items_per_page = 20;
	//$sort = isset( $_GET['sort'] ) ? 'DESC'	: 'ASC';
    $page = isset( $_GET['cpage'] ) ? abs((int)$_GET['cpage']) : 1;
    $offset = ( $page * $items_per_page ) - $items_per_page;
    $all_products_by_page = $wpdb->get_results( $query . " ORDER BY ID ${sort} LIMIT ${offset}, ${items_per_page}" );

	
$all_products = $wpdb->get_results($query);

if ( $all_products )
{
	foreach ( $all_products_by_page as $each_product )
	{?>
	<tr>
	<td><span><?php echo $each_product->ID;?></span></td>
	<td><span><?php echo $each_product->post_title;?></span> </td>
<?php
$product_id=$each_product->ID;
$product_sku = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE post_id=$product_id AND meta_key='_sku'");
$product_stock = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE post_id=$product_id AND meta_key='_stock'");
?>
	<td><span><?php echo $product_sku;?></span></td>
	<td><span><?php echo $product_stock;?></span></td>
	<td><span><a href='admin.php?page=stock_in&tab=update&product_id=<?php echo $product_id;?>'>Add Stock</a></span></td>
	<td><span><a href='admin.php?page=stock_in&tab=history&product_id=<?php echo $product_id;?>'>History</a></span></td>
	</tr>
	<tr><td colspan="6"><h3>
		<?php
	}
	 echo paginate_links( array(
        'base' => add_query_arg( 'cpage', '%#%' ),
        'format' => '',
        'prev_text' => __('&laquo;'),
        'next_text' => __('&raquo;'),
        'total' => ceil($total / $items_per_page),
        'current' => $page
    ));
	?>
	</h3> <span>Total <?php echo $total?> Products</span></td>
	</tr>
	<?php
}
else
{
	?>
	<h2>You didn't have any Records</h2>
	<?php
}
?>
</table>
		<?php
		}
		else if($this->tabs[$tab_key]['key'] ==='stock_in_history'){
			global $wpdb;
			$author_id=get_current_user_id();
			$product_id = $_GET['product_id'];
			
		if(!isset( $_GET['sort'] )){
			$sort = 'DESC';
			$sort_link = 'DESC';
		}
			
	if(isset( $_GET['sort'] )){
		if($_GET['sort']=='ASC'){
			$sort = 'ASC';
			$sort_link = 'DESC';
		}else if($_GET['sort']=='DESC'){
			$sort= 'DESC';
			$sort_link= 'ASC';
		}
	}
?>


	<style>
	.widefat th {
    padding: 8px 10px;
	color:#555;
	font-weight:600;
	}
	</style>
	
	<table class='widefat'>
<tr>
<th><span><a href="admin.php?page=stock_in&tab=history&sort=<?php echo $sort_link ?>">ID</a></span></th>
<th>Product ID</th>
<th>Product Title</th>
<th>Product SKU</th>
<th>Existing QTY</th>
<th>Added QTY</th>
<th>Reduced QTY</th>
<th>Stock After Update</th>
<th>Date</th>
<th>User/Order ID</th>
<th>Type</th>
<!--<th>Status</th>
<th>Date</th>-->
</tr>
<?php
global $wpdb;
$author_id=get_current_user_id();
$stock_in_history=$wpdb->prefix.'stock_in_history';
/*if(isset($_GET['product_id'])){
$stock_in_log = $wpdb->get_results("SELECT * FROM $stock_in_history	WHERE product_id = $product_id ORDER BY id DESC");
}else{
$stock_in_log = $wpdb->get_results("SELECT * FROM $stock_in_history	ORDER BY id DESC");
}*/


if(isset($_GET['product_id'])){
$stock_in_log = "SELECT * FROM $stock_in_history WHERE product_id = $product_id";
}else{
$stock_in_log = "SELECT * FROM $stock_in_history";
}

$query = $stock_in_log;
 $total_query = "SELECT COUNT(1) FROM (${query}) AS combined_table";
    $total = $wpdb->get_var( $total_query );
    $items_per_page = 20;
	//$sort = isset( $_GET['sort'] ) ? 'DESC'	: 'ASC';
    $page = isset( $_GET['cpage'] ) ? abs((int)$_GET['cpage']) : 1;
    $offset = ( $page * $items_per_page ) - $items_per_page;
    $stock_in_log_by_page = $wpdb->get_results( $query . " ORDER BY ID ${sort} LIMIT ${offset}, ${items_per_page}" );



if ( $stock_in_log_by_page )
{
	foreach ( $stock_in_log_by_page as $each_product_log )
	{
	$type = $each_product_log->type;
	$site_url=get_site_url();;
	if($type=='stock_out'){
		$type_style="color:red;";
		$type_text="Stock Out";
		$ip_text="<a href='$site_url/wp-admin/post.php?post=$each_product_log->ip&action=edit'/>Order ID: $each_product_log->ip</a>";
	}else{
		$type_style="color:green;";
		$type_text="Stock In";
		//$ip_text=$each_product_log->ip;
		$ip_text="<a href='$site_url/wp-admin/user-edit.php?user_id=$each_product_log->ip'/>User ID : $each_product_log->ip</a>";
	}
	?>
	<tr>
	<td><span><?php echo $each_product_log->id;?></span></td>
	<td><span><?php echo $each_product_log->product_id;?></span> </td>
	<?php 
	$product_id = $each_product_log->product_id;
	$product_title = $wpdb->get_var("SELECT post_title FROM wp_posts WHERE ID=$product_id ");
	$product_sku = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE post_id=$product_id AND meta_key='_sku'");?>
	<td><span><?php echo $product_title;?></span></td>	
	<td><span><?php echo $product_sku;?></span></td>	
	<td><span><?php echo $each_product_log->existing_qty;?></span> </td>
	<td><span><?php echo $each_product_log->added_qty;?></span> </td>
	<td><span><?php echo $each_product_log->reduced_qty;?></span> </td>
	<td><span><?php echo $each_product_log->stock_after_update;?></span> </td>
	
	<?php 
		$stock_in_history_date = $each_product_log->date;
		$stock_in_date = date('d-m-Y', strtotime($stock_in_history_date)); 
	?>
	<td><span><?php echo $stock_in_date;?></span> </td>
	
	<td>
	<span>
	<?php echo $ip_text; ?>
	</span> </td>
	<td style="<?php echo $type_style; ?>"><span><?php echo $type_text;?></span> </td>
	</tr>
		<tr><td colspan="5"><h3>
		<?php
	}
	 echo paginate_links( array(
        'base' => add_query_arg( 'cpage', '%#%' ),
        'format' => '',
        'prev_text' => __('&laquo;'),
        'next_text' => __('&raquo;'),
        'total' => ceil($total / $items_per_page),
        'current' => $page
    ));
	?>
	</h3> <span>Total <?php echo $total?> Records</span></td>
	</tr>
		<?php
	}	
else
{
	?>
	<h2>You didn't have any Records</h2>
	<?php
}
?>
</table>
<?php		
		}		
		else if($this->tabs[$tab_key]['key'] ==='stock_in_update' && isset($_GET['product_id'])){
			global $wpdb;
			$author_id=get_current_user_id();
			$product_id=$_GET['product_id'];
			$check_product = $wpdb->get_var("SELECT COUNT(*) FROM wp_posts WHERE ID=$product_id");			
			if($check_product==1){
				
$product_sku = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE post_id=$product_id AND meta_key='_sku'");
$product_stock = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE post_id=$product_id AND meta_key='_stock'");

$product_title = $wpdb->get_var("SELECT post_title FROM wp_posts WHERE ID=$product_id");

?>
		  <div id="dashboard-widgets">
	<div id="dashboard-widgets" class="metabox-holder">
	
	<div id="postbox-container-1" class="postbox-container">

<div id="dashboard_activity" class="postbox " >
<button type="button" class="handlediv button-link" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Activity</span><span class="toggle-indicator" aria-hidden="true"></span></button><h2 class='hndle'><span>Update Stock</span></h2>
<div class="inside">
<div id="activity-widget">
<div id="published-posts" class="activity-block">
<h2><?php echo $product_title ?></h2>
<form method="post">
<ul>
<li><span>Product ID : </span><?php echo $product_id; ?></li>
<li><span>SKU : </span><?php echo $product_sku; ?></li>
<li><span>Current Stock : </span><?php echo $product_stock; ?></li>
<li><span>Date : </span><?php echo date('d-m-Y'); ?></li>

	<li><span>Enter Stock : </span> <input type='text' name='stock_input' id='stock_input' placeholder=10 /></li>
<li><input type='submit' name='stock_edit' id='stock_edit' value='Update Stock' class='button'></li>
</form>

<?php		if(isset($_POST['stock_edit'])){
						global $wpdb;
				$new_stock=$_POST['stock_input'];
				$existing_Stock = $product_stock;
			if($new_stock > 0){
	//DIRECT UPDATE OF STOCK
    //update_post_meta($author_id, "_stock", $new_stock);
	
	//or
	//UPDATING OLD STOCK + NEW STOCK COUNT
	$stock_after_update = $product_stock + $new_stock;
	update_post_meta($product_id, "_stock", $stock_after_update);
	
	$stock_in_history=$wpdb->prefix.'stock_in_history';
$ip=$_SERVER['REMOTE_ADDR'];
$dt=date('d-m-Y');
$stime=date('Y-m-d H:i:s');

$wpdb->insert(
	$stock_in_history, 
	array(
		'product_id'=>$product_id,
		'sku'=>$product_sku,
		'existing_qty'=>$product_stock,
		'added_qty'=>$new_stock,
		'stock_after_update'=>$stock_after_update,
		//'dt'=>$dt,
		'date' => $stime, 
		//'ip'=>$ip,
		'ip'=>$author_id,
	)
		);
		echo "Success! You updated Stock - PID - $product_id : QTY : $new_stock : Date - $stime : IP - $ip";
			}
		}?>
	</ul>
	</div>
	</div>
	</div>
	
	</div>
	
	</div>
	</div>
	
	</div>
	
<?php			}
else
{
	?>
	<h2>You didn't have any Records</h2>
	<?php
}
?>
</table>
<?php		
		}
		?>
</div>
	    <div class="clear"></div>
	    </div>
		
		<div class="inside">
			    <h4 class="inner">Need support?</h4>
			    <p class="inner">If you are having problems in Stock in, please Contact us about them from <a href="http://gtptc.com/site/contact/" target="_blank" title="Contact">Contact</a></p>
				</div>
		    </div>
	
	<?php }

}
