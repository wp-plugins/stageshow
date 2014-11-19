<?php
/* 
Description: Code for Automatic Upload Version Notification 
*/
	
error_reporting(E_ALL);
ini_set("display_errors", 1);
	
if (file_exists('wp-content\plugins\StageShowGold\stageshow_defs.php'))
{
	include 'wp-content\plugins\StageShowGold\include\stageshowgold_direct_validate.php';
}

if (file_exists('wp-content\plugins\StageShowPlus\stageshow_defs.php'))
{
	include 'wp-content\plugins\StageShowPlus\include\stageshowplus_direct_validate.php';
}

?>

