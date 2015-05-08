<?php

if (!class_exists('BarcodeBase'))
{
	class BarcodeBase // Define class
	{
		function createImage($text='', $showText=true)
		{
		}
		
		public function outputBarcode($text='', $showText=true)
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
		
	}
}
	
?>