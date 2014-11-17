<?php
/*
Description: PayPal API Functions

Copyright 2012 Malcolm Shergold

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
	
if( !class_exists( 'WP_Http' ) )
	include_once( ABSPATH . WPINC. '/class-http.php' );

// Definitions for API Interface Functions
if (!class_exists('PayPalAPIClass')) 
{
	if (!defined('PAYPAL_APILIB_STREET_LABEL')) 
		define ('PAYPAL_APILIB_STREET_LABEL', 'Address');
	if (!defined('PAYPAL_APILIB_CITY_LABEL')) 
		define ('PAYPAL_APILIB_CITY_LABEL', 'Town/City');
	if (!defined('PAYPAL_APILIB_STATE_LABEL')) 
		define ('PAYPAL_APILIB_STATE_LABEL', 'County');
	if (!defined('PAYPAL_APILIB_ZIP_LABEL')) 
		define ('PAYPAL_APILIB_ZIP_LABEL', 'Postcode');
	if (!defined('PAYPAL_APILIB_COUNTRY_LABEL')) 
		define ('PAYPAL_APILIB_COUNTRY_LABEL', 'Country');
				
	define('PAYPAL_APILIB_SALESTATUS_COMPLETED', 'Completed');
	define('PAYPAL_APILIB_SALESTATUS_PENDING', 'Pending');
	define('PAYPAL_APILIB_SALESTATUS_CHECKOUT', 'Checkout');
		
	define('PAYPAL_APILIB_PPLOGIN_MERCHANTID_TEXTLEN', 65);
	define('PAYPAL_APILIB_PPLOGIN_USER_TEXTLEN', 127);
	define('PAYPAL_APILIB_PPLOGIN_PWD_TEXTLEN', 65);
	define('PAYPAL_APILIB_PPLOGIN_SIG_TEXTLEN', 65);
	define('PAYPAL_APILIB_PPLOGIN_EMAIL_TEXTLEN', 65);
	
	define('PAYPAL_APILIB_PPLOGIN_EDITLEN', 75);
		
	define('PAYPAL_APILIB_PPSALENAME_TEXTLEN',128);
	define('PAYPAL_APILIB_PPSALEEMAIL_TEXTLEN',127);
	define('PAYPAL_APILIB_PPSALEPPNAME_TEXTLEN',128);
	define('PAYPAL_APILIB_PPSALEPPSTREET_TEXTLEN',200);
	define('PAYPAL_APILIB_PPSALEPPCITY_TEXTLEN',40);
	define('PAYPAL_APILIB_PPSALEPPSTATE_TEXTLEN',40);
	define('PAYPAL_APILIB_PPSALEPPZIP_TEXTLEN',20);
	define('PAYPAL_APILIB_PPSALEPPCOUNTRY_TEXTLEN',64);
	define('PAYPAL_APILIB_PPSALEPPPHONE_TEXTLEN',64);	
	define('PAYPAL_APILIB_PPSALETXNID_TEXTLEN',20);
	define('PAYPAL_APILIB_PPSALESTATUS_TEXTLEN',20);

	define('PAYPAL_APILIB_PPSALENAME_EDITLEN',80);
	define('PAYPAL_APILIB_PPSALEEMAIL_EDITLEN',80);
	define('PAYPAL_APILIB_PPSALEPPNAME_EDITLEN',80);
	define('PAYPAL_APILIB_PPSALEPPSTREET_EDITLEN',80);
	define('PAYPAL_APILIB_PPSALEPPCITY_EDITLEN',40);
	define('PAYPAL_APILIB_PPSALEPPSTATE_EDITLEN',40);
	define('PAYPAL_APILIB_PPSALEPPZIP_EDITLEN',20);
	define('PAYPAL_APILIB_PPSALEPPCOUNTRY_EDITLEN',64);
	define('PAYPAL_APILIB_PPSALEPPPHONE_EDITLEN',64);	
	define('PAYPAL_APILIB_PPSALETXNID_EDITLEN',40);		// Extended to 40 because text box was too small
	define('PAYPAL_APILIB_PPSALESTATUS_EDITLEN',20);

	define('PAYPAL_APILIB_PPBUTTONID_TEXTLEN',16);

	define('PAYPAL_APILIB_CHECKOUT_TIMEOUT_TEXTLEN', 3);	
	define('PAYPAL_APILIB_CHECKOUT_TIMEOUT_EDITLEN', 4);	
	define('PAYPAL_APILIB_CHECKOUT_TIMEOUT_DEFAULT', 60);	

	if (!defined('PAYPAL_APILIB_DEFAULT_CURRENCY'))
		define ( 'PAYPAL_APILIB_DEFAULT_CURRENCY', 'GBP' );

	define('PAYPAL_APILIB_REFUNDALL', '-1');
	
	class PayPalAPIClass // Define class
	{
		var		$URLParamsArray;  	//  Array of params for PayPal API HTTP request
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
			
		function __construct( $caller )
		{
			//constructor
			$this->caller = $caller;
			
			// Initialise PayPal API Variables
			$this->DebugMode = false;
			$this->APIusername = '';
			$this->APIpassword = '';
			$this->APIsignature = '';
			
			$this->Reset();
		}
		
		function Reset()
		{
			$this->URLParamsArray = null;
		}
		
		function IsConfigured()
		{
			if ((strlen( $this->APIusername ) == 0) || ( strlen( $this->APIpassword ) == 0 ) || ( strlen( $this->APIsignature ) == 0 ))
			{
				return false;
			}
			if (strlen( $this->APIEndPoint ) == 0)
			{
				return false;
			}
			return true;
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
		
		function SetSaleCompleteURL($url)
		{
			$this->SaleCompleteURL = $url;
		}
		
		function SetSaleCancelURL($url)
		{
			$this->SaleCancelURL = $url;
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
		
		static function GetPayPalURL($testmode)
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
		
		function EnableDebug()
		{
			$this->DebugMode = true;
		}
				
		function APIAction()
		{
			$this->APIResponses = null;
			$response = $this->HTTPAction($this->APIEndPoint, $this->URLParamsArray);
			if ($response['APIStatusMsg'] === 'ERROR')
			{
			}
			else
			{
				parse_str($response['APIResponseText'], $response['APIResponses']);
				if ($this->DebugMode)
				{
					echo "--------------------------------------<br>\n";
					echo "APIResponses:<br>\n";
					Print_r($response['APIResponses']);
					echo "<br>\n";
				}
				if (isset($response['APIResponses']['ACK']))
				{
					if ($response['APIResponses']['ACK'] == 'Success')
					{
						$this->APIResponses = $response['APIResponses'];				
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
			if ($this->DebugMode)
			{
				echo "APIStatusMsg:".$this->APIStatusMsg."<br>\n";
			}
			return $this->APIStatusMsg;
		}
				
		function AddAPIParam($tagName, $tagValue)
		{
			$this->URLParamsArray[$tagName]=$tagValue;
			if ($this->DebugMode)
			{
				echo "$tagName=$tagValue<br>\n";
			}			
		}
		
		function InitAPICallParams($methodID)
		{
			if ($this->DebugMode)
			{
				echo "--------------------------------------<br>\n";
				echo "$methodID API Call<br>\n";
				echo "--------------------------------------<br>\n";
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
			$this->AddAPIParam('VERSION', '64.0');
			$this->AddAPIParam('METHOD', $methodID);
			$this->ButtonVarCount = 0;
		}
		
		static function HTTPAction($url, $urlParams = '', $method = 'POST', $redirect = true)
		{
			$args = array(
			'method' => $method,
			'body' => $urlParams,
			'sslverify' => false
			);
			
			if (!$redirect)
				$args['redirection'] = 0;
			
			$request = new WP_Http;
			$result = $request->request( $url, $args );
			if ( is_wp_error($result) )
			{
				$response['APIResponseText'] = '';
				$response['APIStatus'] = 'ERROR';
				$response['APIStatusMsg'] = $result->get_error_message();
				$response['APIHeaders'] = '';
				$response['APICookies'] = array();
			}
			else
			{
				$response['APIResponseText'] = $result['body'];
				$response['APIStatus'] = $result['response']['code'];
				$response['APIStatusMsg'] = $result['response']['message'];
				$response['APIHeaders'] = $result['headers'];
				$response['APICookies'] = $result['cookies'];
			}
			return $response;			
		}
		
		function GetTransactions($fromDate, $toDate = '')
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsConfigured())
				return;	// Cannot Execute - API Not Configured
				
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
			if (!$this->IsConfigured())
				return;	// Cannot Execute - API Not Configured
				
			// Search for Transaction on PayPal
			$this->Reset();
			$this->InitAPICallParams('GetTransactionDetails');
			$this->AddAPIParam('TRANSACTIONID', $txnId);
			return $this->APIAction('Get Transaction ');
		}
		
		function RefundTransaction($txnId, $amt = PAYPAL_APILIB_REFUNDALL)
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsConfigured())
				return;	// Cannot Execute - API Not Configured
				
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
		
if (!class_exists('PayPalButtonsAPIClass')) 
{
	class PayPalButtonsAPIClass extends PayPalAPIClass // Define class
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
		var		$DebugMode;			//
						
		function __construct( $caller )
		{
			parent::__construct( $caller );			
		}
		
		function Reset()
		{
			parent::Reset();
			$this->ButtonVarCount = 0;
			$this->OptNo = 0;
		}
				
		function AddAPIButtonVar($tagId, $tagValue)
		{
			if (strlen($tagValue) > 0)
			{
				$tagName = "L_BUTTONVAR$this->ButtonVarCount";
				$this->AddAPIParam($tagName, $tagId.'='.$tagValue);
				$this->ButtonVarCount++;
			}
		}
		
		function AddAPIButtonParams($methodID, $hostedButtonID)
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
		
		function AddGetInventoryParams($hostedButtonID)
		{
			$this->AddAPIButtonParams('BMGetInventory', $hostedButtonID);
		}
		
		function AddSearchButtonParams()
		{
			$this->InitAPICallParams('BMButtonSearch');
			$this->AddAPIParam('STARTDATE', '2000-01-01T12:000:00Z');
		}
		
		function AddSetInventoryParams($hostedButtonID, $quantity, $soldOutUrl = '', $reference = 'X')
		{
			$this->AddAPIButtonParams('BMSetInventory', $hostedButtonID);
			if ($quantity < 0)
			{
				// TODO-IMPROVEMENT - Disable Inventory Fudged - PayPal would not disable both Inventory Control and PNL
				// Disable Inventory Control ... enables PNL
				$this->AddAPIParam('TRACKINV', '0');
				$this->AddAPIParam('TRACKPNL', '1');
				$this->AddAPIParam('ITEMNUMBER', $reference);
				$this->AddAPIParam('ITEMCOST', '1.0');
			}
			else
			{
				$this->AddAPIParam('TRACKINV', '1');
				$this->AddAPIParam('SOLDOUTURL', $soldOutUrl);
				$this->AddAPIParam('TRACKPNL', '0');
				$this->AddAPIParam('ITEMQTY', $quantity);
			}
		}
		
		function AddGetButtonDetailsParams($hostedButtonID)
		{
			$this->AddAPIButtonParams("BMGetButtonDetails", $hostedButtonID);
		}
		
		function AddSetButtonParams ($hostedButtonID, $description, $reference)
		{
			$this->AddAPIButtonParams('BMUpdateButton', $hostedButtonID);
			$this->AddAPIParam('BUTTONCODE', 'HOSTED');
			$this->AddAPIParam('BUTTONTYPE', 'CART');
			$this->AddAPIParam('BUTTONSUBTYPE', 'PRODUCTS');
			
			$this->AddCommonButtonParams($description, $reference);
			
			$this->AddAPIButtonVar('button_xref', get_site_url());
		}
		
		function AddButtonOption ($optID, $optPrice)
		{
			$this->AddAPIParam('L_OPTION0SELECT' . $this->OptNo, $optID);
			$this->AddAPIParam('L_OPTION0PRICE' . $this->OptNo, $optPrice);
			$this->OptNo++;
		}
						
		function CreateButton(&$hostedButtonID, $description = 'TBD', $reference = '', $amount = '1.00')
		{
			$hostedButtonID = '';
			// Check that the PayPal login parameters have been set
			if (!$this->IsConfigured())
				return PayPalButtonsAPIClass::PAYPAL_APILIB_CREATEBUTTON_NOLOGIN;	// Cannot Create Button - API Not Configured
			// Create a "Hosted" button on PayPal ... with basic settings
			$this->Reset();
			$this->AddCreateButtonParams($description, $reference, $amount);
			$this->APIStatus = $this->APIAction('Create Button ');
			if ($this->APIStatus !== 'OK')
				return PayPalButtonsAPIClass::PAYPAL_APILIB_CREATEBUTTON_ERROR;
			$hostedButtonID = $this->APIResponses['HOSTEDBUTTONID'];
			return PayPalButtonsAPIClass::PAYPAL_APILIB_CREATEBUTTON_OK;
		}
		
		function DeleteButton($hostedButtonID)
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsConfigured())	
			return;		// Cannot Delete Button - API Not Configured
			if (strlen($hostedButtonID) == 0)
				return;		// Cannot Delete Button - Zero Length Button ID
			// Delete a "Hosted" button on PayPal
			$this->Reset();
			$this->AddDeleteButtonParams($hostedButtonID);
			return $this->APIAction('Delete Button ' . $hostedButtonID);
		}
		
		function SearchButtons()
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsConfigured())
				return;	// Cannot Search for Buttons - API Not Configured
			// Search for "Hosted" buttons on PayPal
			$this->Reset();
			$this->AddSearchButtonParams();
			return $this->APIAction('Buttons Search ');
		}
		
		function GetButtonsList()
		{
			$results = array();
			$status = $this->SearchButtons();
			if ($status != 'OK')
				return $results;
			$buttonNo = 0;
			while (true)
			{
				if (!isset($this->APIResponses['L_HOSTEDBUTTONID'.$buttonNo]))
					break;
				$results[$buttonNo] = new stdClass();
				$results[$buttonNo]->ID = $buttonNo;
				$results[$buttonNo]->hostedButtonID = $this->APIResponses['L_HOSTEDBUTTONID'.$buttonNo];
				$results[$buttonNo]->buttonType = $this->APIResponses['L_BUTTONTYPE'.$buttonNo];				
				$results[$buttonNo]->itemName = $this->APIResponses['L_ITEMNAME'.$buttonNo];
				$results[$buttonNo]->modifyDate = $this->APIResponses['L_MODIFYDATE'.$buttonNo];
				$buttonNo++;
			}
			if ($this->DebugMode)
				StageShowLibUtilsClass::print_r($results, 'results');
			return $results;
		}
		
		function GetInventory($hostedButtonID, &$quantity)
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsConfigured())
				return 'ERROR';	// Cannot Get Button Details - API Not Configured 
			if (strlen($hostedButtonID) == 0)
				return 'ERROR';	// Cannot Get Button Details - Zero Length Button ID 
			$APIStatus = $this->GetInventoryAction($hostedButtonID, $quantity);
			return $APIStatus;
		}
		
		function GetInventoryAction($hostedButtonID, &$quantity)
		{
			$this->Reset();
			$this->AddGetInventoryParams($hostedButtonID);
			$APIStatus = $this->APIAction('Get Inventory ' . $hostedButtonID);
			if ($APIStatus === 'OK')
			{
				if (isset($this->APIResponses['TRACKINV']))
				{
					if (intval($this->APIResponses['TRACKINV']) === 0)
					{
						$quantity = PayPalButtonsAPIClass::PAYPAL_APILIB_INFINITE;
					}
					else
					{
						if (isset($this->APIResponses['ITEMQTY']))
							$quantity = $this->APIResponses['ITEMQTY'];
						else
						$APIStatus === 'ITEMQTY Parameter Missing';
					}
				}
				else
				$APIStatus === 'TRACKINV Parameter Missing';
			}
			return $APIStatus;
		}
		
		function UpdateInventory($hostedButtonID, $quantity, $soldOutUrl = '', $reference = 'X')
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsConfigured())
				return;	// Cannot Update Inventory - API Not Configured 
			if (strlen($hostedButtonID) == 0)
				return;	// Cannot Update Inventory - Zero Length Button ID 
			return $this->UpdateInventoryAction($hostedButtonID, $quantity, $soldOutUrl, $reference);
		}
		
		function UpdateInventoryAction($hostedButtonID, $quantity, $soldOutUrl = '', $reference = 'X')
		{
			$this->Reset();
			// Inventory only works if SOLDOUTURL is set ...
			if ($soldOutUrl == '') $soldOutUrl = get_option('siteurl');
			if ($quantity < 0)
			{
				$this->AddSetInventoryParams($hostedButtonID, -100, $soldOutUrl, $reference);
				return $this->APIAction('Inventory ' . $hostedButtonID);
			}
			$this->AddSetInventoryParams($hostedButtonID, $quantity, $soldOutUrl, $reference);
			return $this->APIAction('Inventory ' . $hostedButtonID);
		}
		
		function AdjustInventory($hostedButtonID, $qtyOffset, $soldOutUrl = '', $reference = 'X') // Function Unused
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsConfigured())
				return 'CONFIG-ERROR';	// Cannot Update Inventory - API Not Configured 
			if (strlen($hostedButtonID) == 0)
				return;	// Cannot Update Inventory - Zero Length Button ID 
			$APIStatus = $this->GetInventoryAction($hostedButtonID, $quantity);
			if ($APIStatus !== 'OK') return $APIStatus;
			if (($qtyOffset == 0) && (isset($this->APIResponses['SOLDOUTURL'])))
			{
				if ($soldOutUrl == '') $soldOutUrl = get_option('siteurl');
				if ($this->APIResponses['SOLDOUTURL'] === $soldOutUrl) return 'OK';
			}
			$quantity += $qtyOffset;
			return $this->UpdateInventoryAction($hostedButtonID, $quantity, $soldOutUrl, $reference);
		}
		
		function GetButton($hostedButtonID)
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsConfigured())
				return 'ERROR';	// Cannot Get Button Details - API Not Configured 
			if (strlen($hostedButtonID) == 0)
				return 'ERROR';	// Cannot Get Button Details - Zero Length Button ID 
			$this->Reset();
			$this->AddGetButtonDetailsParams($hostedButtonID);
			$APIStatus = $this->APIAction('Button ' . $hostedButtonID);
			return $APIStatus;
		}
		
		function GetButtonParams($hostedButtonID)
		{
			if ($this->GetButton($hostedButtonID) !== 'OK')
				return null;
			$buttonParams = $this->APIResponses;
			$unusedQty = 0;
			if ($this->GetInventory($hostedButtonID, $unusedQty) === 'OK')
			{
				$inventoryParams = $this->APIResponses;
				unset($inventoryParams['HOSTEDBUTTONID']);
			}
			else
			$inventoryParams['INVENTORY'] = 'ERROR - Not Available';
			$buttonParams = array_merge($buttonParams, $inventoryParams);
			unset($buttonParams['WEBSITECODE']);
			return $buttonParams;
		}
		
		function UpdateButton($hostedButtonID, $description, $reference, $optPrices, $optIDs = '')
		{
			// Check that the PayPal login parameters have been set
			if (!$this->IsConfigured())
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
			if ($ButtonStatus != PayPalButtonsAPIClass::PAYPAL_APILIB_CREATEBUTTON_OK)
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
}	//End Class PayPalAPIClass

?>