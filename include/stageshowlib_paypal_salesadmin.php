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

require_once 'stageshowlib_salesadmin.php';      
require_once 'stageshowlib_admin.php';      

if (!class_exists('StageShowLibPayPalSalesAdminListClass')) 
{
	class StageShowLibPayPalSalesAdminListClass extends StageShowLibSalesAdminListClass // Define class
	{	
		function __construct($env, $editMode /* = false */) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);
			
			if (!$this->editMode)
			{
				$this->hiddenRowsButtonId = __('Details', $this->myDomain);		
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
					self::BULKACTION_DELETE => __('Delete', $this->myDomain),
					);
					
				$this->HeadersPosn = StageShowLibTableClass::HEADERPOSN_BOTH;
			}
			else
			{
				$this->HeadersPosn = StageShowLibTableClass::HEADERPOSN_TOP;
			}
		}
		
		function GetRecordID($result)
		{
			return $result->saleID;
		}
		
		function GetCurrentURL() 
		{			
			$currentURL = parent::GetCurrentURL();
			if (isset($this->env['SearchText']))
			{
				$currentURL .= '&lastsalessearch='.$this->env['SearchText'];
			}
			return $currentURL;
		}
		
		function DecodeSaleName($value, $result)
		{
			return $this->myDBaseObj->GetSaleName($result);
		}
		
		function GetMainRowsDefinition()
		{
			return array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Name',	            StageShowLibTableClass::TABLEPARAM_ID => 'saleLastName', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW, StageShowLibTableClass::TABLEPARAM_DECODE => 'DecodeSaleName', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Transaction Date', StageShowLibTableClass::TABLEPARAM_ID => 'saleDateTime', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Status',           StageShowLibTableClass::TABLEPARAM_ID => 'saleStatus',   StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW, ),
			);
		}		
		
		function GetStatusOptions()
		{
			return array(
				PAYPAL_APILIB_SALESTATUS_COMPLETED.'|'.__('Completed', $this->myDomain),
				);
		}		
		
		function GetDetailsRowsDefinition()
		{
			// FUNCTIONALITY: Sales - Use PAYPAL_APILIB_******* consts if defined
			$address = defined('PAYPAL_APILIB_STREET_LABEL')  ? PAYPAL_APILIB_STREET_LABEL  : __('Address', $this->myDomain);
			$city    = defined('PAYPAL_APILIB_CITY_LABEL')    ? PAYPAL_APILIB_CITY_LABEL    : __('Town/City', $this->myDomain);
			$state   = defined('PAYPAL_APILIB_STATE_LABEL')   ? PAYPAL_APILIB_STATE_LABEL   : __('County', $this->myDomain);
			$zip     = defined('PAYPAL_APILIB_ZIP_LABEL')     ? PAYPAL_APILIB_ZIP_LABEL     : __('Postcode', $this->myDomain);
			$country = defined('PAYPAL_APILIB_COUNTRY_LABEL') ? PAYPAL_APILIB_COUNTRY_LABEL : __('Country', $this->myDomain);
			$phone   = defined('PAYPAL_APILIB_PHONE_LABEL')   ? PAYPAL_APILIB_PHONE_LABEL   : __('Phone', $this->myDomain);
			
			$statusOptions = $this->GetStatusOptions();
			
			$ourOptions = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'First Name',	             StageShowLibTableClass::TABLEPARAM_ID => 'saleFirstName', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPSALENAME_TEXTLEN,      StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPSALENAME_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Last Name',	             StageShowLibTableClass::TABLEPARAM_ID => 'saleLastName',  StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPSALENAME_TEXTLEN,      StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPSALENAME_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'EMail',	                 StageShowLibTableClass::TABLEPARAM_ID => 'saleEMail',     StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPSALEEMAIL_TEXTLEN,     StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPSALEEMAIL_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $address,	                 StageShowLibTableClass::TABLEPARAM_ID => 'salePPStreet',  StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPSALEPPSTREET_TEXTLEN,  StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPSALEPPSTREET_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $city,	                     StageShowLibTableClass::TABLEPARAM_ID => 'salePPCity',    StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPSALEPPCITY_TEXTLEN,    StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPSALEPPCITY_EDITLEN, ),			
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $state,	                     StageShowLibTableClass::TABLEPARAM_ID => 'salePPState',   StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPSALEPPSTATE_TEXTLEN,   StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPSALEPPSTATE_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $zip,                        StageShowLibTableClass::TABLEPARAM_ID => 'salePPZip',     StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPSALEPPZIP_TEXTLEN,     StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPSALEPPZIP_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $country,                    StageShowLibTableClass::TABLEPARAM_ID => 'salePPCountry', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPSALEPPCOUNTRY_TEXTLEN, StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPSALEPPCOUNTRY_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $phone,                      StageShowLibTableClass::TABLEPARAM_ID => 'salePPPhone',   StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPSALEPPPHONE_TEXTLEN,   StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPSALEPPPHONE_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Total Paid/Due',            StageShowLibTableClass::TABLEPARAM_ID => 'salePaid',      StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'PayPal Fees',               StageShowLibTableClass::TABLEPARAM_ID => 'saleFee',       StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Transaction Date & Time',   StageShowLibTableClass::TABLEPARAM_ID => 'saleDateTime',  StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Transaction ID',            StageShowLibTableClass::TABLEPARAM_ID => 'saleTxnId',     StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW),						
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Status',                    StageShowLibTableClass::TABLEPARAM_ID => 'saleStatus',    StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_SELECT, StageShowLibTableClass::TABLEPARAM_ITEMS => $statusOptions),						
			);
			
			$ourOptions = self::MergeSettings(parent::GetDetailsRowsDefinition(), $ourOptions);
			return $ourOptions;
		}
		
		function GetDetailsRowsFooter()
		{
			$ourOptions = array(
				array(StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_FUNCTION, StageShowLibTableClass::TABLEPARAM_FUNC => 'ShowSaleDetails'),						
			);
			
			$ourOptions = self::MergeSettings(parent::GetDetailsRowsFooter(), $ourOptions);
			
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
		
		function ShowSaleDetails($result, $saleResults)
		{
			if ($this->editMode) 
			{
				return '';
			}
			
			$myDBaseObj = $this->myDBaseObj;
			return $this->BuildSaleDetails($saleResults);
		}
				
		function GetListDetails($result)
		{
			if (isset($this->pricesList)) 
				return $this->pricesList;
			
			return $this->myDBaseObj->GetPricesListWithSales($result->saleID);
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
		
		function OutputEditSale($editSaleEntry, $pricesList)
		{			
			$this->pricesList = $pricesList;
			$this->OutputList($editSaleEntry);
		}
		
	}
}

if (!class_exists('PayPalSalesDetailsAdminClass')) 
{
	class PayPalSalesDetailsAdminClass extends StageShowLibSalesAdminListClass // Define class
	{		
		function __construct($env, $editMode /* = false */) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);
			
			$this->SetRowsPerPage(self::STAGESHOWLIB_EVENTS_UNPAGED);
			
			$this->HeadersPosn = StageShowLibTableClass::HEADERPOSN_TOP;
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
			// FUNCTIONALITY: Sales - List Item, Type, Price and Quantity
			return array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Item',     StageShowLibTableClass::TABLEPARAM_ID => 'saleShowName', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Type',     StageShowLibTableClass::TABLEPARAM_ID => 'ticketType',   StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Price',    StageShowLibTableClass::TABLEPARAM_ID => 'price',        StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW, ),						
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Quantity', StageShowLibTableClass::TABLEPARAM_ID => 'quantity',     StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, ),						
			);
		}		
				
	}
}

if (!class_exists('PayPalSalesAdminClass')) 
{
	class PayPalSalesAdminClass extends StageShowLibAdminClass // Define class
	{		
		var $detailsSaleId;
		var $results;
		var $payPalAPIObj;
		var $saleQtyInputID;
		
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Sales Log';
			
			// TODO - Check this .....
			if (isset($env['saleQtyInputID'])) 
				$this->saleQtyInputID = $env['saleQtyInputID'];
			else
				$this->saleQtyInputID = 'editSaleQty';

			// Call base constructor
			parent::__construct($env);
		}
		
		function DoSalesSearch()
		{
			if (isset($_POST['searchsalesbutton']) && ($_POST['searchsalesbutton'] != ''))
			{
				$this->searchsalestext = $_POST['searchsalestext'];
			}
			else if (isset($_GET['lastsalessearch']))
			{
				$this->searchsalestext = $_GET['lastsalessearch'];
			}
			else
			{
				return;
			}
			
			// Search sales records
			$this->CheckAdminReferer();
				
			if (isset($this->searchsalestext))
			{
				$this->results = $this->myDBaseObj->SearchSalesList($this->searchsalestext);
				$this->env['SearchText'] = $this->searchsalestext;
			}
			
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;				
			$payPalObj = $myDBaseObj->payPalAPIObj;
				
 			$this->payPalAPIObj = $payPalObj;
     
			$this->detailsSaleId = 0;
			$this->salesFor = '';
			
			if (isset($_POST['addsalebutton']))
			{
				// Add a new sale
				$this->CheckAdminReferer();
				
				$this->pricesList = $this->myDBaseObj->GetPricesListWithSales(0);
				if (count($this->pricesList) == 0) break;
				$this->editSaleEntry[0] = $this->pricesList[0];

				$this->editingRecord = true;
			}
			
			$this->DoSalesSearch();
			
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

			$this->invalidInputId = '';
			if (isset($_POST['savechanges']))
			{
				// TODO-IMPROVEMENT - Adding Manual Sale - Check for address errors 				
				/*
					Create arrays of quantities
					
					new***** arrays are the new quantities requested by the user
					curr**** arrays are the quantities currently in the database
					
					****stockQtys are the quantities for a particular stock item (i.e. a hosted button)
					****priceQtys are the quantities for a particular stock option
				*/
				
				if ($this->myDBaseObj->getDbgOption('Dev_ShowMiscDebug')) StageShowLibUtilsClass::print_r($_POST, '_POST');
				$this->editingRecord = true;
				if (isset($_POST['saleID'])) $this->saleId = $_POST['saleID'];

				// Get requested quantities for each price option
				$req = $this->GetRequestedSaleQtys();
				$invalidInputId = $req->errorId;
				
				// Lock database before sale commit
				$myDBaseObj->LockSalesTable();
				
				// Get current qunatities from database
				$curr = $this->GetCurrentSaleQtys();
				
				// Check Stock Quantities
				if ($invalidInputId == '')
				{
					$invalidInputId = $this->CheckStockQtys($curr, $req);
				}
					
				$saleId = isset($this->saleId) ? $this->saleId : 0;
				if ($invalidInputId == '')
				{
					$invalidInputId = $this->AddOrEditSale($curr, $req);	// Note: Updates $this->saleId on new sale			
					
					$this->pricesList = $this->myDBaseObj->GetPricesListWithSales($this->saleId);
					$this->editSaleEntry = $myDBaseObj->GetSaleBuyer($this->saleId);	// Get list of items for a single sale
					
					echo '<div id="message" class="updated"><p>'.__('Sale Details have been saved', $this->myDomain).'</p></div>';
				}
				else
				{
					// Show form to user ... with their values
					$this->pricesList = $this->GetEditSaleFormEntries($saleId);
					$this->editSaleEntry[0] = $this->pricesList[0];
				}
				
				$myDBaseObj->UnlockTables();
				
			}
			
			$this->pageTitle = $this->salesFor . 'Sales Log';			
		}
		
		function Output_MainPage($updateFailed)
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;				
			$payPalObj = $myDBaseObj->payPalAPIObj;
			
			$myDBaseObj->PurgePendingSales();
					
			if (!$this->editingRecord)
			{
				if (!isset($this->results))	
					$this->results = $myDBaseObj->GetAllSalesList();		// Get list of sales (one row per sale)
			}
			
			// HTML Output - Start 
			$formClass = $this->myDomain.'-admin-form '.$this->myDomain.'-sales-summary';
			echo '<div class="'.$formClass.'">'."\n";
?>
	<form method="post">
<?php

			if (!$this->editingRecord)
			{
				echo '<h3>'; 
				if ( isset($this->searchsalestext) )
					_e('Search Results', $this->myDomain); 
				else
					_e('Summary', $this->myDomain); 
				echo "</h3>"; 
				$this->OuputSearchSalesButton();
			}
			else if (!isset($this->saleId))
			{
				echo "<h3>".__('Add Sale', $this->myDomain)."</h3>"; 
			}
						
			if (isset($this->saleId))
				echo "\n".'<input type="hidden" name="saleID" value="'.$this->saleId.'"/>'."\n";
				
			$this->WPNonceField();
				 
			if ($this->editingRecord)
			{
				$this->editSaleEntry[0]->totalQty = '';	
				
				$classId = $myPluginObj->adminClassPrefix.'SalesAdminListClass';
				$salesList = new $classId($this->env, StageShowLibAdminListClass::EDITMODE);	// xxxxxxxxSalesAdminListClass etc.
				$salesList->errorInputId = $this->invalidInputId; // TODO-IMPROVEMENT Highlight error line ...
				
				if ($this->myDBaseObj->getDbgOption('Dev_ShowMiscDebug')) StageShowLibUtilsClass::print_r($this->editSaleEntry, 'Call OutputList-editSaleEntry');
				$salesList->OutputEditSale($this->editSaleEntry, $this->pricesList);
			}
			else if(count($this->results) == 0)
			{
				echo "<div class='noconfig'>".__('No Sales', $this->myDomain)."</div>\n";
			}
			else 
			{
				$this->OutputSalesList($this->env);
			}

			if ($this->editingRecord)
		      echo '
					<br><input class="button-primary" type="submit" name="savechanges" value="'.__('Save Sale', $this->myDomain).'">
					';
			else if ($this->detailsSaleId <= 0)
			{
				$pricesList = $myDBaseObj->GetPricesList(null);
				if (count($pricesList) > 0)
				{
					$this->OuputAddSaleButton();
				}			
				else
					echo $this->NoStockMessage();
			}
			else
			{
					echo StageShowLibAdminClass::ActionButtonHTML('Back to Sales Summary', $this->caller, $this->myDomain, 'tablenav_bottom_actions'); 
					echo StageShowLibAdminClass::ActionButtonHTML('Send Confirmation EMail', $this->caller, $this->myDomain, 'tablenav_bottom_actions'); 
			}

?>
	<br></br>
	</form>
	</div>
<?php
		} // End of function Output_MainPage()
		
		function NoStockMessage()
		{
			return 'No Stock';
		}
		
		function OuputSearchSalesButton()
		{
			echo '<div class="'.$this->myDomain.'-searchsales"><input type="text" maxlength="'.PAYPAL_APILIB_PPSALEEMAIL_TEXTLEN.'" size="20" name="searchsalestext" id="searchsaletext" value="" autocomplete="off" />'."\n";
			$this->OutputButton("searchsalesbutton", __("Search Sales", $this->myDomain));					
			echo '</div>'."\n";
		}
		
		function OuputAddSaleButton()
		{
			$this->OutputButton("addsalebutton", __("Add Sale", $this->myDomain));
		}
		
		function DoActions()
		{
			$rtnVal = false;

			switch ($_GET['action'])
			{
				default:
					break;					
			}
				
			return $rtnVal;
		}
		
		function GetItemID($pricesEntry)
		{
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'GetItemID');
		}
				
		function GetItemPrice($pricesEntry)
		{
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'GetItemPrice');
		}
		
		function GetItemDesc($pricesEntry)
		{
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'GetItemDesc');
		}
		
		function GetStockID($pricesEntry)
		{
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'GetStockID');
		}
		
		function GetSaleQty($ticketsEntry)
		{
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'GetSaleQty');
		}
		
		function SetSaleQty(&$ticketsEntry, $qty)
		{
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'SetSaleQty');
		}
		
		function GetStockInventory($stockID)
		{
			StageShowLibUtilsClass::UndefinedFuncCallError($this, 'GetStockInventory');
		}
		
		function GetCurrentSaleQtys()
		{
			$curr = new stdClass();
			
			$curr->stockQtys = array();	// Unedited Quantity of item that is quantity limited  
			$curr->priceQtys = array();	// Unedited Quantity of item at a certain price
				
			if (isset($_POST['saleID']))
			{
				$reqSaleID = $_POST['saleID'];
					
				$currSaleResults = $this->myDBaseObj->GetSale($reqSaleID);

				foreach($currSaleResults as $currSale)
				{
					// TODO - Calculate quantities for each stock item and price option
					$itemID = $this->GetItemID($currSale);
					$qty = $this->GetSaleQty($currSale);
					$curr->priceQtys[$itemID] = $qty;		// The current sale quantity for an item
					
					$stockID = $this->GetStockID($currSale);						
					$curr->stockQtys[$stockID] = isset($curr->stockQtys[$stockID]) ? $curr->stockQtys[$stockID] + $qty : $qty;
				}
				
				$curr->saleId = $reqSaleID;
			}
			else
			{
				$currSaleResults = array();
			}
						
			if ($this->myDBaseObj->getDbgOption('Dev_ShowMiscDebug')) StageShowLibUtilsClass::print_r($curr->priceQtys, 'currpriceQtys');
			if ($this->myDBaseObj->getDbgOption('Dev_ShowMiscDebug')) StageShowLibUtilsClass::print_r($curr->stockQtys, 'currStockQtys');				
			
			return $curr;
		}

		function GetRequestedSaleQtys()				
		{
			$req = new stdClass();
			
			$newPrice = 0;

			$req->errorId = '';
			$req->stockQtys = array();	// Edited Quantity of item that is quantity limited  
			$req->priceQtys = array();	// Edited Quantity of item at a certain price

			$this->results = $this->myDBaseObj->GetPricesList(null);
			foreach($this->results as $key => $pricesEntry)
			{
				$itemID = $this->GetItemID($pricesEntry);
				$stockID = $this->GetStockID($pricesEntry);
					
				$inputID = $this->saleQtyInputID.$itemID;
					
				if (isset($_POST[$inputID]))
				{
					$qty = $_POST[$inputID];
					
					if ($qty === '') $qty = 0;
					
					$this->SetSaleQty($this->results[$key], $qty);	// Set requested quantity for display
						
					if ($req->errorId != '')
						continue;
						
					if (!is_numeric($qty))
					{
						echo '<div id="message" class="error"><p>'.__('INVALID Quantity (Non-numeric)', $this->myDomain).' - '.$this->GetItemDesc($pricesEntry).'</p></div>';
						$req->errorId = $inputID;
						continue;
					}
					else
					{
						$qty = intval($qty);
						if ($qty >= 0)
						{
							$req->priceQtys[$itemID] = $qty;
						}
						else
						{
							echo '<div id="message" class="error"><p>'.__('INVALID Quantity (Negative)', $this->myDomain).' - '.$this->GetItemDesc($pricesEntry).'</p></div>';
							$req->errorId = $inputID;
							continue;
						}
					}
				}
					
				// Calculate total sale price
				$newPrice += intval($_POST[$inputID]) * $this->GetItemPrice($pricesEntry);
					
				// Calculate quantities for each performance
				$req->stockQtys[$stockID] = isset($req->stockQtys[$stockID]) ? $req->stockQtys[$stockID] + $qty : $qty;
			}
			
			if ($this->myDBaseObj->getDbgOption('Dev_ShowMiscDebug')) 
			{
				StageShowLibUtilsClass::print_r($req->priceQtys, 'this->priceQtys');
				StageShowLibUtilsClass::print_r($req->stockQtys, 'this->stockQtys');
			}
			
			$this->salePaid = $newPrice;
			
			return $req;
		}
		
		function CheckStockQtys($curr, $req)
		{
			$errorId = '';
			$totalQty = 0;
			
			foreach ($req->stockQtys as $stockID => $qty)
			{
				$totalQty += $qty;

				// Calculate the extra sales for each Stock item
				if (isset($curr->stockQtys[$stockID])) 
				{
					$qty -= $curr->stockQtys[$stockID];						
				}
				if ($qty > 0)
				{
					$stockQty = $this->GetStockInventory($stockID);
					
					if ($stockQty < $qty)
					{
						$errorItem = '';
						$errorId = -1;		// Out of Stock could refer to more than one price line
						foreach($this->results as $pricesEntry)
						{
							if ($this->GetStockID($pricesEntry) === $stockID)
							{
								$errorItem = ' - '.$this->GetItemDesc($pricesEntry, false);
								break;
							}
						}
						echo '<div id="message" class="error"><p>'.__('Out of Stock', $this->myDomain).$errorItem.'</p></div>';
						break;
					}
				}
			}
			
			if (($errorId == '') && ($totalQty ==0))
			{
				echo '<div id="message" class="error"><p>'.__('Total Quantity CANNOT be zero', $this->myDomain).'</p></div>';
				$errorId = -1;
			}
						
			return $errorId;
		}
			
		function AddOrEditSale($curr, $req, $salesVals = array())
		{	
			$myDBaseObj = $this->myDBaseObj;
			
			$errorId = '';
			$saleId = isset($this->saleId) ? $this->saleId : 0;
			
			$salesVals['saleFirstName'] = $this->GetFormInput($saleId, 'saleFirstName');
			$salesVals['saleLastName'] = $this->GetFormInput($saleId, 'saleLastName');
			$salesVals['saleEMail'] = $this->GetFormInput($saleId, 'saleEMail');
			$salesVals['salePPStreet'] = $this->GetFormInput($saleId, 'salePPStreet');
			$salesVals['salePPCity'] = $this->GetFormInput($saleId, 'salePPCity');
			$salesVals['salePPState'] = $this->GetFormInput($saleId, 'salePPState');
			$salesVals['salePPZip'] = $this->GetFormInput($saleId, 'salePPZip');
			$salesVals['salePPCountry'] = $this->GetFormInput($saleId, 'salePPCountry');
			$salesVals['salePPPhone'] = $this->GetFormInput($saleId, 'salePPPhone');
			
			$salesVals['salePaid'] = $this->salePaid;
				
			$salesVals['saleStatus'] = $this->GetFormInput($saleId, 'saleStatus');
				
			if ($saleId == 0)
			{
				// Add Transaction Number (from timestamp)
				$salesVals['saleTxnid'] = 'MAN-'.time();	
				
				// TODO - Manual Sale - Fee is zero .... Is this OK?
				$salesVals['saleFee'] = 0;
				$salesVals['saleStatus'] = PAYPAL_APILIB_SALESTATUS_COMPLETED;
				
				$saleId = $myDBaseObj->AddSale(current_time('mysql'), $salesVals);				
			}
			else
			{
				// Save edited sale details
				$this->myDBaseObj->EditSale($saleId, $salesVals);
			}
			
			/* Process Qty Update
				newQty > 0 and origQty = 0 ........... AddSaleItem
				newQty = 0 and origQty > 0 ........... DeleteSaleItem
				newQty <> origQty .................... UpdateSaleItem
			*/
			
			$this->saleId = $saleId;
		
			// Loop through results from GetRequestedSaleQtys() call
			foreach($this->results as $pricesEntry)
			{
				$itemID = $this->GetItemID($pricesEntry);						
				$newQty = isset($req->priceQtys[$itemID]) ? $req->priceQtys[$itemID] : 0;
				$origQty = isset($curr->priceQtys[$itemID]) ? $curr->priceQtys[$itemID] : 0;
						
				if (($newQty > 0) && ($origQty == 0))
				{
					if ($this->myDBaseObj->getDbgOption('Dev_ShowMiscDebug')) echo "ADDING Sale Item: ItemID=$itemID  Curr=$origQty  New=$newQty  <br>\n";
					// TODO Add Edit of Ticket Sale Price
					$myDBaseObj->AddSaleItem($this->saleId, $itemID, $newQty, $this->GetItemPrice($pricesEntry));
				}
				else if (($newQty == 0) && ($origQty > 0))
				{
					if ($this->myDBaseObj->getDbgOption('Dev_ShowMiscDebug')) echo "DELETE Sale Item: ItemID=$itemID  Curr=$origQty  New=$newQty  <br>\n";
					$myDBaseObj->DeleteSaleItem($this->saleId, $itemID);
				}
				else if ($newQty != $origQty)
				{
					if ($this->myDBaseObj->getDbgOption('Dev_ShowMiscDebug')) echo "UPDATE Sale Item: ItemID=$itemID  Curr=$origQty  New=$newQty  <br>\n";
					$myDBaseObj->UpdateSaleItem($this->saleId, $itemID, $newQty, $this->GetItemPrice($pricesEntry));
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
			// FUNCTIONALITY: Sales - Restore form values when save edit fails 
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
				case StageShowLibAdminListClass::BULKACTION_DELETE:		
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
				case StageShowLibAdminListClass::BULKACTION_DELETE:		
					if ($actionCount > 0)		
						$actionMsg = ($actionCount == 1) ? __("1 Sale has been deleted", $this->myDomain) : $actionCount.' '.__("Sales have been deleted", $this->myDomain); 
					else
						$actionMsg =  __("Nothing to Delete", $this->myDomain);
					break;
			}
			
			return $actionMsg;
		}
		
		function OutputSalesList($env)
		{
			$myPluginObj = $this->myPluginObj;
			
			$classId = $myPluginObj->adminClassPrefix.'SalesAdminListClass';
			$salesList = new $classId($env);	// xxxxxxxxxxxxxSalesAdminListClass etc.
			$salesList->OutputList($this->results);		
		}
				
	}
} 
		 
?>