<?php
/* 
Description: StageShow Plugin Top Level Code
 
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

if (!defined('STAGESHOWLIB_DATABASE_FULL'))
{
	if (!class_exists('StageShowLibSalesCartPluginClass')) 
		include STAGESHOW_INCLUDE_PATH.'stageshow_trolley_sales.php';
	
	class StageShowWPOrgCartPluginClass_Parent extends StageShowWPOrgSalesCartPluginClass {}
}
else
{
	if (!class_exists('StageShowLibSalesPluginClass')) 
		include STAGESHOW_INCLUDE_PATH.'stageshow_sales.php';
	
	class StageShowWPOrgCartPluginClass_Parent extends StageShowWPOrgSalesPluginClass {}
}

if (!class_exists('StageShowWPOrgCartPluginClass')) 
{
	class StageShowWPOrgCartPluginClass extends StageShowWPOrgCartPluginClass_Parent // Define class 
	{
		var $ourPluginName;
		var $myDBaseObj;
		var	$env;
		
		var	$adminClassFilePrefix;
		var $adminClassPrefix;
		
		function __construct($caller)		 
		{
			if (defined('STAGESHOW_ERROR_REPORTING')) 
			{
				error_reporting(STAGESHOW_ERROR_REPORTING);
			}
			
			$myDBaseObj = $this->CreateDBClass($caller);
			
			$this->myDBaseObj = $myDBaseObj;
					
			parent::__construct();
			
			$this->myDBaseObj->pluginSlug = 'stageshow';
			$this->adminClassFilePrefix = 'stageshow';
			$this->adminClassPrefix = 'StageShowWPOrg';
			
			$this->env = array(
			    'caller' => $caller,
			    'PluginObj' => $this,
			    'DBaseObj' => $this->myDBaseObj,
			    'Domain' => $this->myDomain,
			);

			$this->getStageshowOptions();
		}
		
		static function CreateDBClass($caller)
		{					
			if (!class_exists('StageShowWPOrgCartDBaseClass')) 
				include STAGESHOW_INCLUDE_PATH.'stageshow_trolley_dbase_api.php';
				
			return new StageShowWPOrgCartDBaseClass($caller);		
		}
		
		//Returns an array of admin options
		function getStageshowOptions() 
		{
			$myDBaseObj = $this->myDBaseObj;
			return $myDBaseObj->adminOptions;
		}
		// Saves the admin options to the options data table
		
		// ----------------------------------------------------------------------
		// Activation / Deactivation Functions
		// ----------------------------------------------------------------------
	
		function init()
		{
			$myDBaseObj = $this->myDBaseObj;
			$myDBaseObj->init($this->env['caller']);
			
			// Get plugin version number
			wp_update_plugins();
		}

		function Cart_OutputContent_OnlineStoreMain($atts)
		{
			parent::Cart_OutputContent_OnlineStoreMain($atts);				
		}
		
		function OutputContent_TrolleyJQueryPostvars()
		{
			$jqCode = parent::OutputContent_TrolleyJQueryPostvars();
			
			if ($this->myDBaseObj->isOptionSet('UseNoteToSeller'))
			{
				$jqCode .= '
				var saleNoteToSellerElem = document.getElementById("saleNoteToSeller");
				if (saleNoteToSellerElem)
				{
					postvars.saleNoteToSeller = saleNoteToSellerElem.value;
				}';
			}
				
			return $jqCode;
		}

		function OutputContent_OnlinePurchaserDetails($cartContents, $extraHTML = '')
		{
			$formHTML = $extraHTML;
			
			if ($this->myDBaseObj->getOption('EnableReservations'))
			{
				// Output Select Status Drop-down Dialogue
				$saleStatus = isset($cartContents->saleStatus) ? $cartContents->saleStatus : '';
				$selectCompleted = ($saleStatus == PAYMENT_API_SALESTATUS_COMPLETED) ? 'selected=true ' : '';
				$selectReserved  = ($saleStatus == PAYMENT_API_SALESTATUS_RESERVED) ? 'selected=true ' : '';
				
				$formHTML .=  '
				<tr class="stageshow-boxoffice-formRow">
					<td class="stageshow-boxoffice-formFieldID">'.__('Status', $this->myDomain).':&nbsp;</td>
					<td class="stageshow-boxoffice-formFieldValue" colspan="2">
				<select id="saleStatus" name="saleStatus">
					<option value="'.PAYMENT_API_SALESTATUS_COMPLETED.'" '.$selectCompleted.'>'.__('Completed', $this->myDomain).'&nbsp;</option>
					<option value="'.PAYMENT_API_SALESTATUS_RESERVED.'" '.$selectReserved.'>'.__('Reserved', $this->myDomain).'&nbsp;</option>
				</select>
					</td>
				</tr>
				';
			}
			else
			{
				$formHTML .= '
				<input type="hidden" id="saleStatus" name="saleStatus" value="'.PAYMENT_API_SALESTATUS_COMPLETED.'"/>
				';
			}
			
			if ($this->myDBaseObj->getOption('UseNoteToSeller'))
			{
				$rowsDef = '';
				$noteToSeller = $cartContents->saleNoteToSeller;
				
				$formHTML .=  '
				<tr class="stageshow-boxoffice-formRow">
				<td class="stageshow-boxoffice-formFieldID">'.__('Message To Seller', $this->myDomain).'</td>
				<td class="stageshow-boxoffice-formFieldValue" colspan="2">
				<textarea name="saleNoteToSeller" id="saleNoteToSeller" '.$rowsDef.'>'.$noteToSeller.'</textarea>
				</td>
				</tr>
				';
			}
			
			$formHTML = parent::OutputContent_OnlinePurchaserDetails($cartContents, $formHTML);
			
			return $formHTML;
		}
		

		
	}
}

?>