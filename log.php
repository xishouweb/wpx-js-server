<?php

class Log_
{
	// 打印log
	function  log_result($word)
	{   $file="log_".date('Y-m-d').".log";
	    $fp = fopen($file,"a");
	    flock($fp, LOCK_EX) ;
	    fwrite($fp,"执行日期：".date('Y-m-d H:i:s')."\n".$word."\n\n");
	    flock($fp, LOCK_UN);
	    fclose($fp);
	}
}

?>