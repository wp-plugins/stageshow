<?php
/* 
Description: Code for Sales Admin Page
 
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

require_once 'stageshowlib_salesadmin.php';      
require_once 'stageshowlib_admin.php';      

if (!class_exists('StageShowLibGatewaySalesAdminListClass')) 
{
	class StageShowLibGatewaySalesAdminListClass extends StageShowLibSalesAdminListClass // Define class
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
				PAYMENT_API_SALESTATUS_COMPLETED.'|'.__('Completed', $this->myDomain),
				);
		}	
		
		function FormatSaleNote($saleNote)
		{
			return str_replace("\n", "<br>", $saleNote);
		}	
		
		function GetDetailsRowsDefinition()
		{
			// FUNCTIONALITY: Sales - Use Payment Gateway consts if defined
			$address = defined('PAYMENT_API_STREET_LABEL')  ? PAYMENT_API_STREET_LABEL  : __('Address', $this->myDomain);
			$city    = defined('PAYMENT_API_CITY_LABEL')    ? PAYMENT_API_CITY_LABEL    : __('Town/City', $this->myDomain);
			$state   = defined('PAYMENT_API_STATE_LABEL')   ? PAYMENT_API_STATE_LABEL   : __('County', $this->myDomain);
			$zip     = defined('PAYMENT_API_ZIP_LABEL')     ? PAYMENT_API_ZIP_LABEL     : __('Postcode', $this->myDomain);
			$country = defined('PAYMENT_API_COUNTRY_LABEL') ? PAYMENT_API_COUNTRY_LABEL : __('Country', $this->myDomain);
			$phone   = defined('PAYMENT_API_PHONE_LABEL')   ? PAYMENT_API_PHONE_LABEL   : __('Phone', $this->myDomain);
			
			$statusOptions = $this->GetStatusOptions();
			
			$gatewayName = $this->myDBaseObj->gatewayObj->GetName();
			$ourOptions = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'First Name',	             StageShowLibTableClass::TABLEPARAM_ID => 'saleFirstName', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_SALENAME_TEXTLEN,      StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_SALENAME_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Last Name',	             StageShowLibTableClass::TABLEPARAM_ID => 'saleLastName',  StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_SALENAME_TEXTLEN,      StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_SALENAME_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'EMail',	                 StageShowLibTableClass::TABLEPARAM_ID => 'saleEMail',     StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_SALEEMAIL_TEXTLEN,     StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_SALEEMAIL_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $address,	                 StageShowLibTableClass::TABLEPARAM_ID => 'salePPStreet',  StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_SALEPPSTREET_TEXTLEN,  StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_SALEPPSTREET_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $city,	                     StageShowLibTableClass::TABLEPARAM_ID => 'salePPCity',    StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_SALEPPCITY_TEXTLEN,    StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_SALEPPCITY_EDITLEN, ),			
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $state,	                     StageShowLibTableClass::TABLEPARAM_ID => 'salePPState',   StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_SALEPPSTATE_TEXTLEN,   StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_SALEPPSTATE_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $zip,                        StageShowLibTableClass::TABLEPARAM_ID => 'salePPZip',     StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_SALEPPZIP_TEXTLEN,     StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_SALEPPZIP_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $country,                    StageShowLibTableClass::TABLEPARAM_ID => 'salePPCountry', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_SALEPPCOUNTRY_TEXTLEN, StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_SALEPPCOUNTRY_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $phone,                      StageShowLibTableClass::TABLEPARAM_ID => 'salePPPhone',   StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_SALEPPPHONE_TEXTLEN,   StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_SALEPPPHONE_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Total Paid/Due',            StageShowLibTableClass::TABLEPARAM_ID => 'salePaid',      StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => $gatewayName.' Fees',        StageShowLibTableClass::TABLEPARAM_ID => 'saleFee',       StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Transaction Date & Time',   StageShowLibTableClass::TABLEPARAM_ID => 'saleDateTime',  StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Transaction ID',            StageShowLibTableClass::TABLEPARAM_ID => 'saleTxnId',     StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW),						
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Status',                    StageShowLibTableClass::TABLEPARAM_ID => 'saleStatus',    StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_SELECT, StageShowLibTableClass::TABLEPARAM_ITEMS => $statusOptions),						
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Note',                      StageShowLibTableClass::TABLEPARAM_ID => 'saleNoteToSeller', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_VIEW, StageShowLibTableClass::TABLEPARAM_DECODE => 'FormatSaleNote'),
			);
			
			$ourOptions = self::MergeSettings(parent::GetDetailsRowsDefinition(), $ourOptions);
			return $ourOptions;
		}
		
		function GetDetailsRowsFooter()
		{
			$ourOptions = array(
				array(StageShowLibTableClass::TABLEPARAM_ID => 'saleDetails', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_FUNCTION, StageShowLibTableClass::TABLEPARAM_FUNC => 'ShowSaleDetails'),						
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
		var $results;
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
			if (isset($_POST['searchsalesbutton']) && ($_POST['searchsalesbutton'] != ''))	// TODO: Check for SQLi
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
				
			$this->salesFor = '';
			
			$this->DoSalesSearch();
			
			if (isset($_POST['emailsale']))
			{
				$this->CheckAdminReferer();
				
				$this->emailSaleId = StageShowLibHTTPIO::GetRequestedInt('id');
				$myDBaseObj->EMailSale($this->emailSaleId);
			}
			
			if (isset($_GET['action']))
			{
				$this->CheckAdminReferer();
				$this->DoActions();
			}

			$this->invalidInputId = '';
			
			$this->pageTitle = $this->salesFor . 'Sales Log';			
		}
		
		function Output_MainPage($updateFailed)
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;				
			
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
				// TODO - SSG - Is this section redundant?
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

			$pricesList = $myDBaseObj->GetPricesList(null);
			if (count($pricesList) > 0)
			{
				$this->OuputAddSaleButton();
			}			
			else
			{
				echo $this->NoStockMessage();				
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
			echo '<div class="'.$this->myDomain.'-searchsales"><input type="text" maxlength="'.PAYMENT_API_SALEEMAIL_TEXTLEN.'" size="20" name="searchsalestext" id="searchsaletext" value="" autocomplete="off" />'."\n";
			$this->OutputButton("searchsalesbutton", __("Search Sales", $this->myDomain));					
			echo '</div>'."\n";
		}
		
		function OuputAddSaleButton()
		{
			$myDBaseObj = $this->myDBaseObj;
			echo $myDBaseObj->ActionButtonHTML('Add Sale', $this->caller, $this->myDomain, 'edit-entry-button', 0, 'editsale');    
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
		
		function DoBulkPreAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// Reset error count etc. on first pass
			if (!isset($this->errorCount)) $this->errorCount = 0;
			
			switch ($bulkAction)
			{
				case StageShowLibAdminListClass::BULKACTION_DELETE:
					// FUNCTIONALITY: Price Plans - Bulk Action Delete - Check Plan Exists
					$saleResults = $this->myDBaseObj->GetSale($recordId);
					if (count($saleResults) == 0)
						$this->errorCount++;
					return ($this->errorCount > 0);
					
				default:
					break;
			}
			
			return false;
		}
		
		function DoBulkAction($bulkAction, $recordId)
		{
			switch ($bulkAction)
			{
				case StageShowLibAdminListClass::BULKACTION_DELETE:		
					$this->myDBaseObj->DeleteSale($recordId);
					return true;
					
				default:
					break;
			}
				
			return false;
		}
		
		function GetBulkActionMsg($bulkAction, $actionCount)
		{
			$actionMsg = '';
			
			switch ($bulkAction)
			{
				case StageShowLibAdminListClass::BULKACTION_DELETE:		
					if ($this->errorCount > 0)
						$actionMsg = $this->errorCount . ' ' . _n("Sale Entry does not exist in Database", "Sale Entries do not exist in Database", $this->errorCount, $this->myDomain);
					else if ($actionCount > 0)		
						$actionMsg = $actionCount . ' ' . _n("Sale has been deleted", "Sales have been deleted", $actionCount, $this->myDomain);
					else
						$actionMsg =  __("Nothing to Delete", $this->myDomain);
					break;
					
				default:
					break;
			}
			
			return $actionMsg;
		}
		
		function OutputSalesList($env)
		{
			$myPluginObj = $this->myPluginObj;
			
			$classId = $myPluginObj->adminClassPrefix.'SalesAdminListClass';
			$salesList = new $classId($env);	// StageShowLibGatewayBaseClassxxxxSalesAdminListClass etc.
			$salesList->OutputList($this->results);		
		}
				
	}
} 
		 
?>