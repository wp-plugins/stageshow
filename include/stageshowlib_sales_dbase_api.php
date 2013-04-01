<?php
/* 
Description: Core Library Database Access functions
 
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

if(!isset($_SESSION)) 
{
	// MJS - SC Mod - Register to use SESSIONS
	session_start();
}	

include 'stageshowlib_dbase_api.php';      
include 'stageshowlib_paypal_api.php';   
include 'stageshowlib_email_api.php';   

if (!class_exists('StageShowLibSalesDBaseClass')) 
{
	/*
	---------------------------------------------------------------------------------
		StageShowLibSalesDBaseClass
	---------------------------------------------------------------------------------
	
	This class provides database functionality to capture PayPal sales data and support
	Instant Payment Notification (IPN).
	*/
	
	if (!defined('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE'))
		define('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE', '');
	if (!defined('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE'))
		define('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE', '');
		
	if (!defined('STAGESHOWLIB_SALES_ACTIVATE_TIMEOUT_EMAIL_TEMPLATE_PATH'))
		define('STAGESHOWLIB_SALES_ACTIVATE_TIMEOUT_EMAIL_TEMPLATE_PATH', '');
		
  	class StageShowLibSalesDBaseClass extends StageShowLibDBaseClass // Define class
  	{	
		const STAGESHOWLIB_TROLLEYTYPE_INTEGRATED = 'Integrated';
		const STAGESHOWLIB_TROLLEYTYPE_PAYPAL = 'PayPal';
		
		var	$emailObj;
		
		var		$PayPalURL;			//  URL for PayPal Payment Requests
		var		$PayPalVerifyURL;	//  URL for PayPal Verify IPN Requests
		
		function __construct($opts)		//constructor		
		{
			parent::__construct($opts);
						
			if (!isset($this->emailObj))
				$this->emailObj = new StageShowLibEMailAPIClass($this);
		}

	    function upgradeDB()
	    {
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$salesDefaultTemplatesPath = WP_CONTENT_DIR . '/plugins/' . $pluginID . '/templates';
			$salesTemplatesPath = WP_CONTENT_DIR . '/uploads/'.$pluginID;
			
			// FUNCTIONALITY: DBase - On upgrade ... Copy sales templates to working folder
			// Copy release templates to plugin persistent templates and images folders
			StageShowLibUtilsClass::recurse_copy($salesDefaultTemplatesPath, $salesTemplatesPath);
			
			if (!isset($this->adminOptions['TrolleyType']))
			{
				// Set TrolleyType default ... detect if this is a new install
				if ( ($this->adminOptions['PayPalAPIUser'] == '')
				  && ($this->adminOptions['PayPalAPIPwd'] == '')
				  && ($this->adminOptions['PayPalAPISig'] == '') )
				 {
					$this->adminOptions['TrolleyType'] = self::STAGESHOWLIB_TROLLEYTYPE_INTEGRATED;
				 }
				else
					$this->adminOptions['TrolleyType'] = self::STAGESHOWLIB_TROLLEYTYPE_PAYPAL;
					
				$this->adminOptions['CheckoutTimeout'] = PAYPAL_APILIB_CHECKOUT_TIMEOUT_DEFAULT;
			}
			
      		$this->saveOptions();      
		}
		
		function PurgeDB()
		{
		}
		
		function uninstall()
		{
			global $wpdb;
      
			$wpdb->query('DROP TABLE IF EXISTS '.$this->opts['SalesTableName']);
			
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$salesTemplatesPath = WP_CONTENT_DIR . '/uploads/'.$pluginID;
			
			// Remove templates and images folders in Uploads folder
			if (is_dir($salesTemplatesPath))
				StageShowLibUtilsClass::deleteDir($salesTemplatesPath);
			
			parent::uninstall();
		}
		
		function CheckIsConfigured()
		{
			if (!$this->usePayPal)
			{
				$isConfigured = $this->IsPayPalConfigured();
			}
			else
			{
				$isConfigured = $this->payPalAPIObj->IsConfigured();
			}
				
			if (!$isConfigured)
			{
				$settingsPageId = basename(dirname($this->opts['Caller']))."_settings";
				
				$settingsPageURL = get_option('siteurl').'/wp-admin/admin.php?page='.$settingsPageId;
				$settingsPageURL .= '&tab=PayPal_Settings';
				$actionMsg = __('Set PayPal Settings First - <a href='.$settingsPageURL.'>Here</a>');
				echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';				
			}
			
			return $isConfigured;
		}
				
		function CanEditPayPalSettings()
		{					
			return true;
		}
		
		function getImagesURL()
		{
			$siteurl = get_option('siteurl');
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			return $siteurl.'/wp-content/uploads/'.$pluginID.'/images/';
		}
		
		function getImageURL($optionId)
		{			
			return isset($this->adminOptions[$optionId]) ? $this->getImagesURL().$this->adminOptions[$optionId] : '';
		}
		
		//Returns an array of admin options
		function getOptions($childOptions = array()) 
		{
			$ourOptions = array(
				'PayPalEnv' => 'live',
				'PayPalCurrency' => PAYPAL_APILIB_DEFAULT_CURRENCY,
				        
				'PayPalAPIUser' => '',
				'PayPalAPISig' => '',
				'PayPalAPIPwd' => '',
				'PayPalAPIEMail' => '',
				        
				'PayPalLogoImageFile' => PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE,
				'PayPalHeaderImageFile' => PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE,
				        
				'PayPalInvChecked' => false,	// Set to true when SS is activated and Inventory URLs have been verified
				        
				'CurrencySymbol' => '',

				'SalesID' => '',        
				'SalesEMail' => '',
				                
				'EMailTemplatePath' => '',
				'TimeoutEMailTemplatePath' => STAGESHOWLIB_SALES_ACTIVATE_TIMEOUT_EMAIL_TEMPLATE_PATH,
								
				'Unused_EndOfList' => ''
			);
			
			$ourOptions = array_merge($ourOptions, $childOptions);
			
			$currOptions = parent::getOptions($ourOptions);
			
			if ($currOptions['PayPalCurrency'] == '')
				$currOptions['PayPalCurrency'] = PAYPAL_APILIB_DEFAULT_CURRENCY;
			
			// PayPalLogoImageURL option has been changed to PayPalLogoImageFile
			if (isset($currOptions['PayPalLogoImageURL']))
			{
				$currOptions['PayPalLogoImageFile'] = basename($currOptions['PayPalLogoImageURL']);
				unset($currOptions['PayPalLogoImageURL']);
			}
				
			// PayPalHeaderImageURL option has been changed to PayPalHeaderImageFile
			if (isset($currOptions['PayPalHeaderImageURL']))
			{
				$currOptions['PayPalHeaderImageFile'] = basename($currOptions['PayPalHeaderImageURL']);
				unset($currOptions['PayPalHeaderImageURL']);
			}
				
			$this->adminOptions = $currOptions;
			
			// Create PayPalAPIClass object here (if required) after Trolley type is known
			$this->usePayPal = !$this->UseIntegratedTrolley();
			//if (($this->usePayPal) && !isset($this->payPalAPIObj))
			if (!isset($this->payPalAPIObj))
			{
				$this->payPalAPIObj = new PayPalButtonsAPIClass(__FILE__);
			}

			return $currOptions;
		}
		
		// Saves the admin options to the PayPal object(s)
		function setPayPalCredentials($OurIPNListener) 
		{
			$useLocalIPNServer = $this->isOptionSet('Dev_IPNLocalServer');
			$payPalTestMode = ($this->adminOptions['PayPalEnv'] == 'sandbox');
			
			$this->PayPalNotifyURL = $OurIPNListener;							
			$this->PayPalURL = PayPalAPIClass::GetPayPalURL($payPalTestMode);

			/** URL for Plugin code to verify PayPal IPNs **/
			if ($useLocalIPNServer)
			{
				$this->PayPalVerifyURL = $this->GetURL('{pluginpath}\test\paypal_VerifyIPNTest.php');	
			}
			else
			{
				$this->PayPalVerifyURL = $this->PayPalURL;
			}				
			
			if ($this->UseIntegratedTrolley())	
				return;
				
			$this->payPalAPIObj->SetTestMode($payPalTestMode);
			
			$this->payPalAPIObj->SetLoginParams(
				$this->adminOptions['PayPalAPIUser'], 
				$this->adminOptions['PayPalAPIPwd'], 
				$this->adminOptions['PayPalAPISig'], 
				$this->adminOptions['PayPalCurrency'], 
				$this->adminOptions['PayPalAPIEMail'],
				$useLocalIPNServer);
								
			if (isset($this->adminOptions['CheckoutCompleteURL']))
			{
				$this->payPalAPIObj->SetSaleCompleteURL($this->adminOptions['CheckoutCompleteURL']);
			}
			
			if (isset($this->adminOptions['CheckoutCancelledURL']))
			{
				$this->payPalAPIObj->SetSaleCancelURL($this->adminOptions['CheckoutCancelledURL']);
			}
							
								
			if ($this->getOption('Dev_ShowPayPalIO') == 1)
				$this->payPalAPIObj->EnableDebug();
		}
		
		static function GetCurrencyTable()
		{
			return array( 
				array('Name' => 'Australian Dollars ',  'Currency' => 'AUD', 'Symbol' => '&#36;',        'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Brazilian Real ',      'Currency' => 'BRL', 'Symbol' => 'R&#36;',       'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Canadian Dollars ',    'Currency' => 'CAD', 'Symbol' => '&#36;',        'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Czech Koruna ',        'Currency' => 'CZK', 'Symbol' => '&#75;&#269;',  'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Danish Krone ',        'Currency' => 'DKK', 'Symbol' => 'kr',           'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Euros ',               'Currency' => 'EUR', 'Symbol' => '&#8364;',      'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Hong Kong Dollar ',    'Currency' => 'HKD', 'Symbol' => '&#36;',        'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Hungarian Forint ',    'Currency' => 'HUF', 'Symbol' => 'Ft',           'Position' => 'Left', 'Format' => '%d'),
				array('Name' => 'Israeli Shekel ',      'Currency' => 'ILS', 'Symbol' => '&#x20aa;',     'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Mexican Peso ',        'Currency' => 'MXN', 'Symbol' => '&#36;',        'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'New Zealand Dollar ',  'Currency' => 'NZD', 'Symbol' => '&#36;',        'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Norwegian Krone ',     'Currency' => 'NOK', 'Symbol' => 'kr',           'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Philippine Pesos ',    'Currency' => 'PHP', 'Symbol' => 'P',            'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Polish Zloty ',        'Currency' => 'PLN', 'Symbol' => '&#122;&#322;', 'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Pounds Sterling ',     'Currency' => 'GBP', 'Symbol' => '&#x20a4;',     'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Singapore Dollar ',    'Currency' => 'SGD', 'Symbol' => '&#36;',        'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Swedish Krona ',       'Currency' => 'SEK', 'Symbol' => 'kr',           'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Swiss Franc ',         'Currency' => 'CHF', 'Symbol' => 'CHF',          'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Taiwan New Dollars ',  'Currency' => 'TWD', 'Symbol' => 'NT&#36;',      'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Thai Baht ',           'Currency' => 'THB', 'Symbol' => '&#xe3f;',      'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'U.S. Dollars ',        'Currency' => 'USD', 'Symbol' => '&#36;',        'Position' => 'Left', 'Format' => '%01.2f'),
				array('Name' => 'Yen ',                 'Currency' => 'JYP', 'Symbol' => '&#xa5;',       'Position' => 'Left', 'Format' => '%d'),
			);
		}
		
		static function GetCurrencyDef($currency)
		{
			$currencyTable = self::GetCurrencyTable();
			
			foreach ($currencyTable as $currencyDef)
			{
				if ($currencyDef['Currency'] == $currency)
				{
					return $currencyDef;
				}
			}
			
			return null;
		}
		
		function GetCurrencySymbol($currency)
		{
			$currencySymbol = '';
			
			$currencyDef = StageShowLibSalesDBaseClass::GetCurrencyDef($currency);
			if (isset($currencyDef['Symbol']))
			{
				$currencySymbol = $currencyDef['Symbol'];
			}
			
			return $currencySymbol;
		}
		
		function GetCurrencyFormat($currency)
		{
			$currencyFormat = '';
			
			$currencyDef = StageShowLibSalesDBaseClass::GetCurrencyDef($currency);
			if (isset($currencyDef['Format']))
			{
				$currencyFormat = $currencyDef['Format'];
			}
			
			return $currencyFormat;
		}
		
		function FormatCurrency($currency)
		{
			$currencyText = sprintf($this->adminOptions['CurrencyFormat'], $currency);
			if ($this->adminOptions['UseCurrencySymbol'])
			{
				$currencyText = $this->adminOptions['CurrencySymbol'].$currencyText;				
			}
			return $currencyText;
		}
		
		function IsPayPalConfigured()
		{
			if ($this->UseIntegratedTrolley())
			{
				if (!isset($this->adminOptions['PayPalMerchantID']))
					return false;
				
				if (!isset($this->adminOptions['PayPalAPIEMail']))
					return false;
				
				if (strlen($this->adminOptions['PayPalMerchantID']) == 0)
					return false;
				
				if (strlen($this->adminOptions['PayPalAPIEMail']) == 0)
					return false;
				
				return true;				
			}
			
			return $this->payPalAPIObj->IsConfigured();
		}
		
		function UseIntegratedTrolley()
		{
			if (!isset($this->adminOptions['TrolleyType']))
				return false;
				
			return($this->adminOptions['TrolleyType'] == self::STAGESHOWLIB_TROLLEYTYPE_INTEGRATED);
		}
		    
		function GetTrolleyType()
		{
			return $this->UseIntegratedTrolley() ? 'Integrated Trolley' : 'PayPal Shopping Cart';
		}
		    
		function Output_TrolleyHelp()
		{
			echo  '<strong>'.__('Shopping Trolley', $this->get_domain()).':</strong> '.$this->GetTrolleyType()."<br>\n";			
		}
		
		function UseTestPayPalSettings($testSettings)
		{
			if (!isset($this->adminOptions))
			{
				$this->getOptions();
			}
			
			foreach($testSettings as $settingID => $settingValue)
			{
				$this->adminOptions[$settingID] = $settingValue;
			}
			
			$this->saveOptions();			
		}
		
		// Saves the admin options to the options data table
		function saveOptions($newOptions = null)
		{
			if ($newOptions == null)
				$newOptions = $this->adminOptions;
				
			$currency = $newOptions['PayPalCurrency'];
			
			$newOptions['CurrencySymbol'] = $this->GetCurrencySymbol($currency);
			$newOptions['CurrencyFormat'] = $this->GetCurrencyFormat($currency);
			
			parent::saveOptions($newOptions);
		}
		
		function getTableDef($tableName)
		{
			$sql = parent::getTableDef($tableName);
			
			switch($tableName)
			{
				case $this->opts['SalesTableName']:
					$sql .= '
						saleID INT UNSIGNED NOT NULL AUTO_INCREMENT,
						saleCheckoutTime DATETIME,
						saleDateTime DATETIME NOT NULL,
						saleName VARCHAR('.PAYPAL_APILIB_PPSALENAME_TEXTLEN.') NOT NULL,
						saleEMail VARCHAR('.PAYPAL_APILIB_PPSALEEMAIL_TEXTLEN.') NOT NULL,
						salePPName VARCHAR('.PAYPAL_APILIB_PPSALEPPNAME_TEXTLEN.'),
						salePPStreet VARCHAR('.PAYPAL_APILIB_PPSALEPPSTREET_TEXTLEN.'),
						salePPCity VARCHAR('.PAYPAL_APILIB_PPSALEPPCITY_TEXTLEN.'),
						salePPState VARCHAR('.PAYPAL_APILIB_PPSALEPPSTATE_TEXTLEN.'),
						salePPZip VARCHAR('.PAYPAL_APILIB_PPSALEPPZIP_TEXTLEN.'),
						salePPCountry VARCHAR('.PAYPAL_APILIB_PPSALEPPCOUNTRY_TEXTLEN.'),
						salePaid DECIMAL(9,2) NOT NULL,
						saleFee DECIMAL(9,2) NOT NULL,
						saleTxnId VARCHAR('.PAYPAL_APILIB_PPSALETXNID_TEXTLEN.') NOT NULL,
						saleStatus VARCHAR('.PAYPAL_APILIB_PPSALESTATUS_TEXTLEN.'),
					';
					break;
			}
							
			return $sql;
		}
		
		function createDB($dropTable = false)
		{
			global $wpdb;
			
			parent::createDB($dropTable);

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			
			$table_name = $this->opts['SalesTableName'];

			if ($dropTable)
				$wpdb->query("DROP TABLE IF EXISTS $table_name");

			$sql  = "CREATE TABLE ".$table_name.' (';
			$sql .= $this->getTableDef($table_name);
			$sql .= 'UNIQUE KEY saleID (saleID)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

			//excecute the query
			$this->dbDelta($sql);

		}
		
		function GetSaleStockID($itemRef, $itemOption)
		{
			return 0;
		}
		
		function GetJoinedTables($sqlFilters = null, $classID = '')
		{
			return '';
		}
		
		function GetWhereSQL($sqlFilters)
		{
			$sqlWhere = '';
			$sqlCmd = ' WHERE ';
			
			if (isset($sqlFilters['saleID']) && ($sqlFilters['saleID'] > 0))
			{
				$sqlWhere .= $sqlCmd.$this->opts['SalesTableName'].'.saleID="'.$sqlFilters['saleID'].'"';
				$sqlCmd = ' AND ';
			}
			
			if (isset($sqlFilters['saleTxnId']) && (strlen($sqlFilters['saleTxnId']) > 0))
			{
				$sqlWhere .= $sqlCmd.$this->opts['SalesTableName'].'.saleTxnId="'.$sqlFilters['saleTxnId'].'"';
				$sqlCmd = ' AND ';
			}
			
			return $sqlWhere;
		}
		
		function AddSQLOpt($sql, $optName, $optValue)
		{
			if (strstr($sql, $optName))
			{
				$sql = str_replace($optName, $optName.$optValue.',', $sql);
			}
			else
			{
				$sql .= $optName.$optValue;
			}
			
			return $sql;
		}
		
		function GetOptsSQL($sqlFilters, $sqlOpts = '')
		{
			if (isset($sqlFilters['orderBy']))
			{
				$sqlOpts = $this->AddSQLOpt($sqlOpts, ' ORDER BY ', $sqlFilters['orderBy']);
			}
			
			if (isset($sqlFilters['limit']))
			{
				$sqlOpts = $this->AddSQLOpt($sqlOpts, ' LIMIT ', $sqlFilters['limit']);
			}
			
			return $sqlOpts;
		}
		
		// Add Sale - Address details are optional
		function AddSaleWithFee($SaleDateTime = '', $saleName = '', $saleEMail = '', $salePaid = 0, $saleFee = 0, $Txnid = '', $saleStatus = '', $salePPName = '', $salePPStreet = '', $salePPCity = '', $salePPState = '', $salePPZip = '', $salePPCountry = '')
		{
			global $wpdb;
			
			if ($SaleDateTime == '')
			{
				$SaleDateTime = date(StageShowLibDBaseClass::MYSQL_DATETIME_FORMAT);
				$saleStatus = PAYPAL_APILIB_SALESTATUS_CHECKOUT;
				$saleName = $saleEMail = $Txnid = '';
				$sql  = 'INSERT INTO '.$this->opts['SalesTableName'].'(saleDateTime, saleName, saleEMail, salePaid, saleFee, saleTxnId, saleStatus, saleCheckoutTime)';
				$sql .= ' VALUES("'.$SaleDateTime.'", "'.$saleName.'", "'.$saleEMail.'", "'.$salePaid.'", "'.$saleFee.'", "'.$Txnid.'", "'.$saleStatus.'", "'.$SaleDateTime.'")';				
			}
			else
			{
				$sql  = 'INSERT INTO '.$this->opts['SalesTableName'].'(saleDateTime, saleName, saleEMail, salePaid, saleFee, saleTxnId, saleStatus, salePPName, salePPStreet, salePPCity, salePPState, salePPZip, salePPCountry)';
				$sql .= ' VALUES("'.$SaleDateTime.'", "'.$saleName.'", "'.$saleEMail.'", "'.$salePaid.'", "'.$saleFee.'", "'.$Txnid.'", "'.$saleStatus.'", "'.$salePPName.'", "'.$salePPStreet.'", "'.$salePPCity.'", "'.$salePPState.'", "'.$salePPZip.'", "'.$salePPCountry.'")';				
			}
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
			$saleID = mysql_insert_id();
	
			return $saleID;
		}
		
		// Edit Sale
		function EditSale($saleID, $saleName, $saleEMail, $salePaid, $salePPStreet, $salePPCity, $salePPState, $salePPZip, $salePPCountry)
		{
			global $wpdb;
			
			$sql  = 'UPDATE '.$this->opts['SalesTableName'];
			$sql .= ' SET saleName="'.$saleName.'"';
			$sql .= ' ,   saleEMail="'.$saleEMail.'"';
			$sql .= ' ,   salePaid="'.$salePaid.'"';
			$sql .= ' ,   salePPStreet="'.$salePPStreet.'"';
			$sql .= ' ,   salePPCity="'.$salePPCity.'"';
			$sql .= ' ,   salePPState="'.$salePPState.'"';
			$sql .= ' ,   salePPZip="'.$salePPZip.'"';
			$sql .= ' ,   salePPCountry="'.$salePPCountry.'"';
			
			$sql .= ' WHERE '.$this->opts['SalesTableName'].'.saleID='.$saleID;;
			$this->ShowSQL($sql); 

			$wpdb->query($sql);	
		}			
		
		function UpdateSale($results)
		{
			global $wpdb;

			$saleID = $results['saleID'];
			
			$sql  = 'UPDATE '.$this->opts['SalesTableName'];
			$sql .= ' SET saleName="'.$results['saleName'].'"';
			
			$sql .= ' ,   saleDateTime="'.$results['saleDateTime'].'"';
			$sql .= ' ,   saleTxnId="'.$results['saleTxnId'].'"';
			$sql .= ' ,   saleStatus="'.$results['saleStatus'].'"';
			$sql .= ' ,   salePPName="'.$results['salePPName'].'"';
			
			$sql .= ' ,   saleEMail="'.$results['saleEMail'].'"';
			$sql .= ' ,   salePaid="'.$results['salePaid'].'"';
			$sql .= ' ,   saleFee="'.$results['saleFee'].'"';
			$sql .= ' ,   salePPStreet="'.$results['salePPStreet'].'"';
			$sql .= ' ,   salePPCity="'.$results['salePPCity'].'"';
			$sql .= ' ,   salePPState="'.$results['salePPState'].'"';
			$sql .= ' ,   salePPZip="'.$results['salePPZip'].'"';
			$sql .= ' ,   salePPCountry="'.$results['salePPCountry'].'"';
			
			$sql .= ' WHERE '.$this->opts['SalesTableName'].'.saleID='.$saleID;;
			$this->ShowSQL($sql); 

			$rtnVal = $wpdb->query($sql);	
			if ($this->getOption('Dev_ShowSQL'))
			{
				echo "<br>UpdateSale Returned: $rtnVal<br>\n";
			}
			
			if (!$rtnVal)
				return 0;
			
			return $saleID;
		}
		
		function PurgePendingSales($timeout = '')
		{
			global $wpdb;
			
			if ($timeout == '')
				$timeout = 60*$this->adminOptions['CheckoutTimeout'];	// 1 hour default
				
			$limitDateTime = date(StageShowLibDBaseClass::MYSQL_DATETIME_FORMAT, time() - $timeout);
			
			$sql  = 'DELETE FROM '.$this->opts['SalesTableName'];
			$sql .= ' WHERE '.$this->opts['SalesTableName'].'.saleStatus="'.PAYPAL_APILIB_SALESTATUS_CHECKOUT.'"';
			$sql .= ' AND   '.$this->opts['SalesTableName'].'.saleCheckoutTime < "'.$limitDateTime.'"';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
			
			$sql  = 'DELETE o FROM '.$this->opts['OrdersTableName'].' o ';
			$sql .= 'LEFT OUTER JOIN '.$this->opts['SalesTableName'].' s ON o.saleID = s.saleID ';
			$sql .= 'WHERE s.saleStatus IS NULL';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);

		}
		
		function AddSaleItem($saleID, $stockID, $qty, $paid)
		{
			global $wpdb;
			
			$paid *= $qty;
			
			$sql  = 'INSERT INTO '.$this->opts['OrdersTableName'].'(saleID, '.$this->DBField('stockID').', '.$this->DBField('orderQty').', '.$this->DBField('orderPaid').')';
			$sql .= ' VALUES('.$saleID.', '.$stockID.', "'.$qty.'", "'.$paid.'")';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
			$orderID = mysql_insert_id();
				
			return $orderID;
		}			
		
		function UpdateSaleItem($saleID, $stockID, $qty, $paid)
		{
			global $wpdb;

			$paid *= $qty;
			
			// Delete a show entry
			$sql  = 'UPDATE '.$this->opts['OrdersTableName'];
			$sql .= ' SET '.$this->DBField('orderQty').'="'.$qty.'"';
			$sql .= ' ,   '.$this->DBField('orderPaid').'="'.$paid.'"';
			$sql .= ' WHERE '.$this->opts['OrdersTableName'].".saleID=$saleID";
			$sql .= ' AND   '.$this->opts['OrdersTableName'].".".$this->DBField('stockID')."=$stockID";

			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}
		
		function DeleteSaleItem($saleID, $stockID)
		{
			global $wpdb;

			// Delete a show entry
			$sql  = 'DELETE FROM '.$this->opts['OrdersTableName'];
			$sql .= ' WHERE '.$this->opts['OrdersTableName'].".saleID=$saleID";
			$sql .= ' AND   '.$this->opts['OrdersTableName'].".".$this->DBField('stockID')."=$stockID";

			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}
		
		function GetSalesQty($sqlFilters)
		{
			$sql  = 'SELECT '.$this->TotalSalesField($sqlFilters).' FROM '.$this->opts['SalesTableName'];	
			$sql .= $this->GetJoinedTables($sqlFilters, __CLASS__);
			$sql .= $this->GetWhereSQL($sqlFilters);
					
			$this->ShowSQL($sql); 
			
			$salesListArray = $this->get_results($sql);
			if (count($salesListArray) == 0)
					return 0;
							 
			return $salesListArray[0]->totalQty;
		}
		
		function GetPricesListWithSales($saleID)
		{	
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'GetPricesListWithSales');
		}
		
		function DeleteSale($saleID)
		{
			global $wpdb;
			
			// Delete a show entry
			$sql  = 'DELETE FROM '.$this->opts['SalesTableName'];
			if (is_array($saleID))
			{
				$salesList = '';
				foreach ($saleID as $saleItemID)
				{
					if ($salesList != '') $salesList .= ',';
					$salesList .= $saleItemID->saleID;
				}
				$sql .= ' WHERE '.$this->opts['SalesTableName'].".saleID IN ($salesList)";
			}
			else
				$sql .= ' WHERE '.$this->opts['SalesTableName'].".saleID=$saleID";
				
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}			

		function GetSaleBuyer($saleID)
		{
			$sqlFilters['saleID'] = $saleID;
			$sql  = 'SELECT * FROM '.$this->opts['SalesTableName'];	
			$sql .= $this->GetWhereSQL($sqlFilters);
					
			$this->ShowSQL($sql); 
			
			$salesListArray = $this->get_results($sql);
			
			return $salesListArray;
		}
		
		function AddSaleFields(&$salesListArray)
		{
		}
		
		function GetSalesList($sqlFilters)
		{
			$selectFields  = '*';
			if (isset($sqlFilters['saleID']) || isset($sqlFilters['priceID']))
			{
				// Explicitly add joined fields from "base" tables (otherwise values will be NULL if there is no matching JOIN)
				$selectFields .= ', '.$this->opts['SalesTableName'].'.saleID';

				$joinCmd = ' LEFT JOIN ';
			}
			else
				$joinCmd = ' JOIN ';
				
			if (isset($sqlFilters['groupBy']))	
			{			
				$totalSalesField = $this->TotalSalesField($sqlFilters);
				if ($totalSalesField != '')
					$selectFields .= ','.$totalSalesField;
			}

			$sql  = 'SELECT '.$selectFields.' FROM '.$this->opts['SalesTableName'];
			$sql .= $this->GetJoinedTables($sqlFilters, __CLASS__);
			
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= $this->GetOptsSQL($sqlFilters);

			$this->ShowSQL($sql); 
			
			$showOutput = $this->getOption('Dev_ShowDBOutput'); 
			$this->adminOptions['Dev_ShowDBOutput'] = '';
			
			$salesListArray = $this->get_results($sql);			
			$this->AddSaleFields($salesListArray);
			
			$this->adminOptions['Dev_ShowDBOutput'] = $showOutput;
			$this->show_results($salesListArray);
					
			return $salesListArray;
		}			

		function GetSalesEMail()
		{
			return $this->adminOptions['SalesEMail'];
		}
		
		function GetLocation()
		{
			return '';
		}
	
		function AddGenericFields($EMailTemplate)
		{
			$EMailTemplate = str_replace('[organisation]', $this->adminOptions['OrganisationID'], $EMailTemplate);
			
			$EMailTemplate = str_replace('[salesEMail]', $this->GetSalesEMail(), $EMailTemplate);
			$EMailTemplate = str_replace('[url]', get_option('siteurl'), $EMailTemplate);
			
			return $EMailTemplate;
		}
		
		function AddEMailFields($EMailTemplate, $saleDetails)
		{
			// FUNCTIONALITY: DBase - Sales - Add DB fields to EMail
			
			$EMailTemplate = $this->AddGenericFields($EMailTemplate);
			
			if ($this->isOptionSet('Dev_ShowMiscDebug'))
				StageShowLibUtilsClass::print_r($this->adminOptions, 'adminOptions');
			
			$emailFields = array(
				// Details from User Profile
				'[saleDateTime]' => 'saleDateTime',
				'[saleName]' => 'saleName',
				'[saleEMail]' => 'saleEMail',
				'[salePaid]' => 'salePaid',
				'[saleTxnId]' => 'saleTxnId',
				'[saleStatus]' => 'saleStatus',

				'[salePPName]' => 'salePPName',
				'[salePPStreet]' => 'salePPStreet',
				'[salePPCity]' => 'salePPCity',
				'[salePPState]' => 'salePPState',
				'[salePPZip]' => 'salePPZip',
				'[salePPCountry]' => 'salePPCountry',
			);
							
			foreach ($emailFields as $tag => $field)
			{
				switch ($tag)
				{
					case '[salePaid]';
						if (isset($saleDetails->$field))
							$saleFieldValue = $this->FormatCurrency($saleDetails->$field);
						else
							$saleFieldValue = "**** $field ".__("Undefined", $this->get_domain())." ****";
						break;
					
					default:
						if (isset($saleDetails->$field))
							$saleFieldValue = $saleDetails->$field;
						else
							$saleFieldValue = "**** $field ".__("Undefined", $this->get_domain())." ****";
						break;
				}
				$EMailTemplate = str_replace($tag, $saleFieldValue, $EMailTemplate);
			}
			
			return $EMailTemplate;
		}
		
		function AddSalesDetailsEMailFields($EMailTemplate, $orderDetails)
		{
			return $EMailTemplate;
		}			

		function GetEmail($ourOptions, $emailRole = '')
		{
			if ($emailRole === '')
				$emailRole = 'Admin';
				
			$ourEmail = '';
			$IDIndex = $emailRole.'ID';
			$EMailIndex = $emailRole.'EMail';
			
			// Get from email address from settings
			if (strlen($ourOptions[$EMailIndex]) > 0)
			{
				$ourEmail .= $ourOptions[$EMailIndex];
				if (strlen($ourOptions[$IDIndex]) > 0)
					$ourEmail = $ourOptions[$IDIndex] . ' <'.$ourEmail.'>';
			}
							
			return $ourEmail;
		}
		
		function CheckEmailTemplatePath($templateID)
		{
			$templatePath = str_replace("\\", "/", $this->adminOptions[$templateID]);
			$this->adminOptions[$templateID] = basename($templatePath);
			//echo "Option[$templateID]: $templatePath -> ".$this->adminOptions[$templateID]."<br>\n";
		}

		function GetEmailTemplatePath($templateID)
		{
			// EMail Template defaults to templates folder
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$templatePath = WP_CONTENT_DIR . '/uploads/'.$pluginID.'/emails/'.$this->adminOptions[$templateID];

			return $templatePath;
		}

		function EMailSale($saleID, $EMailTo = '')
		{
			$templatePath = $this->GetEmailTemplatePath('EMailTemplatePath');
	
			// Get sale	and ticket details
			$salesList = $this->GetSale($saleID);
			if (count($salesList) < 1) 
				return 'salesList Empty';

			return $this->SendEMailFromTemplate($salesList, $templatePath, $EMailTo);
		}
		
		function ReadTemplateFile($Filepath)
		{
			$hfile = fopen($Filepath,"r");
			if ($hfile != 0)
			{
				$fileLen = filesize($Filepath);
				$fileContents = fread($hfile, $fileLen);
				fclose($hfile);
			}
			else
			{
				echo "Error reading $Filepath<br>\n";
				//echo "Error was $php_errormsg<br>\n";
				$fileContents = '';
			}

			return $fileContents;
		}
		
		function GetTemplateSection($mailTemplate, $startMark = '', $endMark = '', $delMarkedLine = false)
		{
			// Get template section starting from line after $startMark and ending line before $endMark
			if ($startMark != '')
			{
				$posnStart = stripos($mailTemplate, $startMark);
				if (($posnStart !== false) && $delMarkedLine) $posnStart = strpos($mailTemplate, "\n", $posnStart);
				if ($posnStart !== false) $mailTemplate = substr($mailTemplate, $posnStart);			
			}
			
			if ($endMark != '')
			{
				$posnEnd = stripos($mailTemplate, $endMark);
				if ($posnEnd !== false) $posnEnd += strlen($endMark);
				if (($posnEnd !== false) && $delMarkedLine) $posnEnd = strrpos(substr($mailTemplate, 0, $posnEnd), "\n");
				if ($posnEnd !== false) $mailTemplate = substr($mailTemplate, 0, $posnEnd);
			}
			
			return $mailTemplate;
		}
		
		function SendEMailFromTemplate($emailContent, $templatePath, $EMailTo = '')
		{				
			$mailTemplate = $this->ReadTemplateFile($templatePath);
			if (strlen($mailTemplate) == 0)
				return "EMail Template Not Found ($templatePath)";
				
			$saleConfirmation = '';
			
			// Find the line with the open php entry then find the end of the line
			$posnPHP = stripos($mailTemplate, '<?php');
			if ($posnPHP !== false) $posnPHP = strpos($mailTemplate, "\n", $posnPHP);
			if ($posnPHP !== false) $posnEOL = strpos($mailTemplate, "\n", $posnPHP+1);
			if (($posnPHP !== false) && ($posnEOL !== false)) 
			{
				$EMailSubject = $this->AddEMailFields(substr($mailTemplate, $posnPHP, $posnEOL-$posnPHP), $emailContent[0]);
				$mailTemplate = substr($mailTemplate, $posnEOL);
			}
			
			// Find the line with the close php entry then find the start of the line
			$posnPHP = stripos($mailTemplate, '?>');
			if ($posnPHP !== false) $posnPHP = strrpos(substr($mailTemplate, 0, $posnPHP), "\n");
			if ($posnPHP !== false) $mailTemplate = substr($mailTemplate, 0, $posnPHP);

			$loopCount = 0;
			for (; $loopCount < 10; $loopCount++)
			{
				$loopStart = stripos($mailTemplate, '[startloop]');
				$loopEnd = stripos($mailTemplate, '[endloop]');

				if (($loopStart === false) || ($loopEnd === false))
					break;

				$section = substr($mailTemplate, 0, $loopStart);
				$saleConfirmation .= $this->AddEMailFields($section, $emailContent[0]);

				$loopStart += strlen('[startloop]');
				$loopLen = $loopEnd - $loopStart;

				foreach($emailContent as $ticket)
				{
					$section = substr($mailTemplate, $loopStart, $loopLen);
					$saleConfirmation .= $this->AddSalesDetailsEMailFields($section, $ticket);
				}

				$loopEnd += strlen('[endloop]');
				$mailTemplate = substr($mailTemplate, $loopEnd);
			}

			// Process the rest of the mail template
			$saleConfirmation .= $this->AddEMailFields($mailTemplate, $emailContent[0]);
			
			// Get email address and organisation name from settings
			$EMailFrom = $this->GetEmail($this->adminOptions, 'Sales');

			if (strlen($EMailTo) == 0) $EMailTo = $emailContent[0]->saleEMail;

			if (isset($this->emailObj))
				$this->emailObj->sendMail($EMailTo, $EMailFrom, $EMailSubject, $saleConfirmation);

			echo '<div id="message" class="updated"><p>'.__('EMail Sent to', $this->get_domain()).' '.$EMailTo.'</p></div>';
						
			return 'OK';
		}
		
		function GetTxnStatus($Txnid)
		{
			$sql = 'SELECT saleStatus FROM '.$this->opts['SalesTableName'].' WHERE saleTxnId="'.$Txnid.'"';
			$this->ShowSQL($sql); 
			$txnEntries = $this->get_results($sql);
			
			if (count($txnEntries) == 0) 
				return '';
			
			return $txnEntries[0]->saleStatus;
		}
		
		function DBField($fieldName)
		{
			return $fieldName;
		}
		
		function UpdateSaleStatus($Txn_id, $Payment_status)
		{
			global $wpdb;
			
			$sql  = 'UPDATE '.$this->opts['SalesTableName'];
			$sql .= ' SET saleStatus="'.$Payment_status.'"';		
			$sql .= ' WHERE saleTxnId="'.$Txn_id.'"';							
			$this->ShowSQL($sql); 
			
			$wpdb->query($sql);			
		}
		
		function LogPendingSale($results)
		{
			return $this->LogSale($results, true);
		}
		
		function FlushPendingSales($timeout)
		{
			// TDOD - Implement and call FlushPendingSales()
		}
		
		function LogSale($results, $isCheckout = false)
		{
			//$ourOptions = get_option($this->optionsID);
			
			if ($isCheckout)
			{
				$saleID = $this->AddSaleWithFee();
			}
			else if (isset($results['saleID']))
			{
				// Just add the sale details 
				$saleID = $this->UpdateSale($results);
				if ($saleID == 0)
				{
					// Checkout has timed out ... 
				}
				return $saleID;
			}				
			else
			{
				$TxdDate  = $results['saleDateTime'];
				$Txnid  = $results['saleTxnId'];
				$saleName  = $results['saleName'];
				$saleEMail  = $results['saleEMail'];
				$saleStatus  = $results['saleStatus'];
				$salePaid  = $results['salePaid'];
				$saleFee  = $results['saleFee'];
				$salePPName  = $results['salePPName'];
				$salePPStreet  = $results['salePPStreet'];
				$salePPCity  = $results['salePPCity'];
				$salePPState  = $results['salePPState'];
				$salePPZip  = $results['salePPZip'];
				$salePPCountry  = $results['salePPCountry'];
				
				// Log sale to Database
				$saleID = $this->AddSaleWithFee(
					$TxdDate, 
					$saleName, 
					$saleEMail, 
					$salePaid, 
					$saleFee,
					$Txnid,
					$saleStatus, 
					$salePPName, 
					$salePPStreet, 
					$salePPCity, 
					$salePPState, 
					$salePPZip, 
					$salePPCountry);				
			}
		  		  
			$itemNo = 1;
			$lineNo = 1;
			While (true)
			{
				if (!isset($results['qty' . $itemNo]))
					break;

				if (isset($results['itemRef' . $itemNo]))
				{
					$itemRef  = $results['itemRef' . $itemNo];
					$itemOption  = $results['itemOption' . $itemNo];
										
					// Find stockID from Database	    
					$stockID = $this->GetSaleStockID($itemRef, $itemOption);
				}
				else
				{
					$stockID = $results['itemID' . $itemNo];
				}
				
				$qty  = $results['qty' . $itemNo];
				$itemPaid  = $results['itemPaid' . $itemNo];

				if ($qty > 0)
				{
					// Log sale item to Database
					$this->AddSaleItem($saleID, $stockID, $qty, $itemPaid);
			    
					$lineNo++;
				} // End of if ($qty > 0)
				$itemNo++;
			}
		  
			return $saleID;
		}

		function AddTableLocks($sql)
		{
			$sql .= $this->opts['SalesTableName'].' WRITE, ';
			$sql .= $this->opts['OrdersTableName'].' WRITE ';
			
			return $sql;
		}
		
		function LockSalesTable()
		{
			global $wpdb;
			
			$sql  = 'LOCK TABLES ';
			
			$sql = $this->AddTableLocks($sql);
			
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}
		
		function UnLockTables()
		{
			global $wpdb;
			
			$sql  = 'UNLOCK TABLES';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}
		
		function HTTPAnchor($url, $name = '')
		{
			if ($name == '') 
			{
				$name = $url;
			}
			$anchor  = '<a href="';
			$anchor .= $url;
			$anchor .= '">'.$name.'</a>';
			
			return $anchor;
		}
		
		function DownloadBaseURL($currOptions)
		{
			if ($currOptions['DownloadURL'] !== '')
				$downloadURL = $currOptions['DownloadURL'];
			else if (isset($this->opts['DefaultDownloadURL']))
				$downloadURL = $this->opts['DefaultDownloadURL'];
			else
				$downloadURL = '';
			
			return $downloadURL;
		}
		
	    function HTTPAction($url, $urlParams = '', $method = 'POST', $redirect = true)
	    {	
			$HTTPResponse = PayPalAPIClass::HTTPAction($url, $urlParams, $method, $redirect);
			if ($this->getOption('Dev_ShowMiscDebug') == 1)
			{
				echo "HTTPAction Called<br>";
				echo "URL: $url<br>";
				echo "METHOD: $method<br>";
				echo "URL Params: <br>";
				print_r($urlParams);
				StageShowLibUtilsClass::print_r($HTTPResponse, 'HTTPResponse:');
			}
			return $HTTPResponse; 
	    }
    
	}
}

?>