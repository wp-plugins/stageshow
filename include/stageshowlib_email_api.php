<?php
/* 
Description: Core Library EMail API functions
 
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

if (!class_exists('StageShowLibMailer')) 
{
	require_once ABSPATH . WPINC . '/class-phpmailer.php';
	require_once ABSPATH . WPINC . '/class-smtp.php';
			
	class StageShowLibMailer extends PHPMailer
	{
		function PreSend()
		{
			$status = parent::PreSend();
			
			if ($this->SMTPDebug)
			{
				echo "Called PHPMailer::PreSend() - Return status=$status<br>\n";
			}
			
			return $status;
		}
		
		public function PostSend()
		{
			if ($this->SMTPDebug)
			{
				echo "Mailer: ".$this->Mailer."<br>\n";
			}
			
			$status = parent::PostSend();
			
			if ($this->SMTPDebug)
			{
				echo "Called PHPMailer::PostSend() - Return status=$status<br>\n";
			}
			
			return $status;
		}
		
	}
}

if (!class_exists('StageShowLibEMailAPIClass')) 
{
	class StageShowLibEMailAPIClass // Define class
	{	
		var $parentObj;
		
		function __construct($ourParentObj)	
		{
			$this->parentObj = $ourParentObj;			
		}
		
		function createPHPMailerObj($SMTPDebug)
		{
			global $phpmailer;
			$phpmailer = new StageShowLibMailer( true );		
			$phpmailer->SMTPDebug = $SMTPDebug;
		}
		
		function AddImage($imageObj)
		{
			return false;
		}
		
		function sendMail($to, $from, $subject, $content, $content2 = '', $headers = '')
		{
			$SMTPDebug = $this->parentObj->getDbgOption('Dev_ShowEMailMsgs');
			$this->createPHPMailerObj($SMTPDebug);
			
			$BccEMail = '';
			
			// FUNCTIONALITY: General - EMail copy of any outgoing email to AdminEMail
			if ($this->parentObj->adminOptions['BccEMailsToAdmin'])
				$BccEMail = $this->parentObj->GetEmail($this->parentObj->adminOptions);	
			$replyTo = $from;
			
			// Define the email headers - separated with \r\n
			if (strlen($headers) > 0) $headers .= "\r\n";
			$headers .= "From: $from";	
				
			// Bcc emails to Admin Email	
			if (strlen($BccEMail) > 0) $headers .= "\r\nbcc: $BccEMail";
			$headers .= "\r\nReply-To: $replyTo";	
				
			if ($SMTPDebug)
			{
				// FUNCTIONALITY: General - Echo EMail when Dev_ShowEMailMsgs selected - Body Encoded with htmlspecialchars
				echo "To:<br>\n";
				echo htmlspecialchars($to);
				echo "<br>\n<br>\n";
				echo "Headers:<br>\n";
				echo str_replace("\r\n", "<br>\r\n", htmlspecialchars($headers));
				echo "<br>\n<br>\n";
				echo "Message:<br>\n";
				echo htmlspecialchars($content);
				echo "<br>\n<br>\n";
			}
						
			// FUNCTIONALITY: General - Send EMail
			//send the email
			wp_mail($to, $subject, $content, $headers);
		}
		

	}
}

?>