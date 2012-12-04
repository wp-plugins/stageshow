<?php

// Include wp-config.php - This will include wp settings and plugins ...
include '../../../wp-config.php';

include 'include/mjslib_NotifyURL.php';
	
$stageShowDBaseClass = STAGESHOW_DBASE_CLASS;
new NotifyURLClass(new $stageShowDBaseClass(__FILE__));	

?>
