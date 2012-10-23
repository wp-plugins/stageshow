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
		var $showZeroQtyEntries;
		var	$salesList;
		
		function __construct($env, $editMode = false) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);

			$this->showZeroQtyEntries = false;
		}
		
		function GetMainRowsDefinition()
		{
			$columnDefs = array(
				array('Label' => 'Qty', 'Id' => 'totalQty', 'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),		
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
				return parent::ShowSaleDetails($result, $salesList);
				
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
				array('Label' => 'Show',     'Id' => 'ticketName',   'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),
				array('Label' => 'Type',     'Id' => 'ticketType',   'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),
				array('Label' => 'Price',    'Id' => 'priceValue',   'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),						
				array('Label' => 'Quantity', 'Id' => 'ticketQty',    'Type' => MJSLibTableClass::TABLEENTRY_TEXT,   'Len' => 4, ),						
			);
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
				array('Label' => 'Location',          'Id' => 'verifyLocation',   'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),
				array('Label' => 'Date and Time',     'Id' => 'verifyDateTime',   'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),
			);
		}		
				
	}
}

