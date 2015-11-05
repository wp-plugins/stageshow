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
	define('STAGESHOWLIB_DBASE_CLASS', 'StageShowLibSalesCartDBaseClass');
	
if (!class_exists('StageShowLibDBaseClass')) 
	include STAGESHOWLIB_INCLUDE_PATH.'stageshowlib_dbase_api.php';
	
if (!class_exists('StageShowLibSalesCartDBaseClass')) 
{
	/*
	---------------------------------------------------------------------------------
		StageShowLibSalesCartDBaseClass
	---------------------------------------------------------------------------------
	
	This class provides database functionality to capture sales data and support
	Payment Notification
	*/
	
	if (!defined('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE'))
		define('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE', '');
		
	if( !defined( 'PAYMENT_API_SALESTATUS_RESERVED' ) )
	{
		define('PAYMENT_API_SALESTATUS_RESERVED', 'Reserved');		
	}

  	class StageShowLibSalesCartDBaseClass extends StageShowLibDBaseClass // Define class
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
				$this->AddGateway($opts, $gatewayID);
			}

			parent::__construct($opts);
						
			if (!isset($this->emailObjClass))
			{
				$this->emailObjClass = 'StageShowLibEMailAPIClass';
				$this->emailClassFilePath = STAGESHOWLIB_INCLUDE_PATH.'stageshowlib_email_api.php';   			
			}
				
		}

		function AddGateway($opts, $gatewayID)
		{
			$this->GatewayID = $gatewayID;
			
			$gatewayFile = 'stageshowlib_'.$this->GatewayID.'_gateway.php'; 
			if (!file_exists(dirname(__FILE__).'/'.$gatewayFile)) return false;

			$gatewayClass = 'StageShowLib_'.$this->GatewayID.'_GatewayClass'; 
			
			include $gatewayFile;      						// i.e. stageshowlib_paypal_gateway.php
			$this->gatewayObj = new $gatewayClass($opts); 	// i.e. StageShowLib_paypal_GatewayClass
			
			$this->GatewayName = $this->gatewayObj->GetName();
			return true;
		}
		
		function getImagesURL()
		{
			if (defined('STAGESHOWLIB_IMAGESURL'))
				return STAGESHOWLIB_IMAGESURL;
				
			$siteurl = get_option('siteurl');
			if ($this->isOptionSet('PayPalImagesUseSSL'))
			{
				$siteurl = str_replace('http', 'https', $siteurl);
			}
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			return $siteurl.'/wp-content/uploads/'.$pluginID.'/images/';
		}
		
		function getImageURL($optionId)
		{			
			$imageURL = isset($this->adminOptions[$optionId]) ? $this->getImagesURL().$this->adminOptions[$optionId] : '';
			return $imageURL;
		}
		
		static function FormatDateForDisplay($dateInDB)
		{
			// Convert time string to UNIX timestamp
			$timestamp = strtotime( $dateInDB );
			return self::FormatTimestampForDisplay($timestamp);
		}
		
		static function FormatTimestampForDisplay($timestamp)
		{
			$dateFormat = self::GetDateTimeFormat();
				
			// Get Time & Date formatted for display to user
			$dateAndTime = date($dateFormat, $timestamp);
			if (strlen($dateAndTime) < 2)
			{
				$dateAndTime = '[Invalid WP Date/Time Format]';
			}
			
			return $dateAndTime;
		}
		
		function FormatCurrencyValue($amount, $asHTML = true)
		{
			$currencyText = sprintf($this->getOption('CurrencyFormat'), $amount);
			return $currencyText;
		}
		
		function FormatCurrency($amount, $asHTML = true)
		{
			$currencyText = $this->FormatCurrencyValue($amount, $asHTML);
			if (!$this->getOption('UseCurrencySymbol'))
				return $currencyText;
				
			if ($asHTML)
			{
				$currencyText = $this->getOption('CurrencySymbol').$currencyText;				
			}
			else
			{
				$currencyText = $this->getOption('CurrencyText').$currencyText;
			}

			return $currencyText;
		}
		
		function SettingsConfigured()
		{
			return $this->gatewayObj->IsGatewayConfigured($this->adminOptions);
		}
		
		function saveOptions()
		{
			$newOptions = $this->adminOptions;

			$currentGateway = $this->gatewayObj->GetID();
			$newGateway = $newOptions['GatewaySelected'];
			if ($newGateway != $currentGateway)		
			{
				// Load new gateway ...
				$this->AddGateway($this->gatewayObj->opts, $newGateway);
			}
			
			$currencyOptionID = $this->gatewayObj->GetCurrencyOptionID();	
			if (isset($newOptions[$currencyOptionID]))
			{
				$currency = $newOptions[$currencyOptionID];			
				$currencyDef = $this->gatewayObj->GetCurrencyDef($currency);
				
				if (isset($currencyDef['Symbol']))
				{
					$newOptions['CurrencySymbol'] = $currencyDef['Symbol'];
					$newOptions['CurrencyText']   = ($currencyDef['Char'] != '') ? $currencyDef['Char'] : $currency.'';
					$newOptions['CurrencyFormat'] = $currencyDef['Format'];
				}
				else
				{
					$newOptions['CurrencySymbol'] = $currency.'';
					$newOptions['CurrencyText']   = $currency.'';
					$newOptions['CurrencyFormat'] = '%01.2f';
				}							
			}
			
			$this->adminOptions = $newOptions;
			parent::saveOptions();
		}
		
		function getTableNames($dbPrefix)
		{
			$DBTables = parent::getTableNames($dbPrefix);
			
			$DBTables->Sales = $dbPrefix.'sales';
			$DBTables->Orders = $dbPrefix.'orders';
			
			return $DBTables;
		}
				
		function GetSalesQueryFields($sqlFilters = null)
		{
			return '*';
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
				$sqlWhere .= $sqlCmd.$this->DBTables->Sales.'.saleID="'.$sqlFilters['saleID'].'"';
				$sqlCmd = ' AND ';
			}
			
			if (isset($sqlFilters['saleTxnId']) && (strlen($sqlFilters['saleTxnId']) > 0))
			{
				$sqlWhere .= $sqlCmd.$this->DBTables->Sales.'.saleTxnId="'.$sqlFilters['saleTxnId'].'"';
				$sqlCmd = ' AND ';
			}
			
			if (isset($sqlFilters['searchtext']))
			{
				$searchFields = array('saleEMail', 'saleFirstName', 'saleLastName');
				
				$sqlWhere .= $sqlCmd.'(';
				$sqlOr = '';				
				foreach ($searchFields as $searchField)
				{
					$sqlWhere .= $sqlOr;
					$sqlWhere .= $this->DBTables->Sales.'.'.$searchField.' LIKE "'.$sqlFilters['searchtext'].'"';
					$sqlOr = ' OR ';
				}
				$sqlWhere .= ')';
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

		// Edit Sale
		function GetSalesFields()
		{
			return array
			(
				'saleFirstName', 
				'saleLastName', 
				'saleDateTime', 
				'saleTxnId', 
				'saleStatus', 
				'salePPName', 
				
				'saleEMail', 
				'salePaid', 
				'saleDonation', 
				'salePostage', 
				'saleTransactionFee', 
				'saleFee', 
				'salePPStreet', 
				'salePPCity', 
				'salePPState', 
				'salePPZip', 
				'salePPCountry', 
				'salePPPhone', 
				
				'saleMethod', 
				
				'saleNoteToSeller', 
				
				'user_login', 
			);
		}			
		
		function Ex_AddSale($saleDateTime = '', $salesVals = array())
		{
			$sqlFields = 'INSERT INTO '.$this->DBTables->Sales.'(saleDateTime';
			$sqlValues = ' VALUES("'.$saleDateTime.'"';
			
			$fieldsList = $this->GetSalesFields();
			
			foreach ($fieldsList as $fieldName)
			{
				if ($fieldName == 'saleDateTime')
					continue;
					
				//if (!isset($salesVals->$fieldName))
				//	continue;
				$fieldValue = $salesVals->$fieldName;
				
				$sqlFields .= ', '.$fieldName;
				$sqlValues .= ', "'.self::_real_escape($fieldValue).'"';
			}
			$sqlFields .= ')';
			$sqlValues .= ')';
			
			$sql = $sqlFields.$sqlValues;
			 
			$this->query($sql);
			$saleID = $this->GetInsertId();
	
			return $saleID;
		}			
		
		function UpdateSale($results, $fromTrolley = self::STAGESHOWLIB_NOTFROMTROLLEY)
		{
			if ($fromTrolley)
			{
				$saleID = $results->saleID;
			}
			else
			{
				$saleID = $results['saleID'];
			}
			
			$fieldsList = $this->GetSalesFields();
			
			$fieldSep = 'UPDATE '.$this->DBTables->Sales.' SET ';
			
			$sql = '';
			foreach ($fieldsList as $fieldName)
			{
				if ($fromTrolley)
				{
					if (!isset($results->$fieldName))
						continue;
					$fieldValue = self::_real_escape($results->$fieldName);
				}
				else
				{
					if (!isset($results[$fieldName]))
						continue;
					$fieldValue = self::_real_escape($results[$fieldName]);
				}
					
				$sql .= $fieldSep.$fieldName.'="'.$fieldValue.'"';
				$fieldSep = ' , ';
			}
			
			if ($fromTrolley)
			{
				$saleID = $results->saleID;
				if (isset($results->saleStatus) && ($results->saleStatus == PAYMENT_API_SALESTATUS_COMPLETED))
				{
					$sql .= $fieldSep.'saleCheckoutURL=""';
				}
			}
			else
			{
				if (isset($results['saleStatus']) && ($results['saleStatus'] == PAYMENT_API_SALESTATUS_COMPLETED))
				{
					$sql .= $fieldSep.'saleCheckoutURL=""';
				}
			}
			
			$sql .= ' WHERE '.$this->DBTables->Sales.'.saleID='.$saleID;;
			 
			$rtnVal = $this->query($sql);	
			if ($this->getDbgOption('Dev_ShowSQL'))
			{
				echo "<br>UpdateSale - query() Returned: $rtnVal<br>\n";
			}
			
			if (!$rtnVal)
				return 0;
			
			if ($this->queryResult == 0)
				return 0-$saleID;
				
			return $saleID;
		}
		
		function AddSaleItem($saleID, $stockID, $qty, $paid, $saleExtras = array())
		{
			$paid *= $qty;
			
			$sqlFields  = 'INSERT INTO '.$this->DBTables->Orders.'(saleID, '.$this->DBField('stockID').', '.$this->DBField('orderQty').', '.$this->DBField('orderPaid');
			$sqlValues  = ' VALUES('.$saleID.', '.$stockID.', "'.$qty.'", "'.$paid.'"';
			
			foreach ($saleExtras as $field => $value)
			{
				$sqlFields .= ','.$field;
				$sqlValues .= ', "'.$value.'"';
			}
			
			$sqlFields .= ')';
			$sqlValues .= ')';
			
			$sql = $sqlFields.$sqlValues;
			
			$this->query($sql);
			$orderID = $this->GetInsertId();
				
			return $orderID;
		}			

		function AddSaleFields(&$salesListArray)
		{
		}
		
		function GetSale($saleID)
		{
			$sqlFilters['saleID'] = $saleID;
			return $this->GetSalesList($sqlFilters);
		}
				
		function TotalSalesField($sqlFilters = null)
		{
			return '';
		}

		function GetSalesList($sqlFilters)
		{
			$selectFields  = $this->GetSalesQueryFields($sqlFilters);
			
			if (isset($sqlFilters['saleID']) || isset($sqlFilters['priceID']))
			{
				// Explicitly add joined fields from "base" tables (otherwise values will be NULL if there is no matching JOIN)
				$selectFields .= ', '.$this->DBTables->Sales.'.saleID';

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

			$sql  = 'SELECT '.$selectFields.' FROM '.$this->DBTables->Sales;
			$sql .= $this->GetJoinedTables($sqlFilters, __CLASS__);
			
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= $this->GetOptsSQL($sqlFilters);
			
			// Get results ... but suppress debug output until AddSaleFields has been called
			$salesListArray = $this->get_results($sql, false);			
			if (!isset($sqlFilters['addTicketFee']))
			{
				$this->AddSaleFields($salesListArray);				
			}
			
			$this->show_results($salesListArray);
					
			return $salesListArray;
		}			

		function DeleteOrders($saleID)
		{
			// Delete a show entry
			$sql  = 'DELETE FROM '.$this->DBTables->Orders;
			$sql .= ' WHERE '.$this->DBTables->Orders.".saleID=$saleID";
		 
			$this->query($sql);
		}
		
		function GetTransactionFee()
		{
			return 0;
		}
		
		function AddSaleFromTrolley($saleID, $cartEntry, $saleExtras = array())
		{
			$this->AddSaleItem($saleID, $cartEntry->itemID, $cartEntry->qty, $cartEntry->price, $saleExtras);
		}

		function OutputViewTicketButton($saleID = 0)
		{
			$text = __('View EMail', $this->get_domain());
			echo $this->GetViewTicketLink($text, 'button-secondary', $saleID);
		}
		
		function GetViewTicketLink($text='', $class = '', $saleId = 0)
		{
		}
		
		function DBField($fieldName)
		{
			return $fieldName;
		}
    
	}
}

?>