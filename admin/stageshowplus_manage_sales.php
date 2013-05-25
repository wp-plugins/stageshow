<?php
/* 
Description: Code for Managing Sales
 
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

include STAGESHOW_ADMIN_PATH.'stageshow_manage_sales.php';

if (!class_exists('StageShowPlusSalesAdminListClass')) 
{
	class StageShowPlusSalesAdminListClass extends StageShowSalesAdminListClass // Define class
	{				
		function __construct($env, $editMode = false) //constructor
		{
			if ($editMode)
			{

			}
			
			// Call base constructor
			parent::__construct($env, $editMode);
		}
		
		function ExtendedSettingsDBOpts()
		{
			return parent::ExtendedSettingsDBOpts();
		}
		
		function GetMainRowsDefinition()
		{
			return parent::GetMainRowsDefinition();
		}		
		
		function GetDetailsRowsDefinition()
		{
			$rowDefs = parent::GetDetailsRowsDefinition();
			
			if (!$this->editMode) 
			{
				// FUNCTIONALITY: Sales - StageShow+ - Edit Sale
				$ourOptions = array(
					array(self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_FUNCTION, self::TABLEPARAM_FUNC => 'AddEditSaleButton'),						
				);
				
				$rowDefs = array_merge($rowDefs, $ourOptions);
			}
							
			return $rowDefs;
		}
		
		function ShowSaleDetails($result, $salesList)
		{
			if (!$this->editMode) 
				return parent::ShowSaleDetails($result, $salesList);
				
			return $this->BuildSaleDetails($salesList);
		}
		
		function AddEditSaleButton($result)
		{
			if (!current_user_can(STAGESHOW_CAPABILITY_SALESUSER))
				return '';
				
			return StageShowLibAdminClass::ActionButtonHTML('Edit Sale', $this->caller, $this->myDomain, 'edit-entry-button', $result->saleID);    
		}
		
	}
}

if (!class_exists('StageShowPlusSalesAdminClass') && class_exists('StageShowSalesAdminClass'))
{
	class StageShowPlusSalesAdminClass extends StageShowSalesAdminClass // Define class
	{
		
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env);
		}
		
		function GetAdminListClass()
		{
			return 'StageShowPlusSalesAdminListClass';			
		}
		
		function DoActions()
		{
			$rtnVal = false;

			switch ($_GET['action'])
			{
				case 'editsale':
					// Initialise values to start editing a sale
					$this->saleId = $_GET['id']; 
					
					$this->pricesList = $this->myDBaseObj->GetPricesListWithSales($this->saleId);
					
					$buyerDetails = $this->myDBaseObj->GetSaleBuyer($this->saleId);	// Get list of items for a single sale
					$this->editSaleEntry[0] = $buyerDetails[0];
						
					$this->editingRecord = true;	// Set this flag to show that we are editing a Sale entry
					$rtnVal = true;
					break;
					
				default:
					$rtnVal = parent::DoActions();
					break;
					
			}
				
			return $rtnVal;
		}

	}
}

?>