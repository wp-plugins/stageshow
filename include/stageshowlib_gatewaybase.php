<?php
/*
Description: Payment Gateway API Functions

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

include 'stageshowlib_logfile.php';
	
// Definitions for API Interface Functions
if (!class_exists('StageShowLibGatewayBaseClass')) 
{
	if (!defined('PAYMENT_API_SALENAME_TEXTLEN'))
	{
		define('PAYMENT_API_SALENAME_TEXTLEN',128);
		define('PAYMENT_API_SALEEMAIL_TEXTLEN',127);
		define('PAYMENT_API_SALEPPNAME_TEXTLEN',128);
		define('PAYMENT_API_SALEPPSTREET_TEXTLEN',200);
		define('PAYMENT_API_SALEPPCITY_TEXTLEN',40);
		define('PAYMENT_API_SALEPPSTATE_TEXTLEN',40);
		define('PAYMENT_API_SALEPPZIP_TEXTLEN',20);
		define('PAYMENT_API_SALEPPCOUNTRY_TEXTLEN',64);
		define('PAYMENT_API_SALEPPPHONE_TEXTLEN',64);	
		define('PAYMENT_API_SALETXNID_TEXTLEN',20);
		define('PAYMENT_API_SALEMETHOD_TEXTLEN',20);		
		define('PAYMENT_API_SALESTATUS_TEXTLEN',20);
		define('PAYMENT_API_EXPTOKEN_TEXTLEN',20);

		define('PAYMENT_API_SALENAME_EDITLEN',80);
		define('PAYMENT_API_SALEEMAIL_EDITLEN',80);
		define('PAYMENT_API_SALEPPNAME_EDITLEN',80);
		define('PAYMENT_API_SALEPPSTREET_EDITLEN',80);
		define('PAYMENT_API_SALEPPCITY_EDITLEN',40);
		define('PAYMENT_API_SALEPPSTATE_EDITLEN',40);
		define('PAYMENT_API_SALEPPZIP_EDITLEN',20);
		define('PAYMENT_API_SALEPPCOUNTRY_EDITLEN',64);
		define('PAYMENT_API_SALEPPPHONE_EDITLEN',64);	
		define('PAYMENT_API_SALETXNID_EDITLEN',40);		// Extended to 40 because text box was too small
		define('PAYMENT_API_SALESTATUS_EDITLEN',20);

		define('PAYMENT_API_URL_TEXTLEN',110);
		define('PAYMENT_API_URL_EDITLEN',80);
			
		define('PAYMENT_API_FILEPATH_TEXTLEN',255);
		define('PAYMENT_API_FILEPATH_EDITLEN', 95);

		define('PAYMENT_API_CHECKOUT_TIMEOUT_TEXTLEN', 3);	
		define('PAYMENT_API_CHECKOUT_TIMEOUT_EDITLEN', 4);	
		define('PAYMENT_API_CHECKOUT_TIMEOUT_DEFAULT', 60);	

		if (!defined('PAYMENT_API_SALESTATUS_COMPLETED'))
		{
			define('PAYMENT_API_SALESTATUS_COMPLETED', 'Completed');
			define('PAYMENT_API_SALESTATUS_CHECKOUT', 'Checkout');
			define('PAYMENT_API_SALESTATUS_UNVERIFIED', 'Unverified');
			define('PAYMENT_API_SALESTATUS_PENDINGPPEXP', 'PendingPPExp');
			define('PAYMENT_API_SALESTATUS_TIMEOUT', 'Timeout');
		}		
	}
		
	if (!defined('STAGESHOWLIB_FILENAME_GATEWAYAPILOG'))
		define('STAGESHOWLIB_FILENAME_GATEWAYAPILOG', 'GatewayAPILog.txt');
		
	if (!defined('PAYPAL_APILIB_DEFAULT_CURRENCY'))
		define ( 'PAYPAL_APILIB_DEFAULT_CURRENCY', 'GBP' );

	class StageShowLibGatewayBaseClass // Define class
	{
		const STAGESHOWLIB_CHECKOUTSTYLE_STANDARD = 1;
		
		var	$testmode = false;
		var $items = array();
		var $totalDue = 0;

		var	$URLParamsArray;  	//  Array of params for Gateway API HTTP request
		
		var		$DebugEnabled = false;
		var		$DebugDisplay = false;
		var		$DebugLogging = false;
		
		function __construct( $opts )
		{
			//constructor
			$this->opts = $opts;
			$this->caller = $opts['Caller'];
			$this->myDomain = $opts['Domain'];	
			$this->myDBaseObj = $opts['DBaseObj'];	
			
			$this->Reset();
			
			if ($this->myDBaseObj!= null)
			{
				if ($this->myDBaseObj->getDbgOption('Dev_ShowGatewayAPI'))
				{
					$this->EnableDebug();
				}
				if ($this->myDBaseObj->getDbgOption('Dev_LogGatewayAPI'))
				{
					$this->EnableLogging();
				}
			}
				
		}

/* ------------------------------------------------------------------

	Gateway Access Functions

	The functions that follow can be redefined in derived classes to
	implement a payment gateway.
	
------------------------------------------------------------------ */
			
		static function GetName()
		{
			return 'Undefined';
		}
		
		static function IsValidGateway($pluginID)
		{
			return true;
		}
		
		static function GetType()
		{
			return 'Undefined';
		}
		
		static function GetID()
		{
			return self::GetType();
		}
		
		static function GetParent()
		{
			return '';
		}
		
		static function GetDefaultCurrency()
		{
			return PAYPAL_APILIB_DEFAULT_CURRENCY;
		}
		
		function GetCheckoutType()
		{
			return self::STAGESHOWLIB_CHECKOUTSTYLE_STANDARD;
		}
		
		function GetCurrencyOptionID()
		{
			return $this->GetName().'Currency';
		}

		function GetCurrencyTable()
		{
			return array();
		}
		
		function IsCallback(&$mode, &$token, &$payerID)
		{
			return false;
		}
		
		function IsTestServer()
		{
			return false;
		}
		
		//Returns an array of admin options
		function Gateway_GetOptions() 
		{
			$ourOptions = array(
			);
			
			return $ourOptions;
		}
		
		function Gateway_SettingsRowsDefinition()
		{
			// Returns an array defining the settings for a Gateway
			return array();
		}
		
		function Gateway_SystemFields()
		{
			// Returns an array of fields that are added for this gateway
			return array();
		}
		
		function Gateway_ClientFields()
		{
			// Returns an array defining user specified fields before checkout
			return array();
		}
		
		function GetCurrencyList()
		{
			$currSelect = array();
			$CurrencyTable = $this->GetCurrencyTable();			
			foreach ($CurrencyTable as $index => $currDef)
			{
				$currSelect[$index] = $currDef['Currency'];
				$currSelect[$index] .= '|';
				$currSelect[$index] .= $currDef['Name'];
				$currSelect[$index] .= ' ('.$currDef['Symbol'].') ';
			}
			
			return $currSelect;
		}
			
		function Gateway_LoadUserScripts()
		{
		}
		
		function Gateway_LoadAdminStyles()
		{
		}
		
		function LoginGatewayAPI($adminOptions, $dbgOptions)
		{
		}
				
		function IsLoginChanged ($adminOptions)
		{
			return false;
		}
			        
		function IsGatewayConfigured ($adminOptions)
		{
			$this->UndefinedFunction("IsGatewayConfigured");
			return false;
		}
		
		function VerifyLogin()
		{
			return 'VerifyLogin not defined';
		}
		
		function GetButtonImage($buttonID)
		{
			return '';
		}
		
		function IsCheckout()
		{
			$buttonID = 'checkout';
			if (defined('CORONDECK_RUNASDEMO'))
			{
				$pluginID = $this->myDBaseObj->get_name();
				$buttonID .= '_'.$pluginID;
			}
			
			if ($this->myDBaseObj->IsButtonClicked($buttonID)) 
				$this->checkout = 'checkout';
			else
				$this->checkout = '';
			
			return $this->checkout;
		}
		
		function GetGatewayRedirectURL($saleId, $saleDetails)
		{
			$this->UndefinedFunction("GetGatewayRedirectURL");
		}
			
		function IsComplete()
		{
			return null;
		}
		
		function UndefinedFunction($funcName)
		{
			$gatewayname = $this->GetName();
			echo "$funcName method not defined for $gatewayname Gateway";
			exit;
			
		}
		
		function AddItem($itemName, $itemPrice, $qty, $shipping)
		{	
			static $paramCount = 1;
			
			$item = new stdClass();
			$item->name = $itemName;
			$item->price = $itemPrice;
			$item->qty = $qty;
			$item->shipping = $shipping;
			
			$this->totalDue += ($itemPrice * $qty);

			$this->items[$paramCount] = $item;
			$paramCount++;		
		}			
		
/* ------------------------------------------------------------------

	Public Functions

------------------------------------------------------------------ */
			
		function GetCurrencyDef($currency)
		{
			$currencyTable = $this->GetCurrencyTable();
			
			foreach ($currencyTable as $currencyDef)
			{
				if ($currencyDef['Currency'] == $currency)
				{
					return $currencyDef;
				}
			}
			
			return null;
		}
		
/* ------------------------------------------------------------------

	Local Functions

------------------------------------------------------------------ */
			
		static function GetGatewayAtts($filePath)
		{
			$gatewayAtts = new stdClass();
			$gatewayAtts->Filename = basename($filePath);
			$gatewayAtts->CallbackFilename = str_replace('_gateway.php', '_callback.php', $gatewayAtts->Filename);
			$gatewayAtts->Id = str_replace('stageshowlib_', '', str_replace('_gateway.php', '', $gatewayAtts->Filename));
			
			$gatewayAtts->ClassName = 'StageShowLib_'.$gatewayAtts->Id.'_GatewayClass'; 
			
			return $gatewayAtts;
		}
			
		static function GetGatewaysList()
		{
			static $gatewaysList = null;
			if ($gatewaysList != null)
				return $gatewaysList;
				
			$gatewaysList = array();
			$parentsList = array();
			
			$dir = dirname(__FILE__);
			$dir .= '/stageshowlib_*_gateway.php';					
			
			// Now get the files list and convert paths to file names
			$filesList = glob($dir);
			foreach ($filesList as $filePath)
			{
				$gatewayAtts = self::GetGatewayAtts($filePath);
/*				
				$count = preg_match('/stageshowlib[_a-zA-z]*_([a-zA-z]*)_gateway\.php/', $filePath, $matches);
				StageShowLibUtilsClass::print_r($matches, '$matches');
				if ($count == 0)
				{
					continue;					
				}
*/		
				include $gatewayAtts->Filename;      						// i.e. stageshowlib_paypal_gateway.php
				$gatewayClass = $gatewayAtts->ClassName; 
				$gatewayAtts->Obj = new $gatewayClass(null); 					// i.e. StageShowLib_paypal_GatewayClass
				$gatewayAtts->Name = $gatewayAtts->Obj->GetName();
				$gatewayAtts->Type = $gatewayAtts->Obj->GetType();
				if ($gatewayAtts->Name == '') continue;
				
				$parentsList[] = $gatewayAtts->Obj->GetParent();
				
				$gatewaysList[$gatewayAtts->Id] = $gatewayAtts;
			}
			
			foreach ($parentsList as $parent)
			{
				if (isset($gatewaysList[$parent]))
				{
					unset($gatewaysList[$parent]);
				}
			}
			
			return $gatewaysList;
		}

		function Reset()
		{
			$this->totalDue = 0;
			$this->items = array();
			$this->URLParamsArray = null;
		}
		
		function EnableDebug()
		{
			$this->DebugEnabled = true;
			$this->DebugDisplay = true;
		}
		
		function EnableLogging()
		{
			$this->DebugEnabled = true;
			$this->DebugLogging = true;
		}
				
		function WriteToLogFile($LogNotifyFile, $DebugMessage)
		{
			$this->LogsFolder = $this->myDBaseObj->adminOptions['LogsFolderPath'].'/';
			$logFileObj = new StageShowLibLogFileClass($this->LogsFolder);
			$logFileObj->LogToFile($LogNotifyFile, $DebugMessage, StageShowLibDBaseClass::ForAppending);
		}

		function OutputDebug($msg, $flush = false)
		{
			static	$msgCache = '';
			$msgCache .= $msg;
			
			if ($this->DebugDisplay)
			{
				echo str_replace("\n", "<br>", $msg);				
			}
			
			if ($this->DebugLogging && $flush)
			{
				$this->WriteToLogFile(STAGESHOWLIB_FILENAME_GATEWAYAPILOG, $msgCache);
				$msgCache = '';
			}
		}
		
		function RedirectToGateway($gatewayURL)
		{
			header( 'Location: '.$gatewayURL ) ;
			exit;
		}

		protected static function isOptionSet($adminOptions, $optionID)
		{
			if (!isset($adminOptions[$optionID]))
				return false;
				
			return ($adminOptions[$optionID] != '');
		}
		
		protected static function IsOptionChanged($adminOptions, $optionID)
		{
			if (!class_exists('StageShowLibUtilsClass')) 
			{
				include_once('stageshowlib_utils.php');
			}
			
			if (isset($_POST[$optionID]) && (trim(StageShowLibUtilsClass::GetArrayElement($adminOptions, $optionID)) !== trim($_POST[$optionID])))
			{
				return true;
			}
					
			return false;
		}		

	}
}

?>