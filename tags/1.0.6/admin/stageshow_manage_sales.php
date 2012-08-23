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

include STAGESHOW_INCLUDE_PATH.'stageshow_sales_table.php';
include STAGESHOW_INCLUDE_PATH.'mjslib_paypal_salesadmin.php';      

if (!class_exists('StageShowSalesAdminClass')) 
{
	class StageShowSalesAdminClass extends PayPalSalesAdminClass // Define class
	{		
		function __construct($env)
		{
			parent::__construct($env);
		}
		
		function GetItemID($pricesEntry)
		{
			return $pricesEntry->priceID;
		}
				
		function GetItemPrice($pricesEntry)
		{
			return $pricesEntry->priceValue;
		}
		
		function GetButtonID($pricesEntry)
		{
			return $pricesEntry->perfPayPalButtonID;
		}
		
		function DoActions()
		{
			$rtnVal = false;
			
			switch ($_GET['action'])
			{
				case 'show':
					// List Sales for Show
					$showID = $_GET['id']; 
					$this->results = $this->myDBaseObj->GetSalesListByShowID($showID);	
					$this->salesFor = '- '.$this->results[0]->showName.' ';
					for ($i=0; $i<count($this->results); $i++)
						$this->results[$i]->ticketQty = $this->myDBaseObj->GetSalesQtyBySaleID($this->results[$i]->saleID);
					return;
						
				case 'perf':
					// List Sales for Performance
					$perfID = $_GET['id']; 
					$this->results = $this->myDBaseObj->GetSalesListByPerfID($perfID);
					$this->salesFor = '- '.$this->results[0]->showName.' ('.$this->myDBaseObj->FormatDateForDisplay($this->results[0]->perfDateTime).') ';
					for ($i=0; $i<count($this->results); $i++)
						$this->results[$i]->ticketQty = $this->myDBaseObj->GetSalesQtyBySaleID($this->results[$i]->saleID);
					
					return;						
			}
			
			PayPalSalesAdminClass::DoActions();
			
			return $rtnVal;
		}
		
		function OutputSalesList($env)
		{
			$classId = $env['PluginObj']->adminClassPrefix.'SalesAdminListClass';
			$salesList = new $classId($env);	// StageShowSalesAdminListClass etc.
			$salesList->OutputList($this->results);		
		}
				
		function OutputSalesDetailsList($env, $isInput = false)
		{
			$salesList = new StageShowSalesAdminDetailsListClass($env, $isInput);		
			$salesList->OutputList($this->results);	
		}
		
	}
}

?>