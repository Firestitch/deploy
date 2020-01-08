<?
	require("__autoload.inc");
	
	header("X-Accel-Buffering: no"); //Disables Nginx's gzip/buffering and allows for output streaming			
	ini_set("display_errors", 1);
	ini_set("output_buffering", "off");
	ini_set("implicit_flush", true);

	$process = @file_get_contents("process.pid");

	if($process)
		exec("kill 9 -".$process);

	$get = addslashes(json_encode($_GET));
	$cmd = 'php deployngx-process.php "'.$get.'"';

	if(stripos(PHP_OS, 'WIN') === false)
		$cmd = "setsid ".$cmd;

	$descriptorspec = [
		0 => ["pipe", "r"],
		1 => ["pipe", "w"],
		2 => ["pipe", "w"]
	];

	$process = proc_open($cmd, $descriptorspec, $pipes, realpath('./'));
	@fclose($pipes[0]);

	$status = proc_get_status($process);

	$pid = value($status,"pid");

	$group_process = trim(shell_exec("ps --pid ".$pid." -o pgrp --no-headers"));

	file_put_contents("process.pid",$group_process);

  	if(is_resource($process)) {

      	while($string=fgets($pipes[1])) {
			echo $string;
			@flush();
			@ob_end_flush();
        }

        while($string=fgets($pipes[2])) {
	  		echo $string;
	  		@flush();
	      	@ob_end_flush();
	    }
    }

	@fclose($pipes[1]);
 	@fclose($pipes[2]);
  	proc_close($process);
