<?php
/* 
Description: Code for Managing Price Plans

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

include STAGESHOW_INCLUDE_PATH . 'stageshowlib_table.php';

if (!class_exists('StageShowPricePlansAdminListClass'))
{
	class StageShowPricePlansAdminListClass extends StageShowLibAdminListClass // Define class
	{
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env, true);
			
			$this->bulkActions = array(
				StageShowLibAdminListClass::BULKACTION_DELETE => __('Delete', $this->myDomain),
			);
		}
		
		function GetTableID($result)
		{
			return "presettab" . $result->planID;
		}
		
		function GetRecordID($result)
		{
			return $result->presetID;
		}
		
		function GetMainRowsDefinition()
		{
			return array(
				array(self::TABLEPARAM_LABEL => 'Type',  self::TABLEPARAM_ID => 'priceType',   self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, self::TABLEPARAM_LEN => STAGESHOW_PRICETYPE_TEXTLEN, ),
				array(self::TABLEPARAM_LABEL => 'Price', self::TABLEPARAM_ID => 'priceValue', self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT, self::TABLEPARAM_LEN => 9, ),						
			);
		}
		
		function OutputList($results, $updateFailed)
		{
			$this->updateFailed = $updateFailed;
			parent::OutputList($results);
		}
		
	}
}

include STAGESHOW_INCLUDE_PATH . 'stageshowlib_admin.php';

if (!class_exists('StageShowPlusPricePlansAdminClass'))
{
	class StageShowPlusPricePlansAdminClass extends StageShowLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Price Plans';
			
			// Call base constructor
			parent::__construct($env);
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj  = $this->myDBaseObj;
			
			// FUNCTIONALITY: Price Plans - Save Changes
			if (isset($_POST['savechanges']))
			{
				$this->CheckAdminReferer();
				
				// Get Plan ID to update
				$planID  = $_POST['planID'];
				$results = $myDBaseObj->GetPricePlansListByPlanID($planID);
				
				$planUpdated = false;
				
				// Verify that Plan Price Refs are unique 
				if (count($results) > 0)
				{
					foreach ($results as $result)
					{
						$priceType = stripslashes($_POST['priceType' . $result->presetID]);
						// FUNCTIONALITY: Price Plan - Reject Duplicate or Blank IDs
						// Verify that the Price Ref is not blank
						if (strlen($priceType) == 0)
						{
							$this->adminMsg = __('Empty Price Ref Entry', $this->myDomain);
							break;
						}
						
						if (isset($refsList[$priceType]))
						{
							$this->adminMsg = __('Duplicated Price Ref Entry', $this->myDomain) . ' (' . $priceType . ')';
							break;
						}
						$refsList[$priceType] = true;
					}
					
					if ($this->adminMsg === '')
					{
						$pricePlan = $results[0];
						
						$newPlanRef  = stripslashes($_POST['planRef' . $pricePlan->planID]);
						$planRefName = 'planRef' . $planID;
						if ($newPlanRef != $pricePlan->planRef)
						{
							// FUNCTIONALITY: Price Plans - Update Price Plan Name blocking duplicate entries
							// Update Plan Ref - Return status indicates if it was unique
							if (!$myDBaseObj->UpdatePlanRef($planID, $newPlanRef))
								$this->adminMsg = __('Duplicated Price Plan ID', $this->myDomain);
							else
								$planUpdated = true;
						}
						
						if ($this->adminMsg === '')
						{
							// FUNCTIONALITY: Price Plans - Update Price Plan Entry
							foreach ($results as $result)
							{
								$newPresetRef = stripslashes($_POST['priceType' . $result->presetID]);
								if ($newPresetRef != $result->priceType)
								{
									// Update Preset Ref
									$myDBaseObj->UpdatePreset($result->presetID, 'priceType', $newPresetRef);
									$planUpdated = true;
								}
								
								$newPresetValue = stripslashes($_POST['priceValue' . $result->presetID]);
								if ($newPresetValue != $result->priceValue)
								{
									// Update Preset Value
									$myDBaseObj->UpdatePreset($result->presetID, 'priceValue', $newPresetValue);
									$planUpdated = true;
								}
							}
						}
					}
				}
				
				if ($this->adminMsg !== '')
					echo '<div id="message" class="error"><p>' . __('Settings have NOT been saved', $this->myDomain) . '. ' . $this->adminMsg . '</p></div>';
				else if ($planUpdated)
					echo '<div id="message" class="updated"><p>' . __('Settings have been saved', $this->myDomain) . '</p></div>';
			}
			else if (isset($_POST['addpriceplanbutton']))
			{
				// FUNCTIONALITY: Price Plans - Add new Price Plan 
				$this->CheckAdminReferer();
				
				// Add Group with unique Group Name 
				$planID = $myDBaseObj->AddPlan('');
				
				if ($planID == 0)
					echo '<div id="message" class="error"><p>' . __('Cannot add a price plan', $this->myDomain) . '</p></div>';
				else
					echo '<div id="message" class="updated"><p>' . __('Default price plan added - Edit and Save to update it.', $this->myDomain) . '</p></div>';
			}
			else if (isset($_POST['addpricebutton']))
			{
				// FUNCTIONALITY: Price Plans - Add new price entry 
				$this->CheckAdminReferer();
				
				$planID = $_POST['planID'];
				
				// Add Group with unique Group Name 
				$presetID = $myDBaseObj->AddPreset($planID);
				
				if ($presetID == 0)
					echo '<div id="message" class="error"><p>' . __('Cannot add a price', $this->myDomain) . '</p></div>';
				else
					echo '<div id="message" class="updated"><p>' . __('Default price entry added - Edit and Save to update it.', $this->myDomain) . '</p></div>';
			}
		}
		
		function GetAdminListClass()
		{
			return 'StageShowPricePlansAdminListClass';			
		}
		
		function Output_MainPage($updateFailed)
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj  = $this->myDBaseObj;
			
			// StageShow Price Plan HTML Output - Start 
			
			$pricePlansList = $myDBaseObj->GetAllPlansList();
			if (count($pricePlansList) == 0)
			{
				// FUNCTIONALITY: Price Plans - Show No Price Plans Confgured
				echo "<div class='noconfig'>" . __('No Price Plans Configured', $this->myDomain) . "</div>\n";
			}
			// FUNCTIONALITY: Price Plans - Output Settings
			foreach ($pricePlansList as $pricePlan)
			{
				$results     = $myDBaseObj->GetPricePlansListByPlanID($pricePlan->planID);
				$groupIDRef  = 'planRef' . $pricePlan->planID;
				$groupIDLen  = STAGESHOW_PLANREF_TEXTLEN;
				$groupIDSize = STAGESHOW_PLANREF_TEXTLEN + 1;
				
				$thisUpdateFailed = (($updateFailed) && ($pricePlan->planID == $_POST['planID']));
				
				if ($thisUpdateFailed)
					$planRef = $_POST[$groupIDRef];
				else
					$planRef = $pricePlan->planRef;
?>
	<div class="stageshow-admin-form">
	<form method="post">
	<div class="stageshow-edit-planref">
	<input id="<?php echo $groupIDRef; ?>" type="text" autocomplete="off" value="<?php echo($planRef); ?>" size="<?php echo $groupIDSize; ?>" maxlength="<?php echo $groupIDLen; ?>" name="<?php echo $groupIDRef; ?>">
	</div>
	<input type="hidden" name="planID" value="<?php echo $pricePlan->planID; ?>"/>
<?php
				$this->WPNonceField();
				if (count($results) == 0)
				{
					echo "<div class='noconfig'>" . __('Price Plan has No Presets', $this->myDomain) . "</div>\n";
				}
				else
				{
					$classId = $this->GetAdminListClass();
					$perfsList = new $classId($this->env);
					$perfsList->OutputList($results, $thisUpdateFailed);
				} // End of if (count($results) == 0) ... else ...
				
?>
      <input type="hidden" name="planID" value="<?php echo $pricePlan->planID; ?>"/>
<?php
				$this->OutputButton("addpricebutton", __("Add New Price", $this->myDomain));
				
				if (count($results) > 0)
				{
					$this->OutputButton("savechanges", __("Save Changes", $this->myDomain), "button-primary");
				}
				
?>
					</form>
					</div>
<?php
			} // End of foreach ($pricePlansList as $pricePlan) ..
?>
				<div class="stageshow-admin-form">
				<form method="post">
<?php
			$this->WPNonceField();
?>	
					<input class="button-secondary" type="submit" name="addpriceplanbutton" value="<?php _e('Add New Price Plan', $this->myDomain) ?>"/>
				</form>
				</div>
<?php
			// StageShow Price Plan HTML Output - End 
		}
		
		function DoBulkAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			switch ($bulkAction)
			{
				case StageShowLibAdminListClass::BULKACTION_DELETE:
					// FUNCTIONALITY: Price Plans - Bulk Action Delete - Remove Price(s) (and Price Plan on last entry)
					$myDBaseObj->DeletePreset($recordId);
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
					// FUNCTIONALITY: Price Plans - Bulk Action Delete - Output Action Status Message
					if ($actionCount > 0)
						$actionMsg = ($actionCount == 1) ? __("1 Price Plan has been deleted", $this->myDomain) : $actionCount . ' ' . __("Price Plans have been deleted", $this->myDomain);
					else
						$actionMsg = __("Nothing to Delete", $this->myDomain);
					break;
			}
			
			return $actionMsg;
		}
		
	}
}

?>