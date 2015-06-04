<?php
/**
 * Plugin Name: PayPal Pro Credit Cards WooCommerce Addon
 * Plugin URI: https://wordpress.org/plugins/paypalpro-woocommerce-addon/
 * Description: Add a feature in wocommerce for customers to pay with Cards Via Paypal.
 * Version: 1.0.1
 * Author: Syed Nazrul Hassan
 * Author URI: https://nazrulhassan.wordpress.com/
 * License: GPL2
 */

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
		$this->icon             = apply_filters( 'woocommerce_pppcc_icon', plugins_url( 'images/paypalprocc.png' , __FILE__ ) );
		$this->has_fields       = true;
		$this->method_title     = 'PayPal Pro Cards Settings';		
		$this->init_form_fields();
		$this->init_settings();
		$this->supports                 = array(  'products',  'refunds');
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

		public function payment_fields()
		{			
	?>
		<table>
		    <tr>
		    	<td><label for="pppcc_cardno"><?php echo __( 'Card No.', 'woocommerce') ?></label></td>
			<td><input type="text" name="pppcc_cardno" class="input-text" placeholder="Credit Card No" /></td>
		    </tr>
		    <tr>
		    	<td><label class="" for="pppcc_expiration_date"><?php echo __( 'Expiration date', 'woocommerce') ?>.</label></td>
			<td>
			   <select name="pppcc_expmonth" style="height: 33px;">
			      <option value=""><?php _e( 'Month', 'woocommerce' ) ?></option>
			      <option value='01'>01</option>
			      <option value='02'>02</option>
			      <option value='03'>03</option>
			      <option value='04'>04</option>
			      <option value='05'>05</option>
			      <option value='06'>06</option>
			      <option value='07'>07</option>
			      <option value='08'>08</option>
			      <option value='09'>09</option>
			      <option value='10'>10</option>
			      <option value='11'>11</option>
			      <option value='12'>12</option>  
			    </select>
			    <select name="pppcc_expyear" style="height: 33px;">
			      <option value=""><?php _e( 'Year', 'woocommerce' ) ?></option><?php
			      $years = array();
			      for ( $i = date( 'y' ); $i <= date( 'y' ) + 15; $i ++ ) {
				printf( '<option value="20%u">20%u</option>', $i, $i );
			      } ?>
			    </select>
			</td>
		    </tr>
		    <tr>
		    	<td><label for="pppcc_cardcvv"><?php echo __( 'Card CVC', 'woocommerce') ?></label></td>
			<td><input type="text" name="pppcc_cardcvv" class="input-text" placeholder="CVC" /></td>
		    </tr>
		</table>
	        <?php  
		} // end of public function payment_fields()
		
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
		        return 'unknown';
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
		
		$cardtype = $this->get_card_type(sanitize_text_field($_POST['pppcc_cardno']));
		
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
		  
		$pppcc_cardno   = sanitize_text_field($_POST['pppcc_cardno']);
		$pppcc_expmonth = sanitize_text_field($_POST['pppcc_expmonth']);
		$pppcc_expyear  = sanitize_text_field($_POST['pppcc_expyear']);
		$pppcc_cardcvv  = sanitize_text_field($_POST['pppcc_cardcvv']);
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
				        "currency":"USD"
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
			//$wc_order->add_order_note( __( 'Full Payment Details. '.$paymentresult, 'woocommerce' ) );
			
			$wc_order->payment_complete($paymentarray['id']);
			
			add_post_meta( $order_id, '_'.$order_id.'_'.$paymentarray['id'].'_metas', $paymentresult);
			
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
				    "currency": "USD"
				  }
				}';
				$ref = curl_init();	
				curl_setopt($ref, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/payments/sale/".$saleid."/refund");
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
				$void = curl_init();	
				curl_setopt($void, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/payments/authorization/".$authid."/void");
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
