<?php
/* 
Description: Code for Managing Performances Configuration
 
Copyright 2011 Malcolm Shergold

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

//    function OutputContent_Performances()
    {
			global $stageShowObj;
			global $myPayPalAPILiveObj;
			global $myPayPalAPITestObj;
      
			$delperfId = 0;
			
      $columns = array(
		    'perfDateTime' => __('Date & Time', STAGESHOW_DOMAIN_NAME),
		    'perfRef' => __('Reference', STAGESHOW_DOMAIN_NAME),
		    'perfSeats' => __('Max Seats', STAGESHOW_DOMAIN_NAME),
		    'perfSales' => __('Tickets Sold', STAGESHOW_DOMAIN_NAME),
		    'perfDelete' => __(' ', STAGESHOW_DOMAIN_NAME)
	    );
	
			if ($stageShowDBaseObj->adminOptions['Dev_ShowDBIds'])
			{
				// Add the ID column
				$columns = array_merge(array('perfID' => __('ID', STAGESHOW_DOMAIN_NAME)), $columns); 
			}
			
      register_column_headers('sshow_perfs_list', $columns);	

			$perfsMsg = '';
				 
			echo '<div class="wrap">';
			if (isset($_POST['savebutton']))
			{
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
				
				// Save Settings Request ....
				$showID = $_POST['showID'];
				$results = $stageShowDBaseObj->GetPerformancesListByShowID($showID);
						
				// Verify that performance Refs are unique 
						
        if(count($results) > 0)
        {
	        foreach($results as $result)
					{
						$perfDateTime = stripslashes($_POST['perfDateTime'.$result->perfID]);
						if (isset($datesList[$perfDateTime]))
						{
							$perfsMsg = __('Duplicated Date Entry', STAGESHOW_DOMAIN_NAME).' ('.$perfDateTime.')';
							break;
						}
						$datesList[$perfDateTime] = true;

						$perfRef = stripslashes($_POST['perfRef'.$result->perfID]);
						if (isset($refsList[$perfRef]))
						{
							$perfsMsg = __('Duplicated Date Entry', STAGESHOW_DOMAIN_NAME).' ('.$perfRef.')';
							break;
						}
						$refsList[$perfRef] = true;
							
						// Verify that the date value is not empty
						if (strlen($perfDateTime) == 0)
						{
							$perfsMsg = __('Empty Date Entry', STAGESHOW_DOMAIN_NAME);
							break;
						}
							
						// Verify that the date value is valid
						if (strtotime($perfDateTime) == FALSE)
						{
							$perfsMsg = __('Invalid Date Entry', STAGESHOW_DOMAIN_NAME).' ('.$perfDateTime.')';
							break;
						}
					}
				}				
						
				if ($perfsMsg !== '')
				{
					echo '<div id="message" class="updated"><p>'.__('Settings have NOT been saved', STAGESHOW_DOMAIN_NAME).'. '.$perfsMsg.'</p></div>';
				}
        else 
				{
					$perfsList = array();
				
					if(count($results) > 0)
					{
						foreach($results as $result)
						{
							$perfUpdated = false;
							$newPerfDateTime = stripslashes($_POST['perfDateTime'.$result->perfID]);
							
							// Reformat date & time to correct for for MySQL
							$reformattedPerfDateTime = strftime('%Y-%m-%d %H:%M:%S', strtotime($newPerfDateTime));
							//if ($newPerfDateTime !== $reformattedPerfDateTime)
							//	echo "Reformatted $newPerfDateTime to $reformattedPerfDateTime <br>\n";
							$newPerfDateTime = $reformattedPerfDateTime;
							
							if ($newPerfDateTime != $result->perfDateTime)
							{
								$stageShowDBaseObj->UpdatePerformanceTime($result->perfID, $newPerfDateTime);
								$result->perfDateTime = $newPerfDateTime;
								$perfUpdated = true;
							}

							$newPerfRef = stripslashes($_POST['perfRef'.$result->perfID]);
							if ($newPerfRef != $result->perfRef)
							{
								$stageShowDBaseObj->UpdatePerformanceRef($result->perfID, $newPerfRef);
								$result->perfRef = $newPerfRef;
								$perfUpdated = true;
							}
							
							$newPerfSeats = stripslashes($_POST['perfSeats'.$result->perfID]);
							if ($newPerfSeats != $result->perfSeats)
							{
								$stageShowDBaseObj->UpdatePerformanceSeats($result->perfID, $newPerfSeats);
								$result->perfSeats = $newPerfSeats;
								$perfUpdated = true;
							}
								
							if ($perfUpdated)
								$perfsList[count($perfsList)] = $result;									
						} // End of foreach($results as $result)
						
						// Add this entry to the list of entries to be updated
						if (count($perfsList) > 0)
							$stageShowDBaseObj->UpdateCartButtons($perfsList);
					} 
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved', STAGESHOW_DOMAIN_NAME).'.</p></div>';
				}
			}			
			else if (isset($_POST['addperfbutton']) && isset($_POST['showID']))
			{
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
				
				$showID = $_POST['showID'];
				
				$statusMsg = '';
				$stageShowDBaseObj->CreateNewPerformance($statusMsg, $showID, date(STAGESHOW_DATETIME_MYSQL_FORMAT));				
				echo '<div id="message" class="updated"><p>'.$statusMsg.'.</p></div>';
			}			 
			else if (isset($_GET['action']))
			{
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
				
				$actionID = $_GET['action'];
				switch ($actionID)
				{
					case 'delete':
						$delperfId = $_GET['id']; 
						break;
						
					case 'activate':
					case 'deactivate':
						$perfId = $_GET['id']; 
						$stageShowDBaseObj->SetPerfActivated($perfId, $actionID);
						break;
				}
				
				$_GET['action'] = '';
			}

			if ($delperfId > 0)
			{
				// Don't delete if any tickets have been sold for this performance
				if (!$stageShowDBaseObj->CanDeletePerformance($perfSales, $delperfId))
				{
					echo '<div id="message" class="updated"><p>'.__('Performance cannot be deleted - Tickets already sold!', STAGESHOW_DOMAIN_NAME).'</p></div>';
				}
				else
				{
					$showID = $_GET['showID'];

					// Delete a performance
					
					// Delete all prices for this performance
					$stageShowDBaseObj->DeletePriceByPerfID($delperfId);
					
					// Get the performance entry
					$results = $stageShowDBaseObj->GetPerformancesListByPerfID($delperfId);
			
					// Delete any PayPal buttons ....
					$myPayPalAPITestObj->DeleteButton($results[0]->perfPayPalTESTButtonID);	
					$myPayPalAPILiveObj->DeleteButton($results[0]->perfPayPalLIVEButtonID);	
								
					// Delete a performance entry
					$stageShowDBaseObj->DeletePerformanceByPerfID($delperfId);

					// Delete 
					echo '<div id="message" class="updated"><p>'.__('Performance entry deleted', STAGESHOW_DOMAIN_NAME).'.</p></div>';
				}
			}

			// Stage Show Performances HTML Output - Start 
?>
    <div class="wrap">
      <div id="icon-stageshow" class="icon32"></div>
      <h2><?php echo $stageShowObj->pluginName.' - '.__('Performance Editor', STAGESHOW_DOMAIN_NAME); ?></h2>
<?php
$showLists = $stageShowDBaseObj->GetAllShowsList();
if (count($showLists) == 0)
{
	echo __('No Show Configured', STAGESHOW_DOMAIN_NAME)."<br>\n";
}
foreach ($showLists as $showList)
{
	$results = $stageShowDBaseObj->GetPerformancesListByShowID($showList->showID);
?>
<br></br>
<form method="post" action="admin.php?page=sshow_performances"> 
	<h3><?php echo($showList->showName); ?></h3>
<?php 
	if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename(__FILE__));
	if (count($results) == 0) 
	{ 
		echo __('Show has NO Performances', STAGESHOW_DOMAIN_NAME)."<br>\n";
	} 
	else 
	{ 
?>
	<table class="widefat" cellspacing="0">
	<thead>
		<tr>
			<?php print_column_headers('sshow_perfs_list'); ?>
		</tr>
	</thead>

	<tfoot>
		<tr>
			<?php print_column_headers('sshow_perfs_list', false); ?>
		</tr>
	</tfoot>
	<tbody>
<?php
// TODO-WISHLIST: Add date and time picker
		foreach($results as $result)
		{
			if (($perfsMsg !== '') && ($showList->showID == $showID))
			{
				// Get value(s) from form controls
				$perfDateTime = stripslashes($_POST['perfDateTime'.$result->perfID]);
				$perfRef = stripslashes($_POST['perfRef'.$result->perfID]);
				$perfSeats = stripslashes($_POST['perfSeats'.$result->perfID]);
			}
			else
			{
				// Get value(s) from database
				$perfDateTime = $result->perfDateTime;
				$perfRef = $result->perfRef;
				$perfSeats = $result->perfSeats;
			}
			
			$actionLinks = '';
			
			if ($stageShowDBaseObj->IsPerfActivated($result->perfID))
			{
				$actionId = 'deactivate';
				$actionText = __('Deactivate', STAGESHOW_DOMAIN_NAME);
			}
			else
			{
				$actionId = 'activate';
				$actionText = __('Activate', STAGESHOW_DOMAIN_NAME);
			}
			$actionLinks = 'admin.php?page=sshow_performances&action='.$actionId.'&id='.$result->perfID;
			$actionLinks = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($actionLinks, plugin_basename(__FILE__)) : $actionLinks;
			$actionLinks = '<a href="'.$actionLinks.'">'.$actionText.'</a>';
			echo "<!-- actionText=$actionText actionId=$actionId -->\n";
			
			// Performances can be deleted if there are no tickets sold or 24 hours after start date/time
			if ($stageShowDBaseObj->CanDeletePerformance($perfSales, $result->perfID, $perfDateTime))
			{
				$deleteLink = 'admin.php?page=sshow_performances&showID='.$showList->showID.'&action=delete&id='.$result->perfID;
				$deleteLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($deleteLink, plugin_basename(__FILE__)) : $deleteLink;
				$deleteLink = '<a href="'.$deleteLink.'" onclick="javascript:return confirmDelete(\''.$perfRef.' ('.$perfDateTime.')\')">Delete</a>';
				if ($actionLinks !== '') $actionLinks .= ', ';
				$actionLinks .= $deleteLink;
			}
			
			if ($actionLinks === '')
				$actionLinks = '&nbsp';
			
			if ($perfSales > 0)
			{
				$perfSalesLink = 'admin.php?page=sshow_sales&action=perf&id='.$result->perfID;
				$perfSalesLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($perfSalesLink, str_replace('performances.php', 'sales.php', plugin_basename(__FILE__))) : $perfSalesLink;
				$perfSalesLink = '<a href="'.$perfSalesLink.'">'.$perfSales.'</a>';
			}
			else
				$perfSalesLink = '0';
			
			echo '<tr>';
			if ($stageShowDBaseObj->adminOptions['Dev_ShowDBIds'])
				echo '<td>'.$result->perfID.'</td>';
			echo '
				<td><input name=perfDateTime'.$result->perfID.' type=text maxlength=28 size=29 style="text-align: center" value="'.$perfDateTime.'" /></td>
				<td><input name=perfRef'.$result->perfID.' type=text maxlength='.STAGESHOW_PERFREF_TEXTLEN.' size='.STAGESHOW_PERFREF_TEXTLEN.' style="text-align: center" value="'.$perfRef.'" /></td>
				<td><input name=perfSeats'.$result->perfID.' type=text maxlength=4 size=4 style="text-align: center" value="'.$perfSeats.'" /></td>
				<td>'.$perfSalesLink.'</td>
				<td style="background-color:#FFF">
				'.$actionLinks.'
				</td>
				</tr>';
		}	// End of foreach($results as $result)
				
?>
</tbody>
    </table>
<?php
	} // End of if (count($results) == 0) ... else ...
?>
      <br></br>
      <input type="hidden" name="showID" value="<?php echo $showList->showID; ?>"/>
<?php if ($stageShowDBaseObj->CanAddPerformance()) { ?>
			<input class="button-secondary" type="submit" name="addperfbutton" value="<?php _e('Add New Performance', STAGESHOW_DOMAIN_NAME) ?>"/>
<?php } ?>						
<?php
	if(count($results) > 0)
	{
		echo '<input class="button-primary" type="submit" name="savebutton" value="'.__('Save Settings', STAGESHOW_DOMAIN_NAME).'"/>';
	}
?>
</form>
<?php
} // End of foreach ($showLists as $showList) ..
?>
</div>
<?php
			// Stage Show Performances HTML Output - End 
		}				 
?>