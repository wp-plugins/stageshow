<?php

// Include wp-config.php - This will include wp settings and plugins ...
include '../../../wp-config.php';

include 'include/mjslib_NotifyURL.php';
	
global $stageShowObj;
$myDBaseObj = $stageShowObj->myDBaseObj;
new NotifyURLClass($myDBaseObj);	

?>
