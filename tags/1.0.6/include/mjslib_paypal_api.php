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

if( !class_exists( 'WP_Http' ) )
	include_once( ABSPATH . WPINC. '/class-http.php' );

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
				
if (!defined( 'PAYPAL_APILIB_PPLOGIN_USER_TEXTLEN' ))
{
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
	define('PAYPAL_APILIB_PPSALETXNID_EDITLEN',20);
	define('PAYPAL_APILIB_PPSALESTATUS_EDITLEN',20);

	define('PAYPAL_APILIB_PPBUTTONID_TEXTLEN',16);

	define('PAYPAL_APILIB_URL_TEXTLEN',110);
	define('PAYPAL_APILIB_URL_EDITLEN',110);
}

// PAYPAL_APILIB_xxxx_NVPTARGET_URLs are the URL that PayPal NVP requests are sent to
if (!defined( 'PAYPAL_APILIB_TEST_NVPTARGET_URL' ))
	define ( 'PAYPAL_APILIB_TEST_NVPTARGET_URL', 'https://www.sandbox.paypal.com/cgi-bin/webscr' );
if (!defined('PAYPAL_APILIB_LIVE_NVPTARGET_URL'))
	define ( 'PAYPAL_APILIB_LIVE_NVPTARGET_URL', 'https://www.paypal.com/cgi-bin/webscr' );
if (!defined('PAYPAL_APILIB_DEFAULT_CURRENCY'))
	define ( 'PAYPAL_APILIB_DEFAULT_CURRENCY', 'GBP' );

// StageShow_PayPal_API.php
// Definitions for API Interface Functions
if (!class_exists('PayPalAPIClass')) {
  class PayPalAPIClass // Define class
  {
		const PAYPAL_APILIB_CREATEBUTTON_OK = 0;
		const PAYPAL_APILIB_CREATEBUTTON_ERROR = 1;
		const PAYPAL_APILIB_CREATEBUTTON_NOLOGIN = 2;
		
    // Class variables:
    var		$URLParamsArray;  //  Array of params for PayPal API HTTP request
    var		$APIEndPoint;			//	PayPal API access URL
    var		$APIusername;			//	PayPal login name
    var		$APIpassword;			//	PayPal login password
    var		$APIsignature;		//	PayPal login signature
    var		$APIemail;				//	PayPal primary email
		
		var		$PayPalCurrency;	// Currency Code for checkout
		
    var		$PayPalNotifyURL; //  URL of the "listener" URL for PayPal IPN messages
		var		$PayPalURL;				//  URL for PayPal NVP Requests
		var		$PayPalVerifyURL;	//  URL for PayPal Verify IPN Requests
		
    var   $APIStatus;				//	API response status value
    var   $APIResponseText;	//	API response text
    var   $APIResponses;		//	API response data parsed into an array

    var		$ButtonVarCount;  //  The number of button variables defined
    var		$OptNo;						//  The number of button options defined

		var		$caller;					//	The path of the calling function
		
    var		$DebugMode;				//

    function __construct( $caller ) { //constructor
			$this->caller = $caller;
			
      // Initialise PayPal API Variables
      $this->Reset();
      
      $this->DebugMode = false;
      $this->APIusername = '';
      $this->APIpassword = '';
      $this->APIsignature = '';
			$this->PayPalNotifyURL = '';
    }

    function Reset() {
      $this->URLParamsArray = null;
      $this->ButtonVarCount = 0;
      $this->OptNo = 0;
    }

    function IsConfigured() {
      if ((strlen( $this->APIusername ) == 0) || ( strlen( $this->APIpassword ) == 0 ) || ( strlen( $this->APIsignature ) == 0 )) {
        return false;
      }

      if (strlen( $this->APIEndPoint ) == 0) {
        return false;
      }

      return true;
    }

		function CheckIsConfigured()
		{
			if ($this->IsConfigured())
				return true;
				
			$settingsPageURL = get_option('siteurl').'/wp-admin/admin.php?page=stageshow_settings';
			$actionMsg = __('Set PayPal Settings First - <a href='.$settingsPageURL.'>Here</a>');
			echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';
			
			return false;
		}
		
    function EnableDebug() {
      $this->DebugMode = true;
    }

		function GetURL($optionURL) {
			// If URL contains a : treat is as an absolute URL
			if (strpos($optionURL, ':') !== false)
				$rtnVal = $optionURL;
			else if (strpos($optionURL, '{pluginpath}') !== false)
			{
				$pluginPath = plugin_basename($this->caller);
				$posn = strpos($pluginPath, '/');
				$pluginName = substr($pluginPath, 0, $posn);
				$rtnVal = str_replace('{pluginpath}', WP_PLUGIN_URL.'/'.$pluginName, $optionURL);
			}
			else
				$rtnVal = get_site_url().'/'.$optionURL;
			
			return $rtnVal;
		}
		
    function SetTestMode($testmode) {
      if ($testmode) {
        $this->APIEndPoint = 'https://api-3t.sandbox.paypal.com/nvp';
				$this->PayPalURL = $this->GetURL(PAYPAL_APILIB_TEST_NVPTARGET_URL);
      }
      else {
        $this->APIEndPoint = 'https://api-3t.paypal.com/nvp';
				$this->PayPalURL = $this->GetURL(PAYPAL_APILIB_LIVE_NVPTARGET_URL);
			}
			
			if (defined('PAYPAL_APILIB_OVERRIDE_VERIFY_IPN_URL'))
				$this->PayPalVerifyURL = $this->GetURL(PAYPAL_APILIB_OVERRIDE_VERIFY_IPN_URL);
			else				
				$this->PayPalVerifyURL = $this->PayPalURL;
		}

    function SetLoginParams($loginEnv, $username, $password, $signature, $currency = '', $email = '') {
      if (( strlen( $username ) == 0 ) || ( strlen( $password ) == 0 ) || ( strlen( $signature ) == 0 )) {
        $this->APIusername = '';
        $this->APIpassword = '';
        $this->APIsignature = '';
				
				$this->APIStatusMsg = __('Missing PayPal Login Param');
        return;
      }

			$this->SetTestMode($loginEnv == 'sandbox');
			
      $this->APIusername = $username;
      $this->APIpassword = $password;
      $this->APIsignature = $signature;
      $this->APIemail = $email;
			$this->PayPalCurrency = $currency;

			$this->APIStatusMsg = '';
		}

    function SetIPNListener($IPNListenerURL) {
			$this->PayPalNotifyURL = $IPNListenerURL;
    }

    function AddAPIParam($tagName, $tagValue) {
      $this->URLParamsArray[$tagName]=$tagValue;
      
      if ($this->DebugMode) {
        echo "$tagName=$tagValue<br>\n";
      }
    }

    function AddAPIButtonVar($tagId, $tagValue) {
      if (strlen($tagValue) > 0)
      {
        $tagName = "L_BUTTONVAR$this->ButtonVarCount";
        $this->AddAPIParam($tagName, $tagId.'='.$tagValue);

        $this->ButtonVarCount++;
      }
    }

    function InitAPICallParams($methodID) {
			if ($this->DebugMode) {
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

      $this->AddAPIButtonVar('item_name', $description);
      $this->AddAPIButtonVar('item_number', $reference);
      $this->AddAPIButtonVar('amount', $amount);
      $this->AddAPIButtonVar('currency_code', $this->PayPalCurrency);
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

      $this->AddAPIButtonVar('item_name', $description);
      $this->AddAPIButtonVar('item_number', $reference);

      $this->AddAPIButtonVar('button_xref', $hostedButtonID);
      $this->AddAPIButtonVar('currency_code', $this->PayPalCurrency);
    }

    function AddButtonOption ($optID, $optPrice)
    {
      $this->AddAPIParam('L_OPTION0SELECT' . $this->OptNo, $optID);
      $this->AddAPIParam('L_OPTION0PRICE' . $this->OptNo, $optPrice);
      $this->OptNo++;
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

    static function HTTPAction($url, $urlParams = '')
    {
			$args = array(
				'method' => 'POST',
				'body' => $urlParams,
				'sslverify' => false
			);

			$request = new WP_Http;
			$result = $request->request( $url, $args );
			if ( is_wp_error($result) ) 
			{
				$response['APIResponseText'] = '';
				$response['APIStatus'] = 'ERROR';
				$response['APIStatusMsg'] = $result->get_error_message();
			}
			else
			{
				$response['APIResponseText'] = $result['body'];
				$response['APIStatus'] = $result['response']['code'];
				$response['APIStatusMsg'] = $result['response']['message'];
			}
			
      return $response;
    }

    function CreateButton(&$hostedButtonID, $description = 'TBD', $reference = '', $amount = '1.00')
    {
	    $hostedButtonID = '';
	    
      // Check that the PayPal login parameters have been set
      if (!$this->IsConfigured())
        return PayPalAPIClass::PAYPAL_APILIB_CREATEBUTTON_NOLOGIN;	// Cannot Create Button - API Not Configured
			
      // Create a "Hosted" button on PayPal ... with basic settings
      $this->Reset();

      $this->AddCreateButtonParams($description, $reference, $amount);
      $this->APIStatus = $this->APIAction('Create Button ');

      if ($this->APIStatus !== 'OK')
				return PayPalAPIClass::PAYPAL_APILIB_CREATEBUTTON_ERROR;
			
      $hostedButtonID = $this->APIResponses['HOSTEDBUTTONID'];
	      
      return PayPalAPIClass::PAYPAL_APILIB_CREATEBUTTON_OK;
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
				MJSLibUtilsClass::print_r($results, 'results');
			
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
				if (isset($this->APIResponses['ITEMQTY']))
					$quantity = $this->APIResponses['ITEMQTY'];
				else
					$APIStatus === 'ITEMQTY Parameter Missing';
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

    function AdjustInventory($hostedButtonID, $qtyOffset, $soldOutUrl = '', $reference = 'X')
    {
      // Check that the PayPal login parameters have been set
      if (!$this->IsConfigured())
        return;	// Cannot Update Inventory - API Not Configured 

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
			$this->SetLoginParams($loginEnv, $username, $password, $signature, 'GBP');
			
			// Blank PayPal login params disabled this PayPal interface
			if ((strlen($username) == 0) && (strlen($password) == 0) && (strlen($signature) == 0))
				return true;
			
			// Get primary email from PayPal - Bug in SandBox - Does not work if primary email is changed
			$this->CreateButton($hostedButtonID);
			if ($hostedButtonID != PayPalAPIClass::PAYPAL_APILIB_CREATEBUTTON_OK) 
			{
				//echo "CreateButton FAILED<br>\n";
				return false;
			}
/*			
			// Get primary email from PayPal - Doesn't seem to work anymore ... 
      if ($this->GetButton($hostedButtonID) === 'OK')
      {
				$varNo = 0;
				while (true)
				{
					if (!isset($this->APIResponses['L_BUTTONVAR'.$varNo]))
						break;
					
					$lButtonVar = $this->APIResponses['L_BUTTONVAR'.$varNo];
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