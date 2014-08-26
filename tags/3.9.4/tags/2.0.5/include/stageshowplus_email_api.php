<?php
/* 
Description: StageShow-Plus extension for StageShow Plugin 
 
Copyright 2012 Malcolm Shergold, Corondeck Ltd. All rights reserved.

You must be a registered user to use this software
*/

define('STAGESHOW_BARCODE_IDENTIFIER', 'SaleBarcode_');
include STAGESHOW_INCLUDE_PATH.'stageshowplus_barcode.php';

include STAGESHOW_INCLUDE_PATH.'stageshowlib_htmlemail_api.php';   

if (!class_exists('StageShowPlusDBaseClass')) 
{
	define('STAGESHOW_LOCATION_TEXTLEN', 32);
	
	class StageShowLibStageShowEMailAPIClass extends StageShowLibHTMLEMailAPIClass // Define class
	{
		var $currBarcodeTxnId;
		var $barcodeIncluded;
		var $MIMEboundary2;
		var $CIDbarcode;
		var $contentBarcodeImage;
		
		// FUNCTIONALITY: EMail - StageShow+ - Add Barcode to EMail
		
		function AddImage($contentHTML)
		{
			$this->currBarcodeTxnId = $this->GetBarcodeTxnId($contentHTML);
			$this->barcodeIncluded = ($this->currBarcodeTxnId !== '');
			if ($this->barcodeIncluded)
			{
				$this->CIDbarcode = STAGESHOW_BARCODE_IDENTIFIER.$this->currBarcodeTxnId;
				$barcodeObj = new Barcode();
				$this->contentBarcodeImage = $barcodeObj->getBase64($this->currBarcodeTxnId, true);
				$this->MIMEboundary2 = "_Next_Part_B_".md5(date('r', time()-1));
			}
		}
		
		function OutputImageBoundary()
		{
			$message = '';
			
			if ($this->barcodeIncluded)
			{
				$message .= "Content-Type: multipart/related; boundary=\"$this->MIMEboundary2\"\n";
				$this->MIMEboundary2 = "\n--$this->MIMEboundary2";
				$message .= "$this->MIMEboundary2\n";
			}
			
			return $message;
		}		
		
		function OutputImage()
		{
			$message = '';
			
			if ($this->barcodeIncluded)
			{
				$message .= "$this->MIMEboundary2\n";
				$message .= "Content-Type: image/png; name=\"barcode.png\"\n";
				$message .= "Content-Transfer-Encoding: base64\n";
				$message .= "Content-ID: <".$this->CIDbarcode.">\n";
						
				$message .= "Content-Disposition: inline; filename=\"barcode.png\"\n";
				$message .= "\n";

				$message .= $this->contentBarcodeImage;
				$message .= "$this->MIMEboundary2--\n";
			}
			
			return $message;
		}		
		
		function GetBarcodeTxnId($content)
		{
			// Find the barcode tag
			$startTag = '<img alt="Sale Barcode" src="cid:'.STAGESHOW_BARCODE_IDENTIFIER;
			$startPosn = strpos($content, $startTag);
			if ($startPosn == false) return '';
			
			$endPosn = strpos($content, '">', $startPosn);
			if ($endPosn == false) return '';
			
			// Move posn to end of the search text
			$startPosn += strlen($startTag);
			
			// Now get the BarcodeID entry
			$barcodeTag = substr($content, $startPosn, $endPosn-$startPosn);
			//echo "barcodeTag---$barcodeTag---<br>\n";
			
			return $barcodeTag;
		}
		
	}
}

?>