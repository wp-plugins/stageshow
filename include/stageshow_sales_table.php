<?php
/* 
Description: Code for Sales Page
 
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

include 'mjslib_table.php';
include 'mjslib_paypal_salesadmin.php';      

if (!class_exists('StageShowSalesAdminListClass')) 
{
	class StageShowSalesAdminListClass extends PayPalSalesAdminListClass // Define class
	{		
		var	$salesList;
		
		function __construct($env, $editMode = false) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);
		}
		
		function GetMainRowsDefinition()
		{
			if ($this->editMode) return array(
				array(self::TABLEPARAM_LABEL => 'Sale Editor',       self::TABLEPARAM_ID => 'edit', ),						
			);
							
			$columnDefs = array(
				array(self::TABLEPARAM_LABEL => 'Qty', self::TABLEPARAM_ID => 'totalQty', self::TABLEPARAM_TYPE => MJSLibTableClass::TABLEENTRY_VIEW, ),		
			);
			
			return array_merge(parent::GetMainRowsDefinition(), $columnDefs);
		}		
		
		function GetDetailsRowsDefinition()
		{
			return parent::GetDetailsRowsDefinition();
		}
		
		function ShowSaleDetails($result, $salesList)
		{
			if (!$this->editMode)
			//if ((!$this->editMode) || ($result->saleID != NULL))
			{
				return parent::ShowSaleDetails($result, $salesList);
			}
			
			return $this->BuildSaleDetails($salesList);
		}
		
		function CreateSalesAdminDetailsListObject($env, $editMode = false)
		{
			return new StageShowSalesAdminDetailsListClass($env, $editMode);	
		}
		
	}
}

if (!class_exists('StageShowSalesAdminDetailsListClass')) 
{
	class StageShowSalesAdminDetailsListClass extends PayPalSalesDetailsAdminClass // Define class
	{		
		function __construct($env, $editMode = false) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);
		}
		
		function GetTableID($result)
		{
			return "stageshow_saledetails_list";
		}
		
		function GetRecordID($result)
		{
			return $result->priceID;
		}
		
		function GetMainRowsDefinition()
		{
			return array(
				array(self::TABLEPARAM_LABEL => 'Show',     self::TABLEPARAM_ID => 'ticketName',   self::TABLEPARAM_TYPE => MJSLibTableClass::TABLEENTRY_VIEW, ),
				array(self::TABLEPARAM_LABEL => 'Type',     self::TABLEPARAM_ID => 'ticketType',   self::TABLEPARAM_TYPE => MJSLibTableClass::TABLEENTRY_VIEW, ),
				array(self::TABLEPARAM_LABEL => 'Price',    self::TABLEPARAM_ID => 'priceValue',   self::TABLEPARAM_TYPE => MJSLibTableClass::TABLEENTRY_VIEW, ),						
				array(self::TABLEPARAM_LABEL => 'Quantity', self::TABLEPARAM_ID => 'ticketQty',    self::TABLEPARAM_TYPE => MJSLibTableClass::TABLEENTRY_TEXT,   self::TABLEPARAM_LEN => 4, ),						
			);
		}
		
		function IsRowInView($result, $rowFilter)
		{
			if (!$this->editMode)
			{
				if ($result->ticketQty == 0)
				{
					// Obnly show rows that have non-zero quantity
					return false;
				}
			}
			
			return true;
		}		
				
	}
}

if (!class_exists('StageShowSalesAdminVerifyListClass')) 
{
	class StageShowSalesAdminVerifyListClass extends MJSLibAdminListClass // Define class
	{		
		function __construct($env, $editMode = false) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);
		}
		
		function GetTableID($result)
		{
			return "stageshow_saleverify_list";
		}
		
		function GetRecordID($result)
		{
			return $result->verifyID;
		}
		
		function GetMainRowsDefinition()
		{
			return array(
				array(MJSLibTableClass::TABLEPARAM_LABEL => 'Location',          MJSLibTableClass::TABLEPARAM_ID => 'verifyLocation',   MJSLibTableClass::TABLEPARAM_TYPE => MJSLibTableClass::TABLEENTRY_VIEW, ),
				array(MJSLibTableClass::TABLEPARAM_LABEL => 'Date and Time',     MJSLibTableClass::TABLEPARAM_ID => 'verifyDateTime',   MJSLibTableClass::TABLEPARAM_TYPE => MJSLibTableClass::TABLEENTRY_VIEW, ),
			);
		}		
				
	}
}

