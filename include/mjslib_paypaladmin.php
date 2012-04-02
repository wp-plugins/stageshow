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
						'AUD|Australian Dollars',
						'CAD|Canadian Dollars',
						'EUR|Euros',
						'GBP|Pounds Sterling',
						'JYP|Yen',
						'USD|U.S. Dollars',
						'NZD|New Zealand Dollar',
						'CHF|Swiss Franc',
						'HKD|Hong Kong Dollar',
						'SGD|Singapore Dollar',
						'SEK|Swedish Krona',
						'DKK|Danish Krone',
						'PLN|Polish Zloty',
						'NOK|Norwegian Krone',
						'HUF|Hungarian Forint',
						'CZK|Czech Koruna',
						'ILS|Israeli Shekel',
						'MXN|Mexican Peso',
						'BRL|Brazilian Real',
						'MYR|Malaysian Ringgits',
						'PHP|Philippine Pesos',
						'TWD|Taiwan New Dollars',
						'THB|Thai Baht',					
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