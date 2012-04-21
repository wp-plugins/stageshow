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
		function __construct($env) //constructor
		{
			$this->hiddenRowsButtonId = 'Details';
			$this->env = $env;
			
			// Call base constructor
			parent::__construct($env);
			
			$myDBaseObj = $this->myDBaseObj;
			
			$this->showDBIds = $myDBaseObj->adminOptions['Dev_ShowDBIds'];					

			$this->SetRowsPerPage($myDBaseObj->adminOptions['PageLength']);
			
			$this->bulkActions = array(
				'delete' => __('Delete', STAGESHOW_DOMAIN_NAME),
				);

			$columns = array(
				'saleName'    => __('Name', STAGESHOW_DOMAIN_NAME),
				'saleDate'    => __('Transaction Date', STAGESHOW_DOMAIN_NAME),
				'saleStatus'  => __('Status', STAGESHOW_DOMAIN_NAME),
				'saleQty'		  => __('Qty', STAGESHOW_DOMAIN_NAME),
			);			
			$this->SetListHeaders('stageshow_sales_list', $columns);
		}
		
		function GetTableID($result)
		{
			return "salestab";
		}
		
		function GetRecordID($result)
		{
			return $result->saleID;
		}
		
		function AddResult($result)
		{
			$this->NewRow($result);

/*
			// TODO - Code for links on Sales List Page - Remove
			if (false)
			{
				$modLink = 'admin.php?page=stageshow_sales&action=details&id='.$result->saleID;
				$modLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($modLink, plugin_basename($this->caller)) : $modLink;

				$this->AddToTable($result, '<a href="'.$modLink.'">'.$result->saleName.'</a>');
				$this->AddToTable($result, '<a href="'.$modLink.'">'.$result->saleDateTime.'</a>');
				$this->AddToTable($result, '<a href="'.$modLink.'">'.$result->saleStatus.'</a>');
			}
			else
*/
			{
				$this->AddToTable($result, $result->saleName);
				$this->AddToTable($result, $result->saleDateTime);
				$this->AddToTable($result, $result->saleStatus);
				$this->AddToTable($result, $result->totalQty);
			}
			
			//<td style="background-color:#FFF">
		}		
		
		function GetHiddenRowsDefinition()	// TODO - Sales Hidden Rows Disabled for Distribution
		{
			$ourOptions = array(
				array('Type' => 'function', 'Show' => 'ShowSaleDetails', 'Save' => 'SaveSaleDetails'),						
			);
			
			$ourOptions = array_merge(parent::GetHiddenRowsDefinition(), $ourOptions);
			return $ourOptions;
		}
		
		function ShowSaleDetails($result)
		{
			$env = $this->env;
			
			$myDBaseObj = $this->myDBaseObj;
			$saleResults = $myDBaseObj->GetSale($result->saleID);
			$salesList = new StageShowSalesAdminDetailsListClass($env);	

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

if (!class_exists('StageShowSalesAdminDetailsListClass')) 
{
	class StageShowSalesAdminDetailsListClass extends MJSLibAdminListClass // Define class
	{		
		var $isInput;
		
		function __construct($env, $isInput = false) //constructor
		{
			// Call base constructor
			parent::__construct($env);
			
			$myDBaseObj = $this->myDBaseObj;

			$this->showDBIds = $myDBaseObj->adminOptions['Dev_ShowDBIds'];					

			$this->SetRowsPerPage($myDBaseObj->adminOptions['PageLength']);
			
			$columns = array(
				'saleShowName' => __('Show', STAGESHOW_DOMAIN_NAME),
				'ticketType'   => __('Type', STAGESHOW_DOMAIN_NAME),
				'price'        => __('Price', STAGESHOW_DOMAIN_NAME),
				'quantity'     => __('Quantity', STAGESHOW_DOMAIN_NAME)
			);			
			$this->SetListHeaders('stageshow_saledetails_list', $columns, MJSLibTableClass::HEADERPOSN_TOP);
			
			$this->isInput = $isInput;
		}
		
		function GetTableID($result)
		{
			return "salestab";
		}
		
		function GetRecordID($result)
		{
			return $result->priceID;
		}
		
		function AddResult($result)
		{
			$this->NewRow($result);
			
			if ($this->isInput)
			{
				$show_and_perf = $result->showName.' - '.$result->perfDateTime;
				
				$this->AddToTable($result, $show_and_perf);
				$this->AddToTable($result, $result->priceType);
				$this->AddToTable($result, $result->priceValue);
				$this->AddInputToTable($result, 'addSaleItem', 4, 0);	
			}
			else
			{
				$this->AddToTable($result, $result->ticketName);
				$this->AddToTable($result, $result->ticketType);
				$this->AddToTable($result, $result->priceValue);
				$this->AddToTable($result, $result->ticketQty);
			}
		}
		
	}
}

