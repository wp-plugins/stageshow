<?php
/* 
Description: Code for Managing Prices Configuration
 
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

include STAGESHOW_INCLUDE_PATH.'mjslib_table.php';

if (!class_exists('StageShowPricesAdminListClass')) 
{
	class StageShowPricesAdminListClass extends MJSLibAdminListClass // Define class
	{		
		var $updateFailed;
		
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env, true);
			
			$this->bulkActions = array(
				'delete' => __('Delete', STAGESHOW_DOMAIN_NAME),
				);
		}
		
		function GetTableID($result)
		{
			return "showtab".$result->showID;;
		}
		
		function GetRecordID($result)
		{
			return $result->priceID;
		}
		
		function GetMainRowsDefinition()
		{
			return array(
				array('Label' => 'Performance',  'Id' => 'perfID',    'Type' => MJSLibTableClass::TABLEENTRY_SELECT, 'Func' => 'PerfDates'),
				array('Label' => 'Type',         'Id' => 'priceType', 'Type' => MJSLibTableClass::TABLEENTRY_TEXT,   'Len' => STAGESHOW_PRICETYPE_TEXTLEN, ),						
				array('Label' => 'Price',        'Id' => 'priceValue','Type' => MJSLibTableClass::TABLEENTRY_TEXT,   'Len' => 9, ),						
			);
		}
		
		function PerfDates($result)
		{
			$perfDatesList = array();
			$perfsLists = $this->myDBaseObj->GetPerformancesListByShowID($result->showID);
			foreach($perfsLists as $perfsEntry)
			{
				$perfDatesList[$perfsEntry->perfID] = $perfsEntry->perfDateTime; // .'|' . $result->perfID;				
			}
			
			return $perfDatesList;
		}
		
		function OutputList($results, $updateFailed)
		{
			$this->updateFailed = $updateFailed;
			parent::OutputList($results);
		}
	}
}

include STAGESHOW_INCLUDE_PATH.'mjslib_admin.php';      

if (!class_exists('StageShowPricesAdminClass')) 
{
	class StageShowPricesAdminClass extends MJSLibAdminClass // Define class
	{
		function __construct($env) //constructor	
    {
			// Call base constructor
			parent::__construct($env);
			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;
      
			$delpriceId = 0;

			$pricesMsg = '';
			$showID = 0;
			
			echo '<div class="wrap">';
			if (isset($_POST['savepricebutton']))
			{
				$this->CheckAdminReferer();
				
				$showID = $_POST['showID'];
				$results = $myDBaseObj->GetPricesListByShowID($showID);

				// Verify that Price Types are unique for each performance				
				
        if(count($results) > 0)
        {
	        foreach($results as $result)
					{
							$newPerfID = $_POST['perfID'.$result->priceID];
							$newPriceType = stripslashes($_POST['priceType'.$result->priceID]);
							$newPriceValue = stripslashes($_POST['priceValue'.$result->priceID]);
							
							// Generate an entry that consists of the PerformanceID and the Price Type
							$priceEntry = $newPerfID.'-'.$newPriceType;
							if (isset($entriesList[$priceEntry]))
							{
								// Convert the perfID to a Performance Date & Time to display to the user
								$perfsList = $myDBaseObj->GetPerformancesListByPerfID($newPerfID);
								
								$pricesMsg = __('Duplicated Price Entry', STAGESHOW_DOMAIN_NAME).' ('.$perfsList[0]->perfDateTime.' - '.$newPriceType.')';
								break;
							}
							
							// Verify that the price value is not empty
							if (strlen($newPriceValue) == 0)
							{
								$pricesMsg = __('Price Not Specified', STAGESHOW_DOMAIN_NAME).' ('.$perfsList[0]->perfDateTime.' - '.$newPriceType.')';
								break;
							}
							
							// Verify that the price value is a numeric value
							if (!is_numeric($newPriceValue))
							{
								$pricesMsg = __('Invalid Price Entry', STAGESHOW_DOMAIN_NAME).' ('.$newPriceValue.')';
								break;
							}
							
							// Verify that the price value is non-zero
							if ($newPriceValue == 0.0)
							{
								$pricesMsg = __('Price Entry cannot be zero', STAGESHOW_DOMAIN_NAME);
								break;
							}
							
							$entriesList[$priceEntry] = true;
					}
				}
				
				if ($pricesMsg !== '')
				{
					echo '<div id="message" class="error"><p>'.__('Settings have NOT been saved', STAGESHOW_DOMAIN_NAME).'. '.$pricesMsg.'</p></div>';
				}
        else 
				{
					$perfsList = array();
				
					if(count($results) > 0)
					{
						foreach($results as $result)
						{
								$pricesUpdated = false;
								
								$newPerfID = $_POST['perfID'.$result->priceID];
								if ($newPerfID != $result->perfID)
								{
									$myDBaseObj->UpdatePricePerfID($result->priceID, $newPerfID);
									$result->perfID = $newPerfID;
									$pricesUpdated = true;
								}
								
								$newPriceType = stripslashes($_POST['priceType'.$result->priceID]);
								if ($newPriceType != $result->priceType)
								{
									$myDBaseObj->UpdatePriceType($result->priceID, $newPriceType);
									$result->priceType = $newPriceType;
									$pricesUpdated = true;
								}

								$newPriceValue = stripslashes($_POST['priceValue'.$result->priceID]);
								if ($newPriceValue != $result->priceValue)
								{
									$myDBaseObj->UpdatePriceValue($result->priceID, $newPriceValue);
									$result->priceValue = $newPriceValue;
									$pricesUpdated = true;
								}
								
								if ($pricesUpdated)
									$perfsList[count($perfsList)] = $result;
									
						} // End foreach
						
						if (count($perfsList) > 0)
							$myDBaseObj->UpdateCartButtons($perfsList);
					}
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved', STAGESHOW_DOMAIN_NAME).'</p></div>';
				}
			}			
			else if(isset($_POST['addpricebutton']))
			{
				$this->CheckAdminReferer();
				
				$showID = $_POST['showID'];
				
				// Performance ID of first performance is passed with call - Type ID is null ... AddPrice() will add (unique) value
				$perfID = $_POST['perfID'];
				$myDBaseObj->AddPrice($perfID, '', '0.00');
				
				echo '<div id="message" class="updated"><p>'.__('Settings have been saved', STAGESHOW_DOMAIN_NAME).'</p></div>';
			}
			
			$this->Output_MainPage($env, $pricesMsg !== '');
		}	
		
		function Output_MainPage($env, $updateFailed)
		{			 			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;
			
			// Stage Show Prices HTML Output - Start 
?>
<script type="text/javascript" src="<?php echo STAGESHOW_URL.'js/stageshow.js'; ?>"></script>
<div class="wrap">
  <div id="icon-stageshow" class="icon32"></div>
	<h2><?php echo $myPluginObj->pluginName.' - '.__('Prices Editor', STAGESHOW_DOMAIN_NAME); ?></h2>
<?php
$showLists = $myDBaseObj->GetAllShowsList();
if (count($showLists) == 0)
{
	if ($myDBaseObj->CheckIsConfigured())
	{
		$showsPageURL = get_option('siteurl').'/wp-admin/admin.php?page='.STAGESHOW_MENUPAGE_SHOWS;
		echo "<div class='error'><p>".__('No Show Configured', STAGESHOW_DOMAIN_NAME).' - <a href='.$showsPageURL.'>'.__('Add one Here', STAGESHOW_DOMAIN_NAME).'</a>'."</p></div>\n";
	}
}
foreach ($showLists as $showList)
{
	$perfsLists = $myDBaseObj->GetPerformancesListByShowID($showList->showID);
?>
	<div class="stageshow-admin-form">
	<form method="post" action="admin.php?page=<?php echo STAGESHOW_MENUPAGE_PRICES; ?>">
<?php 
$this->WPNonceField();
if (count($perfsLists) == 0)
{ 
	$showsPageURL = get_option('siteurl').'/wp-admin/admin.php?page='.STAGESHOW_MENUPAGE_PERFORMANCES;
	$showsPageMsg = $showList->showName.' '.__('has NO Performances', STAGESHOW_DOMAIN_NAME).' - <a href='.$showsPageURL.'>'.__('Add one Here', STAGESHOW_DOMAIN_NAME).'</a>';
?> 
	<div class='error'><p><?php echo $showsPageMsg; ?></p></div>
<?php 
} 
else 
{ 
?>
		<h3><?php echo($showList->showName); ?></h3>
<?php 
$results = $myDBaseObj->GetPricesListByShowID($showList->showID);
if(count($results) == 0)
{
	echo "<div class='noconfig'>".__('Show has NO Prices', STAGESHOW_DOMAIN_NAME)."</div>\n";
}
else
{
	$showsList = new StageShowPricesAdminListClass($env);		
	$showsList->OutputList($results, $updateFailed);	
} // if(count($results) > 0) ....
?>
      <input type="hidden" name="showID" value="<?php echo $showList->showID; ?>"/>
      <input type="hidden" name="perfID" value="<?php echo $perfsLists[0]->perfID; ?>"/>
      <input class="button-secondary" type="submit" name="addpricebutton" value="<?php _e('Add New Price', STAGESHOW_DOMAIN_NAME) ?>"/>
<?php 
	if(count($results) > 0)
		$myDBaseObj->OutputButton("savepricebutton", "Save Changes", "button-primary");
} 
?>
		</form>
		</div>
<?php
}
?>
</div>

<?php
			// Stage Show Prices HTML Output - End 
		}				 
		
		function DoBulkPreAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if (!isset($this->errorCount)) $this->errorCount = 0;
			if (!isset($this->blockCount)) $this->blockCount = 0;
						
			switch ($bulkAction)
			{
				case 'delete':		
					// Don't delete if any tickets have been sold for this performance
					$results = $myDBaseObj->GetSalesListByPriceID($recordId);
					if (count($results) > 0)
						$this->blockCount++;
					return ( ($this->errorCount > 0) || ($this->blockCount > 0) );
			}
				
			return false;
		}
		
		function DoBulkAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			switch ($bulkAction)
			{
				case 'delete':		
					// Now delete the entry in the PRICES table
					$delShowName = $myDBaseObj->DeletePriceByPriceID($recordId);
					return true;
			}
				
			return false;
		}
		
		function GetBulkActionMsg($bulkAction, $actionCount)
		{
			$actionMsg = '';
			
			switch ($bulkAction)
			{
				case 'delete':		
					if ($this->errorCount > 0)
						$actionMsg = ($this->errorCount == 1) ? __("1 Price has a Database Error", $this->pluginName) : $errorCount.' '.__("Prices have a Database Error", $this->pluginName); 
					else if ($this->blockCount > 0)
						$actionMsg = ($this->blockCount == 1) ? __("1 Price cannot be deleted - Tickets already sold!", $this->pluginName) : $this->blockCount.' '.__("Prices cannot be deleted - Tickets already sold!", $this->pluginName); 
					else if ($actionCount > 0)		
						$actionMsg = ($actionCount == 1) ? __("1 Price has been deleted", $this->pluginName) : $actionCount.' '.__("Prices have been deleted", $this->pluginName); 
					else
						$actionMsg = __("Nothing to Delete", $this->pluginName);
					break;
			}
			
			return $actionMsg;
		}
		
	}
}

?>