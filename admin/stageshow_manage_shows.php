<?php
/* 
Description: Code for Managing Show Configuration
 
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
		{
			global $myShowObj;
			global $myPayPalAPILiveObj;
			global $myPayPalAPITestObj;
      
			$delshowId = 0;
			
			if (isset($_GET['action']))
			{
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
				
				switch ($_GET['action'])
				{
					case 'delete':
						$delshowId = $_GET['id']; 
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
			
      register_column_headers('sshow_shows_list', $columns);	

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
					echo '<div id="message" class="updated"><p>'.__('Settings have NOT been saved', STAGESHOW_DOMAIN_NAME).'. '.$showsMsg.'</p></div>';
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
						}
					}
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved', STAGESHOW_DOMAIN_NAME).'.</p></div>';
				}
			}
      
			if (isset($_POST['addshowbutton']))
			{
				// Add Show with unique Show Name 
				$showID = $myDBaseObj->AddShow('New Show');
				
				if ($showID == 0)
					echo '<div id="message" class="updated"><p>'.__('Cannot add a new show - Only one show allowed', STAGESHOW_DOMAIN_NAME).'.</p></div>';
				else
					echo '<div id="message" class="updated"><p>'.__('Default entry added - Edit and Save to update it.', STAGESHOW_DOMAIN_NAME).'.</p></div>';
			}

			if ($delshowId > 0)
			{
				// Don't delete if show still pending and any tickets have been sold for this show
				if (!$myDBaseObj->CanDeleteShow($showSales, $delshowId))
				{
					echo '<div id="message" class="updated"><p>'.__('Show cannot be deleted - Tickets already sold!', STAGESHOW_DOMAIN_NAME).'</p></div>';
				}
				else
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
						$myPayPalAPITestObj->DeleteButton($result->perfPayPalTESTButtonID);	
						$myPayPalAPILiveObj->DeleteButton($result->perfPayPalLIVEButtonID);	
								
						// Delete a performances entry
						$myDBaseObj->DeletePerformanceByPerfID($delperfId);
					}			
					
					// Now delete the entry in the SHOWS table
					$delShowName = $myDBaseObj->DeleteShowByShowID($delshowId);
					
					echo '<div id="message" class="updated"><p>'.$delShowName.' '.__('deleted', STAGESHOW_DOMAIN_NAME).'.</p></div>';
				}
			}

?>
				<div class="wrap">
					<div id="icon-stageshow" class="icon32"></div>
					<h2><?php echo $myShowObj->pluginName.' - '.__('Show Editor', STAGESHOW_DOMAIN_NAME); ?></h2>
					<br></br>
					<form method="post" action="admin.php?page=sshow_shows">
<?php
if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename(__FILE__));
$results = $myDBaseObj->GetAllShowsList();
if(count($results) == 0)
{
	echo __('No Show Configured', STAGESHOW_DOMAIN_NAME)."<br>\n";
}
else
{
?>						
								<table class="widefat" cellspacing="0">
									<thead>
										<tr>
											<?php print_column_headers('sshow_shows_list'); ?>
										</tr>
									</thead>

									<tfoot>
										<tr>
											<?php print_column_headers('sshow_shows_list', false); ?>
										</tr>
									</tfoot>
									<tbody>
										<?php
	foreach($results as $result)
	{
		if ($showsMsg !== '')
		{
			// Get value(s) from form controls
			$showName = stripslashes($_POST['showName'.$result->showID]);
		}
		else
		{
			// Get value(s) from database
			$showName = $result->showName;
		}
		
		if ($myDBaseObj->CanDeleteShow($showSales, $result->showID))
		{
			$deleteLink = 'admin.php?page=sshow_shows&action=delete&id='.$result->showID;
			$deleteLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($deleteLink, plugin_basename(__FILE__)) : $deleteLink;
			$deleteLink = '<a href="'.$deleteLink.'" onclick="javascript:return confirmDelete(\''.$showName.'\')">Delete</a>';
		}
		else
			$deleteLink = '&nbsp';
		
		if ($showSales > 0)
		{
			$showSalesLink = 'admin.php?page=sshow_sales&action=show&id='.$result->showID;
			$showSalesLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($showSalesLink, str_replace('shows.php', 'sales.php', plugin_basename(__FILE__))) : $showSalesLink;
			$showSalesLink = '<a href="'.$showSalesLink.'">'.$showSales.'</a>';
		}
		else
			$showSalesLink = '0';
			
		echo '<tr>';
		if ($myDBaseObj->adminOptions['Dev_ShowDBIds'])
			echo '<td>'.$result->showID.'</td>';
		echo '
	<td><input name=showName'.$result->showID.' type=text maxlength='.STAGESHOW_SHOWNAME_TEXTLEN.' size=50 value="'.$showName.'" /></td>
	<td>'.$showSalesLink.'</td>	
  <td style="background-color:#FFF">
	'.$deleteLink.'
	</td>
	</tr>';
	}				
}
?>
      </tbody>
    </table>
      <br></br>
<?php if ($myDBaseObj->CanAddShow()) { ?>						
      <input class="button-secondary" type="submit" name="addshowbutton" value="<?php _e('Add New Show', STAGESHOW_DOMAIN_NAME) ?>"/>
<?php } ?>						
<?php
	if(count($results) > 0)
	{
		echo '<input class="button-primary" type="submit" name="saveshowbutton" value="'.__('Save Settings', STAGESHOW_DOMAIN_NAME).'"/>';
	}
?>
		</form>
</div>

<?php
    }
?>