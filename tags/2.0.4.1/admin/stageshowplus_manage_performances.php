<?php
/* 
Description: Code for Managing Performances Configuration
 
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

include STAGESHOW_ADMIN_PATH.'stageshow_manage_performances.php';

if (!class_exists('StageShowPlusPerformancesAdminListClass')) 
{
	class StageShowPlusPerformancesAdminListClass extends StageShowPerformancesAdminListClass // Define class
	{				
		function __construct($env) //constructor
		{
			$this->hiddenRowsButtonId = 'TBD';
			
			// Call base constructor
			parent::__construct($env);
			
			$this->hiddenRowsButtonId = __('Options', $this->myDomain);
		}
		
		function ShowExpiresTime($result)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			//print_r($result);
			$perfTimestamp  = strtotime($result->perfDateTime);
			$perfTimestamp -= ($myDBaseObj->adminOptions['PerfExpireLimit'] * $myDBaseObj->adminOptions['PerfExpireUnits']);
			
			return date(StageShowLibDBaseClass::MYSQL_DATETIME_FORMAT, $perfTimestamp);
		}
		
		function GetDetailsRowsDefinition()
		{
			$aboveEntryText = 'above|'.__('Above Entry', $this->myDomain);
			$belowEntryText = 'below|'.__('Below Entry', $this->myDomain);
			
			// FUNCTIONALITY: Performances - StageShow+ - Adds Expires Time, Note Position and Text
			$ourOptions = array(
				array(self::TABLEPARAM_LABEL => 'Expires',         self::TABLEPARAM_ID => 'perfExpires',  self::TABLEPARAM_TYPE => self::TABLEENTRY_FUNCTION, self::TABLEPARAM_FUNC  => 'ShowExpiresTime'),					
				array(self::TABLEPARAM_LABEL => 'Note Position',   self::TABLEPARAM_ID => 'perfNotePosn', self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_ITEMS => array($aboveEntryText, $belowEntryText), ),
				array(self::TABLEPARAM_LABEL => 'Note',            self::TABLEPARAM_ID => 'perfNote',     self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXTBOX,  self::TABLEPARAM_ROWS  => 4, self::TABLEPARAM_COLS => 60, ),
			);
			
			$ourOptions = array_merge(parent::GetDetailsRowsDefinition(), $ourOptions);
			return $ourOptions;
		}
		
		function ExtendedSettingsDBOpts()
		{
			$dbOpts['Table'] = STAGESHOW_PERFORMANCES_TABLE;
			$dbOpts['Index'] = 'perfID';
			
			return $dbOpts;
		}
		
	}
}

if (!class_exists('StageShowPlusPerformancesAdminClass') && class_exists('StageShowPerformancesAdminClass')) 
{
	class StageShowPlusPerformancesAdminClass extends StageShowPerformancesAdminClass // Define class
	{
		function GetAdminListClass()
		{
			return 'StageShowPlusPerformancesAdminListClass';			
		}
		
		function OutputButton($buttonId, $buttonText, $buttonClass = "button-secondary")
		{
			parent::OutputButton($buttonId, $buttonText, $buttonClass);
			
			switch ($buttonId)
			{
				case "addperfbutton":
					// FUNCTIONALITY: Performances - StageShow+ - Add "Price Plan" select to new Performance button
					echo "<!-- Price Plan Select -->\n";
					$this->OutputPricePlanSelect('&nbsp; '.__('initialised as', $this->myDomain).' &nbsp;');
					break;
			}
		}
		
		function OutputPricePlanSelect($label = '')
		{
			$myDBaseObj  = $this->myDBaseObj;

			echo $label;
			
			$pricePlansList = $myDBaseObj->GetAllPlansList();
			
			echo '
			<select name="pricePlan">
			<option value="0" selected="selected">'.__('No Price Plan', $this->myDomain).'&nbsp;&nbsp;</option>
			';
			foreach ($pricePlansList as $pricePlan)
			{
				$planPlanRef = $pricePlan->planRef.'&nbsp;&nbsp;';
				$planID = $pricePlan->planID;
				echo "<option value=\"$planID\">$planPlanRef</option>\n";
			}
			echo '
			</select>
			';
		}
		
	}
}

?>