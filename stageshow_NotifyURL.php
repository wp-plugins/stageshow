<?php

// Include wp-config.php - This will include wp settings and plugins ...
include '../../../wp-config.php';

include 'include/stageshowlib_NotifyURL.php';
	
new IPNNotifyClass(STAGESHOW_DBASE_CLASS, __FILE__);	

?>
