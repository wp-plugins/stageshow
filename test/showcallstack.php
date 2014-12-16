<?php
/**
 * The base configurations of the WordPress.
 *
 */

if (!defined('TEST_SHOWCALLSTACK_DEFINED'))
{
	define('TEST_SHOWCALLSTACK_DEFINED', 1);
	
	function ShowCallStack($context = '')
	{
		echo "<br>";
		if ($context != '')
			echo "$context <br>\n";
			
		$callStack = debug_backtrace();
		foreach ($callStack as $call)
		{
			echo $call['file'].':'.$call['line'].'  '.$call['function'];
			//print_r($call);
			echo "<br>";
		}
	}
}

ShowCallStack();

?>