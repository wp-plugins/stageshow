<?php
/* 
Description: Code for Overview Page
 
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
			global $stageShowObj;
			global $stageShowDBaseObj;
      
      $columns = array(
		    'showName' => __('Show', STAGESHOW_DOMAIN_NAME),
		    'perfCount' => __('Performances', STAGESHOW_DOMAIN_NAME),
		    'showSales' => __('Tickets Sold', STAGESHOW_DOMAIN_NAME)
	    );
	
			if ($stageShowDBaseObj->adminOptions['Dev_ShowDBIds'])
			{
				// Add the ID column
				$columns = array_merge(array('showID' => __('ID', STAGESHOW_DOMAIN_NAME)), $columns); 
			}
			
      register_column_headers('sshow_overview_list', $columns);	

			echo '<div class="wrap">';

			if(isset($_POST['createsample']))
			{
        $this->CreateSample();
			}

			// Stage Show Overview HTML Output - Start 
?>
		<div class="wrap">
			<div id="icon-stageshow" class="icon32"></div>
			<h2><?php echo $stageShowObj->pluginName.' - '.__('Overview', STAGESHOW_DOMAIN_NAME); ?></h2>
			<br></br>
			<form method="post" action="admin.php?page=sshow_adminmenu">
						<?php
if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename(__FILE__));
$results = $stageShowDBaseObj->GetAllShowsList();
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
          <?php print_column_headers('sshow_overview_list'); ?>
        </tr>
      </thead>

      <tfoot>
        <tr>
          <?php print_column_headers('sshow_overview_list', false); ?>
        </tr>
      </tfoot>
      <tbody>
        <?php
	foreach($results as $result)
	{
		// For each show .... find the number of performances
		$results2 = $stageShowDBaseObj->GetPerformancesListByShowID($result->showID);
		$showSales = $stageShowDBaseObj->GetSalesQtyByShowID($result->showID);

		echo '<tr>';
		if ($stageShowDBaseObj->adminOptions['Dev_ShowDBIds'])
			echo '<td>'.$result->showID.'</td>';
		echo '
		<td>'.$result->showName.'</td>
		<td>'.count($results2).'</td>
		<td>'.$showSales.'</td>
		</tr>';
	}				
}
?>
      </tbody>
    </table>
      <br></br>
<?php
if(count($results) == 0)
{
	echo '<input class="button-primary" type="submit" name="createsample" value="'.__('Create Sample', STAGESHOW_DOMAIN_NAME).'"/>';
}
?>
    </form>
</div>

<?php
        // Stage Show Overview HTML Output - End
		}
?>