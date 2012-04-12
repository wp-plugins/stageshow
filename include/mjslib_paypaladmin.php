<?php
/* 
Description: MJS Library Admin Page functions
 
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

include 'mjslib_admin.php';      

if (!class_exists('PayPalSettingsAdminClass'))
{
	class PayPalSettingsAdminClass extends SettingsAdminClass // Define class
	{
		var $ppReadOnly;
		var $paypalOpts;
		
		function __construct($settingsOpts)
		{
			$this->paypalOpts = array
			(
				array('Label' => 'Environment',   'Id' => 'PayPalEnv',      'PayPalLock' => true, 'Type' => 'select', 'Items' => array('live|Live', 'sandbox|Sandbox'), ),
				array('Label' => 'API User',      'Id' => 'PayPalAPIUser',  'PayPalLock' => true, 'Type' => 'text',   'Len' => PAYPAL_APILIB_PPLOGIN_USER_TEXTLEN,  'Size' => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array('Label' => 'API Password',  'Id' => 'PayPalAPIPwd',   'PayPalLock' => true, 'Type' => 'text',   'Len' => PAYPAL_APILIB_PPLOGIN_PWD_TEXTLEN,   'Size' => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array('Label' => 'API Signature', 'Id' => 'PayPalAPISig',   'PayPalLock' => true, 'Type' => 'text',   'Len' => PAYPAL_APILIB_PPLOGIN_SIG_TEXTLEN,   'Size' => PAYPAL_APILIB_PPLOGIN_EDITLEN,  ),
				array('Label' => 'Account EMail', 'Id' => 'PayPalAPIEMail', 'PayPalLock' => true, 'Type' => 'text',   'Len' => PAYPAL_APILIB_PPLOGIN_EMAIL_TEXTLEN, 'Size' => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array('Label' => 'Currency',      'Id' => 'PayPalCurrency', 'PayPalLock' => true, 'Type' => 'select', 
					'Items' => array
					(
						'AUD|Australian Dollars (&#36;)',
						'BRL|Brazilian Real (R&#36;)',
						'CAD|Canadian Dollars (&#36;)',
						'CZK|Czech Koruna (&#75;&#269;)',
						'DKK|Danish Krone (kr)',
						'EUR|Euros (&#8364;)',
						'HKD|Hong Kong Dollar (&#36;)',
						'HUF|Hungarian Forint (Ft)',
						'ILS|Israeli Shekel (&#x20aa;)',
						'MXN|Mexican Peso (&#36;)',
						'NZD|New Zealand Dollar (&#36;)',
						'NOK|Norwegian Krone (kr)',
						'PHP|Philippine Pesos (P)',
						'PLN|Polish Zloty (&#122;&#322;)',
						'GBP|Pounds Sterling (&#x20a4;)',
						'SGD|Singapore Dollar (&#36;)',
						'SEK|Swedish Krona (kr)',
						'CHF|Swiss Franc (CHF)',
						'TWD|Taiwan New Dollars (NT&#36;)',
						'THB|Thai Baht (&#xe3f;)',
						'USD|U.S. Dollars (&#36;)',
						'JYP|Yen (&#xa5;)',
					)
				),
				array('Label' => 'PayPal Checkout Logo Image URL', 'Id' => 'PayPalLogoImageURL',   'Type' => 'text',   'Len' => PAYPAL_APILIB_URL_TEXTLEN,   'Size' => PAYPAL_APILIB_URL_EDITLEN, ),
				array('Label' => 'PayPal Header Image URL',        'Id' => 'PayPalHeaderImageURL', 'Type' => 'text',   'Len' => PAYPAL_APILIB_URL_TEXTLEN,   'Size' => PAYPAL_APILIB_URL_EDITLEN, ),				
			);
			
			$settings['PayPal Settings'] = $this->paypalOpts;			
			$settings = $this->MergeSettings($settings, $settingsOpts);
			
			$this->ppReadOnly = false;
			
			parent::__construct($settings);
		}
				
		function SaveSettings($dbObj)
		{
			$currency = $dbObj->adminOptions['PayPalCurrency'];
			
			parent::SaveSettings($dbObj);
			
			if (($currency !== $dbObj->adminOptions['PayPalCurrency']) || ($dbObj->adminOptions['CurrencySymbol'] === ''))
			{
				$dbObj->adminOptions['CurrencySymbol'] = $this->GetCurrencySymbol($dbObj->adminOptions['PayPalCurrency']);
				$dbObj->saveOptions();			
			}			
		}
		
		function GetCurrencySymbol($currency)
		{
			$currencySymbol = '';
			
			foreach ($this->paypalOpts as $payPalSetting)
			{
				if ($payPalSetting['Id'] === 'PayPalCurrency')
				{					
					// Found the settings Opts for currentcy settings
					$selectOpts = $this->GetSelectOptsArray($payPalSetting['Items']);
					
					if (!isset($selectOpts[$currency]))
						break;
						
					$currencyName = $selectOpts[$currency];					
					$rtnArray = preg_split("/[\(\)]+/", $currencyName);
					
					if (count($rtnArray) >= 2)
						$currencySymbol = $rtnArray[1];
				}
			}
			
			return $currencySymbol;
		}
		
		function GetSettingHTMLTag($adminOptions, $settingOption)
		{
			$controlId = $settingOption['Id'];	
			$currValue = MJSLibUtilsClass::GetArrayElement($adminOptions, $controlId);

			if (!isset($settingOption['PayPalLock']) || ($currValue === ''))
				return parent::GetSettingHTMLTag($adminOptions, $settingOption);
			
			$htmlTags =  '';

			switch ($settingOption['Type'])
			{
				case 'text':
					if ($this->ppReadOnly)
					{
						$settingOption['Type'] = 'value';
						$settingOption['Value'] = $currValue;
					}
					break;
					
				case 'select':
					if ($this->ppReadOnly)
					{
						$settingOption['Type'] = 'value';
						$selectOpts = $this->GetSelectOptsArray($settingOption['Items']);
						if ($currValue !== '')
							$settingOption['Value'] = $selectOpts[$currValue];
						else
							$settingOption['Value'] = '';
					}
					break;
			}
			
			$htmlTags = parent::GetSettingHTMLTag($adminOptions, $settingOption);
				
			return $htmlTags;
		}
	}
}

?>