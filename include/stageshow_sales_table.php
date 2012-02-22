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

if (!class_exists('StageShowAdminSalesListClass')) 
{
	class StageShowAdminSalesListClass extends MJSLibAdminListClass // Define class
	{		
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env);
			
			$myDBaseObj = $this->myDBaseObj;
			
			$this->showDBIds = $myDBaseObj->adminOptions['Dev_ShowDBIds'];					

			$this->SetRowsPerPage(STAGESHOW_SALES_PER_PAGE);
			
			$this->bulkActions = array(
				'delete' => __('Delete', STAGESHOW_DOMAIN_NAME),
				);

			$columns = array(
				'saleName'   => __('Name', STAGESHOW_DOMAIN_NAME),
				'saleDate'   => __('Transaction Date', STAGESHOW_DOMAIN_NAME),
				'saleStatus' => __('Status', STAGESHOW_DOMAIN_NAME),
				'saleQty'    => __('Qty', STAGESHOW_DOMAIN_NAME),
			);			
			$this->SetListHeaders('stageshow_sales_list', $columns);
		}
		
		function GetRecordID($result)
		{
			return $result->saleID;
		}
		
		function AddResult($result)
		{
			$rowAttr = '';
			$this->NewRow($result, $rowAttr);

			$modLink = 'admin.php?page=stageshow_sales&action=details&id='.$result->saleID;
			$modLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($modLink, plugin_basename($this->caller)) : $modLink;
			//$modLink = '<a href="'.$modLink.'">'.$result->saleName.'</a>';

			$this->AddToTable($result, '<a href="'.$modLink.'">'.$result->saleName.'</a>');
			$this->AddToTable($result, '<a href="'.$modLink.'">'.$result->saleDateTime.'</a>');
			$this->AddToTable($result, '<a href="'.$modLink.'">'.$result->saleStatus.'</a>');
			$this->AddToTable($result, $result->totalQty);
			//<td style="background-color:#FFF">

		}		
	}
}

if (!class_exists('StageShowAdminSaleDetailsListClass')) 
{
	class StageShowAdminSaleDetailsListClass extends MJSLibAdminListClass // Define class
	{		
		var $isInput;
		
		function __construct($env, $isInput = false) //constructor
		{
			// Call base constructor
			parent::__construct($env);
			
			$myDBaseObj = $this->myDBaseObj;

			$this->showDBIds = $myDBaseObj->adminOptions['Dev_ShowDBIds'];					

			$this->SetRowsPerPage(STAGESHOW_SALES_PER_PAGE);
			
			$columns = array(
				'saleShowName' => __('Show', STAGESHOW_DOMAIN_NAME),
				'ticketType'   => __('Type', STAGESHOW_DOMAIN_NAME),
				'price'        => __('Price', STAGESHOW_DOMAIN_NAME),
				'quantity'     => __('Quantity', STAGESHOW_DOMAIN_NAME)
			);			
			$this->SetListHeaders('stageshow_saledetails_list', $columns, MJSLibTableClass::HEADERPOSN_TOP);
			
			$this->isInput = $isInput;
		}
		
		function GetRecordID($result)
		{
			return $result->priceID;
		}
		
		function AddResult($result)
		{
			$rowAttr = '';
			$this->NewRow($result, $rowAttr);
			
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

