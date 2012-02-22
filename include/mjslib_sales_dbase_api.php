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
		
    function uninstall()
    {
      global $wpdb;
      
      $wpdb->query('DROP TABLE IF EXISTS '.$this->opts['SalesTableName']);
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
        
        'PayPalLogoImageURL' => PAYPAL_APILIB_DEFAULT_LOGOIMAGE_URL,
        'PayPalHeaderImageURL' => PAYPAL_APILIB_DEFAULT_HEADERIMAGE_URL,
        
        'SalesID' => '',        
        'SalesEMail' => '',
                
        'Unused_EndOfList' => ''
			);
				
			$ourOptions = array_merge($ourOptions, $childOptions);
			
			$currOptions = parent::getOptions($ourOptions);
			
			if ($currOptions['PayPalCurrency'] == '')
				$currOptions['PayPalCurrency'] = PAYPAL_APILIB_DEFAULT_CURRENCY;
			
			$this->adminOptions = $currOptions;
			
			return $currOptions;
		}
		
		// Saves the admin options to the PayPal object(s)
		function setPayPalCredentials($OurIPNListener) 
		{
			$this->payPalAPIObj->SetLoginParams(
				$this->adminOptions['PayPalEnv'], 
				$this->adminOptions['PayPalAPIUser'], 
				$this->adminOptions['PayPalAPIPwd'], 
				$this->adminOptions['PayPalAPISig'], 
				$this->adminOptions['PayPalCurrency'], 
				$this->adminOptions['PayPalAPIEMail']);
			$this->payPalAPIObj->SetIPNListener($OurIPNListener);
				
			if ($this->adminOptions['Dev_ShowPayPalIO'] == 1)
				$this->payPalAPIObj->EnableDebug();
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
			return '';
		}
		
		// Add Sale - Address details are optional
		function AddSale($SaleDateTime, $PayerName, $PayerEmail, $salePrice, $Txnid, $Status, $salePPName = '', $salePPStreet = '', $salePPCity = '', $salePPState = '', $salePPZip = '', $salePPCountry = '')
		{
			global $wpdb;
			
			$sql  = 'INSERT INTO '.$this->opts['SalesTableName'].'(saleDateTime, saleName, saleEMail, salePaid, saleTxnId, saleStatus, salePPName, salePPStreet, salePPCity, salePPState, salePPZip, salePPCountry)';
			$sql .= ' VALUES("'.$SaleDateTime.'", "'.$PayerName.'", "'.$PayerEmail.'", "'.$salePrice.'", "'.$Txnid.'", "'.$Status.'", "'.$salePPName.'", "'.$salePPStreet.'", "'.$salePPCity.'", "'.$salePPState.'", "'.$salePPZip.'", "'.$salePPCountry.'")';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
			$saleID = mysql_insert_id();
	
			return $saleID;
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
				$sql .= ' ORDER BY '.$this->opts['SalesTableName'].'.'.$sqlFilters['orderBy'];
			
			$this->ShowSQL($sql); 
			
			$salesListArray = $this->get_results($sql);
			
			return $salesListArray;
		}			

		function GetSalesEMail($currOptions)
		{
			return $currOptions['SalesEMail'];
		}
		
		function AddEMailFields($currOptions, $EMailTemplate, $saleDetails)
		{
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
						
			$EMailTemplate = str_replace('[organisation]', $currOptions['OrganisationID'], $EMailTemplate);
			
			$EMailTemplate = str_replace('[salesEMail]', $this->GetSalesEMail($currOptions), $EMailTemplate);
			$EMailTemplate = str_replace('[url]', get_option('siteurl'), $EMailTemplate);
			
			return $EMailTemplate;
		}
		
		function AddSalesDetailsEMailFields($currOptions, $EMailTemplate, $orderDetails)
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
		
		function EMailSale($saleID, $EMailTo = '')
		{
			$ourOptions = get_option($this->opts['CfgOptionsID']);
	
			// Get sale	and ticket details
			$salesList = $this->GetSale($saleID);
			if (count($salesList) < 1) 
				return 'salesList Empty';
			$saleDetails = $salesList[0];
			
			$orderDetails = $salesList;
			if (count($orderDetails) < 1) 
				return 'orderDetails Empty';
				
			$filePath = dirname($this->opts['PluginRootFilePath']).'/'.$ourOptions['EMailTemplatePath'];		

			$mailTemplate = $this->ReadTemplateFile($filePath);
			if (strlen($mailTemplate) == 0)
				return "EMail Template Not Found ($filePath)";
				
			$saleConfirmation = '';
			
			// Find the line with the open php entry then find the end of the line
			$posnPHP = stripos($mailTemplate, '<?php');
			if ($posnPHP !== false) $posnPHP = strpos($mailTemplate, "\n", $posnPHP);
			if ($posnPHP !== false) $posnEOL = strpos($mailTemplate, "\n", $posnPHP+1);
			if (($posnPHP !== false) && ($posnEOL !== false)) 
			{
				$EMailSubject = $this->AddEMailFields($ourOptions, substr($mailTemplate, $posnPHP, $posnEOL-$posnPHP), $saleDetails);
				$mailTemplate = substr($mailTemplate, $posnEOL);
			}
			
			// Find the line with the close php entry then find the start of the line
			$posnPHP = stripos($mailTemplate, '?>');
			if ($posnPHP !== false) $posnPHP = strrpos(substr($mailTemplate, 0, $posnPHP), "\n");
			if ($posnPHP !== false) $mailTemplate = substr($mailTemplate, 0, $posnPHP);

			$loopCount = 0;
//echo "<strong><br><br><h2>loopCount - $loopCount <br><br></h2></strong>\n";				
			for (; $loopCount < 10; $loopCount++)
			{
//echo "loopCount - $loopCount <br>\n";				
				$loopStart = stripos($mailTemplate, '[startloop]');
				$loopEnd = stripos($mailTemplate, '[endloop]');

				if (($loopStart === false) || ($loopEnd === false))
					break;

				$section = substr($mailTemplate, 0, $loopStart);
				$saleConfirmation .= $this->AddEMailFields($ourOptions, $section, $saleDetails);

				$loopStart += strlen('[startloop]');
				$loopLen = $loopEnd - $loopStart;

				foreach($orderDetails as $ticket)
				{
					$section = substr($mailTemplate, $loopStart, $loopLen);
					$saleConfirmation .= $this->AddSalesDetailsEMailFields($ourOptions, $section, $ticket);
				}

				$loopEnd += strlen('[endloop]');
				$mailTemplate = substr($mailTemplate, $loopEnd);
			}

//echo "<strong><br><br><h2>Abort EMailSale early? - $loopCount <br><br></h2></strong>\n";				
//return "Test Complete";	
			// Process the rest of the mail template
			$saleConfirmation .= $this->AddEMailFields($ourOptions, $mailTemplate, $saleDetails);

			// Get email address and organisation name from settings
			$EMailFrom = $this->GetEmail($ourOptions, 'Sales');

			if (strlen($EMailTo) == 0) $EMailTo = $saleDetails->saleEMail;

			if (isset($this->emailObj))
				$this->emailObj->sendMail($EMailTo, $EMailFrom, $EMailSubject, $saleConfirmation);

			echo '<div id="message" class="updated"><p>'.__('EMail Sent to', $this->get_name()).' '.$EMailTo.'</p></div>';
						
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
			$PayerName  = $results['PayerName'];
			$PayerEmail  = $results['PayerEmail'];
			$SaleStatus  = $results['SaleStatus'];
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
				$PayerName, 
				$PayerEmail, 
				$salePrice, 
				$Txnid,
				$SaleStatus, 
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
			$this->ShowSQL('URL:', $url.$urlParams);
			$HTTPResponse = PayPalAPIClass::HTTPAction($url, $urlParams);
			if ($this->adminOptions['Dev_ShowDBOutput'] == 1)
				MJSLibUtilsClass::print_r($HTTPResponse, 'HTTPResponse:');
			return $HTTPResponse; 
    }
    
	}
}

?>