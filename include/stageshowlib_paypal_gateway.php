<?php
/*
Description: PayPal API Functions

Copyright 2014 Malcolm Shergold

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

if (defined('STAGESHOWLIB_TRACK_INCLUDES_FILE'))
{
	include STAGESHOWLIB_TRACK_INCLUDES_FILE;
	trackIncludes(__FILE__);
}
	
// Definitions for API Interface Functions
if (!class_exists('StageShowLib_paypal_GatewayClass')) 
{
	if (!defined('PAYPAL_APILIB_IPN_NOTIFY_URL'))
		define('PAYPAL_APILIB_IPN_NOTIFY_URL', STAGESHOWLIB_URL.'include/stageshowlib_paypal_callback.php');

	if (defined('PAYPAL_APILIB_STREET_LABEL')) 
		define ('PAYMENT_API_STREET_LABEL', PAYPAL_APILIB_STREET_LABEL);	
	if (defined('PAYPAL_APILIB_CITY_LABEL')) 
		define ('PAYMENT_API_CITY_LABEL', PAYPAL_APILIB_CITY_LABEL);
	if (defined('PAYPAL_APILIB_STATE_LABEL')) 
		define ('PAYMENT_API_STATE_LABEL', PAYPAL_APILIB_STATE_LABEL);
	if (defined('PAYPAL_APILIB_ZIP_LABEL')) 
		define ('PAYMENT_API_ZIP_LABEL', PAYPAL_APILIB_ZIP_LABEL);
	if (defined('PAYPAL_APILIB_COUNTRY_LABEL')) 
		define ('PAYMENT_API_COUNTRY_LABEL', PAYPAL_APILIB_COUNTRY_LABEL);
				
	if (!defined('PAYMENT_API_LOGIN_MERCHANTID_TEXTLEN'))
	{
		define('PAYMENT_API_LOGIN_MERCHANTID_TEXTLEN', 65);
		define('PAYMENT_API_LOGIN_USER_TEXTLEN', 127);
		define('PAYMENT_API_LOGIN_PWD_TEXTLEN', 65);
		define('PAYMENT_API_LOGIN_SIG_TEXTLEN', 65);
		
		define('PAYMENT_API_LOGIN_EDITLEN', 75);
			
		define('PAYMENT_API_BUTTONID_TEXTLEN',16);		
	
		define('PAYPAL_APILIB_REFUNDALL', '-1');	
	}

	if (!defined('PAYPAL_APILIB_DEFAULT_CURRENCY'))
		define ( 'PAYPAL_APILIB_DEFAULT_CURRENCY', 'GBP' );

	include_once('stageshowlib_gatewaybase.php');

	class StageShowLib_paypal_GatewayClass extends StageShowLibGatewayBaseClass // Define class
	{
		var		$APIEndPoint;		//	PayPal API access URL
		var   	$APIResponses;		//	API response data parsed into an array
		var		$APIStatusMsg;
		
		var		$APIusername;		//	PayPal login name
		var		$APIpassword;		//	PayPal login password
		var		$APIsignature;		//	PayPal login signature
		var		$APIemail;			//	PayPal primary email
		var		$PayPalCurrency;	//  PayPal Currency Code
		
		var		$SaleCompleteURL = '';
		var		$SaleCancelURL = '';
			
		var		$checkout = '';
		
		function __construct( $opts )
		{
			$this->opts = $opts;
			parent::__construct($opts);

			// Initialise PayPal API Variables
			$this->APIusername = '';
			$this->APIpassword = '';
			$this->APIsignature = '';
		}
		
		static function GetName()
		{
			return 'PayPal';
		}
		
		static function GetType()
		{
			return 'paypal';
		}
		
		static function GetDefaultCurrency()
		{
			return PAYPAL_APILIB_DEFAULT_CURRENCY;
		}
		
		function GetCheckoutType()
		{
			return $this->myDBaseObj->getOption('PayPalCheckoutType');
		}
		
		function GetCurrencyOptionID()
		{
			return 'PayPalCurrency';
		}
		
		function GetCurrencyTable()
		{
			return array( 
				array('Name' => 'Australian Dollars ',  'Currency' => 'AUD', 'Symbol' => '&#36;',        'Char' => 'A$', 'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Brazilian Real ',      'Currency' => 'BRL', 'Symbol' => 'R&#36;',       'Char' => 'R$', 'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Canadian Dollars ',    'Currency' => 'CAD', 'Symbol' => '&#36;',        'Char' => '$',  'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Czech Koruna ',        'Currency' => 'CZK', 'Symbol' => '&#75;&#269;',  'Char' => '',   'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Danish Krone ',        'Currency' => 'DKK', 'Symbol' => 'kr',           'Char' => '',   'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Euros ',               'Currency' => 'EUR', 'Symbol' => '&#8364;',      'Char' => '',   'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Hong Kong Dollar ',    'Currency' => 'HKD', 'Symbol' => '&#36;',        'Char' => '$',  'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Hungarian Forint ',    'Currency' => 'HUF', 'Symbol' => 'Ft',           'Char' => '',   'Position' => 'Left', 'Format' => '%d'),
				array('Name' => 'Israeli Shekel ',      'Currency' => 'ILS', 'Symbol' => '&#x20aa;',     'Char' => '',   'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Mexican Peso ',        'Currency' => 'MXN', 'Symbol' => '&#36;',        'Char' => '$',  'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'New Zealand Dollar ',  'Currency' => 'NZD', 'Symbol' => '&#36;',        'Char' => '$',  'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Norwegian Krone ',     'Currency' => 'NOK', 'Symbol' => 'kr',           'Char' => '',   'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Philippine Pesos ',    'Currency' => 'PHP', 'Symbol' => 'P',            'Char' => '',   'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Polish Zloty ',        'Currency' => 'PLN', 'Symbol' => '&#122;&#322;', 'Char' => '',   'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Pounds Sterling ',     'Currency' => 'GBP', 'Symbol' => '&#x20a4;',     'Char' => '£',  'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Singapore Dollar ',    'Currency' => 'SGD', 'Symbol' => 'S&#36;',       'Char' => 'S$', 'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Swedish Krona ',       'Currency' => 'SEK', 'Symbol' => 'kr',           'Char' => '',   'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Swiss Franc ',         'Currency' => 'CHF', 'Symbol' => 'CHF',          'Char' => '',   'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Taiwan New Dollars ',  'Currency' => 'TWD', 'Symbol' => 'NT&#36;',      'Char' => 'NT$','Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Thai Baht ',           'Currency' => 'THB', 'Symbol' => '&#xe3f;',      'Char' => '',   'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'U.S. Dollars ',        'Currency' => 'USD', 'Symbol' => '&#36;',        'Char' => '$',  'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Yen ',                 'Currency' => 'JYP', 'Symbol' => '&#xa5;',       'Char' => '',   'Position' => 'Left', 'Format' => '%d'),
			);
		}
		
		//Returns an array of admin options
		function Gateway_GetOptions() 
		{
			$ourOptions = array(
				'PayPalCurrency' => PAYPAL_APILIB_DEFAULT_CURRENCY,
				        
				'PayPalMerchantID' => '',
				'PayPalAPIUser' => '',
				'PayPalAPISig' => '',
				'PayPalAPIPwd' => '',
				'PayPalAPIEMail' => '',
			);
			
			$ourOptions = array_merge($ourOptions, parent::Gateway_GetOptions());
			
			return $ourOptions;
		}
		
		function Gateway_SettingsRowsDefinition()
		{
			$CurrencyTable = $this->GetCurrencyTable();			
			foreach ($CurrencyTable as $index => $currDef)
			{
				$currSelect[$index] = $currDef['Currency'];
				$currSelect[$index] .= '|';
				$currSelect[$index] .= $currDef['Name'];
				$currSelect[$index] .= ' ('.$currDef['Symbol'].') ';
			}
			
			$rowDefs = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Account EMail',                   StageShowLibTableClass::TABLEPARAM_TAB => 'gateway-settings-tab-paypal', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalAPIEMail',        StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   StageShowLibTableClass::TABLEPARAM_NOTFORDEMO => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_LOGIN_EMAIL_TEXTLEN,       StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_LOGIN_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Merchant ID',                     StageShowLibTableClass::TABLEPARAM_TAB => 'gateway-settings-tab-paypal', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalMerchantID',      StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   StageShowLibTableClass::TABLEPARAM_NOTFORDEMO => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_LOGIN_MERCHANTID_TEXTLEN,  StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_LOGIN_EDITLEN, StageShowLibTableClass::TABLEPARAM_BLOCKBLANK => true, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Currency',                        StageShowLibTableClass::TABLEPARAM_TAB => 'gateway-settings-tab-paypal', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalCurrency',        StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_SELECT, StageShowLibTableClass::TABLEPARAM_ITEMS => $currSelect, ),
			);
			
			if (basename(dirname(dirname(__FILE__))) != 'stageshow')
			{
				$rowDefs = StageShowLibAdminListClass::MergeSettings($rowDefs, array(
					array(StageShowLibTableClass::TABLEPARAM_LABEL => 'API User',                        StageShowLibTableClass::TABLEPARAM_TAB => 'gateway-settings-tab-paypal', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalAPIUser',         StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   StageShowLibTableClass::TABLEPARAM_NOTFORDEMO => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_LOGIN_USER_TEXTLEN,        StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_LOGIN_EDITLEN, StageShowLibTableClass::TABLEPARAM_AFTER => 'PayPalMerchantID', ),
					array(StageShowLibTableClass::TABLEPARAM_LABEL => 'API Password',                    StageShowLibTableClass::TABLEPARAM_TAB => 'gateway-settings-tab-paypal', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalAPIPwd',          StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   StageShowLibTableClass::TABLEPARAM_NOTFORDEMO => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_LOGIN_PWD_TEXTLEN,         StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_LOGIN_EDITLEN, StageShowLibTableClass::TABLEPARAM_AFTER => 'PayPalAPIUser', ),
					array(StageShowLibTableClass::TABLEPARAM_LABEL => 'API Signature',                   StageShowLibTableClass::TABLEPARAM_TAB => 'gateway-settings-tab-paypal', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalAPISig',          StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   StageShowLibTableClass::TABLEPARAM_NOTFORDEMO => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_LOGIN_SIG_TEXTLEN,         StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_LOGIN_EDITLEN, StageShowLibTableClass::TABLEPARAM_AFTER => 'PayPalAPIPwd',  ),
				));								
			}

			return $rowDefs;
		}
			
		function Gateway_LoadUserScripts()
		{
		}
		
		function Gateway_LoadAdminStyles()
		{
		}
		
		function LoginGatewayAPI($adminOptions, $dbgOptions)
		{
			$this->SetLoginParams(
				$adminOptions['PayPalAPIUser'], 
				$adminOptions['PayPalAPIPwd'], 
				$adminOptions['PayPalAPISig'], 
				$adminOptions['PayPalCurrency']);
			$this->SetTestMode(false);

			$useLocalIPNServer = isset($dbgOptions['Dev_IPNLocalServer']) && ($dbgOptions['Dev_IPNLocalServer']);

			$this->GatewayNotifyURL = PAYPAL_APILIB_IPN_NOTIFY_URL;							
			$this->PayPalURL = $this->GetPayPalURL(false);

			// URL for Plugin code to verify PayPal IPNs
			if ($useLocalIPNServer)
			{
				$pageURL = StageShowLibUtilsClass::GetPageURL();
				$pluginName = basename(dirname(dirname(__FILE__)));
				$verifyURL = $pageURL.'wp-content/plugins/'.$pluginName.'/test/paypal_VerifyIPNTest.php';	
				$this->PayPalVerifyURL = $verifyURL;	
			}
			else
			{
				$this->PayPalVerifyURL = $this->PayPalURL;
			}				
		}
		
		function IsLoginChanged ($adminOptions)
		{
			if (StageShowLibAdminClass::IsOptionChanged($adminOptions, 'PayPalAPIUser'))
				return true;
			if (StageShowLibAdminClass::IsOptionChanged($adminOptions, 'PayPalAPIPwd'))
				return true;
			if (StageShowLibAdminClass::IsOptionChanged($adminOptions, 'PayPalAPISig'))
				return true;			
			return false;
		}
				
		function IsGatewayConfigured ($adminOptions)
		{
			// Must have EITHER PayPalMerchantID or PayPalAPIEMail
			if (!self::isOptionSet($adminOptions, 'PayPalMerchantID') && !self::isOptionSet($adminOptions, 'PayPalAPIEMail'))
				return false;
			
			// Either All of PayPalAPIUser, PayPalAPIPwd and PayPalAPISig must be defined or none of them
			$ApiOptsCount = 0;
			if (self::isOptionSet($adminOptions, 'PayPalAPIUser')) $ApiOptsCount++;
			if (self::isOptionSet($adminOptions, 'PayPalAPIPwd')) $ApiOptsCount++;
			if (self::isOptionSet($adminOptions, 'PayPalAPISig')) $ApiOptsCount++;
			if (($ApiOptsCount != 0) && ($ApiOptsCount != 3))
				return false;
				
			return true;					
		}
		
		function VerifyLogin()
		{
			$payPalButtonsAPIObj = new StageShowLibPayPalButtonsAPIClass($this->opts);
			if ($payPalButtonsAPIObj->VerifyPayPalLogin(
				'live', 
				stripslashes($_POST['PayPalAPIUser']),
				stripslashes($_POST['PayPalAPIPwd']), 
				stripslashes($_POST['PayPalAPISig'])))
			{
				// New PayPal API Settings are valid			
				return '';
			}
			else
			{
				// FUNCTIONALITY: Settings - Reject PayPal settings if cannot create hosted button 
				$APIStatus = $payPalButtonsAPIObj->APIStatus;
				return __('PayPal Login FAILED', $this->myDomain)." - $APIStatus";
			}
			
		}
		
		function IsCheckout()
		{
			$buttonID = 'checkout';
			if (defined('CORONDECK_RUNASDEMO'))
			{
				$pluginID = $this->myDBaseObj->get_name();
				$buttonID .= '_'.$pluginID;
			}
			
			if (isset($_POST[$buttonID]) || isset($_POST[$buttonID.'_x'])) 
				$this->checkout = 'checkout';
			else
				$this->checkout = '';
			
			return $this->checkout;
		}
		
		function GetGatewayRedirectURL($saleId, $saleDetails)
		{
			$myDBaseObj = $this->myDBaseObj;
							
			foreach ($this->items as $paramCount => $item)
			{
				$reqParams['item_name_'.$paramCount] = $item->name;
				$reqParams['amount_'.$paramCount] = $item->price;
				$reqParams['quantity_'.$paramCount] = $item->qty;
				$reqParams['shipping_'.$paramCount] = $item->shipping;
			}
			
			$logoURL = $myDBaseObj->getImageURL('PayPalLogoImageFile');
			$headerURL = $myDBaseObj->getImageURL('PayPalHeaderImageFile');

			$reqParams['image_url'] = $logoURL;
			$reqParams['cpp_header_image'] = $headerURL;
			$reqParams['no_shipping'] = '2';

			// Use Merchant ID if it is defined
			if ($myDBaseObj->isOptionSet('PayPalMerchantID'))
			{
				$reqParams['business'] = $myDBaseObj->adminOptions['PayPalMerchantID'];	// Can use adminOptions['PayPalAPIEMail']
			}
			else
			{
				$reqParams['business'] = $myDBaseObj->adminOptions['PayPalAPIEMail'];	// Can use adminOptions['PayPalAPIEMail']
			}
			$reqParams['currency_code'] = $myDBaseObj->adminOptions['PayPalCurrency'];
			$reqParams['cmd'] = '_cart';
			$reqParams['upload'] = '1';
			
			if ($myDBaseObj->adminOptions['CheckoutCompleteURL'] != '')
			{
				$reqParams['rm'] = '2';
				$reqParams['return'] = $myDBaseObj->adminOptions['CheckoutCompleteURL'];
			}
			
			if ($myDBaseObj->adminOptions['CheckoutCancelledURL'] != '')
			{
				$reqParams['cancel_return'] = $myDBaseObj->adminOptions['CheckoutCancelledURL'];
			}
				
			$reqParams['notify_url'] = $this->GatewayNotifyURL;
		
			$gatewayURL = $this->GetPayPalURL(false);
			foreach ($reqParams as $paypalArg => $paypalParam)
				$gatewayURL = add_query_arg($paypalArg, urlencode($paypalParam), $gatewayURL);

			$gatewayURL = add_query_arg('custom', $saleId, $gatewayURL);
					
			return $gatewayURL;					
		}
		
		protected function IsAPIConfigured(&$apiStatus)
		{
			$apiStatus = '';
			if ((strlen( $this->APIusername ) == 0) || ( strlen( $this->APIpassword ) == 0 ) || ( strlen( $this->APIsignature ) == 0 ))
			{
				$apiStatus = "API UserName/Pasword Undefined";
			}
			else if (strlen( $this->APIEndPoint ) == 0)
			{
				$apiStatus = "APIEndPoint Undefined";
			}
			else
			{
				return true;
			}

			if (($this->DebugEnabled) && ($apiStatus != ''))
			{
				$this->OutputDebug("--------------------------------------\n");
				$this->OutputDebug("API Access Error: $apiStatus\n");
				$this->OutputDebug("\n", true);
			}
			
			return false;
		}
		
		function SetLoginParams($username, $password, $signature, $currency = PAYPAL_APILIB_DEFAULT_CURRENCY, $email = '')		
		{
			if (( strlen( $username ) == 0 ) || ( strlen( $password ) == 0 ) || ( strlen( $signature ) == 0 ))
			{
				$this->APIusername = '';
				$this->APIpassword = '';
				$this->APIsignature = '';
				$this->APIStatusMsg = __('Missing PayPal Login Param');
				return;
			}
			$this->APIusername = $username;
			$this->APIpassword = $password;
			$this->APIsignature = $signature;
			$this->APIemail = $email;
			$this->PayPalCurrency = $currency;
			$this->APIStatusMsg = '';
		}
		
		function SetTestMode($testmode)
		{
			if ($testmode)
			{
				$this->APIEndPoint = 'https://api-3t.sandbox.paypal.com/nvp';
			}
			else 
			{
				$this->APIEndPoint = 'https://api-3t.paypal.com/nvp';
			}
		}
		
		function GetPayPalURL($testmode)
		{
			if ($testmode)
			{
				return 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			}
			else 
			{
				return 'https://www.paypal.com/cgi-bin/webscr';
			}
		}
		
		protected function APIAction()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$this->APIResponses = null;
			$response = $myDBaseObj->HTTPAction($this->APIEndPoint, $this->URLParamsArray);
			if ($response['APIStatusMsg'] === 'ERROR')
			{
			}
			else
			{
				parse_str($response['APIResponseText'], $response['APIResponses']);
				if ($this->DebugEnabled)
				{
					$this->OutputDebug("--------------------------------------\n");
					$this->OutputDebug("APIResponses:\n");
					if (class_exists('StageShowLibUtilsClass')) 
					{
						$this->OutputDebug(StageShowLibUtilsClass::print_r($response['APIResponses'], 'response[APIResponses]', true));
					}
					else
					{
						$this->OutputDebug(print_r($response['APIResponses'], true));
						$this->OutputDebug("\n");
					}
				}
				if (isset($response['APIResponses']['ACK']))
				{
					$this->APIResponses = $response['APIResponses'];				
					if ($response['APIResponses']['ACK'] == 'Success')
					{
						$this->APIStatusMsg = 'OK';
					}
					else
					{
						$this->APIStatusMsg = 'API Error ';
						if (isset($response['APIResponses']['L_ERRORCODE0']))
							$this->APIStatusMsg .= $response['APIResponses']['L_ERRORCODE0'];
						$this->APIStatusMsg .= ' - ';
						if (isset($response['APIResponses']['L_SHORTMESSAGE0']))
							$this->APIStatusMsg .= $response['APIResponses']['L_SHORTMESSAGE0'];
					}
				}
				else
					$this->APIStatusMsg = 'API Error - No Response';
			}
			if ($this->DebugEnabled)
			{
				$this->OutputDebug("APIStatusMsg:".$this->APIStatusMsg."\n", true);
			}
			return $this->APIStatusMsg;
		}
				
		protected function AddAPIParam($tagName, $tagValue)
		{
			$this->URLParamsArray[$tagName]=$tagValue;
			if ($this->DebugEnabled)
			{
				$this->OutputDebug ("$tagName=$tagValue\n");
			}			
		}
		
		protected function InitAPICallParams($methodID)
		{
			if ($this->DebugEnabled)
			{
				$this->OutputDebug("--------------------------------------\n");
				$this->OutputDebug("$methodID API Call\n");
				$this->OutputDebug("--------------------------------------\n", true);
			}
			if (strlen($this->APIusername) > 0)
			{
				$this->AddAPIParam('USER', $this->APIusername);
			}
			else
			{
				echo "ERROR: API Username not specified<br>\n";
			}
			if (strlen($this->APIusername) > 0)
			{
				$this->AddAPIParam('PWD', $this->APIpassword);
			}
			else
			{
				echo "ERROR: API Password not specified<br>\n";
			}
			if (strlen($this->APIusername) > 0)
			{
				$this->AddAPIParam('SIGNATURE', $this->APIsignature);
			}
			else
			{
				echo 'ERROR: API Signature not specified<br>\n';
			}
			$this->AddAPIParam('VERSION', '65.1');
			$this->AddAPIParam('METHOD', $methodID);
			$this->ButtonVarCount = 0;
		}
		
		function GetTransactions($fromDate, $toDate = '')
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsAPIConfigured($apiStatus))
				return $apiStatus;	// Cannot Execute - API Not Configured
				
			$this->InitAPICallParams('TransactionSearch');
			$this->AddAPIParam('STARTDATE', $fromDate);
			if ($toDate != '')
			{
				$this->AddAPIParam('ENDDATE', $toDate);
			}
			return $this->APIAction('Get Transactions ');
		}
		
		function GetTransaction($txnId)
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsAPIConfigured($apiStatus))
				return $apiStatus;	// Cannot Execute - API Not Configured
				
			// Search for Transaction on PayPal
			$this->Reset();
			$this->InitAPICallParams('GetTransactionDetails');
			$this->AddAPIParam('TRANSACTIONID', $txnId);
			return $this->APIAction('Get Transaction ');
		}
		
		function RefundTransaction($txnId, $amt = PAYPAL_APILIB_REFUNDALL)
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsAPIConfigured($apiStatus))
				return $apiStatus;	// Cannot Execute - API Not Configured
				
			// Search for Transaction on PayPal
			$this->Reset();
			$this->InitAPICallParams('RefundTransaction');
			$this->AddAPIParam('TRANSACTIONID', $txnId);
			//$this->AddAPIParam('INVOICEID', $tbd);
			//$this->AddAPIParam('NOTE', $tbd);
			if ($amt == PAYPAL_APILIB_REFUNDALL)
			{
				$this->AddAPIParam('REFUNDTYPE', 'Full');
			}
			else
			{
				$this->AddAPIParam('REFUNDTYPE', 'Partial');
				$this->AddAPIParam('AMT', $amt);
				$this->AddAPIParam('CURRENCYCODE', $this->PayPalCurrency);
			}
			return $this->APIAction('Refund Transaction ');
		}
		
	}
}
		
if (!class_exists('StageShowLibPayPalButtonsAPIClass')) 
{
	class StageShowLibPayPalButtonsAPIClass extends StageShowLib_paypal_GatewayClass // Define class
	{
		const PAYPAL_APILIB_CREATEBUTTON_OK = 0;
		const PAYPAL_APILIB_CREATEBUTTON_ERROR = 1;
		const PAYPAL_APILIB_CREATEBUTTON_NOLOGIN = 2;
		const PAYPAL_APILIB_INFINITE = -1;
		
		// Class variables:
		var   	$APIStatus;			//	API response status value
		var   	$APIResponseText;	//	API response text
		var		$ButtonVarCount;  	//  The number of button variables defined
		var		$OptNo;				//  The number of button options defined
		var		$caller;			//	The path of the calling function
						
		function __construct( $opts )
		{
			parent::__construct( $opts );			
		}
		
		function Reset()
		{
			parent::Reset();
			$this->ButtonVarCount = 0;
			$this->OptNo = 0;
		}
				
		private function AddAPIButtonVar($tagId, $tagValue)
		{
			if (strlen($tagValue) > 0)
			{
				$tagName = "L_BUTTONVAR$this->ButtonVarCount";
				$this->AddAPIParam($tagName, $tagId.'='.$tagValue);
				$this->ButtonVarCount++;
			}
		}
		
		private function AddAPIButtonParams($methodID, $hostedButtonID)
		{
			$this->InitAPICallParams($methodID);
			$this->AddAPIParam('HOSTEDBUTTONID', $hostedButtonID);
		}
		
		function AddGetBalanceParams()
		{
			$this->InitAPICallParams('BMGetInventory');
		}
		
		function AddCreateButtonParams($description = 'TBD', $reference = '', $amount = '1.00')
		{
			$this->InitAPICallParams('BMCreateButton');
			$this->AddAPIParam('BUTTONTYPE', 'CART');
			$this->AddAPIParam('BUTTONSUBTYPE', 'PRODUCTS');
			
			$this->AddCommonButtonParams($description, $reference);
			
			$this->AddAPIButtonVar('amount', $amount);			
		}
		
		function AddCommonButtonParams($description, $reference)
		{
			$this->AddAPIButtonVar('item_name', $description);
			$this->AddAPIButtonVar('item_number', $reference);
			$this->AddAPIButtonVar('currency_code', $this->PayPalCurrency);	
					
			if ($this->SaleCompleteURL != '')
			{
				$this->AddAPIButtonVar('return', $this->SaleCompleteURL);
			}	
			
			if ($this->SaleCancelURL != '')
			{
				$this->AddAPIButtonVar('cancel_return', $this->SaleCancelURL);
			}	
		}
		
		function AddDeleteButtonParams($hostedButtonID)
		{
			$this->AddAPIButtonParams('BMManageButtonStatus', $hostedButtonID);
			$this->AddAPIParam('BUTTONSTATUS', 'DELETE');
		}
		
		private function AddGetButtonDetailsParams($hostedButtonID)
		{
			$this->AddAPIButtonParams("BMGetButtonDetails", $hostedButtonID);
		}
		
		private function AddSetButtonParams ($hostedButtonID, $description, $reference)
		{
			$this->AddAPIButtonParams('BMUpdateButton', $hostedButtonID);
			$this->AddAPIParam('BUTTONCODE', 'HOSTED');
			$this->AddAPIParam('BUTTONTYPE', 'CART');
			$this->AddAPIParam('BUTTONSUBTYPE', 'PRODUCTS');
			
			$this->AddCommonButtonParams($description, $reference);
			
			$this->AddAPIButtonVar('button_xref', get_site_url());
		}
		
		private function AddButtonOption ($optID, $optPrice)
		{
			$this->AddAPIParam('L_OPTION0SELECT' . $this->OptNo, $optID);
			$this->AddAPIParam('L_OPTION0PRICE' . $this->OptNo, $optPrice);
			$this->OptNo++;
		}
						
		function CreateButton(&$hostedButtonID, $description = 'TBD', $reference = '', $amount = '1.00')
		{
			$hostedButtonID = '';
			// Check that the PayPal login parameters have been set
			if (!$this->IsAPIConfigured($apiStatus))
				return StageShowLibPayPalButtonsAPIClass::PAYPAL_APILIB_CREATEBUTTON_NOLOGIN;	// Cannot Create Button - API Not Configured
			// Create a "Hosted" button on PayPal ... with basic settings
			$this->Reset();
			$this->AddCreateButtonParams($description, $reference, $amount);
			$this->APIStatus = $this->APIAction('Create Button ');
			if ($this->APIStatus !== 'OK')
				return StageShowLibPayPalButtonsAPIClass::PAYPAL_APILIB_CREATEBUTTON_ERROR;
			$hostedButtonID = $this->APIResponses['HOSTEDBUTTONID'];
			return StageShowLibPayPalButtonsAPIClass::PAYPAL_APILIB_CREATEBUTTON_OK;
		}
		
		function DeleteButton($hostedButtonID)
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsAPIConfigured($apiStatus))	
			return;		// Cannot Delete Button - API Not Configured
			if (strlen($hostedButtonID) == 0)
				return;		// Cannot Delete Button - Zero Length Button ID
			// Delete a "Hosted" button on PayPal
			$this->Reset();
			$this->AddDeleteButtonParams($hostedButtonID);
			return $this->APIAction('Delete Button ' . $hostedButtonID);
		}
		
		function GetButton($hostedButtonID)
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsAPIConfigured($apiStatus))
				return 'ERROR';	// Cannot Get Button Details - API Not Configured 
			if (strlen($hostedButtonID) == 0)
				return 'ERROR';	// Cannot Get Button Details - Zero Length Button ID 
			$this->Reset();
			$this->AddGetButtonDetailsParams($hostedButtonID);
			$APIStatus = $this->APIAction('Button ' . $hostedButtonID);
			return $APIStatus;
		}
		
		function UpdateButton($hostedButtonID, $description, $reference, $optPrices, $optIDs = '')
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsAPIConfigured($apiStatus))
				return;	// Cannot Update Button - API Not Configured 
			if (strlen($hostedButtonID) == 0)
				return;	// Cannot Update Button - Zero Length Button ID 
			$this->Reset();
			$this->AddSetButtonParams($hostedButtonID, $description, $reference);
			if (is_array($optPrices))
			{
				if (count($optIDs) != count($optPrices))
				{
					// Error - Unequal Array sizes
					echo "ERROR: optIDs[] and optPrices[] different sizes in UpdateButton() function <br>\n";
					return;
				}
				$this->AddAPIParam('OPTION0NAME', 'TicketTypes');
				for ($index=0; $index<count($optIDs); $index++)
				{
					$this->AddButtonOption($optIDs[$index], $optPrices[$index]);
				}
			}
			else
			{
				$this->AddAPIButtonVar('amount', $optPrices);
			}
			return $this->APIAction('Button ' . $hostedButtonID);
		}
		
		function VerifyPayPalLogin($loginEnv, $username, $password, $signature)
		{
			$this->APIemail = '';      
			$this->SetTestMode($loginEnv == 'sandbox');
			$this->SetLoginParams($username, $password, $signature);
			// Blank PayPal login params disabled this PayPal interface
			if ((strlen($username) == 0) && (strlen($password) == 0) && (strlen($signature) == 0))
				return true;
				
			$ButtonStatus = $this->CreateButton($hostedButtonID);
			if ($ButtonStatus != StageShowLibPayPalButtonsAPIClass::PAYPAL_APILIB_CREATEBUTTON_OK)
			{
				//echo "CreateButton FAILED<br>\n";
				return false;
			}
			/*			
			// Get primary email from PayPal - Doesn't seem to work anymore ... 
			// Bug in SandBox - Does not work if primary email is changed
			if ($this->GetButton($hostedButtonID) === 'OK')
			{
				$varNo = 0;
				while (true)
				{
					if (!isset($this->APIResponses['L_BUTTONVAR'.$varNo]))
						break;
					$lButtonVar = $this->APIResponses['L_BUTTONVAR'.$varNo];
					if (get_magic_quotes_gpc())
						$lButtonVar = stripslashes($lButtonVar);
					if (substr($lButtonVar, 0, 1) === '"')
					{
						// Remove double quotes ....
						$lButtonVar = substr($lButtonVar, 1, strlen($lButtonVar)-2);
					}
					$lButtonVars = explode('=', $lButtonVar);	
					if ($lButtonVars[0] === 'business')
					{
						$this->APIemail = $lButtonVars[1];					
						break;
					}
					$varNo++;
				}
			}
			*/      
			// Tidy up - Button was only to check login and get email .... delete it!
			$this->DeleteButton($hostedButtonID);
			
			// VerifyPayPalLogin - Returned $this->APIStatusMsg 
			return ($this->APIStatusMsg === 'OK');
		}
	}
}

?>