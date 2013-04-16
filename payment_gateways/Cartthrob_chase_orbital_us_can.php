<?php 

class Cartthrob_chase_orbital_us_can extends Cartthrob_payment_gateway
{
	public $title = 'chase_orbital_title';
 	public $overview = 'chase_orbital_overview';
	public $settings = array(
		array(
			'name' => 'username',
			'short_name' => 'username',
			'type' => 'text'
		),
		array(
			'name' => 'password',
			'short_name' => 'password',
			'type' => 'text'
		),
		array(
			'name' => 'chase_orbital_company',
			'short_name' => 'company_name',
			'type' => 'text'
		),
		array(
			'name' => 'chase_orbital_us_merchant_id',
			'short_name' => 'merchant_id',
			'type' => 'text'
		),
		array(
			'name' => 'chase_orbital_can_merchant_id',
			'short_name' => 'can_merchant_id',
			'type' => 'text'
		),
		array(
			'name' => 'chase_orbital_dev_username',
			'short_name' => 'dev_username',
			'type' => 'text'
		),
		array(
			'name' => 'chase_orbital_dev_password',
			'short_name' => 'dev_password',
			'type' => 'text'
		),
		array(
			'name' => 'chase_orbital_dev_us_merchant_id',
			'short_name' => 'dev_merchant_id',
			'type' => 'text'
		),
		array(
			'name' => 'chase_orbital_dev_can_merchant_id',
			'short_name' => 'dev_can_merchant_id',
			'type' => 'text'
		),
		array(
			'name' => 'chase_orbital_client_id',
			'short_name' => 'client_id',
			'type' => 'text'
		),
		array(
			'name' => 'chase_orbital_tid',
			'short_name' => 'terminal_id',
			'type' => 'text', 
			'default' => '001'
		),
		array(
			'name' => 'chase_orbital_bin',
			'short_name' => 'bin_number',
			'type' => 'text', 
			'default' => '000002'
		),
		array(
			'name' => "mode",
			'short_name' => 'mode',
			'type' => 'radio',
			'default' => "test",
			'options' => array(
				"test"	=> "test",
				"live"	=> "live",
				"certification" => 'chase_certification',
			)
		),
		array(
			'name' => "chase_orbital_certification_test_amount",
			'short_name' => 'certification_test_amount',
			'type' => 'text',
			'default' => "30.00",
		),
	);
	
	public $required_fields = array(
		'credit_card_number',
		'expiration_year',
		'expiration_month'
	);
	
	public $fields = array(
		'first_name',
		'last_name',
		'address',
		'address2',
		'city',
		'state',
		'zip',
		'country_code',
		'phone',
		'email_address',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_address',
		'shipping_address2',
		'shipping_city',
		'shipping_state',
		'shipping_zip',
		'card_type',
		'credit_card_number',
		'CVV2',
		'expiration_year',
		'expiration_month'
	);
	
	public $hidden = array();
	public $card_types = array(
		"mc",
		"visa",
		"discover",
		"diners",
		"amex", 
		"jcb"
		);
	
	/**
	 * process_payment
	 *
 	 * @param string $credit_card_number 
	 * @return mixed | array | bool An array of error / success messages  is returned, or FALSE if all fails.
	 * @author Chris Newton
	 * @access public
	 * @since 1.0.0
	 */
	public function process_payment($credit_card_number)
	{
		$total = $this->total(); 
		$currency_code = "840"; 
		
 		if ($this->plugin_settings('mode') == "live") 
		{
			$host = "https://orbital1.paymentech.net";
 			$username = $this->plugin_settings('username'); 
			$password = $this->plugin_settings('password');
			if ($this->order('country_code') == "USA")
			{
				$merchant_id = $this->plugin_settings('merchant_id'); 
			}
			else
			{
				$merchant_id = $this->plugin_settings('can_merchant_id'); 
			}
		}
		else
		{
			$host	= "https://orbitalvar1.paymentech.net";
			$username = $this->plugin_settings('dev_username'); 
			$password = $this->plugin_settings('dev_password'); 
 			if ($this->order('country_code') == "USA")
			{
				$merchant_id = $this->plugin_settings('dev_merchant_id'); 
			}
			else
			{
				$merchant_id = $this->plugin_settings('dev_can_merchant_id'); 
			}
		}
 		if ($this->order('country_code') == "CAN")
		{
			$currency_code = "124"; 
		}
 		
 		if ($this->plugin_settings('mode') == "certification") 
		{
			$total = $this->plugin_settings('certification_test_amount'); 
		}	
		
		$request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Request></Request>');
		$new_order = $request->addChild("NewOrder");
		
		
		$CardSecValInd = NULL;
		$CardSecVal = NULL; 
		if($this->order('CVV2')){
			if ($this->order('card_type')=='visa' || $this->order('card_type')=='discover')
			{
				$CardSecValInd =1;
			}
			$CardSecVal = $this->order('CVV2'); 
 		}
		
		$post_array = array(
		   "OrbitalConnectionUsername"=> $username,
		   "OrbitalConnectionPassword"=>$password,
		   "IndustryType"=>"EC",
		   "MessageType"=>"AC",
		   "BIN"=>$this->plugin_settings('bin_number'),
		   "MerchantID"=>$merchant_id,
		   "TerminalID"=>$this->plugin_settings('terminal_id'),
		   "CardBrand"=>NULL,
		   "AccountNum"=>$credit_card_number,
		   "Exp"=>str_pad($this->order('expiration_month'), 2, '0', STR_PAD_LEFT).$this->year_2($this->order('expiration_year')),
		   "CurrencyCode"=>$currency_code, // bin 000002 only supports US Dollar and Canadian Dollar - 840 is US
		   "CurrencyExponent"=>"2",
			'CardSecValInd'	=> $CardSecValInd,
			'CardSecVal'	=> $CardSecVal,
		   "AVSzip"=>$this->order('zip'),
		   "AVSaddress1"=>substr($this->order('address'), 0, 30),
		   "AVSaddress2"=>substr($this->order('address2'), 0, 30),
		   "AVScity"=>substr($this->order('city'), 0, 20),
		   "AVSstate"=>substr($this->order('state'), 0, 2),
		   "AVSphoneNum"=>$this->order('phone'),
			"AVSname"=>$this->order('first_name')." ".$this->order('last_name'),
		   "OrderID"=>substr(substr(time(), 0, 8)."-".$this->order('entry_id'), 0, 22),
		   "Amount"=>round($total*100),
		);
		
		if ($this->order('country_code') == "CAN")
		{
			// @NOTE. Might need to add back in. Depends on whether AVS needs state or not. 
			unset($post_array['AVSstate']);
		} 
		foreach ($post_array as $key=>$item)
		{
			if ($item != "")
			{
				$new_order->addChild($key, $item);
			}
		}
 		
 		$xml = (string) $request->asXML(); 

		$header = "POST /AUTHORIZE/ HTTP/1.0\r\n"; 
		$header.= "MIME-Version: 1.0\r\n";
		$header.= "Content-type: application/PTI52\r\n";
		$header.= "Content-length: "  .strlen($xml) . "\r\n";
		$header.= "Content-transfer-encoding: text\r\n";
		$header.= "Request-number: 1\r\n";
		$header.= "Document-type: Request\r\n";
		$header.= "Interface-Version: CartThrob vs. 0430+ PHP\r\n";
		$header.= "Connection: close \r\n\r\n";  
		$header.= $xml;
 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$host);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);   // setting header manually. 
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $header);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$data = curl_exec($ch);  
		
		$resp['authorized']	 	= FALSE; 
		$resp['declined'] 		= FALSE; 
		$resp['transaction_id']	= NULL;
		$resp['failed']			= TRUE; 
		$resp['error_message']	= "";
		$resp['processing']		= FALSE;
		
		if (curl_errno($ch)) 
		{
			$resp['error_message']	= $this->lang('curl_gateway_failure'); 
			return $resp;
		}
		else 
		{
			curl_close($ch);
		}
		
 		$transaction = new SimpleXMLElement($data);

		if (!$transaction)
		{
 			$resp['error_message']	= $this->lang('curl_gateway_failure'); 
			return $resp; 
		}
		
		
		if ($this->plugin_settings('mode') == "certification") 
		{
 			echo "Auth/Decline Code: ". @$transaction->NewOrderResp->AuthCode. '<br /> '; 
			echo "Resp Code: ". @$transaction->NewOrderResp->RespCode. '<br /> '; 
			echo "AVS Resp: ". @$transaction->NewOrderResp->AVSRespCode. '<br /> '; 
			echo "CVD Resp: ". @$transaction->NewOrderResp->CVV2RespCode. '<br /> '; 
			echo "TxRefNum: ". @$transaction->NewOrderResp->TxRefNum. '<br /> '; 
			
			echo "<pre>";
			print_r($transaction); 
			echo "</pre>";
			exit;
		}
		
		

		
		if((string)  $transaction->NewOrderResp->ProcStatus=="0" && (string) $transaction->NewOrderResp->ApprovalStatus ==1)
		{
			$resp['authorized']	 	= TRUE; 
			$resp['declined'] 		= FALSE; 
			$resp['transaction_id']	= (string) $transaction->NewOrderResp->TxRefNum;
			$resp['failed']			= FALSE; 
			$resp['error_message']	= "";
		}
		else
		{
			$resp['authorized']	 	= FALSE; 
			$resp['declined'] 		= FALSE; 
			$resp['transaction_id']	= NULL;
			$resp['failed']			= TRUE; 
			$resp['error_message']	= (string) $transaction->NewOrderResp->StatusMsg;
		}
		
		return $resp; 
 
 	}
 }
// END Class