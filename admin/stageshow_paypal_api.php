<?php
/*
Description: PayPal API Functions

Copyright 2011 Malcolm Shergold

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

// STAGESHOW_PAYPAL_xxxx_NVPTARGET_URLs are the URL that PayPal NVP requests are sent to
if (!defined( 'STAGESHOW_PAYPAL_TEST_NVPTARGET_URL' ))
	define ( 'STAGESHOW_PAYPAL_TEST_NVPTARGET_URL', 'https://www.sandbox.paypal.com/cgi-bin/webscr' );
if (!defined('STAGESHOW_PAYPAL_LIVE_NVPTARGET_URL'))
	define ( 'STAGESHOW_PAYPAL_LIVE_NVPTARGET_URL', 'https://www.paypal.com/cgi-bin/webscr' );
if (!defined('STAGESHOW_PAYPAL_DEFAULT_CURRENCY'))
	define ( 'STAGESHOW_PAYPAL_DEFAULT_CURRENCY', 'GBP' );

define ( 'STAGESHOW_PAYPAL_CREATEBUTTON_OK', '0' );
define ( 'STAGESHOW_PAYPAL_CREATEBUTTON_ERROR', '1' );
define ( 'STAGESHOW_PAYPAL_CREATEBUTTON_NOLOGIN', '2' );

// StageShow_PayPal_API.php
// Definitions for API Interface Functions
if (!class_exists('PayPalAPIClass')) {
  class PayPalAPIClass {
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

    var		$DebugMode;				//

    function PayPalAPIClass( $testMode = false ) { //constructor
      // Initialise PayPal API Variables
      $this->Reset();
      
      $this->DebugMode = false;
      $this->APIusername = '';
      $this->APIpassword = '';
      $this->APIsignature = '';
			$this->PayPalNotifyURL = '';
			
			$this->SetTestMode($testMode);
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

    function EnableDebug() {
      $this->DebugMode = true;
    }

		function GetURL($optionURL) {
			// If URL contains a : treat is as an absolute URL
			if (!strpos($optionURL, ':'))
				return get_site_url().'/'.$optionURL;
			else
				return $optionURL;
		}
		
    function SetTestMode($testmode) {
      if ($testmode) {
        $this->APIEndPoint = 'https://api-3t.sandbox.paypal.com/nvp';
				$this->PayPalURL = $this->GetURL(STAGESHOW_PAYPAL_TEST_NVPTARGET_URL);
				$this->PayPalVerifyURL = $this->GetURL(STAGESHOW_PAYPAL_TEST_NVPTARGET_URL);				
				
				if (defined('STAGESHOW_PAYPAL_TEST_VERIFY_IPN_URL'))
					$this->PayPalVerifyURL = $this->GetURL(STAGESHOW_PAYPAL_TEST_VERIFY_IPN_URL);				
      }
      else {
        $this->APIEndPoint = 'https://api-3t.paypal.com/nvp';
				$this->PayPalURL = $this->GetURL(STAGESHOW_PAYPAL_LIVE_NVPTARGET_URL);
				$this->PayPalVerifyURL = $this->GetURL(STAGESHOW_PAYPAL_LIVE_NVPTARGET_URL);
			}
		}

    function SetLoginParams($username, $password, $signature, $email, $currency) {
      if (( strlen( $username ) == 0 ) || ( strlen( $password ) == 0 ) || ( strlen( $signature ) == 0 )) {
        $this->APIusername = '';
        $this->APIpassword = '';
        $this->APIsignature = '';
				
				$this->APIStatusMsg = __('Missing PayPal Login Param', STAGESHOW_DOMAIN_NAME);
        return;
      }

      $this->APIusername = $username;
      $this->APIpassword = $password;
      $this->APIsignature = $signature;
      $this->APIemail = $email;
			$this->PayPalCurrency = $currency;

      if (defined('STAGESHOW_PAYPAL_IPN_NOTIFY_URL'))
				$this->PayPalNotifyURL = STAGESHOW_PAYPAL_IPN_NOTIFY_URL;
				
			$this->APIStatusMsg = '';
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

    function AddCreateButtonParams($description = 'TBD', $reference = '')
    {
      $this->InitAPICallParams('BMCreateButton');
      $this->AddAPIParam('BUTTONTYPE', 'CART');
      $this->AddAPIParam('BUTTONSUBTYPE', 'PRODUCTS');

      $this->AddAPIButtonVar('item_name', $description);
      $this->AddAPIButtonVar('item_number', $reference);
      $this->AddAPIButtonVar('amount', '1.00');
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

    function AddSetInventoryParams($hostedButtonID, $quantity, $soldOutUrl = '')
    {
      $this->AddAPIButtonParams('BMSetInventory', $hostedButtonID);

			if ($quantity < 0)
			{
				// TODO-IMPROVEMENT - Disable Inventory Fudged - PayPal would not disable both Inventory Control and PNL
				// Disable Inventory Control ... enables PNL
				$this->AddAPIParam('TRACKINV', '0');
				$this->AddAPIParam('TRACKPNL', '1');
				$this->AddAPIParam('ITEMNUMBER', 'X');
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

      $this->AddAPIParam('OPTION0NAME', 'TicketTypes');
    }

    function AddButtonOption ($optID, $optPrice)
    {
      $this->AddAPIParam('L_OPTION0SELECT' . $this->OptNo, $optID);
      $this->AddAPIParam('L_OPTION0PRICE' . $this->OptNo, $optPrice);
      $this->OptNo++;
    }

    function APIAction($APIName)
    {
      $this->HTTPAction($this->APIEndPoint, $APIName);

      if ($this->APIStatusMsg === 'ERROR')
      {
      }
      else
      {
        parse_str($this->APIResponseText, $this->APIResponses);

        if ($this->DebugMode)
        {
					echo "--------------------------------------<br>\n";
					echo "APIResponses:<br>\n";
          Print_r($this->APIResponses);
          echo "<br>\n";
        }

        if (isset($this->APIResponses['ACK']))
        {
					if ($this->APIResponses['ACK'] == 'Success')
						$this->APIStatusMsg = 'OK';
					else
					{
						$this->APIStatusMsg = 'API Error ';
						if (isset($this->APIResponses['L_ERRORCODE0']))
							$this->APIStatusMsg .= $this->APIResponses['L_ERRORCODE0'];
						$this->APIStatusMsg .= ' - ';
						if (isset($this->APIResponses['L_SHORTMESSAGE0']))
							$this->APIStatusMsg .= $this->APIResponses['L_SHORTMESSAGE0'];
					}
				}
				else
					$this->APIStatusMsg = 'API Error - No Response';
      }

      return $this->APIStatusMsg;
    }

    function HTTPAction($url, $APIName)
    {
			$args = array(
				'method' => 'POST',
				'body' => $this->URLParamsArray,
				'sslverify' => false
			);
			
			$request = new WP_Http;
			$result = $request->request( $url, $args );
			if ( is_wp_error($result) ) 
			{
				$this->APIResponseText = '';
				$this->APIStatus = 'ERROR';
				$this->APIStatusMsg = $result->get_error_message();
			}
			else
			{
				$response = $result['response'];
				
				$this->APIResponseText = $result['body'];
				$this->APIStatus = $response['code'];
				$this->APIStatusMsg = $response['message'];
			}
			
      return $this->APIStatusMsg;
    }

    function CreateButton(&$hostedButtonID, $description = 'TBD', $reference = '')
    {
	    $hostedButtonID = '';
	    
      // Check that the PayPal login parameters have been set
      if (!$this->IsConfigured())
        return STAGESHOW_PAYPAL_CREATEBUTTON_NOLOGIN;	// Cannot Create Button - API Not Configured
			
      // Create a "Hosted" button on PayPal ... with basic settings
      $this->Reset();

      $this->AddCreateButtonParams($description, $reference);
      $this->APIStatus = $this->APIAction('Create Button ');

      if ($this->APIStatus !== 'OK')
				return STAGESHOW_PAYPAL_CREATEBUTTON_ERROR;
			
      $hostedButtonID = $this->APIResponses['HOSTEDBUTTONID'];
	      
      return STAGESHOW_PAYPAL_CREATEBUTTON_OK;
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

    function GetInventory($hostedButtonID, &$quantity)
    {
      // Check that the PayPal login parameters have been set
      if (!$this->IsConfigured())
        return 'ERROR';	// Cannot Get Button Details - API Not Configured 

      if (strlen($hostedButtonID) == 0)
        return 'ERROR';	// Cannot Get Button Details - Zero Length Button ID 

      $this->Reset();
      $this->AddGetInventoryParams($hostedButtonID);

      $APIStatus = $this->APIAction('Get Inventory ' . $hostedButtonID);
      if ($APIStatus === 'OK')
      {
      }
      
      return $APIStatus;
    }    

    function UpdateInventory($hostedButtonID, $quantity, $soldOutUrl = '')
    {
      // Check that the PayPal login parameters have been set
      if (!$this->IsConfigured())
        return;	// Cannot Update Inventory - API Not Configured 

      if (strlen($hostedButtonID) == 0)
        return;	// Cannot Update Inventory - Zero Length Button ID 

      $this->Reset();
      
      if ($quantity < 0)
      {
				$this->AddSetInventoryParams($hostedButtonID, -100, $soldOutUrl);
				$this->APIAction('Inventory ' . $hostedButtonID);
      }
      
      $this->AddSetInventoryParams($hostedButtonID, $quantity, $soldOutUrl);
      return $this->APIAction('Inventory ' . $hostedButtonID);
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

    function UpdateButton($hostedButtonID, $description, $reference, $optIDs, $optPrices)
    {
      // Check that the PayPal login parameters have been set
      if (!$this->IsConfigured())
        return;	// Cannot Update Button - API Not Configured 
        
      if (strlen($hostedButtonID) == 0)
        return;	// Cannot Update Button - Zero Length Button ID 

      $this->Reset();
      $this->AddSetButtonParams($hostedButtonID, $description, $reference);

      if (count($optIDs) != count($optPrices))
      {
        // Error - Unequal Array sizes
        echo "ERROR: optIDs[] and optPrices[] different sizes in UpdateButton() function <br>\n";
        return;
      }

      for ($index=0; $index<count($optIDs); $index++)
      {
        $this->AddButtonOption($optIDs[$index], $optPrices[$index]);
      }

      return $this->APIAction('Button ' . $hostedButtonID);
    }

		function VerifyPayPalLogin($username, $password, $signature)
		{
      $this->APIemail = '';      
			$this->SetLoginParams($username, $password, $signature, '', 'GBP');
			
			// Blank PayPal login params disabled this PayPal interface
			if ((strlen($username) == 0) && (strlen($password) == 0) && (strlen($signature) == 0))
				return true;
			
//      $this->SearchButtons();
			
			// Get primary email from PayPal - Bug in SandBox - Does not work if primary email is changed
			$this->CreateButton($hostedButtonID);
			if ($hostedButtonID === '') 
			{
				//echo "CreateButton FAILED<br>\n";
				return false;
			}
			
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
      
      // Tidy up - Button was only to check login and get email .... delete it!
      $this->DeleteButton($hostedButtonID);
			
			// VerifyPayPalLogin - Returned $this->APIStatusMsg 
			
			return ($this->APIStatusMsg === 'OK');
		}
  }
}	//End Class PayPalAPIClass

global $myPayPalAPILiveObj;
global $myPayPalAPITestObj;
if (class_exists('PayPalAPIClass')) {
  $myPayPalAPILiveObj = new PayPalAPIClass();
  $myPayPalAPITestObj = new PayPalAPIClass(true);
}

?>