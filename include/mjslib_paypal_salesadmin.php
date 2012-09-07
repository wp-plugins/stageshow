<?php
/* 
Description: Code for Sales Admin Page
 
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
include 'mjslib_table.php';      

if (!class_exists('PayPalSalesAdminListClass')) 
{
	class PayPalSalesAdminListClass extends MJSLibAdminListClass // Define class
	{	
		function __construct($env, $editMode /* = false */) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);
			
			if (!$this->editMode)
			{
				$this->hiddenRowsButtonId = 'Details';
			}
			else
			{
				$this->hiddenRowStyle = '';
				$this->hiddenRowsButtonId = '';
				$this->moreText = '';
			}			
			
			if (!$editMode)
			{
				$this->bulkActions = array(
					'delete' => __('Delete', $this->pluginName),
					);
					
				$this->HeadersPosn = MJSLibTableClass::HEADERPOSN_BOTH;
			}
			else
			{
				$this->HeadersPosn = MJSLibTableClass::HEADERPOSN_TOP;
			}
		}
		
		function GetRecordID($result)
		{
			return $result->saleID;
		}
		
		function GetMainRowsDefinition()
		{
			return array(
				array('Label' => 'Name',	            'Id' => 'saleName',     'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),
				array('Label' => 'Transaction Date',  'Id' => 'saleDateTime',	'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),
				array('Label' => 'Status',            'Id' => 'saleStatus',   'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),						
			);
		}		
		
		function GetDetailsRowsDefinition()
		{
			$ourOptions = array(
				array('Label' => 'Name',	                     'Id' => 'saleName',      'Type' => MJSLibTableClass::TABLEENTRY_TEXT, 'Len' => PAYPAL_APILIB_PPSALENAME_TEXTLEN,      'Size' => PAYPAL_APILIB_PPSALENAME_EDITLEN, ),
				array('Label' => 'EMail',	                     'Id' => 'saleEMail',     'Type' => MJSLibTableClass::TABLEENTRY_TEXT, 'Len' => PAYPAL_APILIB_PPSALEEMAIL_TEXTLEN,     'Size' => PAYPAL_APILIB_PPSALEEMAIL_EDITLEN, ),
				array('Label' => 'PayPal Username',	           'Id' => 'salePPName',    'Type' => MJSLibTableClass::TABLEENTRY_VIEW),
				array('Label' => PAYPAL_APILIB_STREET_LABEL,	 'Id' => 'salePPStreet',  'Type' => MJSLibTableClass::TABLEENTRY_TEXT, 'Len' => PAYPAL_APILIB_PPSALEPPSTREET_TEXTLEN,  'Size' => PAYPAL_APILIB_PPSALEPPSTREET_EDITLEN, ),
				array('Label' => PAYPAL_APILIB_CITY_LABEL,	   'Id' => 'salePPCity',    'Type' => MJSLibTableClass::TABLEENTRY_TEXT, 'Len' => PAYPAL_APILIB_PPSALEPPCITY_TEXTLEN,    'Size' => PAYPAL_APILIB_PPSALEPPCITY_EDITLEN, ),			
				array('Label' => PAYPAL_APILIB_STATE_LABEL,	   'Id' => 'salePPState',   'Type' => MJSLibTableClass::TABLEENTRY_TEXT, 'Len' => PAYPAL_APILIB_PPSALEPPSTATE_TEXTLEN,   'Size' => PAYPAL_APILIB_PPSALEPPSTATE_EDITLEN, ),
				array('Label' => PAYPAL_APILIB_ZIP_LABEL,	     'Id' => 'salePPZip',     'Type' => MJSLibTableClass::TABLEENTRY_TEXT, 'Len' => PAYPAL_APILIB_PPSALEPPZIP_TEXTLEN,     'Size' => PAYPAL_APILIB_PPSALEPPZIP_EDITLEN, ),
				array('Label' => PAYPAL_APILIB_COUNTRY_LABEL,	 'Id' => 'salePPCountry', 'Type' => MJSLibTableClass::TABLEENTRY_TEXT, 'Len' => PAYPAL_APILIB_PPSALEPPCOUNTRY_TEXTLEN, 'Size' => PAYPAL_APILIB_PPSALEPPCOUNTRY_EDITLEN, ),
				array('Label' => 'Paid',                       'Id' => 'salePaid',      'Type' => MJSLibTableClass::TABLEENTRY_VIEW),
				array('Label' => 'Transaction Date/Time',      'Id' => 'saleDateTime',  'Type' => MJSLibTableClass::TABLEENTRY_VIEW),
				array('Label' => 'Transaction ID',             'Id' => 'saleTxnId',     'Type' => MJSLibTableClass::TABLEENTRY_VIEW),						
				array('Label' => 'Status',                     'Id' => 'saleStatus',    'Type' => MJSLibTableClass::TABLEENTRY_VIEW),						
			);
			
			$ourOptions = array_merge(parent::GetDetailsRowsDefinition(), $ourOptions);
			return $ourOptions;
		}
		
		function GetDetailsRowsFooter()
		{
			$ourOptions = array(
				array('Type' => MJSLibTableClass::TABLEENTRY_FUNCTION, 'Func' => 'ShowSaleDetails'),						
			);
			
			$ourOptions = array_merge(parent::GetDetailsRowsFooter(), $ourOptions);
			
			return $ourOptions;
		}
		
		function GetTableID($result)
		{
			return "paypal-sales-list-tab";
		}
		
		function CreateSalesAdminDetailsListObject($env, $editMode /* = false */)
		{
			return new PayPalSalesDetailsAdminClass($env, $editMode);	
		}
		
		function ShowSaleDetails($result)
		{
			if ($this->editMode) return '';
			
			$myDBaseObj = $this->myDBaseObj;
			$saleResults = $myDBaseObj->GetSale($result->saleID);
			return $this->BuildSaleDetails($saleResults);
		}
				
		function BuildSaleDetails($saleResults)
		{
			$env = $this->env;
			$salesList = $this->CreateSalesAdminDetailsListObject($env, $this->editMode);	
			
			// Set Rows per page to disable paging used on main page
			$salesList->enableFilter = false;
			
			ob_start();	
			$salesList->OutputList($saleResults);	
			$saleDetailsOoutput = ob_get_contents();
			ob_end_clean();

			return $saleDetailsOoutput;
		}
		
	}
}

if (!class_exists('PayPalSalesDetailsAdminClass')) 
{
	class PayPalSalesDetailsAdminClass extends MJSLibAdminListClass // Define class
	{		
		function __construct($env, $editMode /* = false */) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);
			
			$this->HeadersPosn = MJSLibTableClass::HEADERPOSN_TOP;
		}
			
		function GetTableID($result)
		{
			return "paypal-sale-details-tab";
		}
		
		function GetRecordID($result)
		{
			return $result->saleID;
		}
		
		function GetMainRowsDefinition()
		{
			return array(
				array('Label' => 'Item',     'Id' => 'saleShowName', 'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),
				array('Label' => 'Type',     'Id' => 'ticketType',   'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),
				array('Label' => 'Price',    'Id' => 'price',        'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),						
				array('Label' => 'Quantity', 'Id' => 'quantity',     'Type' => MJSLibTableClass::TABLEENTRY_TEXT, ),						
			);
		}		
				
	}
}

if (!class_exists('PayPalSalesAdminClass')) 
{
	class PayPalSalesAdminClass extends MJSLibAdminClass // Define class
	{		
		var $detailsSaleId;
		var $results;
		var $payPalAPIObj;
		var $saleQtyInputID;
		
		function __construct($env, $myPluginObj = null, $myDBaseObj = null, $payPalObj = null) //constructor	
		{
			// Call base constructor
			parent::__construct($env);
			
			if (isset($env['saleQtyInputID'])) 
				$this->saleQtyInputID = $env['saleQtyInputID'];
			else
				$this->saleQtyInputID = 'editSaleQty';

			if (!is_array($env))
			{
				$this->myPluginObj = $myPluginObj;
				$this->myDBaseObj = $myDBaseObj;
 				$this->payPalAPIObj = $payPalObj;
			}
			else
			{
				$myPluginObj = $this->myPluginObj;
				$myDBaseObj = $this->myDBaseObj;				
				$payPalObj = $myDBaseObj->payPalAPIObj;
				
 				$this->payPalAPIObj = $payPalObj;
			}
     
			$this->detailsSaleId = 0;
			$this->salesFor = '';
			
			if (isset($_POST['emailsale']))
			{
				$this->CheckAdminReferer();
				
				$this->emailSaleId = $_POST['id'];
				$myDBaseObj->EMailSale($this->emailSaleId);
			}
			
			if (isset($_GET['action']))
			{
				$this->CheckAdminReferer();
				$this->DoActions();
			}

			$invalidInputId = '';
			if (isset($_POST['savesale']))
			{
				// TODO-IMPROVEMENT - Adding Manual Sale - Check for address errors 				
				/*
					Create arrays of quantities
					
					new***** arrays are the new quantities requested by the user
					curr**** arrays are the quantities currently in the database
					
					****StockQtys are the quantities for a particular stock item (i.e. a hosted button)
					****PriceQtys are the quantities for a particular stock option
				*/
				
				if ($this->myDBaseObj->getOption('Dev_ShowMiscDebug')) MJSLibUtilsClass::print_r($_POST, '_POST');
				$this->editingRecord = true;
				if (isset($_POST['saleID'])) $this->saleId = $_POST['saleID'];

				// Get current qunatities from database
				$this->GetCurrentSaleQtys();
				
				// Get requested quantities for each price option
				$invalidInputId = $this->GetRequestedSaleQtys();
					
				// Check Stock Quantities
				if ($invalidInputId == '')
					$invalidInputId = $this->CheckStockQtys();
					
				$saleId = isset($this->saleId) ? $this->saleId : 0;
				if ($invalidInputId == '')
				{
					$invalidInputId = $this->AddOrEditSale();	// Note: Updates $this->saleId on new sale			
					
					$this->pricesList = $this->myDBaseObj->GetPricesListWithSales($this->saleId);
					$this->editSaleEntry = $myDBaseObj->GetSaleBuyer($this->saleId);	// Get list of items for a single sale
					
					echo '<div id="message" class="updated"><p>'.__('Sale Details have been saved', $this->pluginName).'</p></div>';
				}
				else
				{
					// Show form to user ... with their values
					$this->pricesList = $this->GetEditSaleFormEntries($saleId);
					$this->editSaleEntry[0] = $this->pricesList[0];
				}
			}
					
			if (!$this->editingRecord)
			{
				if (!isset($this->results))	
					$this->results = $myDBaseObj->GetAllSalesList();		// Get list of sales (one row per sale)
			}
			
			echo '<div class="wrap">';

			// HTML Output - Start 
?>
		<div class="wrap">
				<div id="icon-<?php echo $this->pluginName; ?>" class="icon32"></div>
			<h2>
				<?php echo $myPluginObj->pluginName.' '.$this->salesFor.' - '.__('Sales Log', $this->pluginName); ?>
			</h2>
				<form method="post" action="admin.php?page=<?php echo $this->pluginName; ?>_sales">
					<h3>
						<?php 					
			if (!$this->editingRecord)
				_e('Summary', $this->pluginName); 
			else if (!isset($this->saleId))
				_e('Add Sale', $this->pluginName); 
?>
					</h3>
					<?php
			if (isset($this->saleId))
				echo "\n".'<input type="hidden" name="saleID" value="'.$this->saleId.'"/>'."\n";
				
			$this->WPNonceField();
				 
			if ($this->editingRecord)
			{
				$this->editSaleEntry[0]->totalQty = '';	
				
				$classId = $env['PluginObj']->adminClassPrefix.'SalesAdminListClass';
				$salesList = new $classId($env, MJSLibAdminListClass::EDITMODE);	// StageShowSalesAdminListClass etc.
				$salesList->errorInputId = $invalidInputId; // TODO-IMPROVEMENT Highlight error line ...
				
				if ($this->myDBaseObj->getOption('Dev_ShowMiscDebug')) MJSLibUtilsClass::print_r($this->editSaleEntry, 'Call OutputList-editSaleEntry');
				$salesList->OutputList($this->editSaleEntry, $this->pricesList);
			}
			else if(count($this->results) == 0)
			{
				echo "<div class='noconfig'>".__('NO Sales', $this->pluginName)."</div>\n";
			}
			else 
			{
				$this->OutputSalesList($env);
			}

	if ($this->editingRecord)
      echo '
			<br><input class="button-primary" type="submit" name="savesale" value="'.__('Save Sale', $this->pluginName).'">
			';
	else if ($this->detailsSaleId <= 0)
	{
		$pricesList = $myDBaseObj->GetPricesList(null);
		if (count($pricesList) > 0)
			echo MJSLibAdminClass::AddActionButton($this->caller, __('Add Sale', $this->pluginName), 'tablenav_bottom_actions'); 
		else
			echo $this->NoStockMessage();
	}
	else
	{
			echo MJSLibAdminClass::AddActionButton($this->caller, __('Back to Sales Summary', $this->pluginName), 'tablenav_bottom_actions'); 
			echo MJSLibAdminClass::AddActionButton($this->caller, __('Send Confirmation EMail', $this->pluginName), 'tablenav_bottom_actions'); 
	}
	echo "<br></br>\n";
?>
				</form>
		</div>

		<?php
        // HTML Output - End
		}	
		
		function NoStockMessage()
		{
			return 'NO Stock';
		}
		
		function DoActions()
		{
			$rtnVal = false;

			switch ($_GET['action'])
			{
				case 'addsale':
					$this->CheckAdminReferer();
					
					$this->pricesList = $this->myDBaseObj->GetPricesListWithSales(0);
					if (count($this->pricesList) == 0) break;
					$this->editSaleEntry[0] = $this->pricesList[0];

					$this->editingRecord = true;
					break;
					
				default:
					break;					
			}
				
			return $rtnVal;
		}
		
		function GetItemID($pricesEntry)
		{
			MJSLibUtilsClass::UndefinedFuncCallError($this, 'GetItemID');
		}
				
		function GetItemPrice($pricesEntry)
		{
			MJSLibUtilsClass::UndefinedFuncCallError($this, 'GetItemPrice');
		}
		
		function GetItemDesc($pricesEntry)
		{
			MJSLibUtilsClass::UndefinedFuncCallError($this, 'GetItemDesc');
		}
		
		function GetButtonID($pricesEntry)
		{
			MJSLibUtilsClass::UndefinedFuncCallError($this, 'GetButtonID');
		}
		
		function GetSaleQty($ticketsEntry)
		{
			MJSLibUtilsClass::UndefinedFuncCallError($this, 'GetSaleQty');
		}
		
		function SetSaleQty(&$ticketsEntry, $qty)
		{
			MJSLibUtilsClass::UndefinedFuncCallError($this, 'SetSaleQty');
		}
		
		function GetCurrentSaleQtys()
		{
			$this->currStockQtys = array();
			$this->currPriceQtys = array();
				
			if (isset($_POST['saleID']))
			{
				$reqSaleID = $_POST['saleID'];
					
				$currSaleResults = $this->myDBaseObj->GetSale($reqSaleID);

				foreach($currSaleResults as $currSale)
				{
					// Calculate quantities for each stock item and price option
					$itemID = $this->GetItemID($currSale);
					$buttonID = $this->GetButtonID($currSale);						
					$qty = $this->GetSaleQty($currSale);
					
					$this->currStockQtys[$buttonID] = isset($this->currStockQtys[$buttonID]) ? $this->currStockQtys[$buttonID] + $qty : $qty;
					$this->currPriceQtys[$itemID] = $qty;
				}
				
				$this->saleId = $reqSaleID;
			}
			else
			{
				$currSaleResults = array();
			}
						
			if ($this->myDBaseObj->getOption('Dev_ShowMiscDebug')) MJSLibUtilsClass::print_r($this->currPriceQtys, 'currPriceQtys');
			if ($this->myDBaseObj->getOption('Dev_ShowMiscDebug')) MJSLibUtilsClass::print_r($this->currStockQtys, 'this->currStockQtys');				
		}

		function GetRequestedSaleQtys()				
		{
			$errorId = '';
			$newPrice = 0;

			$this->newPriceQtys = array();
			$this->newStockQtys = array();

			$this->results = $this->myDBaseObj->GetPricesList(null);
			foreach($this->results as $key => $pricesEntry)
			{
				$itemID = $this->GetItemID($pricesEntry);
				$buttonID = $this->GetButtonID($pricesEntry);
					
				$inputID = $this->saleQtyInputID.$itemID;
					
				if (isset($_POST[$inputID]))
				{
					$qty = $_POST[$inputID];
					
					if ($qty === '') $qty = 0;
					
					$this->SetSaleQty($this->results[$key], $qty);	// Set requested quantity for display
						
					if ($errorId != '')
						continue;
						
					if (!is_numeric($qty))
					{
						echo '<div id="message" class="error"><p>'.__('INVALID Quantity (Non-numeric)', $this->pluginName).' - '.$this->GetItemDesc($pricesEntry).'</p></div>';
						$errorId = $inputID;
						continue;
					}
					else
					{
						$qty = intval($qty);
						if ($qty >= 0)
						{
							$this->newPriceQtys[$itemID] = $qty;
						}
						else
						{
							echo '<div id="message" class="error"><p>'.__('INVALID Quantity (Negative)', $this->pluginName).' - '.$this->GetItemDesc($pricesEntry).'</p></div>';
							$errorId = $inputID;
							continue;
						}
					}
				}
					
				// Calculate total sale price
				$newPrice += intval($_POST[$inputID]) * $this->GetItemPrice($pricesEntry);
					
				// Calculate quantities for each performance
				$this->newStockQtys[$buttonID] = isset($this->newStockQtys[$buttonID]) ? $this->newStockQtys[$buttonID] + $qty : $qty;
			}
			
			if ($this->myDBaseObj->getOption('Dev_ShowMiscDebug')) MJSLibUtilsClass::print_r($this->newPriceQtys, 'this->newPriceQtys');
			if ($this->myDBaseObj->getOption('Dev_ShowMiscDebug')) MJSLibUtilsClass::print_r($this->newStockQtys, 'this->newStockQtys');
			
			$this->salePrice = $newPrice;
			
			return $errorId;
		}
		
		function CheckStockQtys()
		{
			$errorId = '';
			$totalQty = 0;
			
			foreach ($this->newStockQtys as $buttonID => $qty)
			{
				$totalQty += $qty;

				if (isset($this->currStockQtys[$buttonID])) $qty -= $this->currStockQtys[$buttonID];						
				if ($qty > 0)
				{
					if ($this->payPalAPIObj->GetInventory($buttonID, $stockQty) === 'OK')
					{
						if ($stockQty < $qty)
						{
							$errorItem = '';
							$errorId = -1;		// Out of Stock could refer to more than one price line
							foreach($this->results as $pricesEntry)
							{
								if ($this->GetButtonID($pricesEntry) === $buttonID)
								{
									$errorItem = ' - '.$this->GetItemDesc($pricesEntry, false);
									break;
								}
							}
							echo '<div id="message" class="error"><p>'.__('Out of Stock', $this->pluginName).$errorItem.'</p></div>';
							break;
						}
					}
				}
			}
			
			if (($errorId == '') && ($totalQty ==0))
			{
				echo '<div id="message" class="error"><p>'.__('Total Quantity CANNOT be zero', $this->pluginName).'</p></div>';
				$errorId = -1;
			}
						
			return $errorId;
		}
				
		function AddOrEditSale()
		{	
			$myDBaseObj = $this->myDBaseObj;
			
			$errorId = '';
			$saleId = isset($this->saleId) ? $this->saleId : 0;
			
			$saleName = $this->GetFormInput($saleId, 'saleName');
			$saleEMail = $this->GetFormInput($saleId, 'saleEMail');
			$salePPStreet = $this->GetFormInput($saleId, 'salePPStreet');
			$salePPCity = $this->GetFormInput($saleId, 'salePPCity');
			$salePPState = $this->GetFormInput($saleId, 'salePPState');
			$salePPZip = $this->GetFormInput($saleId, 'salePPZip');
			$salePPCountry = $this->GetFormInput($saleId, 'salePPCountry');
			
			if (!isset($this->saleId))
			{
				// Add Transaction Number (from timestamp)
				$saleTxnid = 'MAN-'.time();	
				
				$this->saleId = $myDBaseObj->AddSale(date(MJSLibDBaseClass::MYSQL_DATETIME_FORMAT), $saleName, $saleEMail, $this->salePrice, $saleTxnid, 'Completed', $saleName, $salePPStreet, $salePPCity, $salePPState, $salePPZip, $salePPCountry);				
			}
			else
			{
				// Save edited sale details
				$this->myDBaseObj->EditSale($this->saleId, $saleName, $saleEMail, $this->salePrice, $salePPStreet, $salePPCity, $salePPState, $salePPZip, $salePPCountry);				
			}
					
			/* Process Qty Update
				newQty > 0 and origQty = 0 ........... AddSaleItem
				newQty = 0 and origQty > 0 ........... DeleteSaleItem
				newQty <> origQty .................... UpdateSaleItem
			*/
			foreach($this->results as $pricesEntry)
			{
				$itemID = $this->GetItemID($pricesEntry);						
				$newQty = isset($this->newPriceQtys[$itemID]) ? $this->newPriceQtys[$itemID] : 0;
				$origQty = isset($this->currPriceQtys[$itemID]) ? $this->currPriceQtys[$itemID] : 0;
						
				if (($newQty > 0) && ($origQty == 0))
				{
					if ($this->myDBaseObj->getOption('Dev_ShowMiscDebug')) echo "ADDING Sale Item: ItemID=$itemID  Curr=$origQty  New=$newQty  <br>\n";
					$myDBaseObj->AddSaleItem($this->saleId, $itemID, $newQty);
				}
				else if (($newQty == 0) && ($origQty > 0))
				{
					if ($this->myDBaseObj->getOption('Dev_ShowMiscDebug')) echo "DELETE Sale Item: ItemID=$itemID  Curr=$origQty  New=$newQty  <br>\n";
					$myDBaseObj->DeleteSaleItem($this->saleId, $itemID);
				}
				else if ($newQty != $origQty)
				{
					if ($this->myDBaseObj->getOption('Dev_ShowMiscDebug')) echo "UPDATE Sale Item: ItemID=$itemID  Curr=$origQty  New=$newQty  <br>\n";
					$myDBaseObj->UpdateSaleItem($this->saleId, $itemID, $newQty);
				}
			}
					
			$siteurl = get_option('siteurl');
			foreach ($this->newStockQtys as $buttonID => $qty)
			{
				if (isset($this->currStockQtys[$buttonID])) $qty -= $this->currStockQtys[$buttonID];						
				if ($qty == 0) continue;
						
				// Update Inventory for this stock item
				if ($this->payPalAPIObj->AdjustInventory($buttonID, 0-$qty) !== 'OK')
				{
					// TODO-PRIORITY Deal with paypal inventory error ??
				}
			}
					
			return $errorId;
		}
				
		function GetFormInput($saleId, $element, $default = "")
		{
			if ($saleId > 0) $element = $element.$saleId;
			return isset($_POST[$element]) ? stripslashes($_POST[$element]) : $default;
		}
		
		function GetEditSaleFormEntries($saleID)
		{
			// Get the prices with all quantites zero
			$pricesList = $this->myDBaseObj->GetPricesListWithSales(0);			

			if ($saleID > 0) 
			{
	 			$buyerDetails = $this->myDBaseObj->GetSaleBuyer($saleID);

				foreach ($buyerDetails[0] as $key => $default)
					$pricesList[0]->$key = $this->GetFormInput($saleID, $key, $default);
			}
			else
			{
				foreach ($pricesList[0] as $key => $default)
					$pricesList[0]->$key = $this->GetFormInput($saleID, $key, $default);
			}			

			return $pricesList;	
		}
		
		function DoBulkAction($bulkAction, $recordId)
		{
			switch ($bulkAction)
			{
				case 'delete':		
					$this->myDBaseObj->DeleteSale($recordId);
					return true;
			}
				
			return false;
		}
		
		function GetBulkActionMsg($bulkAction, $actionCount)
		{
			$actionMsg = '';
			
			switch ($bulkAction)
			{
				case 'delete':		
					if ($actionCount > 0)		
						$actionMsg = ($actionCount == 1) ? __("1 Sale has been deleted", $this->pluginName) : $actionCount.' '.__("Sales have been deleted", $this->pluginName); 
					else
						$actionMsg =  __("Nothing to Delete", $this->pluginName);
					break;
			}
			
			return $actionMsg;
		}
		
		function OutputSalesList($env)
		{
			$classId = $env['PluginObj']->adminClassPrefix.'SalesAdminListClass';
			$salesList = new $classId($env);	// StageShowSalesAdminListClass etc.
			$salesList->OutputList($this->results);		
		}
				
	}
} 
		 
?>