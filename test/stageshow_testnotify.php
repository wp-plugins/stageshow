<?php

include '../include/stageshow_gatewaysimulator.php';

if (!class_exists('StageShowWPOrgTestNotify')) 
{
	class StageShowWPOrgTestNotify extends StageShowGatewaySimulator
	{
		function __construct() 
		{
			$this->CanEditTotal = true;
			
	  		parent::__construct(STAGESHOWLIB_DBASE_CLASS);	
	   	}
	
		function OutputHeader() 
		{
			$header = '
				<html>
					<head>
						<title>'.$this->myDBaseObj->adminOptions["OrganisationID"].' Payment Gateway Simulator</title>
						<meta http-equiv="Content-Type" content="text/html;">
						<link href="css/style.css" rel="stylesheet" type="text/css">
					</head>
					<body alink="#0000FF" vlink="#0000FF">
					<h1>'.$this->myDBaseObj->adminOptions["OrganisationID"].' - Demo Payment Gateway Notify Simulator</h1>
			';

			return $header;
	    }
		
		function OutputFooter() 
		{
			$formHTML .= '
				</body>
				</html>
			';
				
			echo $formHTML;
		}
	}
}

new StageShowWPOrgTestNotify();
	
?>