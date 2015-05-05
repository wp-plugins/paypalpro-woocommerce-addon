<?php
/**
 * Plugin Name: PayPal Pro Credit Cards WooCommerce Addon
 * Plugin URI: https://wordpress.org/plugins/paypalpro-woocommerce-addon/
 * Description: Add a feature in wocommerce for customers to pay with Cards Via Paypal.
 * Version: 1.0.0
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
		$this->title               	  = $this->get_option( 'pppcc_title' );
		$this->pppcc_appid      		  = $this->get_option( 'pppcc_appid' );
		$this->pppcc_secret     		  = $this->get_option( 'pppcc_secret' );
		$this->pppcc_sandbox            = $this->get_option( 'pppcc_sandbox' ); 

		define("PAYPALPROCC_SANDBOX", ($this->pppcc_sandbox=='yes'? true : false));

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

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
		  'label' => __( 'Enable Stripe', 'woocommerce' ),
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

		public function process_payment($order_id)
		{
		global $woocommerce;
		$wc_order 	= new WC_Order( $order_id );
		$grand_total 	= $wc_order->order_total;
		
		if(PAYPALPROCC_SANDBOX == 'yes')
		{ 
		 define("API_TOKEN_URL", "https://api.sandbox.paypal.com/v1/oauth2/token");
		 define("API_PAYMT_URL", "https://api.sandbox.paypal.com/v1/payments/payment");
		}
		else
		{ 
		 define("API_TOKEN_URL", "https://api.paypal.com/v1/oauth2/token");
		 define("API_PAYMT_URL", "https://api.paypal.com/v1/payments/payment");
		}

		
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
		
		function cardType($number)
		{
		    $number=preg_replace('/[^\d]/','',$number);
		    if (preg_match('/^3[47][0-9]{13}$/',$number))
		    {
		        return 'amex';
		    }
		    elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',$number))
		    {
		        return 'Diners Club';
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
		}
		  
		$pppcc_cardno   = sanitize_text_field($_POST['pppcc_cardno']);
		$pppcc_expmonth = sanitize_text_field($_POST['pppcc_expmonth']);
		$pppcc_expyear  = sanitize_text_field($_POST['pppcc_expyear']);
		$pppcc_cardcvv  = sanitize_text_field($_POST['pppcc_cardcvv']);
		$pppcc_cardtype = cardType($pppcc_cardno); 
	
	
		$data_string = '{"intent":"sale",
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
				      "description":"'.$wc_order->id.'"
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
		curl_setopt($pay, CURLOPT_RETURNTRANSFER, true);                                                                      
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
			$wc_order->add_order_note( __( 'Full Payment Details. '.$paymentresult, 'woocommerce' ) );
			$wc_order->payment_complete();
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

	}  // end of class WC_Pppcc_Gateway

} // end of if class exist WC_Gateway

}

add_action( 'plugins_loaded', 'pppcc_init' );
