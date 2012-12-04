<?php
/* 
Description: MJS Library Database Access functions
 
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

include 'mjslib_dbase_api.php';      
include 'mjslib_paypal_api.php';   
include 'mjslib_email_api.php';   

if (!class_exists('MJSLibSalesDBaseClass')) 
{
	define('MJSLIB_SALES_STOCKNAME_TEXTLEN',40);
	define('MJSLIB_SALES_STOCKREF_TEXTLEN',10);
	define('MJSLIB_SALES_STOCKTYPE_TEXTLEN',40);
	define('MJSLIB_SALES_STOCKFILEPATH_TEXTLEN',60);
	define('MJSLIB_SALES_STOCKPRICE_TEXTLEN',12);	// Decimal Number Precision = 9.2

	if (!defined('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE'))
		define('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE', '');
	if (!defined('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE'))
		define('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE', '');
		
  class MJSLibSalesDBaseClass extends MJSLibDBaseClass // Define class
  {	
		var	$payPalAPIObj;
		var	$emailObj;
		
		function __construct($opts)		//constructor		
		{
			if (class_exists('PayPalAPIClass')) 
			{
				$this->payPalAPIObj = new PayPalAPIClass(__FILE__);
			}

			parent::__construct($opts);
			
			if (!isset($this->emailObj))
				$this->emailObj = new MJSLibEMailAPIClass($this);
		}

	    function upgradeDB()
	    {
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$salesDefaultTemplatesPath = WP_CONTENT_DIR . '/plugins/' . $pluginID . '/templates/';
			$salesTemplatesPath = WP_CONTENT_DIR . '/uploads/'.$pluginID;
			
			// FUNCTIONALITY: DBase - On upgrade ... Copy sales templates to working folder
			// Copy release templates to stageshow persistent templates and images folders
			MJSLibUtilsClass::recurse_copy($salesDefaultTemplatesPath, $salesTemplatesPath);
		}
		
		function uninstall()
		{
			global $wpdb;
      
			$wpdb->query('DROP TABLE IF EXISTS '.$this->opts['SalesTableName']);
			
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$salesTemplatesPath = WP_CONTENT_DIR . '/uploads/'.$pluginID;
			
			// Remove templates and images folders in Uploads folder
			if (is_dir($salesTemplatesPath))
				MJSLibUtilsClass::deleteDir($salesTemplatesPath);
			
			parent::uninstall();
		}
		
		function CheckIsConfigured()
		{
			return $this->payPalAPIObj->CheckIsConfigured();
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
			return $this->getImagesURL().$this->adminOptions[$optionId];
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
			
			return $currOptions;
		}
		
		// Saves the admin options to the PayPal object(s)
		function setPayPalCredentials($OurIPNListener) 
		{
			$useLocalIPNServer = $this->isOptionSet('Dev_IPNLocalServer');
				
			$this->payPalAPIObj->SetLoginParams(
				$this->adminOptions['PayPalEnv'], 
				$this->adminOptions['PayPalAPIUser'], 
				$this->adminOptions['PayPalAPIPwd'], 
				$this->adminOptions['PayPalAPISig'], 
				$this->adminOptions['PayPalCurrency'], 
				$this->adminOptions['PayPalAPIEMail'],
				$useLocalIPNServer);
				
			$this->payPalAPIObj->SetIPNListener($OurIPNListener);
				
			if ($this->getOption('Dev_ShowPayPalIO') == 1)
				$this->payPalAPIObj->EnableDebug();
		}
    
		function UseTestPayPalSettings()
		{
			$this->getOptions();
			
			if (defined('PAYPAL_APILIB_ACTIVATE_TESTMODE'))
			{
				$this->adminOptions['PayPalEnv']  = 'sandbox';
				
				// Pre-configured PayPal Sandbox settings - can be defined in wp-config.php
				$this->adminOptions['PayPalAPIUser']  = PAYPAL_APILIB_ACTIVATE_TESTUSER;
				$this->adminOptions['PayPalAPIPwd']   = PAYPAL_APILIB_ACTIVATE_TESTPWD;
				$this->adminOptions['PayPalAPISig']   = PAYPAL_APILIB_ACTIVATE_TESTSIG;
				$this->adminOptions['PayPalAPIEMail'] = PAYPAL_APILIB_ACTIVATE_TESTEMAIL;
	    	}
			else
			{
				$this->adminOptions['PayPalEnv']  = 'live';
				
				// Pre-configured PayPal "Live" settings - can be defined in wp-config.php
				$this->adminOptions['PayPalAPIUser']  = PAYPAL_APILIB_ACTIVATE_LIVEUSER;
				$this->adminOptions['PayPalAPIPwd']   = PAYPAL_APILIB_ACTIVATE_LIVEPWD;
				$this->adminOptions['PayPalAPISig']   = PAYPAL_APILIB_ACTIVATE_LIVESIG;
				$this->adminOptions['PayPalAPIEMail'] = PAYPAL_APILIB_ACTIVATE_LIVEEMAIL;				
			}      
				
      		if (defined('SALESMAN_DEFAULT_SALES_ID'))
				$this->adminOptions['SalesID'] = SALESMAN_DEFAULT_SALES_ID;
     		if (defined('SALESMAN_DEFAULT_SALES_EMAIL'))
				$this->adminOptions['SalesEMail'] = SALESMAN_DEFAULT_SALES_EMAIL;
      
     		if (defined('SALESMAN_DEFAULT_EMAIL_TEMPLATE_PATH'))
			{
				if ($this->adminOptions['EMailTemplatePath'] == '')
		      		$this->adminOptions['EMailTemplatePath'] = SALESMAN_DEFAULT_EMAIL_TEMPLATE_PATH;				
			}
				
			$this->saveOptions();			
		}
		
		function createDB($dropTable = false)
		{
			global $wpdb;
      
			parent::createDB($dropTable);

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			
			$table_name = $this->opts['SalesTableName'];

			if ($dropTable)
				$wpdb->query("DROP TABLE IF EXISTS $table_name");

			$sql = "CREATE TABLE ".$table_name.' ( 
					saleID INT UNSIGNED NOT NULL AUTO_INCREMENT,
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
					saleTxnId VARCHAR('.PAYPAL_APILIB_PPSALETXNID_TEXTLEN.') NOT NULL,
					saleStatus VARCHAR('.PAYPAL_APILIB_PPSALESTATUS_TEXTLEN.'),
					UNIQUE KEY saleID (saleID)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

			//excecute the query
			$this->ShowSQL($sql);
			dbDelta($sql);

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
		
		function GetOptsSQL($sqlFilters)
		{
			$sqlOpts = '';
			
			if (isset($sqlFilters['limit']))
				$sqlOpts .= ' LIMIT '.$sqlFilters['limit'];
			
			return $sqlOpts;
		}
		
		// Add Sale - Address details are optional
		function AddSale($SaleDateTime, $saleName, $saleEMail, $salePaid, $Txnid, $Status, $salePPName = '', $salePPStreet = '', $salePPCity = '', $salePPState = '', $salePPZip = '', $salePPCountry = '')
		{
			global $wpdb;
			
			$sql  = 'INSERT INTO '.$this->opts['SalesTableName'].'(saleDateTime, saleName, saleEMail, salePaid, saleTxnId, saleStatus, salePPName, salePPStreet, salePPCity, salePPState, salePPZip, salePPCountry)';
			$sql .= ' VALUES("'.$SaleDateTime.'", "'.$saleName.'", "'.$saleEMail.'", "'.$salePaid.'", "'.$Txnid.'", "'.$Status.'", "'.$salePPName.'", "'.$salePPStreet.'", "'.$salePPCity.'", "'.$salePPState.'", "'.$salePPZip.'", "'.$salePPCountry.'")';
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
		
		function AddSaleItem($saleID, $stockID, $qty)
		{
			global $wpdb;
			
			$sql  = 'INSERT INTO '.$this->opts['OrdersTableName'].'(saleID, '.$this->DBField('stockID').', '.$this->DBField('orderQty').')';
			$sql .= ' VALUES('.$saleID.', '.$stockID.', "'.$qty.'")';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
			$orderID = mysql_insert_id();
				
			return $orderID;
		}			
		
		function UpdateSaleItem($saleID, $stockID, $qty)
		{
			global $wpdb;

			// Delete a show entry
			$sql  = 'UPDATE '.$this->opts['OrdersTableName'];
			$sql .= ' SET '.$this->DBField('orderQty').'="'.$qty.'"';
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
			MJSLibUtilsClass::UndefinedFuncCallError($this, 'GetPricesListWithSales');
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

			if (isset($sqlFilters['orderBy']))
				$sql .= ' ORDER BY '.$sqlFilters['orderBy'];
			
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
		
		function AddEMailFields($EMailTemplate, $saleDetails)
		{
			// FUNCTIONALITY: DBase - Sales - Add generic DB fields to EMail
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
				$EMailTemplate = str_replace($tag, $saleDetails->$field, $EMailTemplate);
						
			$EMailTemplate = str_replace('[organisation]', $this->adminOptions['OrganisationID'], $EMailTemplate);
			
			$EMailTemplate = str_replace('[salesEMail]', $this->GetSalesEMail(), $EMailTemplate);
			$EMailTemplate = str_replace('[url]', get_option('siteurl'), $EMailTemplate);
			
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
		
		function LogSale($results)
		{
			$ourOptions = get_option($this->optionsID);
			
			$TxdDate  = $results['TxdDate'];
			$Txnid  = $results['Txnid'];
			$saleName  = $results['saleName'];
			$saleEmail  = $results['saleEmail'];
			$saleStatus  = $results['saleStatus'];
			$salePrice  = $results['salePrice'];
			$salePPName  = $results['salePPName'];
			$salePPStreet  = $results['salePPStreet'];
			$salePPCity  = $results['salePPCity'];
			$salePPState  = $results['salePPState'];
			$salePPZip  = $results['salePPZip'];
			$salePPCountry  = $results['salePPCountry'];

			// Log sale to Database
			$saleID = $this->AddSale(
				$TxdDate, 
				$saleName, 
				$saleEmail, 
				$salePrice, 
				$Txnid,
				$saleStatus, 
				$salePPName, 
				$salePPStreet, 
				$salePPCity, 
				$salePPState, 
				$salePPZip, 
				$salePPCountry);
		  		  
			$itemNo = 1;
			$lineNo = 1;
			While (true)
			{
				if (!isset($results['itemID' . $itemNo]))
					break;

				$itemID  = $results['itemID' . $itemNo];
				$itemName  = $results['itemName' . $itemNo];
				$itemRef  = $results['itemRef' . $itemNo];
				$itemOption  = $results['itemOption' . $itemNo];
				$qty  = $results['qty' . $itemNo];

				if ($qty > 0)
				{
					// Find stockID from Database	    
					$stockID = $this->GetSaleStockID($itemRef, $itemOption);

					// Log sale item to Database
					$this->AddSaleItem($saleID, $stockID, $qty);
			    
					$lineNo++;
				} // End of if ($qty > 0)
				$itemNo++;
			}
		  
			return $saleID;
		}

	    function HTTPAction($url, $urlParams = '')
	    {	
			$HTTPResponse = PayPalAPIClass::HTTPAction($url, $urlParams);
			if ($this->getOption('Dev_ShowMiscDebug') == 1)
				MJSLibUtilsClass::print_r($HTTPResponse, 'HTTPResponse:');
			return $HTTPResponse; 
	    }
    
	}
}

?>