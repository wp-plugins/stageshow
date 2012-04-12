<?php
/* 
Description: Code for Managing Show Configuration
 
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

if (!class_exists('StageShowAdminShowsListClass')) 
{
	class StageShowAdminShowsListClass extends MJSLibAdminListClass // Define class
	{		
		var $updateFailed;
		
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env);
			
			$myDBaseObj = $this->myDBaseObj;
			
			$this->showDBIds = $myDBaseObj->adminOptions['Dev_ShowDBIds'];					

			$this->SetRowsPerPage($myDBaseObj->adminOptions['PageLength']);
			$this->hasHiddenRows = $myDBaseObj->HasHiddenRows();
			
			$this->bulkActions = array(
				'activate' => __('Activate/Deactivate', STAGESHOW_DOMAIN_NAME),
				'delete'   => __('Delete', STAGESHOW_DOMAIN_NAME),
				);

			$columns = array(
		    'showName'  => __('Show Name', STAGESHOW_DOMAIN_NAME),
		    'showSales' => __('Tickets Sold', STAGESHOW_DOMAIN_NAME),
		    'showState' => __('State', STAGESHOW_DOMAIN_NAME)
				); 
				
			$this->SetListHeaders('stageshow_sales_list', $columns);
		}
		
		function GetTableID($result)
		{
			return "showtab";
		}
		
		function GetRecordID($result)
		{
			return $result->showID;
		}
		
		function OutputList($results, $updateFailed)
		{
			$this->updateFailed = $updateFailed;
			parent::OutputList($results);
		}
		
		function AddResult($result)
		{
			$myDBaseObj = $this->myDBaseObj;

			if ($this->updateFailed)
			{
				// Get value(s) from form controls
				$showName = stripslashes($_POST['showName'.$result->showID]);
			}
			else
			{
				// Get value(s) from database
				$showName = $result->showName;
			}
			$showSales = $result->totalQty;
				
			$showState = $myDBaseObj->IsStateActive($result->showState) ? __("Active", STAGESHOW_DOMAIN_NAME) : __("INACTIVE", STAGESHOW_DOMAIN_NAME);
			
			$linkBackURL = 'admin.php?page=stageshow_shows';
			$linkBackURL = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($linkBackURL, plugin_basename($this->caller)) : $linkBackURL;

			$editNoteLink = $linkBackURL.'&action=note&id='.$result->showID;
			
			if ($showSales > 0)
			{
				$showSalesLink = 'admin.php?page=stageshow_sales&action=show&id='.$result->showID;
				$showSalesLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($showSalesLink, plugin_basename($this->caller)) : $showSalesLink;
				$showSalesLink = '<a href="'.$showSalesLink.'">'.$showSales.'</a>';
			}
			else
				$showSalesLink = '0';
				
			$this->NewRow($result);
			
			$this->AddInputToTable($result, 'showName', STAGESHOW_SHOWNAME_TEXTLEN, $showName);
			$this->AddToTable($result, $showSalesLink);
			$this->AddToTable($result, $showState);
		}		
	}
}

include STAGESHOW_INCLUDE_PATH.'mjslib_admin.php';      

if (!class_exists('StageShowShowsAdminClass')) 
{
	class StageShowShowsAdminClass extends MJSLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			// Call base constructor
			parent::__construct($env);
			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
      
			if (isset($_GET['action']))
			{
				check_admin_referer(plugin_basename($this->caller)); // check nonce created by wp_nonce_field()
				
				$actionID = $_GET['action'];
				switch ($actionID)
				{
					case 'note':
						$noteID = $_GET['id']; 
						break;
				}
			}

      $columns = array(
		    'showName' => __('Show Name', STAGESHOW_DOMAIN_NAME),
		    'showSales' => __('Tickets Sold', STAGESHOW_DOMAIN_NAME),
		    'showDelete' => __(' ', STAGESHOW_DOMAIN_NAME)
	    );
	
			if ($myDBaseObj->adminOptions['Dev_ShowDBIds'])
			{
				// Add the ID column
				$columns = array_merge(array('showID' => __('ID', STAGESHOW_DOMAIN_NAME)), $columns); 
			}
			
			$showsMsg = '';
				 
			echo '<div class="wrap">';
			if (isset($_POST['saveshowbutton']))
			{
				// Save Settings Request ....
				$results = $myDBaseObj->GetAllShowsList();
						
				// Verify that show names are unique 				
        if(count($results) > 0)
        {
	        foreach($results as $result)
					{
							$showEntry = stripslashes($_POST['showName'.$result->showID]);
							if (isset($entriesList[$showEntry]))
							{
								$showsMsg = __('Duplicated Show Name', STAGESHOW_DOMAIN_NAME).' ('.$showEntry.')';
								break;
							}
							$entriesList[$showEntry] = true;
					}
				}				
				
				if ($showsMsg !== '')
				{
					echo '<div id="message" class="error"><p>'.__('Settings have NOT been saved', STAGESHOW_DOMAIN_NAME).'. '.$showsMsg.'</p></div>';
				}
        else 
				{
					if (count($results) > 0)
					{
						foreach($results as $result)
						{
							$newShowName = stripslashes($_POST['showName'.$result->showID]);
							if ($newShowName != $result->showName)
							{
								$myDBaseObj->UpdateShowName($result->showID, $newShowName);
							}
				
							// Save option extensions
							$myDBaseObj->UpdateExtendedSettings($result, $result->showID);											
						}
					}
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved', STAGESHOW_DOMAIN_NAME).'.</p></div>';
				}
			}
      
			if (isset($_POST['addshowbutton']))
			{
				// Add Show with unique Show Name 
				$showID = $myDBaseObj->AddShow('');
				
				if ($showID == 0)
					echo '<div id="message" class="error"><p>'.__('Cannot add a new show - Only one show allowed', STAGESHOW_DOMAIN_NAME).'.</p></div>';
				else
					echo '<div id="message" class="updated"><p>'.__('Default entry added - Edit and Save to update it.', STAGESHOW_DOMAIN_NAME).'.</p></div>';
			}

			if ( isset( $_POST['action'] ) && (-1 != $_POST['action']) )
				$bulkAction = $_POST['action'];
			else if ( isset( $_POST['action2'] ) && (-1 != $_POST['action2']) )
				$bulkAction =  $_POST['action2'];
			else
				$bulkAction = '';
				
			if (($bulkAction !== '') && isset($_POST['rowSelect']))
			{
				// Bulk Action Apply button actions
				check_admin_referer(plugin_basename($this->caller)); // check nonce created by wp_nonce_field()
				
				$actionMsg = '';
				switch ($bulkAction)
				{
					case 'delete':		
						$errorCount = 0;
						$blockCount = 0;							
						foreach($_POST['rowSelect'] as $delshowId)
						{
							// Don't delete if any tickets have been sold for this performance
							$delShowEntry = $myDBaseObj->GetShowsList($delshowId);
							if (count($delShowEntry) == 0)
								$errorCount++;
							else if (!$myDBaseObj->CanDeleteShow($delShowEntry[0]))
								$blockCount++;
						}
						
						if (($errorCount > 0) || ($blockCount > 0))
						{
							if ($errorCount > 0)
							{
								$actionMsg = ($errorCount == 1) ? __("1 Show has a Database Error", $this->pluginName) : $errorCount.' '.__("Shows have a Database Error", $this->pluginName); 
								echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';
							}
							if ($blockCount > 0)
							{
								$actionMsg = ($blockCount == 1) ? __("1 Show cannot be deleted - Tickets already sold!", $this->pluginName) : $blockCount.' '.__("Shows cannot be deleted - Tickets already sold!", $this->pluginName); 
								echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';
							}
							break;
						}
						
						// Delete shows
						$actionCount = 0;
						foreach($_POST['rowSelect'] as $delshowId)
						{
							// Get a list of performances
							$results = $myDBaseObj->GetPerformancesListByShowID($delshowId);
						
							foreach($results as $result)
							{
								// Get ID of performance to delete
								$delperfId = $result->perfID;
									
								// Delete all prices for this performance
								$myDBaseObj->DeletePriceByPerfID($delperfId);
								
								// Delete any PayPal buttons ....
								$myDBaseObj->payPalAPIObj->DeleteButton($result->perfPayPalButtonID);	
										
								// Delete a performances entry
								$myDBaseObj->DeletePerformanceByPerfID($delperfId);
							}			
							
							// Now delete the entry in the SHOWS table
							$delShowName = $myDBaseObj->DeleteShowByShowID($delshowId);

							$actionCount++;
						}
						
						if ($actionCount > 0)
						{
							$actionMsg = ($actionCount == 1) ? __("1 Show has been deleted", $this->pluginName) : $actionCount.' '.__("Shows have been deleted", $this->pluginName); 
							echo '<div id="message" class="updated"><p>'.$actionMsg.'</p></div>';
						}
						else
						{
							$actionMsg = __("Nothing to Delete", $this->pluginName);
							echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';
						}
						break;
						
					case 'activate':
						$actionCount = 0;
						foreach($_POST['rowSelect'] as $showID)
						{
							$showEntry = $myDBaseObj->GetShowsList($showID);
							if ($myDBaseObj->IsStateActive($showEntry[0]->showState))
								$myDBaseObj->SetShowActivated($showID, 'deactivate');
							else
								$myDBaseObj->SetShowActivated($showID, 'activate');
						}
						break;
				}
			}

?>
				<div class="wrap">
					<div id="icon-stageshow" class="icon32"></div>
					<h2><?php echo $myPluginObj->pluginName.' - '.__('Show Editor', STAGESHOW_DOMAIN_NAME); ?></h2>
					<form method="post" action="admin.php?page=stageshow_shows">
<?php
	if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename($this->caller));

	if (isset($noteID))
	{
		$results = $myDBaseObj->GetShowsList($noteID);
		echo '<div class="stageshow-noteform">';	
		if(count($results) > 0)
		{
			$result = $results[0];
?>						
      <input type="hidden" name="id" value="<?php echo $result->showID; ?>"/>
				<table>
					<tr>
						<td><?php _e('Show', STAGESHOW_DOMAIN_NAME); ?></td>
						<td><?php echo $result->showName ?></td>
					</tr>
				</table>
				
				<br></br>
				<input class="button-primary" type="submit" name="savenotebutton" value="<?php _e('Save Settings', STAGESHOW_DOMAIN_NAME); ?>">
				&nbsp;
<?php				
		}
?>				
				<input class="button-secondary" type="submit" name="backtoshows" value="<?php _e('Back to Shows Summary', STAGESHOW_DOMAIN_NAME); ?>">
				<br></br>
			</div>
<?php
			}
			else
			{
				$this->Output_MainPage($env, $showsMsg !== '');
			}
		}

		function Output_MainPage($env, $updateFailed)	
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if (!$myDBaseObj->CheckIsConfigured())
				return;

			$results = $myDBaseObj->GetAllShowsList();
			if(count($results) == 0)
			{
				echo "<div class='noconfig'>".__('No Show Configured', STAGESHOW_DOMAIN_NAME)."</div>\n";
			}
			else
			{
				$showsList = new StageShowAdminShowsListClass($env);		
				$showsList->OutputList($results, $updateFailed);	
			}
			
			if ($myDBaseObj->CanAddShow()) 
				$myDBaseObj->OutputButton("addpricebutton", "Add New Price");
				
			if(count($results) > 0)
				$myDBaseObj->OutputButton("saveshowbutton", "Save Changes", "button-primary");
?>
		</form>
</div>

<?php
		} // End of function Output_MainPage()
	}
}

?>