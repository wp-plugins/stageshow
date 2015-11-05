<?php
/* 
Description: Core Library Database Access functions
 
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

if(!isset($_SESSION)) 
{
	// MJS - SC Mod - Register to use SESSIONS
	session_start();
}	

if (!defined('STAGESHOWLIB_DBASE_CLASS'))
	define('STAGESHOWLIB_DBASE_CLASS', 'StageShowLibSalesDBaseClass');
	
if (!defined('STAGESHOWLIB_DATABASE_FULL')) define('STAGESHOWLIB_DATABASE_FULL', true);

if (!class_exists('StageShowLibSalesCartDBaseClass')) 
	include STAGESHOWLIB_INCLUDE_PATH.'stageshowlib_sales_trolley_dbase_api.php';

if (!class_exists('StageShowLibSalesDBaseClass')) 
{
	/*
	---------------------------------------------------------------------------------
		StageShowLibSalesDBaseClass
	---------------------------------------------------------------------------------
	
	This class provides database functionality to capture sales data and support
	Payment Notification
	*/
	
	if (!defined('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE'))
		define('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE', '');
	if (!defined('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE'))
		define('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE', '');
		
	if (!defined('STAGESHOWLIB_SALES_ACTIVATE_TIMEOUT_EMAIL_TEMPLATE_PATH'))
		define('STAGESHOWLIB_SALES_ACTIVATE_TIMEOUT_EMAIL_TEMPLATE_PATH', '');
		
	if (!defined('STAGESHOWLIB_FILENAME_HTTPIOLOG'))
		define('STAGESHOWLIB_FILENAME_HTTPIOLOG', 'HTTPLog.txt');
						
	class StageShowLibSalesDBaseClass extends StageShowLibSalesCartDBaseClass // Define class 
  	{	
		const STAGESHOWLIB_LOGSALEMODE_CHECKOUT = 'Checkout';
		const STAGESHOWLIB_LOGSALEMODE_RESERVE = 'Reserve';
		const STAGESHOWLIB_LOGSALEMODE_PAYMENT = 'Payment';
		
		const STAGESHOWLIB_FROMTROLLEY = true;
		const STAGESHOWLIB_NOTFROMTROLLEY = false;
		
		var 	$GatewayID = '';
		var		$GatewayName = '';
		
		function __construct($opts)		//constructor		
		{
			$optionsId = $opts['CfgOptionsID'];
			$currOptions = get_option($optionsId);
			$gatewayUpdated = true;
			$opts['DBaseObj'] = $this;
			if (isset($currOptions['GatewaySelected']))
			{
				$gatewayID = $currOptions['GatewaySelected'];	
				if ($this->AddGateway($opts, $gatewayID))
				{
					$gatewayUpdated = false;
				}
				else
				{
					$gatewayID = $currOptions['GatewaySelected'];					
				}
			}
			if ($gatewayUpdated)
			{
				$gatewayID = $this->GetDefaultGateway();
				if ($this->AddGateway($opts, $gatewayID))
				{
					$currOptions['GatewaySelected'] = $gatewayID;
				}
				else
				{
					$currOptions['GatewaySelected'] = '';
				}

				update_option($opts['CfgOptionsID'], $currOptions);
			}

			parent::__construct($opts);
						
			if (!isset($this->emailObjClass))
			{
				$this->emailObjClass = 'StageShowLibEMailAPIClass';
				$this->emailClassFilePath = STAGESHOWLIB_INCLUDE_PATH.'stageshowlib_email_api.php';   			
			}
				
		}

    	function GetDefaultGateway()
    	{
    		return 'paypal';
		}
		
		function GetSerialisedPostVars()
		{
			if (!isset($_POST['stageshowlib_PostVars'])) return;
			
			$Postvars = array();
			
			$serObj = stripslashes($_POST['stageshowlib_PostVars']);
			$postVarsArrray = unserialize($serObj);
			foreach($postVarsArrray as $postKey => $postVar)
			{
				$postVar = urldecode($postVar);
				$Postvars[$postKey] = $postVar;
				$_POST[$postKey] = $postVar;
			}
			
			if ($this->isDbgOptionSet('Dev_ShowPOST'))
			{
				StageShowLibUtilsClass::print_r($Postvars, '$_POST(Postvars)');
			}		
		}
		
		function SplitSaleNameField()
		{
			if (!$this->IfColumnExists($this->DBTables->Sales, 'saleName'))
				return false;
				
			// Split saleName field into two parts 			
			$sql  = 'UPDATE '.$this->DBTables->Sales.' SET ';
			$sql .= 'saleName = CONCAT(" ", REPLACE(saleName, ".", " "))';
			$this->query($sql);	

			$sql  = 'UPDATE '.$this->DBTables->Sales.' SET ';
			$sql .= 'saleFirstName = TRIM(SUBSTR(saleName, 1, LENGTH(saleName) - LOCATE(" ", REVERSE(saleName))))';
			$this->query($sql);	

			$sql  = 'UPDATE '.$this->DBTables->Sales.' SET ';
			$sql .= 'saleLastName = TRIM(SUBSTR(saleName, 1 + (LENGTH(saleName) - LOCATE(" ", REVERSE(saleName)))))';
			$this->query($sql);	

			$this->deleteColumn($this->DBTables->Sales, 'saleName');
					
			return true;
		}

	    function upgradeDB()
	    {
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$salesDefaultTemplatesPath = WP_CONTENT_DIR . '/plugins/' . $pluginID . '/templates';
			$salesTemplatesPath = WP_CONTENT_DIR . '/uploads/'.$pluginID;
			
			// FUNCTIONALITY: DBase - On upgrade ... Copy sales templates to working folder
			// Copy release templates to plugin persistent templates and images folders
			StageShowLibUtilsClass::recurse_copy($salesDefaultTemplatesPath, $salesTemplatesPath);
			
			if (!isset($this->adminOptions['CheckoutTimeout']) || ($this->adminOptions['CheckoutTimeout'] == ''))
			{
				$this->adminOptions['CheckoutTimeout'] = PAYMENT_API_CHECKOUT_TIMEOUT_DEFAULT;
			}
			
			if (!isset($this->adminOptions['PayPalCheckoutType']))
			{
				$this->adminOptions['PayPalCheckoutType'] = StageShowLibGatewayBaseClass::STAGESHOWLIB_CHECKOUTSTYLE_STANDARD;
			}
			
      		$this->saveOptions();      
			
			// FUNCTIONALITY: DBase - On upgrade ... Add any database fields
			// Add DB Tables
			$this->createDB();
			
			// Remove the saleName field - Move data first
			$this->SplitSaleNameField();
		}
		
		function PurgeDB()
		{
		}
		
		function uninstall()
		{
			$this->DropTable($this->DBTables->Sales);
			
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$salesTemplatesPath = WP_CONTENT_DIR . '/uploads/'.$pluginID;
			
			// Remove templates and images folders in Uploads folder
			if (is_dir($salesTemplatesPath))
				StageShowLibUtilsClass::deleteDir($salesTemplatesPath);
			
			parent::uninstall();
		}
				
		function CheckIsConfigured()
		{
			$isConfigured = $this->SettingsConfigured();
				
			if (!$isConfigured)
			{
				$settingsPageId = basename(dirname($this->opts['Caller']))."_settings";
				$gatewayName = $this->gatewayObj->GetName();
				$myDomain = $this->get_domain();
				
				$settingsPageURL = get_option('siteurl').'/wp-admin/admin.php?page='.$settingsPageId;
				$settingsPageURL .= '&tab=gateway-settings';
				$actionMsg = __('Set', $myDomain).' '.$gatewayName.' '.__('Payment Gateway Settings First', $myDomain).' - <a href='.$settingsPageURL.'>'.__('Here', $myDomain).'</a>';
				echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';				
			}
			
			return $isConfigured;
		}
				
		
		
		//Returns an array of admin options
		function getOptions($childOptions = array()) 
		{
			$ourOptions = array(
				'CheckoutCompleteURL' => '',        
				'CheckoutCancelledURL' => '',
				          
				'PayPalLogoImageFile' => PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE,
				'PayPalHeaderImageFile' => PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE,
				        
				'CurrencySymbol' => '',

				'SalesID' => '',        
				'SalesEMail' => '',
				                
				'EMailTemplatePath' => '',
				'TimeoutEMailTemplatePath' => STAGESHOWLIB_SALES_ACTIVATE_TIMEOUT_EMAIL_TEMPLATE_PATH,
								
				'Unused_EndOfList' => ''
			);
			
			$ourOptions = array_merge($this->gatewayObj->Gateway_GetOptions(), $ourOptions);
			
			$ourOptions = array_merge($ourOptions, $childOptions);
			
			$currOptions = parent::getOptions($ourOptions);
			
			$saveToDB = false;
			
			// PayPalLogoImageURL option has been changed to PayPalLogoImageFile
			if (isset($currOptions['PayPalLogoImageURL']))
			{
				$currOptions['PayPalLogoImageFile'] = basename($currOptions['PayPalLogoImageURL']);
				unset($currOptions['PayPalLogoImageURL']);
				$saveToDB = true;
			}
				
			// PayPalHeaderImageURL option has been changed to PayPalHeaderImageFile
			if (isset($currOptions['PayPalHeaderImageURL']))
			{
				$currOptions['PayPalHeaderImageFile'] = basename($currOptions['PayPalHeaderImageURL']);
				unset($currOptions['PayPalHeaderImageURL']);
				$saveToDB = true;
			}
				
			$this->adminOptions = $currOptions;

			$this->gatewayObj->LoginGatewayAPI($this->adminOptions, $this->dbgOptions);

			if ($saveToDB)
				$this->saveOptions();
				
			return $currOptions;
		}
		
		function Output_PluginHelp()
		{
			$timezone = get_option('timezone_string');
			if ($timezone == '')
			{
				$settingsPageURL = get_option('siteurl').'/wp-admin/options-general.php';
				$statusMsg = __('Timezone not set - Set it', $this->get_domain())." <a href=$settingsPageURL>".__('Here', $this->get_domain()).'</a>';
				echo '<div id="message" class="error"><p>'.$statusMsg.'</p></div>';
			}
			
			$mode = (defined('CORONDECK_RUNASDEMO')) ? ' (Demo Mode)' : ''; 
			echo  '<strong>'.__('Plugin', $this->get_domain()).':</strong> '.$this->get_pluginName()."$mode<br>\n";			
			echo  '<strong>'.__('Version', $this->get_domain()).':</strong> '.$this->get_version()."<br>\n";			
			echo  '<strong>'.__('Gateway', $this->get_domain()).':</strong> '.$this->GatewayName."<br>\n";			
			echo  '<strong>'.__('Timezone', $this->get_domain()).':</strong> '.$timezone."<br>\n";			

			if (!$this->isDbgOptionSet('Dev_DisableTestMenus'))
				$this->ShowDebugModes();
		}
		
		function SetTestSettings($testSettings)
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
		
		
		function CheckVersionNumber($stockRec)
		{
		}
		
		function getTableDef($tableName)
		{
			$sql = parent::getTableDef($tableName);
			
			switch($tableName)
			{
				case $this->DBTables->Sales:
					$sql .= '
						saleCheckoutTime DATETIME,
						saleDateTime DATETIME NOT NULL,
						saleFirstName VARCHAR('.PAYMENT_API_SALENAME_TEXTLEN.') NOT NULL,
						saleLastName VARCHAR('.PAYMENT_API_SALENAME_TEXTLEN.') NOT NULL,
						saleEMail VARCHAR('.PAYMENT_API_SALEEMAIL_TEXTLEN.') NOT NULL,
						salePPName VARCHAR('.PAYMENT_API_SALEPPNAME_TEXTLEN.'),
						salePPStreet VARCHAR('.PAYMENT_API_SALEPPSTREET_TEXTLEN.'),
						salePPCity VARCHAR('.PAYMENT_API_SALEPPCITY_TEXTLEN.'),
						salePPState VARCHAR('.PAYMENT_API_SALEPPSTATE_TEXTLEN.'),
						salePPZip VARCHAR('.PAYMENT_API_SALEPPZIP_TEXTLEN.'),
						salePPCountry VARCHAR('.PAYMENT_API_SALEPPCOUNTRY_TEXTLEN.'),
						salePPPhone VARCHAR('.PAYMENT_API_SALEPPPHONE_TEXTLEN.'),
						salePaid DECIMAL(9,2) NOT NULL,
						saleDonation DECIMAL(9,2) NOT NULL DEFAULT 0,
						salePostage DECIMAL(9,2) NOT NULL DEFAULT 0,
						saleTransactionFee DECIMAL(9,2) NOT NULL DEFAULT 0,
						saleFee DECIMAL(9,2) NOT NULL,
						saleTxnId VARCHAR('.PAYMENT_API_SALETXNID_TEXTLEN.') NOT NULL,
						saleMethod VARCHAR('.PAYMENT_API_SALEMETHOD_TEXTLEN.'),
						saleStatus VARCHAR('.PAYMENT_API_SALESTATUS_TEXTLEN.'),
						saleNoteToSeller TEXT,
						salePPExpToken VARCHAR('.PAYMENT_API_EXPTOKEN_TEXTLEN.') NOT NULL DEFAULT "",
						saleCheckoutURL VARCHAR('.PAYMENT_API_URL_TEXTLEN.') NOT NULL DEFAULT "",
						user_login VARCHAR(60) NOT NULL DEFAULT "",
					';
					break;
			}
							
			return $sql;
		}
		
		function clearAll()
		{
			parent::clearAll();

			$this->DropTable($this->DBTables->Sales);
		}
		
		function createDB($dropTable = false)
		{
			parent::createDB($dropTable);

			$this->createDBTable($this->DBTables->Sales, 'saleID', $dropTable);
		}
		
		function GetSaleStockID($itemRef, $itemOption)
		{
			return 0;
		}
		
		function GetSaleName($result)
		{
			if (is_array($result))
			{
				return trim($result['saleFirstName'].' '.$result['saleLastName']);
			}
			else
			{
				return trim($result->saleFirstName.' '.$result->saleLastName);
			}
		}
		
		function AddSale($saleDateTime = '', $salesVals = array())
		{
			$sqlFields = 'INSERT INTO '.$this->DBTables->Sales.'(saleDateTime';
			$sqlValues = ' VALUES("'.$saleDateTime.'"';

			foreach ($salesVals as $fieldID => $fieldVal)
			{
				if ($fieldID == 'saleDateTime')
					continue;
					
				$sqlFields .= ', '.$fieldID;
				$sqlValues .= ', "'.self::_real_escape($fieldVal).'"';
			}
			$sqlFields .= ')';
			$sqlValues .= ')';
			
			$sql = $sqlFields.$sqlValues;

			$this->query($sql);
			$saleID = $this->GetInsertId();
			
			return $saleID;
		}
		
		// Edit Sale
		
			
		function PurgePendingSales($timeout = '')
		{
			if ($timeout == '')
				$timeout = 60*$this->adminOptions['CheckoutTimeout'];	// 1 hour default
				
			$limitDateTime = date(StageShowLibDBaseClass::MYSQL_DATETIME_FORMAT, current_time( 'timestamp' ) - $timeout);
			
			$sql  = 'DELETE FROM '.$this->DBTables->Sales;
			$sql .= ' WHERE '.$this->DBTables->Sales.'.saleStatus="'.PAYMENT_API_SALESTATUS_CHECKOUT.'"';
			$sql .= ' AND   '.$this->DBTables->Sales.'.saleCheckoutTime < "'.$limitDateTime.'"';
			
			$this->query($sql);
			
			$sql  = 'DELETE o FROM '.$this->DBTables->Orders.' o ';
			$sql .= 'LEFT OUTER JOIN '.$this->DBTables->Sales.' s ON o.saleID = s.saleID ';
			$sql .= 'WHERE s.saleStatus IS NULL';
			 
			$this->query($sql);
		}
		
		function AddSampleSaleItem($saleID, $stockID, $qty, $paid, $saleExtras = array())
		{
			return $this->AddSaleItem($saleID, $stockID, $qty, $paid, $saleExtras);
		}
		
		
		function GetSalesQty($sqlFilters)
		{
			$sql  = 'SELECT '.$this->TotalSalesField($sqlFilters).' FROM '.$this->DBTables->Sales;	
			$sql .= $this->GetJoinedTables($sqlFilters, __CLASS__);
			$sql .= $this->GetWhereSQL($sqlFilters);
					
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
			// Delete a show entry
			$sql  = 'DELETE FROM '.$this->DBTables->Sales;
			if (is_array($saleID))
			{
				$salesList = '';
				foreach ($saleID as $saleItemID)
				{
					if ($salesList != '') $salesList .= ',';
					$salesList .= $saleItemID->saleID;
				}
				$sql .= ' WHERE '.$this->DBTables->Sales.".saleID IN ($salesList)";
			}
			else
				$sql .= ' WHERE '.$this->DBTables->Sales.".saleID=$saleID";
				
			$this->query($sql);
		}			

		
		function GetAllSalesList($sqlFilters = null)
		{
			$sqlFilters['groupBy'] = 'saleID';
			$sqlFilters['orderBy'] = $this->DBTables->Sales.'.saleID DESC';
			return $this->GetSalesList($sqlFilters);
		}

		function SearchSalesList($searchtext)
		{
			$sqlFilters['searchtext'] = '%'.$searchtext.'%';
			return $this->GetAllSalesList($sqlFilters);
		}						
		
		function AddPendingSaleItem(&$saleDetails, $itemName, $itemAmt)
		{
			if ($itemAmt <= 0) return 0;
			
			$itemNo = count($saleDetails);
			
			$saleDetails[$itemNo] = new stdClass();
			$saleDetails[$itemNo]->ticketName = $itemName;
			$saleDetails[$itemNo]->ticketQty = 1;
			$saleDetails[$itemNo]->ticketPaid = $itemAmt;
			$saleDetails[$itemNo]->ticketType = '';
			
			return $itemNo;
		}						
		
		function GetPendingSale($saleID)
		{
			$saleDetails = $this->GetSale($saleID);
			
			if (count($saleDetails) > 0)
			{
				$this->AddPendingSaleItem($saleDetails, __('Booking Fee', $this->get_Domain()), $saleDetails[0]->saleTransactionFee);
				$this->AddPendingSaleItem($saleDetails, __('Donation', $this->get_Domain()), $saleDetails[0]->saleDonation);
			}
			
			return $saleDetails;
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
			$EMailTemplate = parent::AddGenericFields($EMailTemplate);			
			$EMailTemplate = str_replace('[salesEMail]', $this->GetSalesEMail(), $EMailTemplate);
			
			return $EMailTemplate;
		}
		
		function IsCurrencyField($tag)
		{
			switch ($tag)
			{
				case '[saleFee]':
				case '[saleTransactionFee]':
				case '[salePaid]':
				case '[saleDonation]':
				case '[salePostage]':
					return true;
			}
			
			return false;					
		}
		
		function FormatEMailField($tag, $field, &$saleDetails)
		{
			if ($tag =='[saleName]')
			{
				return $this->GetSaleName($saleDetails);
			}
			
			if ($tag =='[saleNoteToSeller]')
			{
				$saleNoteToSeller = stripslashes($saleDetails->saleNoteToSeller);
				if ($saleNoteToSeller != '')
				{
					$saleNoteToSeller = str_replace("\n", "<br>", $saleNoteToSeller);
					$saleNoteToSeller = str_replace("<br><br>", "<br>", $saleNoteToSeller);
				}
				return $saleNoteToSeller;
			}
			
			if (!property_exists($saleDetails, $field))
			{
				return "**** $field ".__("Undefined", $this->get_domain())." ****";
			}
			
			if ($this->IsCurrencyField($tag))
			{
				$saleFieldValue = $this->FormatCurrency($saleDetails->$field, false);
			}
			else 
			{
					$saleFieldValue = $saleDetails->$field;
			}
			
			return $saleFieldValue;
		}
		
		static function HasCheckoutImage()
		{
			return false;
		}
		
		function AddEMailFields($EMailTemplate, $saleDetails)
		{
			// FUNCTIONALITY: DBase - Sales - Add DB fields to EMail
			
			$EMailTemplate = $this->AddGenericFields($EMailTemplate);
			
			if ($this->isDbgOptionSet('Dev_ShowMiscDebug'))
				StageShowLibUtilsClass::print_r($this->adminOptions, 'adminOptions');
				
			// Add any email fields that are not in the sale record
			$saleDetails->saleName = '';
			
			foreach ($saleDetails as $key => $value)
			{
				$tag = '['.$key.']';
				$value = $this->FormatEMailField($tag, $key, $saleDetails);
				$EMailTemplate = str_replace($tag, $value, $EMailTemplate);
			}
			
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
		
		function CheckEmailTemplatePath($templateID, $defaultTemplate = '', $baseClassTemplate = '')
		{
			$templatePath = str_replace("\\", "/", $this->adminOptions[$templateID]);
			$templatePath = basename($templatePath);

			// Fix for update downgrading template error
			if ( ($baseClassTemplate != '')
			  && ($defaultTemplate != $baseClassTemplate)
			  && ($templatePath == $baseClassTemplate))
			{
			  	$templatePath = $defaultTemplate;
			}
			
			if ($templatePath == '')
			{
				$templatePath = $defaultTemplate;
			}
			$this->adminOptions[$templateID] = $templatePath;
		}

		function GetEmailTemplatePath($templateID, $sale = array())
		{
			return $this->GetTemplatePath($templateID, 'emails');
		}

		function GetTemplatePath($templateID, $folder)
		{
			// EMail Template defaults to templates folder
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$templatePath = WP_CONTENT_DIR . '/uploads/'.$pluginID.'/'.$folder.'/'.$this->adminOptions[$templateID];

			return $templatePath;
		}

		function EMailSale($saleID, $EMailTo = '')
		{
			$salesList = $this->GetSale($saleID);
			if (count($salesList) < 1) 
				return 'salesList Empty';
				
			return $this->EMailSaleRecord($salesList, $EMailTo);
		}

		function EMailSaleRecord($salesList, $EMailTo = '')
		{
			$templatePath = $this->GetEmailTemplatePath('EMailTemplatePath', $salesList);
	
			return $this->SendEMailFromTemplate($salesList, $templatePath, $EMailTo);
		}
		
		function ReadTemplateFile($Filepath)
		{
			$hfile = fopen($Filepath,"r");
			if ($hfile != 0)
			{
				$fileLen = filesize($Filepath);
				if ($fileLen > 0)
					$fileContents = fread($hfile, $fileLen);
				else
					$fileContents = '';
				fclose($hfile);
			}
			else
			{
				echo "Error reading $Filepath<br>\n";
				$fileContents = '';
			}

			return $fileContents;
		}
		
		function SendEMailFromTemplate($saleRecord, $templatePath, $EMailTo = '')
		{		
			$EMailSubject = '';
			$saleConfirmation = '';

			include $this->emailClassFilePath;
			$this->emailObj = new $this->emailObjClass($this);
			
			$rtnStatus = $this->AddSaleToTemplate($saleRecord, $templatePath, $EMailSubject, $saleConfirmation);	
			if ($rtnStatus != 'OK')
				return $rtnStatus;
				
			// Get email address and organisation name from settings
			$EMailFrom = $this->GetEmail($this->adminOptions, 'Sales');

			if (strlen($EMailTo) == 0) $EMailTo = $saleRecord[0]->saleEMail;

			$this->emailObj->sendMail($EMailTo, $EMailFrom, $EMailSubject, $saleConfirmation);

			return 'OK';		
		}
		
		function DoTemplateLoop($section, $loopType, $saleRecord)	
		{				
			$saleConfirmation = '';
			
			switch ($loopType)
			{
				case '[startloop]':
					foreach($saleRecord as $ticket)
					{
						$saleConfirmation .= $this->AddEMailFields($section, $ticket);
					}
					break;
				
				default:
					$saleConfirmation = "<br><strong>Unknown Loop Definition in Template ($loopType)</strong><br><br>";
					break;
			}
			
			return $saleConfirmation;
		}
		
		function AddSaleToTemplate($saleRecord, $templatePath, &$EMailSubject, &$saleConfirmation)	
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
				$EMailSubject = $this->AddEMailFields(substr($mailTemplate, $posnPHP, $posnEOL-$posnPHP), $saleRecord[0]);
				$mailTemplate = substr($mailTemplate, $posnEOL);
			}
						
			// Find the line with the close php entry then find the start of the line
			$posnPHP = stripos($mailTemplate, '?>');
			if ($posnPHP !== false) $posnPHP = strrpos(substr($mailTemplate, 0, $posnPHP), "\n");
			if ($posnPHP !== false) $mailTemplate = substr($mailTemplate, 0, $posnPHP);

			$loopCount = 0;
			for (; $loopCount < 10; $loopCount++)
			{
				if (preg_match('/(\[[a-zA-Z0-9]*loop\])/', $mailTemplate, $matches) != 1)
					break;

				$loopStart = stripos($mailTemplate, $matches[0]);
				$loopEnd = stripos($mailTemplate, '[endloop]');

				if (($loopStart === false) || ($loopEnd === false))
					break;

				$section = substr($mailTemplate, 0, $loopStart);
				$saleConfirmation .= $this->AddEMailFields($section, $saleRecord[0]);

				$loopStart += strlen($matches[0]);
				$loopLen = $loopEnd - $loopStart;

				$section = substr($mailTemplate, $loopStart, $loopLen);
				$saleConfirmation .= $this->DoTemplateLoop($section, $matches[0], $saleRecord);

				$loopEnd += strlen('[endloop]');
				$mailTemplate = substr($mailTemplate, $loopEnd);
			}

			// Process the rest of the mail template
			$saleConfirmation .= $this->AddEMailFields($mailTemplate, $saleRecord[0]);
			
			return 'OK';		
		}
		
		function GetTxnStatus($Txnid)
		{
			$sql = 'SELECT saleStatus FROM '.$this->DBTables->Sales.' WHERE saleTxnId="'.$Txnid.'"';
			 
			$txnEntries = $this->get_results($sql);
			
			if (count($txnEntries) == 0) 
				return '';
			
			return $txnEntries[0]->saleStatus;
		}
		
		function GetCheckoutURL($SaleId)
		{
			$sql = 'SELECT saleCheckoutURL FROM '.$this->DBTables->Sales.' WHERE saleId="'.$SaleId.'"';
			 
			$chkoutEntries = $this->get_results($sql);
			
			if (count($chkoutEntries) == 0) 
				return '';
			
			return $chkoutEntries[0]->saleCheckoutURL;
		}
		
		function UpdateSaleIDStatus($SaleId, $Payment_status)
		{
			$sql  = 'UPDATE '.$this->DBTables->Sales;
			$sql .= ' SET saleStatus="'.$Payment_status.'"';		
			$sql .= ' WHERE saleId="'.$SaleId.'"';							
			 
			$this->query($sql);			
		}
		
		function UpdateSaleToken($SaleId, $saleToken)
		{
			$sql  = 'UPDATE '.$this->DBTables->Sales;
			$sql .= ' SET salePPExpToken="'.$saleToken.'"';		
			$sql .= ' WHERE saleId="'.$SaleId.'"';							
			 
			$this->query($sql);			
		}
		
		function UpdateSaleStatus($Txn_id, $Payment_status)
		{
			$sql  = 'UPDATE '.$this->DBTables->Sales;
			if ($Payment_status == PAYMENT_API_SALESTATUS_COMPLETED)
			{
				$sql .= ' SET saleStatus, saleCheckoutURL="'.$Payment_status.'", ""';
			}
			else		
			{
				$sql .= ' SET saleStatus="'.$Payment_status.'"';
			}		
			$sql .= ' WHERE saleTxnId="'.$Txn_id.'"';							
			 
			$this->query($sql);	
					
			// Get the SaleId and return it ....
			$sql  = 'SELECT saleId FROM '.$this->DBTables->Sales;
			$sql .= ' WHERE saleTxnId="'.$Txn_id.'"';							
			 
			$saleEntry = $this->get_results($sql);
			if (count($saleEntry) == 0)
					return 0;
							 
			return $saleEntry[0]->saleId;
		}
		
		function GetSaleExtras($itemNo, $results)
		{
			return array();
		}
		
		function LogSale($results, $saleMode = self::STAGESHOWLIB_LOGSALEMODE_PAYMENT)
		{
			switch ($saleMode)
			{
				case self::STAGESHOWLIB_LOGSALEMODE_CHECKOUT:
					$saleDateTime = current_time('mysql'); 
					
					$saleVals['saleCheckoutTime'] = $saleDateTime;
					$saleVals['saleStatus'] = PAYMENT_API_SALESTATUS_CHECKOUT;
					
					if (isset($results['saleMethod']))    $saleVals['saleMethod'] = $results['saleMethod'];
				
					// Add empty values for fields that do not have a default value
					$saleVals['saleFirstName'] = isset($results['saleFirstName']) ? $results['saleFirstName'] : '';
					$saleVals['saleLastName']  = isset($results['saleLastName']) ? $results['saleLastName'] : '';
					$saleVals['saleEMail']     = isset($results['saleEMail']) ? $results['saleEMail'] : '';
					
					// Add values for fields that are entered by user
					if (isset($results['salePPStreet']))    $saleVals['salePPStreet'] = $results['salePPStreet'];
					if (isset($results['salePPCity']))      $saleVals['salePPCity'] = $results['salePPCity'];
					if (isset($results['salePPState']))     $saleVals['salePPState'] = $results['salePPState'];
					if (isset($results['salePPZip']))       $saleVals['salePPZip'] = $results['salePPZip'];
					if (isset($results['salePPCountry']))   $saleVals['salePPCountry'] = $results['salePPCountry'];
					if (isset($results['salePPPhone']))     $saleVals['salePPPhone'] = $results['salePPPhone'];
					
					$saleVals['saleTxnid'] = '';

					$saleVals['salePaid'] = '0.0';
					$saleVals['saleFee'] = '0.0';
					if (isset($results['saleTransactionfee']))  $saleVals['saleTransactionFee'] = $results['saleTransactionfee'];
					if (isset($results['saleDonation']))        $saleVals['saleDonation'] = $results['saleDonation'];
					if (isset($results['salePostage']))         $saleVals['salePostage'] = $results['salePostage'];
					if (isset($results['saleNoteToSeller']))	$saleVals['saleNoteToSeller'] = $results['saleNoteToSeller'];
					if (isset($results['salePPExpToken']))      $saleVals['salePPExpToken'] = $results['salePPExpToken'];
					if (isset($results['saleDiscountCode']))    $saleVals['saleDiscountCode'] = $results['saleDiscountCode'];

					global $current_user;
					if (is_user_logged_in())
					{
						wp_get_current_user();
						$saleVals['user_login'] = $current_user->user_login;
					}		
									
					$saleVals['saleCheckoutURL'] = StageShowLibUtilsClass::GetPageURL();
							
					$saleID = $this->AddSale($saleDateTime, $saleVals);

					break;
				
				case self::STAGESHOWLIB_LOGSALEMODE_PAYMENT:
					// Just add the sale details 
					$saleID = $this->UpdateSale($results);
					return $saleID;

				case self::STAGESHOWLIB_LOGSALEMODE_RESERVE:
					$saleDateTime  = $results['saleDateTime'];
					
					foreach ($results as $fieldID => $fieldVal)
					{
						// Don't pass ticket details to AddSale() ... these are passed in AddSaleItem()
						if (is_numeric(substr($fieldID, -1, 1)))
							continue;
							
						$saleVals[$fieldID] = $fieldVal;
					}
					
					// Log sale to Database
					$saleID = $this->AddSale($saleDateTime, $saleVals);
					break;
				
				default:
					echo "<br><br>Invalid saleMode in LogSale() call<br><br>";
					return 0;
				
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
					$saleExtras = $this->GetSaleExtras($itemNo, $results);
					$this->AddSaleItem($saleID, $stockID, $qty, $itemPaid, $saleExtras);
			    
					$lineNo++;
				}
				$itemNo++;
			}
		  
			return $saleID;
		}

		function AddTableLocks($sql)
		{
			$sql .= $this->DBTables->Sales.' WRITE, ';
			$sql .= $this->DBTables->Orders.' WRITE ';
			
			return $sql;
		}
		
		function LockSalesTable()
		{
			$sql = $this->AddTableLocks('LOCK TABLES ');
			$this->query($sql);
		}
		
		function UnLockTables()
		{
			$sql  = 'UNLOCK TABLES';
			$this->query($sql);
		}
		
	    function HTTPGet($url)
	    {	
			return $this->HTTPRequest($url, '', 'GET');
		}
		
	    function HTTPPost($url, $urlParams = '')
	    {	
			return $this->HTTPRequest($url, $urlParams, 'POST');
		}
		
	    function HTTPRequest($url, $urlParams = '', $method = '', $redirect = true)
	    {	
			if ($method == '')
			{
				$method = ($urlParams == '') ? 'GET' : 'POST';			
			}
			
			$HTTPResponse = $this->HTTPAction($url, $urlParams, $method, $redirect);
			if ( $this->isDbgOptionSet('Dev_ShowMiscDebug')
			  || $this->isDbgOptionSet('Dev_LogHTTP') )
			{
				$httpLog = '';
				$httpLog .= "HTTPRequest Called<br>";
				$httpLog .= "URL: $url<br>";
				$httpLog .= "METHOD: $method<br>";
				$httpLog .= "URL Params: <br>";
				$httpLog .= print_r($urlParams, true);
				$httpLog .= StageShowLibUtilsClass::print_r($HTTPResponse, 'HTTPResponse:', true);
				
				if ($this->isDbgOptionSet('Dev_ShowMiscDebug'))
					echo $httpLog;
					
				if ($this->isDbgOptionSet('Dev_LogHTTP'))
				{					
					include 'stageshowlib_logfile.php';
					$LogsFolder = $this->getOption('LogsFolderPath').'/';
					$logFileObj = new StageShowLibLogFileClass($LogsFolder);
					$logFileObj->StampedLogToFile(STAGESHOWLIB_FILENAME_HTTPIOLOG, $httpLog, StageShowLibDBaseClass::ForAppending);
				}
			}
			return $HTTPResponse; 
	    }
    
		function HTTPAction($url, $urlParams = '', $method = 'POST', $redirect = true)
		{
			if( !class_exists( 'WP_Http' ) )
				include_once( ABSPATH . WPINC. '/class-http.php' );

			$args = array(
			'method' => $method,
			'body' => $urlParams,
			'sslverify' => false
			);
			
			if (!$redirect)
				$args['redirection'] = 0;
			
			$request = new WP_Http;
			$HTTPResult = $request->request( $url, $args );
			if ( is_wp_error($HTTPResult) )
			{
				$response['APIResponseText'] = '';
				$response['APIStatus'] = 'ERROR';
				$response['APIStatusMsg'] = $HTTPResult->get_error_message();
				$response['APIHeaders'] = '';
				$response['APICookies'] = array();
			}
			else
			{
				$response['APIResponseText'] = $HTTPResult['body'];
				$response['APIStatus'] = $HTTPResult['response']['code'];
				$response['APIStatusMsg'] = $HTTPResult['response']['message'];
				$response['APIHeaders'] = $HTTPResult['headers'];
				$response['APICookies'] = $HTTPResult['cookies'];
			}
/*			
			{
				echo "HTTPRequest Called<br>";
				echo "URL: $url<br>";
				echo "METHOD: $method<br>";
				echo "URL Params: <br>";
				print_r($urlParams);
				print_r($response, 'HTTPResponse:');
			}
*/
			return $response;			
		}

	}
}

?>
