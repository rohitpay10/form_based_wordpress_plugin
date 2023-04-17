<?php
/**
 * Plugin Name: Pay10 Payment Form
 * Plugin URI: https://github.com/rohitpay10/
 * Description: This plugin allow you to accept form payments using Pay10. This plugin will add a simple form that user will fill, when he clicks on submit he will redirected to payten website to complete his transaction and on completion his payment, Pay10 will send that user back to your website along with transactions details. This plugin uses server-to-server verification to add additional security layer for validating transactions. Admin can also see all transaction details with payment status by going to "Pay10 Payment Details" from menu in admin.
 * Version: 1.0
 * Author: Pay10 ( Rohit Kumar Singh )
 * Author URI: http://pay10.com/
 * Text Domain: Pay10 Payments
 */

//ini_set('display_errors','On');
register_activation_hook(__FILE__, 'payten_activation');
register_deactivation_hook(__FILE__, 'payten_deactivation');

// do not conflict with WooCommerce payten Plugin Callback
if(!isset($_GET["wc-api"])){
	add_action('init', 'payten_form_response');
}

add_shortcode( 'pay10form', 'payten_form_handler' );

add_action( 'wp_footer', function() {
   if ( !empty($_POST['RESPONSE_CODE'] )) {
$current_url=current_location();
      // fire the custom action

if ($_POST['STATUS']=="Captured") {
	echo "<script>swal({
     title: 'Wow!',
     text: 'We Have received your payment.',
     icon: 'success',
     type: 'success'
 }).then(function() {
     window.location = '$url';
 });</script>";
}
    else{
    	echo "<script>swal({
     title: 'Wow!',
     text: 'We have not received your payment.',
     icon: 'error',
     type: 'failure'
 }).then(function() {
     window.location = '$current_url';
 });</script>";
    }	
     
   }
} );


if(isset($_GET['form_msg']) && $_GET['form_msg'] != ""){
	add_action('the_content', 'paytenFormShowMessage');
}

function paytenFormShowMessage($content){
	return '<div class="box">'.htmlentities(urldecode($_GET['form_msg'])).'</div>'.$content;
}
	
// css for admin

add_action('admin_head', 'my_custom_fonts');
function my_custom_fonts() {
  echo '<style>
   .toplevel_page_payten_options_page img{
      width:19px;
    } 
  </style>';
}

function current_location()
{
    if (isset($_SERVER['HTTPS']) &&
        ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

//Adding script

function wpdocs_theme_name_scripts() {
    wp_enqueue_script( 'sweetalert', 'https://unpkg.com/sweetalert/dist/sweetalert.min.js', array(), '1.0.0', false );
}
add_action( 'wp_enqueue_scripts', 'wpdocs_theme_name_scripts' );

//adding css

 wp_register_style( 'pay10formstyle', plugins_url('css/pay10_form_style.css',__FILE__ ) );
 wp_enqueue_style('pay10formstyle');



function payten_activation() {
	global $wpdb, $wp_rewrite;
	$settings = payten_settings_list();
	foreach ($settings as $setting) {
		add_option($setting['name'], $setting['value']);
	}
	add_option( 'payten_form_details_url', '', '', 'yes' );
	$post_date = date( "Y-m-d H:i:s" );
	$post_date_gmt = gmdate( "Y-m-d H:i:s" );

	$ebs_pages = array(
		'payten-page' => array(
			'name' => 'payten Transaction Details page',
			'title' => 'payten Transaction Details page',
			'tag' => '[payten_form_details]',
			'option' => 'payten_form_details_url'
		),
	);
	
	$newpages = false;
	
	$payten_page_id = $wpdb->get_var("SELECT id FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%" . $payten_pages['payten-page']['tag'] . "%'	AND `post_type` != 'revision'");
	if(empty($payten_page_id)){
		$payten_page_id = wp_insert_post( array(
			'post_title'	=>	$payten_pages['payten-page']['title'],
			'post_type'		=>	'page',
			'post_name'		=>	$payten_pages['payten-page']['name'],
			'comment_status'=> 'closed',
			'ping_status'	=>	'closed',
			'post_content' =>	$payten_pages['payten-page']['tag'],
			'post_status'	=>	'publish',
			'post_author'	=>	1,
			'menu_order'	=>	0
		));
		$newpages = true;
	}

	update_option( $payten_pages['payten-page']['option'], _get_page_link($payten_page_id) );
	unset($payten_pages['payten-page']);
	
	$table_name = $wpdb->prefix . "payten_form";
	$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
				`id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`name` varchar(255),
				`email` varchar(255),
				`phone` varchar(255),
				`address` varchar(255),
				`city` varchar(255),
				`country` varchar(255),
				`state` varchar(255),
				`order_id` varchar(255),
				`zip` varchar(255),
				`amount` varchar(255),
				`payment_status` varchar(255),
				`date` datetime
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);

	if($newpages){
		wp_cache_delete( 'all_page_ids', 'pages' );
		$wp_rewrite->flush_rules();
	}
}

function payten_deactivation() {
	$settings = payten_settings_list();
	foreach ($settings as $setting) {
		delete_option($setting['name']);
	}
}

function payten_settings_list(){
	$settings = array(
		array(
			'display' => 'Merchant ID',
			'name'    => 'payten_merchant_id',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant Id Provided by Pay10'
		),
		array(
			'display' => 'Merchant Key',
			'name'    => 'payten_merchant_key',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant Secret Key Provided by Pay10'
		),
		array(
			'display' => 'Merchant Hosted Key',
			'name'    => 'payten_merchant_hosted_key',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant hosted key provided by Pay10'
		),

		array(
			'display' => 'Transaction URL',
			'name'    => 'transaction_url',
			'value'   => 'https://secure.pay10.com/pgui/jsp/paymentrequest',
			'type'    => 'textbox',
			'hint'    => 'Transaction URL Provided by Pay10'
		),

		array(
			'display' => 'Default Amount',
			'name'    => 'payten_amount',
			'value'   => '100',
			'type'    => 'textbox',
			'hint'    => 'the default form amount, WITHOUT currency signs -- ie. 100'
		),
		array(
			'display' => 'Default Button/Link Text',
			'name'    => 'payten_content',
			'value'   => 'Pay Now',
			'type'    => 'textbox',
			'hint'    => 'the default text to be used for buttons or links if none is provided'
		),
	
		array(
			'display' => 'Form Fields : ( Enable/Disable )',
			'name'    => 'payten_form_address',
			'value'   => '1',
			'type'    => 'checkbox',
			'hint'    => 'Address'
		),
		array(
			'name'    => 'payten_form_city',
			'value'   => '1',
			'type'    => 'checkbox',
			'hint'    => 'City'
		),
		array(
			'name'    => 'payten_form_state',
			'value'   => '1',
			'type'    => 'checkbox',
			'hint'    => 'State'
		),	
		array(
			'name'    => 'payten_form_pincode',
			'value'   => '1',
			'type'    => 'checkbox',
			'hint'    => 'Pincode'
		),
		array(
			'name'    => 'payten_form_country',
			'value'   => '1',
			'type'    => 'checkbox',
			'hint'    => 'Country'
		)									
	);
	return $settings;
}


if (is_admin()) {
	add_action( 'admin_menu', 'payten_admin_menu' );
	add_action( 'admin_init', 'payten_register_settings' );
}


function payten_admin_menu() {
	add_menu_page('payten Form', 'Pay10 Form', 'manage_options', 'payten_options_page', 'payten_options_page', plugin_dir_url(__FILE__).'assets/logo.ico');

	add_submenu_page('payten_options_page', 'payten Form Settings', 'Settings', 'manage_options', 'payten_options_page');

	add_submenu_page('payten_options_page', 'payten Form Payment Details', 'Payment Details', 'manage_options', 'wp_payten_form', 'wp_payten_form_listings_page');
	
	require_once(dirname(__FILE__) . '/payten-form-listings.php');
}


function payten_options_page() {
	$field_name =array("Address","City","State","Pincode","Country");
	$i=0;

	echo	'<div class="wrap">
				<h1>Pay10 Configuarations</h1>
				<form method="post" action="options.php">';
					wp_nonce_field('update-options');
					echo '<table class="form-table">';
						$settings = payten_settings_list();
						foreach($settings as $setting){
						echo '<tr valign="top"><th scope="row">'.$setting['display'].'</th><td>';
              
							if ($setting['type']=='radio') {
								echo $setting['yes'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="1" '.(get_option($setting['name']) == 1 ? 'checked="checked"' : "").' />';
								echo $setting['no'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="0" '.(get_option($setting['name']) == 0 ? 'checked="checked"' : "").' />';
		
							}

                            elseif ($setting['type']=='checkbox') {
								echo $field_name[$i].' <input class="pay10-ui-toggle" id="field-id" type="'.$setting['type'].'" name="'.$setting['name'].'" value="1" '.(get_option($setting['name']) == 1 ? 'checked="checked"' : "").' />';$i++;
								//echo "No".$setting['no'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="0" '.(get_option($setting['name']) == 0 ? 'checked="checked"' : "").' />';
		
							} 
							 elseif ($setting['type']=='select') {
								echo '<select name="'.$setting['name'].'">';
								foreach ($setting['values'] as $value=>$name) {
									echo '<option value="'.$value.'" ' .(get_option($setting['name'])==$value? '  selected="selected"' : ''). '>'.$name.'</option>';
								}
								echo '</select>';

							} else {
								echo '<input type="'.$setting['type'].'" name="'.$setting['name'].'" value="'.get_option($setting['name']).'" />';
							}

							echo '<p class="description" id="tagline-description">'.$setting['hint'].'</p>';
							echo '</td></tr>';
						}

						echo '<tr>
									<td colspan="2" align="center">
										<input type="submit" class="button-primary" value="Save Changes" />
										<input type="hidden" name="action" value="update" />';
										echo '<input type="hidden" name="page_options" value="';
										foreach ($settings as $setting) {
											echo $setting['name'].',';
										}
										echo '" />
									</td>
								</tr>';
						echo '</table>
						    </form>';

			$last_updated = "";
			$path = plugin_dir_path( __FILE__ ) . "/payten_version.txt";
			if(file_exists($path)){
				$handle = fopen($path, "r");
				if($handle !== false){
					$date = fread($handle, 10); // i.e. DD-MM-YYYY or 25-04-2018
					$last_updated = '<p>Last Updated: '. date("d F Y", strtotime($date)) .'</p>';
				}
			}

			include( ABSPATH . WPINC . '/version.php' );
			$footer_text = '<hr/><div class="text-center">'.$last_updated.'<p>Wordpress Version: '. $wp_version .'</p></div><hr/>';

			echo $footer_text.'</div>';
}


function payten_register_settings() {
	$settings = payten_settings_list();
	foreach ($settings as $setting) {
		register_setting($setting['name'], $setting['value']);
	}
}

function payten_form_handler(){

	if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "payten_form_request"){
		return payten_form_submit();
	} else {
		return payten_form_form();
	}
}

function payten_form_form(){
	$current_url = "//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$html = ""; 
	$html = '<form name="frmTransaction" method="post">
					<p class="form-row">
						<label for="form_name">Name:</label><br>
						<input type="text" style="width:60%;height:25px;" name="form_name" class="left" maxlength="255" value="" required/>
					</p>
					<p>
						<label for="form_email">Email:</label><br>
						<input type="text" style="width:60%;height:25px;" name="form_email" maxlength="255" value="" required />
					</p>
					<p>
						<label for="form_phone">Phone:</label><br>
						<input type="text" style="width:60%;height:25px;" name="form_phone" maxlength="15" value="" required />
					</p>
					<p>
						<label for="form_amount">Amount:</label><br>
						<input type="text" style="width:60%;height:25px;" name="form_amount"  maxlength="10" value="'.trim(get_option('payten_amount')).'" required />
					</p>
					<p style="display:'.(trim(get_option('payten_form_address'))==0 ? 'none':'block').'">
						<label for="form_address">Address:</label><br>
						<input type="text" style="width:60%;height:25px;" name="form_address" maxlength="255" value=""/>
					</p>
					<p style="display:'.(trim(get_option('payten_form_city'))==0 ? 'none':'block').'">
						<label for="form_city">City:</label><br>
						<input type="text" style="width:60%;height:25px;" name="form_city" maxlength="255" value=""  />
					</p>
					<p style="display:'.(trim(get_option('payten_form_state'))==0 ? 'none':'block').'">
						<label for="form_state">State:</label><br>
						<input type="text" style="width:60%;height:25px;" name="form_state" maxlength="255" value=""  />
					</p>
					<p style="display:'.(trim(get_option('payten_form_pincode'))==0 ? 'none':'block').'">
						<label for="form_postal_code">Postal Code:</label><br>
						<input type="text" style="width:60%;height:25px;" name="form_postal_code" maxlength="10" value=""   />
					</p>
					<p style="display:'.(trim(get_option('payten_form_country'))==0 ? 'none':'block').'">
						<label for="form_country">Country:</label><br>
						<input type="text" style="width:60%;height:25px;" name="form_country" maxlength="255" value=""  />
					</p>
					<p>
						<input type="hidden" name="action" value="payten_form_request">
						<input type="submit" style="background-color:#ed1b2e;color: white;border: none;padding: 10px 30px;cursor: pointer;" value="' . ((trim(get_option('payten_content'))!='')?trim(get_option('payten_content')):'Pay Now') .'"  />
					</p>
				</form>';
	
	return $html;
}


function payten_form_submit(){

	$valid = true; // default input validation flag
	$html = '';
	$msg = '';
		//print_r($_POST);exit;	
	if( trim($_POST['form_name']) != ''){
	    $form_name = $_POST['form_name'];
	} else {
		$valid = false;
		$msg.= 'Name is required </br>';
	}
			
	if( trim($_POST['form_email']) != ''){
		$form_email = $_POST['form_email'];
		if( preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/" , $form_email)){}
		else{
			$valid = false;
			$msg.= 'Invalid email format </br>';
		}
	} else {
		$valid = false;
		$msg.= 'E-mail is required </br>';
	}
				
	if( trim($_POST['form_amount']) != ' ' || trim($_POST['form_amount'])<= 0){
		$form_amount = $_POST['form_amount'];
		if( (is_numeric($form_amount)) && ( (strlen($form_amount) > '1') || (strlen($form_amount) == '1')) ){}
		else{
			$valid = false;
			$msg.= 'Amount cannot be less then  ₹1</br>';
		}
	} else {
		$valid = false;
		$msg.= 'Amount is required </br>';
	}
//echo $valid;print_r($_POST);exit;
	if($valid){
		
		require_once(dirname(__FILE__) . '/lib/bppg_helper.php');
		global $wpdb;

		$order_id='Pay10_'.date('dmyHis').rand(10,1000);

		$table_name = $wpdb->prefix . "payten_form";
		$data = array(
					'name' => sanitize_text_field($_POST['form_name']),
					'email' => sanitize_email($_POST['form_email']),
					'phone' => sanitize_text_field($_POST['form_phone']),
					'address' => sanitize_text_field($_POST['form_address']),
					'city' => sanitize_text_field($_POST['form_city']),
					'country' => sanitize_text_field($_POST['form_country']),
					'state' => sanitize_text_field($_POST['form_state']),
					'order_id' => sanitize_text_field($order_id),
					'zip' => sanitize_text_field($_POST['form_postal_code']),
					'amount' => sanitize_text_field($_POST['form_amount']),
					'payment_status' => 'Pending',
					'date' => date('Y-m-d H:i:s'),
				);

		$result = $wpdb->insert($table_name, $data);

		if(!$result){
			throw new Exception($wpdb->last_error);
		}


		//End Post Request
 
 		$post_params = array(
			'PAY_ID' => trim(get_option('payten_merchant_id')),
				'ORDER_ID' =>$order_id,
				'RETURN_URL' =>get_permalink(),
				'CUST_EMAIL'=>sanitize_email($_POST['form_email']),
				'CUST_NAME' =>sanitize_text_field($_POST['form_name']),
				'CUST_STREET_ADDRESS1'=>sanitize_text_field($_POST['form_address']),
				'CUST_CITY' =>sanitize_text_field($_POST['form_city']),
				'CUST_STATE' => sanitize_text_field($_POST['form_state']),
				'CUST_COUNTRY' =>sanitize_text_field($_POST['form_country']),
				'CUST_ZIP' =>sanitize_text_field($_POST['form_postal_code']),
				'CUST_PHONE'=>sanitize_text_field($_POST['form_phone']),
				'CURRENCY_CODE' =>356,
				'AMOUNT'        =>sanitize_text_field($_POST['form_amount']*100),
				'PRODUCT_DESC' =>'Form Collection' ,
				'CUST_SHIP_STREET_ADDRESS1' =>'',
				'CUST_SHIP_CITY'  => '',
				'CUST_SHIP_STATE' =>'',
				'CUST_SHIP_COUNTRY'=>'',
				'CUST_SHIP_ZIP'  =>'',
				'CUST_SHIP_PHONE'=>'',
				'CUST_SHIP_NAME' =>'',
				'TXNTYPE'      =>'SALE',                            
		);		
	
		$post_params["HASH"] = PayForm::getHashFromArray($post_params,trim(get_option('payten_merchant_key')));
    
		$form_action = trim(get_option('transaction_url'));
		$html = "<center><h1>Please do not refresh this page...</h1></center>";

		
		$html .= '<form method="post" action="'.$form_action.'" name="f1">';

		foreach($post_params as $k=>$v){
			$html .= '<input type="hidden" name="'.$k.'" value="'.$v.'">';
		}

		$html .= "</form>";
		$html .= '<script type="text/javascript">document.f1.submit();</script>';
		return $html;

	} else {
		return $msg;
	}
}

function payten_form_meta_box() {
	$screens = array( 'paytenform' );
	
	foreach ( $screens as $screen ) {
		add_meta_box(  'myplugin_sectionid', __( 'payten', 'myplugin_textdomain' ),'payten_form_meta_box_callback', $screen, 'normal','high' );
	}
}

add_action( 'add_meta_boxes', 'payten_form_meta_box' );

function payten_form_meta_box_callback($post) {
	echo "admin";
}

function payten_form_response(){

	global $wpdb;


	//print_r($_POST);exit;

    if ($_POST['ENCDATA']) {
    	$string = aes_decryption($_POST['ENCDATA']);
	    $string = split_decrypt_string($string);
	    $_POST = $string;

    }

	if($_POST['STATUS']=="Captured")
{
		require_once(dirname(__FILE__) . '/lib/bppg_helper.php');
		global $wpdb;

		$payten_merchant_key = trim(get_option('payten_merchant_key'));
		$payten_merchant_id = trim(get_option('payten_merchant_id'));
		$transaction_status_url = trim(get_option('transaction_status_url'));
		?>
		<script>swal("Thank you for your order. Your transaction has been successful.");</script>
		<?php

   $msg = "Thank you for your order. Your transaction has been successful.";
         $wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "payten_form SET payment_status = 'Payment Completed' WHERE  order_id =%s", sanitize_text_field($string['ORDER_ID'])));
         
				}
				else{

					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "payten_form SET payment_status = 'Failed' WHERE  order_id =%s", sanitize_text_field($string['ORDER_ID'])));
				}
						
}



//for aes encrytion

     function aes_encyption($hash_string){
     // $CryptoKey= $this->pg_merchant_hosted_key; //Prod Key
     global $wpdb;
     $CryptoKey= trim(get_option('payten_merchant_hosted_key')); //Prod Key
     $iv = substr($CryptoKey, 0, 16); //or provide iv
     $method = "AES-256-CBC";
     $ciphertext = openssl_encrypt($hash_string, $method, $CryptoKey, OPENSSL_RAW_DATA, $iv);
     $ENCDATA= base64_encode($ciphertext);
     return $ENCDATA;
    }       

     function aes_decryption($ENCDATA){
    global $wpdb;
    $CryptoKey= trim(get_option('payten_merchant_hosted_key')); //Prod Key
    $iv = substr($CryptoKey, 0, 16); //or provide iv
    $method = "AES-256-CBC";
    $encrptedString  = openssl_decrypt($ENCDATA, $method, $CryptoKey, 0, $iv);
    return $encrptedString;
    }  

     function split_decrypt_string($value)
    {
        $plain_string=explode('~',$value);
        $final_data = array();
        foreach ($plain_string as $key => $value) {
            $simple_string=explode('=',$value);
           $final_data[$simple_string[0]]=$simple_string[1];
        } 
        return $final_data;
    }

	/* This function has been created for cronn jobs  */
	
	// add_action( 'my_cronjob', 'my_cronjob_function' );
	// function my_cronjob_function() {
	// // $to_email = "#";
	// // $subject = "";
	// // $body = "";
	// // $headers = "From: #";
	// // mail($to_email, $subject, $body, $headers);
	// }

	// wp_schedule_single_event( time() + 10, 'my_cronjob' );


//form submit

// Render the form HTML
function kh_render_update_media_form() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
            <input type="hidden" name="action" value="my_media_update">
            <input type="submit" value="Update Media Titles and ALT Text">
        </form>
    </div>
    <?php
}

// Process the form submission
add_action( 'admin_post_my_media_update', 'pay10_transaction_status' );
function pay10_transaction_status() {
   // Update transaction on button click
   $payten_merchant_key = trim(get_option('payten_merchant_key'));
   echo $payten_merchant_id = trim(get_option('payten_merchant_id'));echo "<br>";
   echo $payten_merchant_hosted_key = trim(get_option('payten_merchant_hosted_key'));
   $order_id=$_POST['order_id'];
   global $wpdb;
   $amount = ($wpdb->get_var($wpdb->prepare("SELECT amount From ".$wpdb->prefix . "payten_form WHERE  order_id =%s", sanitize_text_field($order_id))))*100;
   $data = paytenCheckoutStatusEnquiry(false,$payten_merchant_id,$order_id,$amount,'STATUS',356,$payten_merchant_key);
		
	 $status =$data['STATUS'];
       $wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "payten_form SET payment_status = '$status' WHERE  order_id =%s", sanitize_text_field($data['ORDER_ID'])));

    wp_redirect( admin_url( 'admin.php?page=wp_payten_form&updated=true' ) );
    exit;
}


function paytenCheckoutStatusEnquiry($sandbox,$PAY_ID,$ORDER_ID,$AMOUNT,$TXNTYPE,$CURRENCY_CODE,$SALT){

$data = array("PAY_ID" => $PAY_ID, 'ORDER_ID' => $ORDER_ID, 'AMOUNT' => $AMOUNT, 'TXNTYPE' => $TXNTYPE, 'CURRENCY_CODE' => $CURRENCY_CODE); echo "<br>";

foreach ($data as $key => $value) {
$responceParamsJoined[] = "$key=$value";
}
 $responceParamsJoined;
$HASH = GenHash($responceParamsJoined,$SALT);
$data["HASH"] = $HASH;

if ($sandbox == true) {
                $url = "https://uat.pay10.com/pgws/transact";
            } else {
                $url = "https://secure.pay10.com/pgws/transact";
            }
//print_r($data);echo "<br>";
$postvars =  json_encode($data);
$cURL = curl_init();
curl_setopt($cURL, CURLOPT_URL,$url);
curl_setopt($cURL, CURLOPT_POST, 1);
curl_setopt($cURL, CURLOPT_POSTFIELDS,$postvars);
curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
curl_setopt($cURL, CURLOPT_HTTPHEADER, array(                                                                        
    'Content-Type: application/json',                                                                                
    'Content-Length: ' . strlen($postvars))                                                                       
);

$server_output = curl_exec($cURL);
$statusArray = json_decode($server_output, true);
curl_close ($cURL);
return $statusArray;
}


function GenHash($data, $SALT){ 
	sort($data);
    $merchant_data_string = implode('~', $data);echo "<br>";
    echo  $format_Data_string = $merchant_data_string . $SALT;echo "<br>";
    $hashData_uf = hash('sha256', $format_Data_string);
    $hashData = strtoupper($hashData_uf);
    return $hashData;
     
}

