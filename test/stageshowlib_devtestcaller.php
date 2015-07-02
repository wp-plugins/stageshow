<?php
/* 
Description: Code for Managing Development Testing
 
Copyright 2014 Malcolm Shergold

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
	
include 'stageshowlib_testbase.php';  

if (!class_exists('StageShowLibDevCallerClass')) 
{
	class StageShowLibDevCallerClass extends StageShowLibAdminClass // Define class
	{
		static function DevTestFilesList($testDir, $domain)
		{
			$fileNames = strtolower($domain).'_test_*.php';
			$filePath = $testDir.$fileNames;
			$testFiles = glob( $filePath );
			return $testFiles;
		}
		
		function __construct($env, $domain) //constructor	
		{
			$this->ourClassPrefix = $domain.'_Test_';
			$this->libClassPrefix = 'StageShowLib_Test_';
			$this->pageTitle = 'Dev TESTING';
						
			// Call base constructor
			parent::__construct($env);			
		}

		function ProcessActionButtons()
		{
		}
		
		function Output_MainPage($updateFailed)
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;
			
			// Stage Show TEST HTML Output - Start 				
			$ourTestFilePrefix = strtolower($this->ourClassPrefix);
			$ourTestFilePrefixLen = strlen($ourTestFilePrefix);
			
			$libTestFilePrefix = strtolower($this->libClassPrefix);
			$libTestFilePrefixLen = strlen($libTestFilePrefix);
			
			$testClasses = array();
			$maxIndex = 0;
			$testDir = dirname(__FILE__).'/';
			$testFiles = scandir( $testDir );

			foreach ($testFiles as $testFile)
			{
				$testName = str_replace('.php','', $testFile);
				if (substr($testName, 0, $ourTestFilePrefixLen) == $ourTestFilePrefix)
				{
					$testName = substr($testName, $ourTestFilePrefixLen);
					$testClass = $this->ourClassPrefix.$testName;
				}
				else if (substr($testName, 0, $libTestFilePrefixLen) == $libTestFilePrefix)
				{
					$testName = substr($testName, $libTestFilePrefixLen);
					$testClass = $this->libClassPrefix.$testName;
				}
				else
					continue;
					
				//echo "Test File: $testFile <br>\n";
								
				$filePath = $testDir.strtolower($testClass).'.php';
				include $filePath;
				
				$testObj = new $testClass($this->env); 
				$index = $testObj->GetOrder() * 10;
				
				if (isset($testClasses[$index]))
				{
					echo "<br><strong>Duplicate Index - $testClass</strong> - Moved to next available location</br>\n";
					while (isset($testClasses[$index])) 
					{
						$index++;
					}
				}				
				$testClassInfo = new stdClass;
				$testClassInfo->Name = $testName;
				$testClassInfo->Path = $filePath;
				$testClassInfo = $testObj->AddPostArgs($testClassInfo);
				$testClassInfo->Obj = $testObj;
				
				$testClasses[$index] = $testClassInfo;
				
				$maxIndex = ($index > $maxIndex) ? $index : $maxIndex;
			}
			
			//StageShowLibUtilsClass::print_r($testClasses, 'testObjs');
			
			for ($index = 0; $index<=$maxIndex; $index++)
			{
				if (!isset($testClasses[$index]))
				{
					continue;
				}
				$testClassInfo = $testClasses[$index];
				$testObj = $testClassInfo->Obj;

				$postArgs  = 'method="post"';
				if ($testClassInfo->Target != "") $postArgs .= ' action="'.$testClassInfo->Target.'"';
				
				echo "<form $postArgs>\n";
				$this->WPNonceField($testClassInfo->Referer);
				$testObj->Show();
				echo '</form>'."\n";
			}
			
		}				 


	}
}

?>