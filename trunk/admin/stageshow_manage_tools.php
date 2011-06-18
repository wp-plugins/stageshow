<?php
/* 
Description: Code for Admin Tools
 
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

	global $stageShowObj;
	$actionURL=get_site_url().'/wp-content/plugins/stageshow/admin/stageshow_Export.php';
		
	$DeleteOrphans = ($stageShowDBaseObj->adminOptions['DeleteOrphans'] == true); 
	if(isset($_POST['flushsalesbutton']))
	{
		global $stageShowDBaseObj;
			
		check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
				
		$stageShowDBaseObj->DeleteOrphanedSales();
	}
	
?>

<div class="wrap">
	<div id="icon-stageshow" class="icon32"></div>
	<h2><?php echo $stageShowObj->pluginName.' - '.__('Tools', STAGESHOW_DOMAIN_NAME); ?></h2>
<h3><?php _e('Export Data'); ?></h3>
<p><?php _e('Export Configuration and Ticket Sales to a "TAB Separated Text" format file on your computer.'); ?></p>
<p><?php _e('This format can be imported to many applications including spreadsheets and databases.'); ?></p>
<form action="<?php echo $actionURL; ?>" method="get">
<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename(__FILE__)); ?>
<table class="form-table">
<tr>
<th><label for="sshow_ex_type"><?php _e('Export'); ?></label></th>
<td>
<select name="sshow_ex_type">
	<option value="all"><?php _e('Settings and Tickets'); ?>&nbsp;&nbsp;</option>
	<option value="settings"><?php _e('Settings Only'); ?> </option>
	<option value="tickets" selected="selected"><?php _e('Tickets Only'); ?> </option>
</select>
</td>
</tr>
<!--	
		<tr valign="top">
      <td><?php _e('Orphaned Records', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td>
				<input type="checkbox" name="sshow_delete_orphans" value="1" <?php if ($DeleteOrphans) echo "checked=1"; ?> />
				<?php _e('Delete Orphaned Records after Export') ?>
       </td>
		</tr>
-->		
</table>
<p>
<p class="submit">
<input type="submit" name="submit" class="button" value="<?php esc_attr_e('Download Export File'); ?>" />
<input type="hidden" name="page" value="sshow_tools" />
<input type="hidden" name="download" value="true" />
</p>
</form>
</div>

<h3><?php _e('Sales Records'); ?></h3>
<p><?php _e('Sales records are not deleted when shows or performances are deleted.'); ?></p>
<p><?php _e('Individual sales records can be deleted on the sales page. All sales records for sales where the corresponding show or performance has been removed can be deleted by clicking the button below.'); ?></p>
<form method="post" action="admin.php?page=sshow_tools">
	<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename(__FILE__)); ?>
<p>
<p class="submit">
<input class="button-secondary" type="submit" name="flushsalesbutton" value="<?php _e('Flush Sales Records', STAGESHOW_DOMAIN_NAME) ?>" onclick="javascript:return confirmDelete('Orphaned Sales Records')"/>
</p>
</form>
</div>

<?php

function blod()
{
}
?>