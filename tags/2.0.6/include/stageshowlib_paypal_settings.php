<?php
/* 
Description: Code for Managing PayPal Settings
 
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

include 'stageshowlib_table.php';

if (!class_exists('PayPalSettingsAdminListClass')) 
{
	define('PAYPAL_APILIB_URL_TEXTLEN',110);
	define('PAYPAL_APILIB_URL_EDITLEN',110);
		
	class PayPalSettingsAdminListClass extends StageShowLibAdminListClass // Define class
	{	
		const TABLEPARAM_PAYPALLOCK = 'PayPalLock';
		
		function __construct($env) //constructor
		{			
			$this->blockPayPalEdit = $env['BlockPayPalEdit'];
			
			// Call base constructor
			parent::__construct($env, true);
			
			$this->HeadersPosn = StageShowLibTableClass::HEADERPOSN_TOP;
		}
		
		function GetTableID($result)
		{
			return "paypal-settings";
		}
		
		function GetRecordID($result)
		{
			return '';
		}
		
		function GetMainRowsDefinition()
		{
			$this->isTabbedOutput = true;
			
			$rowDefs = array(			
				array(self::TABLEPARAM_LABEL => 'PayPal Settings',       self::TABLEPARAM_ID => 'paypal-settings-tab', ),
			);
			
			return $rowDefs;
		}		
		
		function GetDetailsRowsDefinition()
		{
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$paypalUploadImagesPath = WP_CONTENT_DIR . '/uploads/'.$pluginID.'/images';
			
			$CurrencyTable = StageShowLibSalesDBaseClass::GetCurrencyTable();			
			foreach ($CurrencyTable as $index => $currDef)
			{
				$currSelect[$index] = $currDef['Currency'];
				$currSelect[$index] .= '|';
				$currSelect[$index] .= $currDef['Name'];
				$currSelect[$index] .= ' ('.$currDef['Symbol'].') ';
			}
			
			$trolleyOptions = array(
				StageShowLibSalesDBaseClass::STAGESHOWLIB_TROLLEYTYPE_PAYPAL.'|'.__('PayPal Shopping Cart', $this->myDomain),
				StageShowLibSalesDBaseClass::STAGESHOWLIB_TROLLEYTYPE_INTEGRATED.'|'.__('Integrated Shopping Trolley', $this->myDomain),
				);
				
			$rowDefs = array(
				array(self::TABLEPARAM_LABEL => 'Shopping Trolley',                self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'TrolleyType',           self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT, self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_ITEMS => $trolleyOptions, self::TABLEPARAM_DEFAULT => StageShowLibSalesDBaseClass::STAGESHOWLIB_TROLLEYTYPE_INTEGRATED, self::TABLEPARAM_ONCHANGE => 'onSalesInterfaceClick'),
				array(self::TABLEPARAM_LABEL => 'Environment',                     self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalEnv',             self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT, self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_ITEMS => array('live|Live', 'sandbox|Sandbox'), ),
				array(self::TABLEPARAM_LABEL => 'Merchant ID',                     self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalMerchantID',      self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_MERCHANTID_TEXTLEN,  self::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array(self::TABLEPARAM_LABEL => 'API User',                        self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalAPIUser',         self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_USER_TEXTLEN,        self::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array(self::TABLEPARAM_LABEL => 'API Password',                    self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalAPIPwd',          self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_PWD_TEXTLEN,         self::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array(self::TABLEPARAM_LABEL => 'API Signature',                   self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalAPISig',          self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_SIG_TEXTLEN,         self::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN,  ),
				array(self::TABLEPARAM_LABEL => 'Account EMail',                   self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalAPIEMail',        self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_EMAIL_TEXTLEN,       self::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array(self::TABLEPARAM_LABEL => 'Checkout Timeout',                self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'CheckoutTimeout',       self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_LEN => PAYPAL_APILIB_CHECKOUT_TIMEOUT_TEXTLEN,    self::TABLEPARAM_SIZE => PAYPAL_APILIB_CHECKOUT_TIMEOUT_EDITLEN, ),
				array(self::TABLEPARAM_LABEL => 'Currency',                        self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalCurrency',        self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT, self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_ITEMS => $currSelect, ),
				array(self::TABLEPARAM_LABEL => 'PayPal Header Image File',        self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalHeaderImageFile', self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT, self::TABLEPARAM_DIR => $paypalUploadImagesPath, self::TABLEPARAM_EXTN => 'gif', ),
				array(self::TABLEPARAM_LABEL => 'EMail Logo Image File',           self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalLogoImageFile',   self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT, self::TABLEPARAM_DIR => $paypalUploadImagesPath, self::TABLEPARAM_EXTN => 'jpg', ),
				array(self::TABLEPARAM_LABEL => 'Checkout Complete URL',           self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'CheckoutCompleteURL',   self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_LEN => PAYPAL_APILIB_URL_TEXTLEN,         self::TABLEPARAM_SIZE => PAYPAL_APILIB_URL_EDITLEN,  ),
				array(self::TABLEPARAM_LABEL => 'Checkout Cancelled URL',          self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'CheckoutCancelledURL',  self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_LEN => PAYPAL_APILIB_URL_TEXTLEN,         self::TABLEPARAM_SIZE => PAYPAL_APILIB_URL_EDITLEN,  ),
			);
			
			$rowDefs = $this->MergeSettings(parent::GetDetailsRowsDefinition(), $rowDefs);
			
			// FUNCTIONALITY: Settings - PayPal settings Read-only once Prices configured
			if (isset($this->blockPayPalEdit) && $this->blockPayPalEdit)
			{				
				foreach ($rowDefs as $rowKey => $rowDef)
				{
					if (!isset($rowDef[self::TABLEPARAM_PAYPALLOCK]))
						continue;
						
					switch ($rowDef[StageShowLibTableClass::TABLEPARAM_TYPE])
					{
						case StageShowLibTableClass::TABLEENTRY_TEXT:
						case StageShowLibTableClass::TABLEENTRY_TEXTBOX:
						case StageShowLibTableClass::TABLEENTRY_SELECT:
							//Block Editing if PayPal entries are locked .....
							$rowDefs[$rowKey][StageShowLibTableClass::TABLEPARAM_TYPE] =  StageShowLibTableClass::TABLEENTRY_READONLY;
							break;
					}
				}
			}
				
			return $rowDefs;
		}		
				
		function OutputJavascript($selectedTabIndex = 0)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// FUNCTIONALITY: Settings - Default settings tab "incremented"" once Prices configured
			// Change default tab if PayPal settings have been set
			$selectedTab = 0;
			if ($myDBaseObj->IsPayPalConfigured() && (count($this->columnDefs)>0))
				$selectedTab++;
			
			parent::OutputJavascript($selectedTab);
		}
		
	}
}

if (!class_exists('PayPalSettingsAdminClass')) 
{
	class PayPalSettingsAdminClass extends StageShowLibSettingsAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->myDBaseObj = $env['DBaseObj'];	// Copy here because CanEditPayPalSettings() may uses it ...
			
			if (!$this->myDBaseObj->getOption('Dev_DisablepayPalLock'))
				$this->blockPayPalEdit = !$this->myDBaseObj->CanEditPayPalSettings();
			else
				$this->blockPayPalEdit = false;
			
			$env['BlockPayPalEdit'] = $this->blockPayPalEdit;
			
			// Call base constructor
			parent::__construct($env);			
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
					
			$SettingsUpdateMsg = '';
			$this->hiddenTags = '';
				
			// PAYPAL SETTINGS
			$PayPalAPITestChanged = false;

			if (isset($_POST['savechanges']))
			{
				$this->CheckAdminReferer();
				
				$PayPalAPIChanged = false;
				if ($this->IsOptionChanged($myDBaseObj->adminOptions, 'PayPalAPIUser','PayPalAPIPwd','PayPalAPISig') || isset($_POST['errormsglive']))
				{
					// Block changes to PayPal Login Parameters if there are performances configured				
					if ($this->blockPayPalEdit)
					{
						// Put back original settings
						$_POST['PayPalAPIUser'] = $myDBaseObj->adminOptions['PayPalAPIUser'];
						$_POST['PayPalAPIPwd'] = $myDBaseObj->adminOptions['PayPalAPIPwd'];
						$_POST['PayPalAPISig'] = $myDBaseObj->adminOptions['PayPalAPISig'];
						
						$SettingsUpdateMsg = __('Plugin Entries already created - Paypal Login details cannot be changed.', $this->myDomain);
					}
					else 
					{
						$payPalAPIObj = new PayPalButtonsAPIClass(__FILE__);
						if ($payPalAPIObj->VerifyPayPalLogin(
							stripslashes($_POST['PayPalEnv']), 
							stripslashes($_POST['PayPalAPIUser']), 
							stripslashes($_POST['PayPalAPIPwd']), 
							stripslashes($_POST['PayPalAPISig'])))
						{
							// New PayPal API Settings are valid			
							$PayPalAPIChanged = true;
						}
						else
						{
							// FUNCTIONALITY: Settings - Reject PayPal settings if cannot create hosted button 
							$APIStatus = $payPalAPIObj->APIStatus;
							$SettingsUpdateMsg = __('PayPal Login FAILED', $this->myDomain)." - $APIStatus";
							$this->hiddenTags .= '<input type="hidden" name="errormsglive" value="'.$SettingsUpdateMsg.'"/>'."\n";
						}
					}
				}
				        
				if ($this->IsOptionChanged($myDBaseObj->adminOptions, 'AdminEMail'))
				{
					if (!$this->ValidateEmail(stripslashes($_POST['AdminEMail'])))
					{
						$SettingsUpdateMsg = __('Invalid Sales EMail', $this->myDomain);
					}
				}
        
				if ($this->IsOptionChanged($myDBaseObj->adminOptions, 'LogsFolderPath'))
				{
					// Confrm that logs folder path is valid or create folder
					$LogsFolder = stripslashes($_POST['LogsFolderPath']);
					if (!strpos($LogsFolder, ':'))
						$LogsFolder = ABSPATH . '/' . $LogsFolder;
					
					$LogsFolderValid = is_dir($LogsFolder);
					if (!$LogsFolderValid)
					{
						mkdir($LogsFolder, 0644, TRUE);
						$LogsFolderValid = is_dir($LogsFolder);
					}
					
					if ($LogsFolderValid)
					{
						// New PayPal API Settings are valid			
					}
					else
					{
						$SettingsUpdateMsg = __('Cannot Create Logs Folder', $this->myDomain);
					}
				}
        
				
				if ($SettingsUpdateMsg === '')
				{
					$this->SaveSettings($myDBaseObj);					
					$myDBaseObj->saveOptions();
					
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved', $this->myDomain).'</p></div>';
				}
				else
				{
					$this->Reload();		
					
					echo '<div id="message" class="error"><p>'.$SettingsUpdateMsg.'</p></div>';
					echo '<div id="message" class="error"><p>'.__('Paypal settings have NOT been saved.', $this->myDomain).'</p></div>';
				}
			}
			
		}
		
		function Reload($reloadMode = true)
		{
			$this->reloadMode = $reloadMode;
		}
		
		function SaveSettings($dbObj)
		{			
			parent::SaveSettings($dbObj);
		}
		
		function GetAdminListClass()
		{
			return 'PayPalSettingsAdminListClass';			
		}
		
			
	}
}

?>