<?php
/* 
Description: MJS Library EMail API functions
 
Copyright 2012 Malcolm Shergold

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

include 'mjslib_email_api.php';   

if (!class_exists('MJSLibHTMLEMailAPIClass')) 
{
  class MJSLibHTMLEMailAPIClass extends MJSLibEMailAPIClass // Define class
  {	
		function __construct($ourParentObj)		//constructor		
		{
			parent::__construct($ourParentObj);
		}
		
		function AddImage($contentHTML)
		{
		}
		
		function OutputImageBoundary()
		{
			return '';
		}		
		
		function OutputImage()
		{
			return '';
		}		
		
		function sendMail($to, $from, $subject, $content1, $content2 = '', $headers = '')
		{
			if ((strlen($content1) > 0) && (stripos($content1, '<html>') !== false))
			{
				$contentHTML = $content1;
				$contentTEXT = $content2;
				
				if (strlen($contentTEXT) == 0)
				{
					// Create TEXT content from HTML content
					
					// Change <br> and <p> to line feeds
					$contentTEXT = $contentHTML;
					
					// Convert HTML Anchor to ... Anchor_Text(Anchor_HREF)					
					$noOfMatches = preg_match_all('|\<a.*href=(.*)\>(.*)\<\/a\>|', $contentTEXT, $regexResults);
					for ($i=0; $i<$noOfMatches; $i++)
					{
						$origLink = $regexResults[0][$i];
						$origURL  = $regexResults[1][$i];
						$origText = $regexResults[2][$i];

						$origURL = str_replace('"', '', $origURL);
						$origURL = str_replace('mailto:', '', $origURL);
						
						if ($origText == $origURL)
							$targetText = $origText;
						else if ($origText == '')
							$targetText = '';
						else
							$targetText = "$origText($origURL)";
						
						$contentTEXT = str_replace($origLink, $targetText, $contentTEXT);	
					}
					
					$search = array (
						"'<script[^>]*?>.*?</script>'si",		// Javascript
						"'([\r\n])[\s]+'",									// White space
						"'<(br|p)>'i",											//
						"'<[/!]*?[^<>]*?>'si",							// All HTML tags
						"'&(quot|#34);'i",									// Double Quote
						"'&(amp|#38);'i",										// Ampersand
						"'&(lt|#60);'i",										// Less than
						"'&(gt|#62);'i",										// Greater than
						"'&(nbsp|#160);'i",									// Space
						"'&(iexcl|#161);'i",								//
						"'&(cent|#162);'i",									//
						"'&(pound|#163);'i",								//
						"'&(copy|#169);'i",									//
						"'&[A-Za-z]+;'si",									// Any remaining HTML characters
						"'&#(d+);'e");											// evaluate as php

					$replace = array (
						"",
						"",
						"\n",
						"",
						"\"",
						"&",
						"<",
						">",
						" ",
						chr(161),
						chr(162),
						chr(163),
						chr(169),
						"",
						"");

					$contentTEXT = preg_replace($search, $replace, $contentTEXT);
				}

				// Create a unique boundary string using the MD5 algorithm to generate a random hash
				$MIMEboundary  = "_Next_Part_A_".md5(date('r', time()));

				// Add the MIME headers
				if (strlen($headers) > 0) $headers .= "\r\n";
				$headers .= "MIME-Version: 1.0";
				$headers .= "\r\nContent-Type: multipart/alternative; boundary=\"$MIMEboundary\"";	// boundary string and mime type specification

				$this->AddImage($contentHTML);

				$MIMEboundary = "\n--$MIMEboundary";

				// Build the MIME encoded email body
				$message  = '';
				$message .= "This is a message with multiple parts in MIME format\n";
				$message .= "$MIMEboundary\n";
				$message .= "Content-Type: text/plain\n";
				$message .= "Content-Transfer-Encoding: 8bit\n";
				$message .= "\n";
				$message .= $contentTEXT;
				$message .= "$MIMEboundary\n";

				$message .= $this->OutputImageBoundary();
				
				$message .= "Content-Type: text/html; charset=\"utf-8\"\n";
				$message .= "Content-Transfer-Encoding: 8bit\n";
				$message .= "Content-Transfer-Encoding: quoted-printable\n";
				$message .= "\n";
				$message .= $contentHTML;

				$message .= $this->OutputImage();
				
				$message .= "$MIMEboundary--\n";
			}
			else
			{
				$message = $content1;
			}
			//file_put_contents(STAGESHOW_ADMIN_PATH.'HTMLEMail.txt', $message);

			return parent::sendMail($to, $from, $subject, $message, '', $headers);
		}


	}
}

?>