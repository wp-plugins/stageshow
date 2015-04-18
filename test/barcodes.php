<?php

include "../include/stageshowplus_barcode_old.php";
include "../include/stageshowplus_barcode.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);
 	
function DoBarcode($bc, $filename, $testText)
{
	$urlBase = str_replace('barcodes.php', '', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

	echo "<br><br>\n";

	//echo "Filename: $filename<br>\n";

	$bc->toFile($testText, $filename);			
		
	$barcodeURL = $urlBase.$filename;

	echo '<img alt="Sale Barcode" src="'.$barcodeURL.'">'."\n";
	echo "<br><br>\n";
	echo "Created Bitmap File: $filename<br>\n";
	echo "URL: <a href=\"$barcodeURL\">$barcodeURL</a><br>\n";

}

$testText = "1234567890ABCDEF";
$testText = "12345678";
$testBarcode = StageShowLibBarcodeClass::BARCODE_TYPE_CODE39;
if(isset($_POST['testbarcodes']))
{
	$testText = stripslashes($_POST['barcodeText']);
	$testBarcode = stripslashes($_POST['barcodeType']);
}
	
?>
<form method="post">
<table>
<tr><td>Text:</td><td><input type="text" maxlength="16" size="17" name="barcodeText" id="barcodeText" value="<?php echo $testText; ?>" autocomplete="off" /></td></tr>
<tr><td>Type:</td><td><select id="barcodeType" name="barcodeType">
<?php
$barcodeTypes = array(
	StageShowLibBarcodeClass::BARCODE_TYPE_CODE39,
	StageShowLibBarcodeClass::BARCODE_TYPE_CODE128,
	StageShowLibBarcodeClass::BARCODE_TYPE_CODE25,
	StageShowLibBarcodeClass::BARCODE_TYPE_CODABAR,
	);
foreach ($barcodeTypes as $barcodeType)
{
	$selected = ($testBarcode == $barcodeType) ? 'selected=true ' : '';
	echo '<option value="'.$barcodeType.'" '.$selected.'>'.$barcodeType."&nbsp;</option>\n";
}
?>
</select></td></tr>
<tr><td><input class="button-primary" type="submit" name="testbarcodes" value="Test Barcodes"/>						
</table>
</form>

<?php

DoBarcode(new StageShowLibBarcodeClass(), "Barcode_Orig.png", $testText);

$bc2 = new StageShowLibBarcodeClass($testBarcode, StageShowLibBarcodeClass::BARCODE_HORIZONTAL, BARCODE_HEIGHT);
/*
$bc2->code39_thinwidth = ''.BARCODE_THINWIDTH;
$bc2->code39_widewidth = ''.BARCODE_THICKWIDTH;

$bc2->padding = 0;

$bc2->fontSize = BARCODE_FONTSIZE;
*/
echo "<br><br>\n";
echo "Barcode Type:".$testBarcode."<br>\n";
echo "lastError:".$bc2->GetLastError()."<br>\n";
//echo "isHorizontal:".$bc2->isHorizontal."<br>\n";

DoBarcode($bc2, "Barcode_New.png", $testText);

?>