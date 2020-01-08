<?
	require("__autoload.inc");

	//$_GET = ["branch"=>"deploy"];
	
	$get = addslashes(json_encode($_GET));

	$cmd = "php deployngx-process.php \"".$get."\"";

	$descriptorspec = [
		0 => ["pipe", "r"],
		1 => ["pipe", "w"],
		2 => ["pipe", "w"]
	];

	$process = proc_open($cmd, $descriptorspec, $pipes, realpath('./'));
	@fclose($pipes[0]);

	$status = proc_get_status($process);

  	if(is_resource($process)) {

      	while($string=fgets($pipes[1])) {
			echo $string;
			@ob_end_flush();
        }
    }

	@fclose($pipes[1]);
 	@fclose($pipes[2]);
  	proc_close($process);
