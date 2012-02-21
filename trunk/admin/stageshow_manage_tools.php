<?php
/* 
Description: Code for Admin Tools
 
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

include STAGESHOW_INCLUDE_PATH.'mjslib_admin.php';      

if (!class_exists('StageShowToolsAdminClass')) 
{
	class StageShowToolsAdminClass extends MJSLibAdminClass // Define class
	{
		var $actionURL;
		
		function __construct($env) //constructor	
		{
			// Call base constructor
			parent::__construct($env);
			
			$myPluginObj = $this->myPluginObj;
			
			$this->actionURL = get_site_url().'/wp-content/plugins/stageshow/admin/stageshow_Export.php';
				
?>			
<div class="wrap">
	<div id="icon-stageshow" class="icon32"></div>
	<h2><?php echo $myPluginObj->pluginName.' - '.__('Tools', STAGESHOW_DOMAIN_NAME); ?></h2>
<?php
			$this->Tools_Validate($env);
			$this->Tools_Export();
			$this->Tools_FlushSalesRecords();
?>			
	</div>
</div>
<?php
			}

			function Output_Javascript_SetFocus($elementId)
			{
?>
<script type="text/javascript">
	<!--
  
	function setInitialFocus()
	{
     document.getElementById("<?php echo $elementId ?>").focus();
	}
	window.onload = setInitialFocus;
						
// -->
</script>
<?php
			}

			function Tools_Validate($env)
			{
				$myDBaseObj = $this->myDBaseObj;

				$TxnId = '';
				
				$this->Output_Javascript_SetFocus("TxnId");
							
			?>

<h3><?php _e('Validate Sale'); ?></h3>
<form method="post" action="admin.php?page=stageshow_tools">
<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename(__FILE__)); ?>
<table class="form-table">
	<tr>
		<th><label for="sshow_ex_type"><?php _e('Transaction ID'); ?></label></th>
		<td>
			<input type="text" maxlength="<?php echo PAYPAL_APILIB_PPSALETXNID_TEXTLEN; ?>" size="<?php echo PAYPAL_APILIB_PPSALETXNID_TEXTLEN; ?>" name="TxnId" id="TxnId" value="<?php echo $TxnId; ?>" autocomplete="off" />
			</td>
	</tr>
<?php
				if(isset($_POST['validatesalebutton']))
				{
					check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()

					$TxnId = stripslashes($_POST['TxnId']);
					
					if (strlen($TxnId) > 0)
					{
						echo '<tr><td colspan="2">Results - Transaction ID: '.$TxnId.'</tr></td>'."\n";

						$results = $myDBaseObj->GetAllSalesListBySaleTxnId($TxnId);
						
						if (count($results) > 0)
						{
							include STAGESHOW_INCLUDE_PATH.'stageshow_sales_table.php';

							$salesList = new StageShowAdminSaleDetailsListClass($env);		
							
							echo '<tr><td colspan="2">'."\n";
							$salesList->OutputList($results);	
							echo "</tr></td>\n";
						}
						else
						{
							echo '<tr><td colspan="2">No matching record found!</tr></td>'."\n";
						}
					}
				}
?>
				</table>
				<p>
<p class="submit">
<input class="button-secondary" type="submit" name="validatesalebutton" value="<?php _e('Validate', STAGESHOW_DOMAIN_NAME) ?>"/>
</p>
</form>
<?php
		}
		
		function Tools_Export()
		{
			$this->actionURL = get_site_url().'/wp-content/plugins/stageshow/admin/stageshow_Export.php';
				
?>

<h3><?php _e('Export Data'); ?></h3>
<p><?php _e('Export Configuration and Ticket Sales to a "TAB Separated Text" format file on your computer.'); ?></p>
<p><?php _e('This format can be imported to many applications including spreadsheets and databases.'); ?></p>
<form action="<?php echo $this->actionURL; ?>" method="get">
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
</table>
<p>
<p class="submit">
<input type="submit" name="submit" class="button" value="<?php esc_attr_e('Download Export File'); ?>" />
<input type="hidden" name="page" value="stageshow_tools" />
<input type="hidden" name="download" value="true" />
</p>
</form>
<?php
		}
		
		function Tools_FlushSalesRecords()
		{			
			$myDBaseObj = $this->myDBaseObj;
			$DeleteOrphans = ($myDBaseObj->adminOptions['DeleteOrphans'] == true); 
			if(isset($_POST['flushsalesbutton']))
			{
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
						
				$myDBaseObj->DeleteOrphanedSales();
			}
	
?>
<h3><?php _e('Sales Records'); ?></h3>
<p><?php _e('Sales records are not deleted when shows or performances are deleted.'); ?></p>
<p><?php _e('Individual sales records can be deleted on the sales page. All sales records for sales where the corresponding show or performance has been removed can be deleted by clicking the button below.'); ?></p>
<form method="post" action="admin.php?page=stageshow_tools">
	<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename(__FILE__)); ?>
<p>
<p class="submit">
<input class="button-secondary" type="submit" name="flushsalesbutton" value="<?php _e('Flush Sales Records', STAGESHOW_DOMAIN_NAME) ?>" onclick="javascript:return confirmDelete('Orphaned Sales Records')"/>
</p>
</form>

<?php
		}
		
	}
}

?>