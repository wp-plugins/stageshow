<?php

define ('BARCODE_FILEMODE','png');
define ('BARCODE_THINWIDTH',1);
define ('BARCODE_THICKWIDTH',2);
define ('BARCODE_HEIGHT',30);
define ('BARCODE_FONTSIZE',2);

define ('BARCODE_STARTSTOPCHAR','*');

class Barcode // Define class
{
	var	$errorCode;
	
	private $bcHeight, $bcThinWidth, $bcThickWidth, $bcFontSize, $mode, $outMode, $fileType;
	
	function __construct($mode='png')
	{	
		$this->bcHeight = BARCODE_HEIGHT;
		$this->bcThinWidth = BARCODE_THINWIDTH;
		$this->bcThickWidth = $this->bcThinWidth * BARCODE_THICKWIDTH;
		$this->fontSize = BARCODE_FONTSIZE;
		$this->mode = $mode;
		$this->outMode = array('gif'=>'gif', 'png'=>'png', 'jpeg'=>'jpeg', 'wbmp'=>'vnd.wap.wbmp');
		
		$this->errorCode = '';
		
		if (!$this->fileType = $this->outMode[$this->mode])
		{
			throw new exception("barCode::build - unrecognized output format ({$this->mode})");
		}
		
		// Code Table - Code 3 of 9 Barcodes
		$this->codeMap = array(
			'0'=>'000110100', '1'=>'100100001', '2'=>'001100001', '3'=>'101100000',
			'4'=>'000110001', '5'=>'100110000', '6'=>'001110000', '7'=>'000100101',
			'8'=>'100100100', '9'=>'001100100', 'A'=>'100001001', 'B'=>'001001001',
			'C'=>'101001000', 'D'=>'000011001', 'E'=>'100011000', 'F'=>'001011000',
			'G'=>'000001101', 'H'=>'100001100', 'I'=>'001001100', 'J'=>'000011100',
			'K'=>'100000011', 'L'=>'001000011', 'M'=>'101000010', 'N'=>'000010011',
			'O'=>'100010010', 'P'=>'001010010', 'Q'=>'000000111', 'R'=>'100000110',
			'S'=>'001000110', 'T'=>'000010110', 'U'=>'110000001', 'V'=>'011000001',
			'W'=>'111000000', 'X'=>'010010001', 'Y'=>'110010000', 'Z'=>'011010000',
			' '=>'011000100', '$'=>'010101000', '%'=>'000101010', '*'=>'010010100',
			'+'=>'010001010', '-'=>'010000101', '.'=>'110000100', '/'=>'010100010'
		);
	}
	
	public function getMode()
	{
		return $this->mode;
	}
	
	public function output($text='', $showText=true)
	{
		$bin = $this->createImage($text, $showText);
		
		header("Content-type:  image/{$this->fileType}");
		echo $bin;		
	}
		
	public function getBase64($text)
	{
		$bin = $this->createImage($text);
		$BarcodeBinaryEncoded = chunk_split ( base64_encode ( $bin ) );
		
		return $BarcodeBinaryEncoded;
	}		
		
	public function toFile($text, $fileName, $showText=true)
	{
		$bin = $this->createImage($text, $showText, $fileName);
		file_put_contents($fileName, $bin);
	}
	
	function GetPermittedChars()
	{
		$testText = '';
		foreach ($this->codeMap as $nextChar => $bitmap)
		{
			if ($nextChar === BARCODE_STARTSTOPCHAR)
				continue;
			$testText .= $nextChar;
		}
		
		return $testText;
	}
	
	function GenerateTestBitmap($testText, $filename)
	{
		$this->toFile($testText, $filename);
	}
	
	private function createImage($text='', $showText=true)
	{		
		$this->errorCode = '';
		
		$text  =  strtoupper($text);
		$dispText = $text;
		$text = BARCODE_STARTSTOPCHAR.$text.BARCODE_STARTSTOPCHAR; // adds start and stop chars
		$textLen  =  strlen($text);	
		$barcodeWidth  =  $textLen * (7 * $this->bcThinWidth + 3 * $this->bcThickWidth) - $this->bcThinWidth; 
		
		if ($showText)
			$pxHt = imagefontheight($this->fontSize) + 2;
		else
			$pxHt = 0;
		
		$im = imagecreate($barcodeWidth, $this->bcHeight + $pxHt);
		 
		$black = imagecolorallocate($im, 0, 0, 0);
		$white = imagecolorallocate($im, 255, 255, 255);
		
		imagefill($im, 0, 0, $white);
		$xpos = 0;
		for ($idx=0; $idx<$textLen; $idx++)
		{
			if (!isset($text[$idx]))
			{
				$this->errorCode = "Character cannot be encoded - ".$idx;
				$char = '-';	// Character cannot be encoded - Use a - and set error flag
			}
			else
				$char = $text[$idx];
			
			for ($ptr=0; $ptr<=8; $ptr++)
			{
				$elementWidth = ($this->codeMap[$char][$ptr]) ? $this->bcThickWidth : $this->bcThinWidth;
				if (($ptr + 1) % 2)
					imagefilledrectangle($im, $xpos, 0, $xpos + $elementWidth-1, $this->bcHeight, $black);
				$xpos += $elementWidth;
			}
			$xpos += $this->bcThinWidth;
		}
		if ($showText)
		{
			$pxWid = imagefontwidth($this->fontSize) * strlen($dispText) + 10;
			$bigCenter = $barcodeWidth / 2;
			$textCenter = $pxWid / 2;
			imagestring($im, $this->fontSize, ($bigCenter - $textCenter) + 5, $this->bcHeight + 1, $dispText, $black);
		}
		
		ob_start();		
		switch($this->mode)
		{
			case 'gif': $image_value = imagegif($im);
			case 'png': $image_value = imagepng($im);
			case 'jpeg': $image_value = imagejpeg($im);
			case 'wbmp': $image_value = imagewbmp($im);
		}
		$bin = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($im);
		
		return $bin;
	}
}

?>
