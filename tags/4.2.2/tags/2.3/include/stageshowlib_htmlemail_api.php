<?php
/* 
Description: Core Library EMail API functions
 
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

include 'stageshowlib_email_api.php';   

if (!class_exists('StageShowLibHTMLEMailAPIClass')) 
{
	class StageShowLibHTMLMailer extends StageShowLibMailer
	{
		function PreSend()
		{
			$status = parent::PreSend();
			
			if ($status)
			{
				$MIMEContentSpec = 'Content-';
				$MIMEContentSpecLen = strlen($MIMEContentSpec);
					
				$origMailHeaders = explode( "\n", $this->MIMEHeader );

				$MIMEHeader = '';
				
				$contentTypeDefined = false;		
				foreach ($origMailHeaders as $origMailHeader)
				{
					if (substr($origMailHeader, 0, $MIMEContentSpecLen) == $MIMEContentSpec)
					{
						// This is a MIME content specifier - Reject if Content-Type already defined
						if ($contentTypeDefined)
						{
							continue;
						}
							
						// The first entry will be the Content-Type entry we added ... keep it!
						$contentTypeDefined = true;	
					}
						
					if (strlen($origMailHeader) == 0)
					{
						continue;
					}

					$MIMEHeader .= $origMailHeader."\n";
				}

				$this->MIMEHeader = $MIMEHeader;				
			}

			return $status;
		}  	
		
	}
  
	class StageShowLibHTMLEMailAPIClass extends StageShowLibEMailAPIClass // Define class
	{	
		function __construct($ourParentObj)		//constructor		
		{
			parent::__construct($ourParentObj);
		}
		
		function createPHPMailerObj($SMTPDebug)
		{
			global $phpmailer;
			$phpmailer = new StageShowLibHTMLMailer( true );		
			$phpmailer->SMTPDebug = $SMTPDebug;
		}
		
		function sendMail($to, $from, $subject, $content1, $content2 = '', $headers = '', $imageobjs = array())
		{
	  		// FUNCTIONALITY: EMail - Send MIME format EMail with text and HTML versions
			if ((strlen($content1) > 0) && (stripos($content1, '<html>') !== false))
			{
				$contentHTML = $content1;
				$contentTEXT = $content2;
				
				if (strlen($contentTEXT) == 0)
				{
	  				// FUNCTIONALITY: EMail - Create TEXT content from HTML content
					
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
				$MIMEMarker = md5(date('r', time()));
				$this->MIMEboundaryA  = "Part_A_".$MIMEMarker;
				$this->MIMEboundaryB  = "Part_B_".$MIMEMarker;

				// Add the MIME headers
				if (strlen($headers) > 0) $headers .= "\r\n";
				$headers .= "MIME-Version: 1.0";				
				$headers .= "\r\nContent-Type: multipart/alternative; boundary=\"$this->MIMEboundaryA\"";	// boundary string and mime type specification

				// Build the MIME encoded email body
				$message  = '';
				$message .= "This is a message with multiple parts in MIME format\n";
				$message .= "--$this->MIMEboundaryA\n";
				$message .= "Content-Type: text/plain\n";
				$message .= "Content-Transfer-Encoding: 8bit\n";
				$message .= "\n";
				$message .= $contentTEXT;
				$message .= "--$this->MIMEboundaryA\n";
				
				$message .= "Content-Type: multipart/related; boundary=\"$this->MIMEboundaryB\"\n\n";
				$message .= "--$this->MIMEboundaryB\n";
				
				$message .= "Content-Type: text/html; charset=\"utf-8\"\n";
				$message .= "Content-Transfer-Encoding: 8bit\n";
				//$message .= "Content-Transfer-Encoding: quoted-printable\n";
				$message .= "\n";
				
				$message .= $contentHTML;
				$message .= "\n";

				foreach ($imageobjs as $imageobj)
				{
					$message .= $this->OutputMIMEImage($imageobj);
				}			
				$message .= "--$this->MIMEboundaryB--\n";				
				$message .= "\n";
				$message .= "--$this->MIMEboundaryA--\n";
			}
			else
			{
				$message = $content1;
			}

			return parent::sendMail($to, $from, $subject, $message, '', $headers);
		}
		
		function OutputMIMEImage($imageobj)
		{
			$message = '';

			$message .= "--$this->MIMEboundaryB\n";
			$message .= "Content-Type: image/png; name=\"".$imageobj->file."\"\n";
			$message .= "Content-Transfer-Encoding: base64\n";
			$message .= "Content-ID: <".$imageobj->cid.">\n";
						
			//$message .= "Content-Disposition: inline; filename=\"".$imageobj->file."\"\n";
			$message .= "\n";

			$message .= $imageobj->image;
			$message .= "\n";
			
			return $message;
		}		
		
	}
}

?>