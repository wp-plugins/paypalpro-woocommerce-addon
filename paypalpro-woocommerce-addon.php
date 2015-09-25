<?php
/**
 * Plugin Name: PayPal Payments Pro WooCommerce Addon
 * Plugin URI: https://wordpress.org/plugins/paypalpro-woocommerce-addon/
 * Description: This plugin adds a feature in wocommerce for customers to pay with Credit Cards Via Paypal Payment Pro.
 * Version: 1.0.2
 * Author: Syed Nazrul Hassan
 * Author URI: https://nazrulhassan.wordpress.com/
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
function pppcc_init()
{

function add_pppcc_gateway_class( $methods ) 
{
	$methods[] = 'WC_Pppcc_Gateway'; 
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_pppcc_gateway_class' );

if(class_exists('WC_Payment_Gateway'))
{
	class WC_Pppcc_Gateway extends WC_Payment_Gateway 
	{
		
		public function __construct()
		{

		$this->id               = 'paypalprocc';
		$this->icon             = plugins_url( 'images/paypalprocc.png' , __FILE__ ) ;
		$this->has_fields       = true;
		$this->method_title     = 'PayPal Pro Cards Settings';		
		$this->init_form_fields();
		$this->init_settings();
		$this->supports                 = array('default_credit_card_form', 'products',  'refunds');
		$this->title               	  = $this->get_option( 'pppcc_title' );
		$this->pppcc_appid      		  = $this->get_option( 'pppcc_appid' );
		$this->pppcc_secret     		  = $this->get_option( 'pppcc_secret' );
		$this->pppcc_sandbox            = $this->get_option( 'pppcc_sandbox' );
		$this->pppcc_authorize_only     = $this->get_option( 'pppcc_authorize_only' ); 
		$this->pppcc_cardtypes          = $this->get_option( 'pppcc_cardtypes'); 

		if(!defined("PAYPALPROCC_SANDBOX"))
		 { define("PAYPALPROCC_SANDBOX", ($this->pppcc_sandbox=='yes'? true : false)); }
		if(!defined("PAYPALPROCC_INTENT"))
		 { define("PAYPALPROCC_INTENT",$this->pppcc_authorize_only=='yes'? 'authorize' : 'sale'); }
		if(PAYPALPROCC_SANDBOX == 'yes')
		{ 
		  if(!defined("API_TOKEN_URL"))
		  { define("API_TOKEN_URL", "https://api.sandbox.paypal.com/v1/oauth2/token"); }
		  if(!defined("API_PAYMT_URL"))
		  { define("API_PAYMT_URL", "https://api.sandbox.paypal.com/v1/payments/payment"); }
		}
		else
		{ 
		  if(!defined("API_TOKEN_URL"))
		  { define("API_TOKEN_URL", "https://api.paypal.com/v1/oauth2/token"); }
		  if(!defined("API_PAYMT_URL"))
		  { define("API_PAYMT_URL", "https://api.paypal.com/v1/payments/payment"); }
		}

		if(is_admin())
		{	
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
		}

		public function admin_options()
		{
		?>
		<h3><?php _e( 'PayPal Pro Credit Cards for Woocommerce', 'woocommerce' ); ?></h3>
		<p><?php  _e( 'PayPal Pro is a direct gateway which allows you to take credit card payments directly on your checkout page over the Internet.', 'woocommerce' ); ?></p>
		<table class="form-table">
		  <?php $this->generate_settings_html(); ?>
		</table>
		<?php
		}

		public function init_form_fields()
		{
		$this->form_fields = array(
		'enabled' => array(
		  'title' => __( 'Enable/Disable', 'woocommerce' ),
		  'type' => 'checkbox',
		  'label' => __( 'Enable PayPal Pro Cards', 'woocommerce' ),
		  'default' => 'yes'
		  ),
		'pppcc_title' => array(
		  'title' => __( 'Title', 'woocommerce' ),
		  'type' => 'text',
		  'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		  'default' => __( 'PayPal Pro CC', 'woocommerce' ),
		  'desc_tip'      => true,
		  ),
		'pppcc_appid' => array(
		  'title' => __( 'PayPal App ID', 'woocommerce' ),
		  'type' => 'text',
		  'description' => __( 'This is PayPal App ID found in Paypal App.', 'woocommerce' ),
		  'default' => '',
		  'desc_tip'      => true,
		  'placeholder' => 'PayPal App ID'
		  ),
		
		'pppcc_secret' => array(
		  'title' => __( 'PayPal Secret Key', 'woocommerce' ),
		  'type' => 'text',
		  'description' => __( 'This is PayPal Secret Key found in Paypal App.', 'woocommerce' ),
		  'default' => '',
		  'desc_tip'      => true,
		  'placeholder' => 'PayPal App Secret key'
		  ),

		
		'pppcc_sandbox' => array(
		  'title'       => __( 'PayPal Pro Sandbox', 'woocommerce' ),
		  'type'        => 'checkbox',
		  'label'       => __( 'Enable PayPal Pro sandbox', 'woocommerce' ),
		  'default'     => 'no',
		  'description' => __( 'If checked its in sanbox mode and if unchecked its in live mode', 'woocommerce' )
		),
		
		'pppcc_authorize_only' => array(
			 'title'       => __( 'Authorize Only', 'woocommerce' ),
			 'type'        => 'checkbox',
			 'label'       => __( 'Enable Authorize Only Mode (Authorize & Capture If Unchecked)', 'woocommerce' ),
			 'description' => __( 'If checked will only authorize the credit card only upon checkout.', 'woocommerce' ),
			 'desc_tip'      => true,
			 'default'     => 'no',
		),
		
		'pppcc_cardtypes' => array(
		 'title'    => __( 'Accepted Cards', 'woocommerce' ),
		 'type'     => 'multiselect',
		 'class'    => 'chosen_select',
		 'css'      => 'width: 350px;',
		 'desc_tip' => __( 'Select the card types to accept.', 'woocommerce' ),
		 'options'  => array(
			'mastercard'       => 'MasterCard',
			'visa'             => 'Visa',
			'discover'         => 'Discover',
			'amex' 		       => 'American Express',
			'jcb'		       => 'JCB',
			'dinersclub'       => 'Dinners Club',
		 ),
		 'default' => array( 'mastercard', 'visa', 'discover', 'amex' ),
		)
		
	  );
  		}


  		function is_available() {
            if ( ! in_array( get_woocommerce_currency(), apply_filters( 'paypalprocc_woocommerce_supported_currencies', array( 'USD', 'GBP', 'CAD', 'CAD', 'EUR', 'JPY' ) ) ) ) return false;

            if(empty($this->pppcc_appid) || empty($this->pppcc_secret)) return false;

            return true;
        }


  		/*Get Icon*/
		public function get_icon() {
		$icon = '';
		if(is_array($this->pppcc_cardtypes ))
		{
        foreach ( $this->pppcc_cardtypes  as $card_type ) {

				if ( $url = $this->get_payment_method_image_url( $card_type ) ) {
					
					$icon .= '<img src="'.esc_url( $url ).'" alt="'.esc_attr( strtolower( $card_type ) ).'" />';
				}
			}
		}
		else
		{
			$icon .= '<img src="'.esc_url( plugins_url( 'images/paypalprocc.png' , __FILE__ ) ).'" alt="Authorize.Net Payment Gateway" />';	  
		}

         return apply_filters( 'woocommerce_pppcc_icon', $icon, $this->id );
		}
 
		public function get_payment_method_image_url( $type ) {

		$image_type = strtolower( $type );
				return  WC_HTTPS::force_https_url( plugins_url( 'images/' . $image_type . '.jpg' , __FILE__ ) ); 
		}
		/*Get Icon*/


		
		/*Get or Set access token if needed renew and save to db*/
		public function getsetaccesstoken()
		{
			$pppwooaddon_values  =  get_option('pppwooaddon_values', true );
		    $expires_at = isset( $pppwooaddon_values['expires_at'] ) ? esc_attr( $pppwooaddon_values['expires_at'] ) : '';
		
		if($expires_at  < time() || empty($expires_at) || '' == $expires_at || NULL == $expires_at )
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, API_TOKEN_URL);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($ch, CURLOPT_USERPWD, $this->pppcc_appid.":".$this->pppcc_secret);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

			$result = curl_exec($ch);

			
			$json = json_decode($result);
			$access_token = $json->access_token;
			curl_close($ch);
			$expires_at = time() + $json->expires_in - 1000 ; 
			update_option('pppwooaddon_values',array('expires_at'=>$expires_at,'access_token'=>$access_token));
			return $access_token;
		}
		else
		{
			$access_token   = isset( $pppwooaddon_values['access_token'] ) ? esc_attr( $pppwooaddon_values['access_token'] ) : '';
			return $access_token;
		}
		
		}// end of setgetaccesstoken 
		
		/*Get Card Types*/
		function get_card_type($number)
		{
		    $number=preg_replace('/[^\d]/','',$number);
		    if (preg_match('/^3[47][0-9]{13}$/',$number))
		    {
		        return 'amex';
		    }
		    elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',$number))
		    {
		        return 'dinersclub';
		    }
		    elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/',$number))
		    {
		        return 'discover';
		    }
		    elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/',$number))
		    {
		        return 'jcb';
		    }
		    elseif (preg_match('/^5[1-5][0-9]{14}$/',$number))
		    {
		        return 'mastercard';
		    }
		    elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/',$number))
		    {
		        return 'visa';
		    }
		    else
		    {
		        return 'unknown card type';
		    }
		}// End of getcard type function
		
		
		//Function to check IP
		function get_client_ip() 
		{
			$ipaddress = '';
			if (getenv('HTTP_CLIENT_IP'))
				$ipaddress = getenv('HTTP_CLIENT_IP');
			else if(getenv('HTTP_X_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
			else if(getenv('HTTP_X_FORWARDED'))
				$ipaddress = getenv('HTTP_X_FORWARDED');
			else if(getenv('HTTP_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_FORWARDED_FOR');
			else if(getenv('HTTP_FORWARDED'))
				$ipaddress = getenv('HTTP_FORWARDED');
			else if(getenv('REMOTE_ADDR'))
				$ipaddress = getenv('REMOTE_ADDR');
			else
				$ipaddress = '0.0.0.0';
			return $ipaddress;
		}
		
		
		
		
		public function process_payment($order_id)
		{
		global $woocommerce;
		$wc_order 	= new WC_Order( $order_id );
		$grand_total 	= $wc_order->order_total;
		
		$cardtype = $this->get_card_type(sanitize_text_field($_POST['paypalprocc-card-number']));
		
		if(!in_array($cardtype ,$this->pppcc_cardtypes ))
         		{
         			wc_add_notice('Merchant do not support accepting in '.$cardtype,  $notice_type = 'error' );
         			return array (
								'result'   => 'success',
								'redirect' => WC()->cart->get_checkout_url(),
							   );
				die;
         		}

		
		
		//End of function to check IP
        $pppcc_cardno     = sanitize_text_field(str_replace(' ','',$_POST['paypalprocc-card-number']));
		$pppcc_cardcvv    = sanitize_text_field($_POST['paypalprocc-card-cvc']);

		$exp_date         = explode( "/", sanitize_text_field($_POST['paypalprocc-card-expiry']));
		$pppcc_expmonth        = str_replace( ' ', '', $exp_date[0]);
		$pppcc_expyear         = str_replace( ' ', '',$exp_date[1]);
		if (strlen($pppcc_expyear) == 2) {   $pppcc_expyear += 2000;  }
		  
	
		$pppcc_cardtype = $this->get_card_type($pppcc_cardno); 
		$access_token   = $this->getsetaccesstoken() ;
	
		$data_string = '{"intent":"'.PAYPALPROCC_INTENT.'",
				  "payer":{
				    "payment_method":"credit_card",
				    "funding_instruments":[
				      {
				        "credit_card":{
				          "number":"'.$pppcc_cardno.'",
				          "type":"'.$pppcc_cardtype.'",
				          "expire_month":"'.$pppcc_expmonth.'",
				          "expire_year":"'.$pppcc_expyear.'",
				          "cvv2":"'.$pppcc_cardcvv.'",
				          "first_name":"'.$wc_order->billing_first_name.'",
				          "last_name":"'.$wc_order->billing_last_name.'",
				          "billing_address":{
				            "line1":"'.$wc_order->billing_address_1.$wc_order->billing_address_2.'",
				            "city":"'.$wc_order->billing_city.'",
				            "state":"'.$wc_order->billing_state.'",
				            "postal_code":"'.$wc_order->billing_postcode.'",
				            "country_code":"'.$wc_order->shipping_country.'"
				          }
				        }
				      }
				    ]
				  },
				  "transactions":[
				    {
				      "amount":{
				        "total":"'.$grand_total.'",
				        "currency":"'.get_woocommerce_currency().'"
				      },
				      "description":"'.get_bloginfo('blogname').' Order #'.$wc_order->get_order_number().'"
				    }
				  ]
				}';

		
		// process payment
		$pay = curl_init();	
		curl_setopt($pay, CURLOPT_URL, API_PAYMT_URL);
		curl_setopt($pay, CURLOPT_HEADER, false);
		curl_setopt($pay, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($pay, CURLOPT_POST, true);
		curl_setopt($pay, CURLOPT_RETURNTRANSFER, true);                                                                     
		curl_setopt($pay, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($pay, CURLOPT_POSTFIELDS, $data_string);             
		curl_setopt($pay, CURLOPT_HTTPHEADER, array(                                                                          
					'Content-Type: application/json; charset=utf-8', 
					'Authorization: Bearer '.$access_token,
				     'Content-Length:'.strlen($data_string))      
			);                                                                                                                   
		
		$paymentresult = curl_exec($pay);
		curl_close($pay);
		
		$paymentarray = json_decode($paymentresult,true);
		

		if($paymentarray['id'])
		{
			
			$wc_order->add_order_note( __( 'Paypal payment completed at. '.$paymentarray['create_time'].' with Charge ID = '.$paymentarray['id'].' and State='.$paymentarray['state'] , 'woocommerce' ) );
			
			$wc_order->payment_complete($paymentarray['id']);
			
			add_post_meta( $order_id, '_'.$order_id.'_'.$paymentarray['id'].'_metas', $paymentresult);

			//echo "<pre>"; print_r($paymentarray); var_dump($paymentarray); die;

			if("sale" == $paymentarray['intent'] && "approved" == $paymentarray['state'] )
		    {
		    	add_post_meta( $order_id, '_paypalprocc_charge_status', 'charge_auth_captured');
		    }

		    if("authorize" == $paymentarray['intent'] && "approved" == $paymentarray['state'] )
		    {
		    	add_post_meta( $order_id, '_paypalprocc_charge_status', 'charge_auth_only');
		    }
			
			return array (
			  'result'   => 'success',
			  'redirect' => $this->get_return_url( $wc_order ),
			);
		
		}
		else
		{
			$wc_order->add_order_note( __( 'Transaction Details. '.$paymentresult, 'woocommerce' ) );
			wc_add_notice($paymentresult, $notice_type = 'error' );
		}



		} // end of function process_payment() 

		
		
		
		// process refund
		public function process_refund( $order_id, $amount = NULL, $reason = '' )
		{
			global $woocommerce;
		     $wc_order 	= new WC_Order( $order_id );
			$trx_id		= get_post_meta( $order_id , '_transaction_id', true );
			$trx_metas   	= get_post_meta( $order_id , '_'.$order_id.'_'.$trx_id.'_metas',true);
			$trx_metas_val = json_decode($trx_metas,true);
			
			
			$saleid     = @$trx_metas_val['transactions'][0]['related_resources'][0]['sale']['id'] ;
			$authid     = @$trx_metas_val['transactions'][0]['related_resources'][0]['authorization']['id'] ;
			
			$saleamount = @$trx_metas_val['transactions'][0]['related_resources'][0]['sale']['amount']['total'] ;
			$authamount = @$trx_metas_val['transactions'][0]['related_resources'][0]['authorization']['amount']['total'] ;
			
			//echo '40din'.$saleid.$saleamount.$authid.$authamount; return false; die;
			
			$access_token = $this->getsetaccesstoken() ;
			
			if( $amount > $saleamount && $saleamount > 0 )
			{return false; }
			
			else
			{
			
				
			if($saleid) 
			{
				$data_string =	'{
				  "amount":
				  {
				    "total": "'.$amount.'",
				    "currency": "'.get_woocommerce_currency().'"
				  }
				}';

				if(PAYPALPROCC_SANDBOX == 'yes')
				{
					$refundurl = "https://api.sandbox.paypal.com/v1/payments/sale/".$saleid."/refund";
				}
				else
				{
					$refundurl = "https://api.paypal.com/v1/payments/sale/".$saleid."/refund";
				}

				$ref = curl_init();	
				curl_setopt($ref, CURLOPT_URL, $refundurl);
				curl_setopt($ref, CURLOPT_HEADER, false);
				curl_setopt($ref, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ref, CURLOPT_POST, true);
				curl_setopt($ref, CURLOPT_RETURNTRANSFER, true);                                                                     
				curl_setopt($ref, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
				curl_setopt($ref, CURLOPT_POSTFIELDS, $data_string);                                                                       
				curl_setopt($ref, CURLOPT_HTTPHEADER, array(                                                                          
							'Content-Type: application/json; charset=utf-8', 
							'Authorization: Bearer '.$access_token,
							'Content-Length:'.strlen($data_string))      
					);                                                                                                                   
			
				$refundresult = curl_exec($ref);
				curl_close($ref);
				
				$refundarray = json_decode($refundresult,true);
				if($refundarray['id'])
				{
					$wc_order->add_order_note( __( 'Paypal refund for '.$refundarray['parent_payment'].' completed at. '.$refundarray['create_time'].' with ID = '.$refundarray['id'].' and State='.$refundarray['state'] , 'woocommerce' ) );
					add_post_meta( $order_id, '_'.$order_id.'_'.$refundarray['id'].'_metas', $refundresult);
					return true;
				}
				else
				{
					$wc_order->add_order_note( __( 'Transaction Details. '.$refundresult, 'woocommerce' ) );
					return false;
				}
			}
			
			//processing for return true or false 
			}
			
			if($amount > $authamount && $authamount > 0 )
			{return false;}
			else
			{

				if(PAYPALPROCC_SANDBOX == 'yes')
				{
					$voidurl = "https://api.sandbox.paypal.com/v1/payments/authorization/".$authid."/void";
				}
				else
				{
					$voidurl = "https://api.paypal.com/v1/payments/authorization/".$authid."/void";
				}

				$void = curl_init();	
				curl_setopt($void, CURLOPT_URL,$voidurl );
				curl_setopt($void, CURLOPT_HEADER, false);
				curl_setopt($void, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($void, CURLOPT_POST, true);
				curl_setopt($void, CURLOPT_RETURNTRANSFER, true);    				
				curl_setopt($void, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($void, CURLOPT_HTTPHEADER, array(      
							'Content-Type: application/json; charset=utf-8', 
							'Authorization: Bearer '.$access_token)      
					);                                                                                                                   
			
				$voidresult = curl_exec($void);
				curl_close($void);
				
				$voidarray = json_decode($voidresult,true);
				if($voidarray['id'])
				{
				$wc_order->add_order_note( __( 'Paypal Void for '.$voidarray['parent_payment'].' completed at. '.$voidarray['create_time'].' with ID = '.$voidarray['id'].' and State='.$voidarray['state'] , 'woocommerce' ) );
					add_post_meta( $order_id, '_'.$order_id.'_'.$voidarray['id'].'_metas', $voidresult);
					return true;
				}	
				else
				{
					$wc_order->add_order_note( __( 'Transaction Details. '.$voidresult, 'woocommerce' ) );
					return false;
				}
			}

			return false;

		}

	}  // end of class WC_Pppcc_Gateway

} // end of if class exist WC_Gateway

}

add_action( 'plugins_loaded', 'pppcc_init' );


function paypalprocc_woocommerce_addon_activate() {

	if(!function_exists('curl_exec'))
	{
		 wp_die( '<pre>This plugin requires PHP CURL library installled in order to be activated </pre>' );
	}
}
register_activation_hook( __FILE__, 'paypalprocc_woocommerce_addon_activate' );


/*Plugin Settings Link*/
function paypalprocc_woocommerce_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=wc_pppcc_gateway">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'paypalprocc_woocommerce_settings_link' );

/*Settings Link*/



/*Capture Charge*/

function paypalprocc_capture_meta_box() {
	global $post;
	$chargestatus = get_post_meta( $post->ID, '_paypalprocc_charge_status', true );
	if($chargestatus == 'charge_auth_only')
	{
			add_meta_box(
				'paypalprocc_capture_chargeid',
				__( 'Capture Payment for Order', 'woocommerce' ),
				'paypalprocc_capture_meta_box_callback',
				'shop_order',
				'side',
				'default'
			);
	}
}
add_action( 'add_meta_boxes', 'paypalprocc_capture_meta_box' );


function paypalprocc_capture_meta_box_callback( $post ) {

	//charge_auth_only, charge_auth_captured, charge_auth_captured_later
	echo '<input type="checkbox" name="_paypalprocc_capture_charge" value="1"/>&nbsp;Check & Save Order to Capture';
}


/*Execute charge on order save*/
function paypalprocc_capture_meta_box_action($order_id, $items )
{
	if(isset($items['_paypalprocc_capture_charge']) && (1 ==$items['_paypalprocc_capture_charge']) ) 
	{
	//	global $post;
		
		$wc_order 	= new WC_Order( $order_id );
		$trx_id		= get_post_meta( $order_id , '_transaction_id', true );
		$trx_metas   = get_post_meta( $order_id , '_'.$order_id.'_'.$trx_id.'_metas',true);
		$trx_metas_val = json_decode($trx_metas,true);
		
		$authid     = @$trx_metas_val['transactions'][0]['related_resources'][0]['authorization']['id'] ;
		$authamount = @$trx_metas_val['transactions'][0]['related_resources'][0]['authorization']['amount']['total'] ;
		$authcurrcy = @$trx_metas_val['transactions'][0]['related_resources'][0]['authorization']['amount']['currency'] ;
		
		if(class_exists('WC_Pppcc_Gateway'))
		{
			$paypalproccpg = new WC_Pppcc_Gateway();
			$access_token = $paypalproccpg->getsetaccesstoken() ;

			if($paypalproccpg->pppcc_sandbox == 'yes')
			{
				$captureurl = "https://api.sandbox.paypal.com/v1/payments/authorization/".$authid."/capture";
			}
			else
			{
				$captureurl = "https://api.paypal.com/v1/payments/authorization/".$authid."/capture";
			}

		}


		$data_string =	'{
						  "amount":{
						    "currency":"'.$authcurrcy.'",
						    "total":"'.$authamount.'"
						  },
						  "is_final_capture":true
						}';

		$cap = curl_init();	
		curl_setopt($cap, CURLOPT_URL, $captureurl);
		curl_setopt($cap, CURLOPT_HEADER, false);
		curl_setopt($cap, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($cap, CURLOPT_POST, true);
		curl_setopt($cap, CURLOPT_RETURNTRANSFER, true);                                                                     
		curl_setopt($cap, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($cap, CURLOPT_POSTFIELDS, $data_string);                                                                       
		curl_setopt($cap, CURLOPT_HTTPHEADER, array(                                                                          
					'Content-Type: application/json; charset=utf-8', 
					'Authorization: Bearer '.$access_token,
					'Content-Length:'.strlen($data_string))      
			);                                                                                                                   
	
		$captureresult = curl_exec($cap);
		curl_close($cap);
		
		$capturearray = json_decode($captureresult,true);

		if($capturearray['id'])
		{
			$wc_order = new WC_Order($order_id);
			$wc_order->add_order_note( __( 'Capture for '.$capturearray['parent_payment'].' completed at. '.$capturearray['create_time'].' with ID = '.$capturearray['id'].' and State='.$capturearray['state'] , 'woocommerce' ) );
			add_post_meta( $order_id, '_'.$order_id.'_'.$capturearray['id'].'_metas', $captureresult);
			update_post_meta( $order_id, '_paypalprocc_charge_status', 'charge_auth_captured_later');
		}	
		else
		{
			$wc_order->add_order_note($captureresult ,'woocommerce');
		}

	}	

}
add_action ("woocommerce_saved_order_items", "paypalprocc_capture_meta_box_action", 10,2);
/*Execute charge on order save*/