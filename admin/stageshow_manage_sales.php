<?php
/* 
Description: Code for Sales Page
 
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

include STAGESHOW_INCLUDE_PATH.'stageshow_sales_table.php';

if (!class_exists('StageShowWPOrgSalesAdminClass')) 
{
	class StageShowWPOrgSalesAdminClass extends PayPalSalesAdminClass // Define class
	{		
		function __construct($env)
		{
			$env['saleQtyInputID'] = 'ticketQty';
			parent::__construct($env);
		}
		
		function NoStockMessage()
		{
			$perfsPageURL = get_option('siteurl').'/wp-admin/admin.php?page='.STAGESHOW_MENUPAGE_PRICES;
			$perfsPageMsg = __('No Prices Defined', $this->myDomain).' - <a href='.$perfsPageURL.'>'.__('Add one Here', $this->myDomain).'</a>';
			$perfsPageMsg = "<div class='error'><p>$perfsPageMsg</p></div>";
			return $perfsPageMsg;
		}
		
		function OuputAddSaleButton()
		{
			if ( current_user_can(STAGESHOWLIB_CAPABILITY_SALESUSER) )
			{
				parent::OuputAddSaleButton();
			}
		}
		
		function Output_MainPage($updateFailed)
		{
			return parent::Output_MainPage($updateFailed);
		}
		
		function DoActions()
		{
			$rtnVal = false;
			
			switch ($_GET['action'])
			{
				case 'show':
					// FUNCTIONALITY: Sales - Lists Sales for a Show
					// List Sales for Show
					$showID = $_GET['id']; 
					$showEntry = $this->myDBaseObj->GetShowsList($showID);
					if (count($showEntry) == 0)
					{
						// Invalid showID ... bail out!
						break;
					}
					$this->salesFor = $showEntry[0]->showName.' - ';
					$this->results = $this->myDBaseObj->GetSalesListByShowID($showID);	
					for ($i=0; $i<count($this->results); $i++)
						$this->results[$i]->ticketQty = $this->myDBaseObj->GetSalesQtyBySaleID($this->results[$i]->saleID);
					$rtnVal = true;
					break;
						
				case 'perf':
					// FUNCTIONALITY: Sales - Lists Sales for a Performance
					// List Sales for Performance
					$perfID = $_GET['id']; 
					$perfEntry = $this->myDBaseObj->GetPerformancesListByPerfID($perfID);
					if (count($perfEntry) == 0)
					{
						// Invalid perfID ... bail out!
						break;
					}
					$this->results = $this->myDBaseObj->GetSalesListByPerfID($perfID);
					$this->salesFor = $perfEntry[0]->showName.' ('.$this->myDBaseObj->FormatDateForDisplay($perfEntry[0]->perfDateTime).') - ';
					for ($i=0; $i<count($this->results); $i++)
						$this->results[$i]->ticketQty = $this->myDBaseObj->GetSalesQtyBySaleID($this->results[$i]->saleID);
					$rtnVal = true;
					break;
					
				case 'editsale':
					$pluginObj = $this->env['PluginObj'];
					$pluginObj->SetTrolleyID('');
					$pluginObj->editpage = isset($_REQUEST['editpage']) ? $_REQUEST['editpage'] : 'start';

					$this->editingRecord = true;	// Set this flag to show that we are editing a Sale entry
					
					switch($pluginObj->editpage)
					{
						case 'start':
							// Initialise values to start editing a sale							
							$pluginObj->editpage = 'tickets';
							$pluginObj->ClearTrolleyContents();
							$rtnVal = true;
							break;
							
						case 'tickets':
							//$rtnVal = parent::DoActions();
							break;
					}
					break;
										
				default:
					$rtnVal = parent::DoActions();
					break;
					
			}
				
			return $rtnVal;
		}
		
		function CanChangeState($prevSaleStatus, $newSaleStatus)
		{
			if ($newSaleStatus  == PAYMENT_API_SALESTATUS_COMPLETED)
			{
				if ($prevSaleStatus == PAYMENT_API_SALESTATUS_CHECKOUT) 
			  		return true;
				if ($prevSaleStatus == PAYMENT_API_SALESTATUS_UNVERIFIED) 
			  		return true;
			  	return false;				
			}
			  	
			return false;
		}

		function DoBulkAction($bulkAction, $recordId)
		{
			switch ($bulkAction)
			{
				case StageShowWPOrgSalesAdminListClass::BULKACTION_COMPLETED:	
					$saleResults = $this->myDBaseObj->GetSale($recordId);
					$saleEntry = $saleResults[0];
					
					// Check record can be changed to COMPLETED
					$prevSaleStatus = $saleEntry->saleStatus;
					
					// Get the sale entry
					if (!$this->CanChangeState($prevSaleStatus, PAYMENT_API_SALESTATUS_COMPLETED))
						return false;
						
					// Change saleStatus to Completed	
					$this->myDBaseObj->UpdateSaleIDStatus($recordId, PAYMENT_API_SALESTATUS_COMPLETED);
					if ($prevSaleStatus == PAYMENT_API_SALESTATUS_UNVERIFIED)
					{
						// Previously Unverified Sale is confirmed - Send EMail to Purchaser
						$this->myDBaseObj->EMailSale($saleEntry->saleID);
					}
					return true;
			}
				
			return parent::DoBulkAction($bulkAction, $recordId);
		}
		
	}
}

?>