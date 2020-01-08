<?
	require "vendor/autoload.php";
	require("__autoload.inc");
	use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use MiladRahimi\PhpCrypt\Crypt;
	
	header("X-Accel-Buffering: no"); //Disables Nginx's gzip/buffering and allows for output streaming			
	ignore_user_abort(true);
	ini_set("display_errors", 1);
	ini_set("output_buffering", "off");
	ini_set("implicit_flush", true);

	$is_windows 	= stripos(PHP_OS, 'WIN') !== false;
	$github_payload = @json_decode(value($_POST,"payload"));
	$github_email 	= value($github_payload,["pusher","email"],"");
	$github_name 	= value($github_payload,["pusher","name"],"Unknown");	
	$branch 		= value($_GET,"branch");
	$action			= value($_GET,"action","build");
	$environment	= value($_GET,"environment","dev");
	$platform		= value($_GET,"platform");

	preg_match("/([^\\/]+)$/",value($github_payload,"ref"),$matches);
	$github_branch = value($matches,1);	

	$data = [
		"github_branch"=>$github_branch,
		"branch"=>$branch,
		"action"=>$action,
		"environment"=>$environment,
		"platform"=>$platform
	];

	$get = addslashes(json_encode($data));
	$cmd = 'php deployngx-process.php "'.$get.'"';

	if(!$is_windows) {
		$process = @file_get_contents("process.pid");

		if($process)
			exec("kill 9 -".$process);

		$cmd = "setsid ".$cmd;
	}

	$descriptorspec = [
		0 => ["pipe", "r"],
		1 => ["pipe", "w"],
		2 => ["pipe", "w"]
	];

	$process = proc_open($cmd, $descriptorspec, $pipes, realpath('./'));
	@fclose($pipes[0]);

	if(!$is_windows) {
		$status = proc_get_status($process);
		$pid = value($status,"pid");
		$group_process = trim(shell_exec("ps --pid ".$pid." -o pgrp --no-headers"));
		file_put_contents("process.pid",$group_process);
	}

	$errors = [];
  	if(is_resource($process)) {

      	while($string=fgets($pipes[1])) {
			echo $string;
			@flush();
			@ob_end_flush();
        }

        while($string=fgets($pipes[2])) {
	  		echo $string;
			$errors[] = $string;

	  		@flush();
	      	@ob_end_flush();
	    }
    }

	if($errors && $github_email) {

		$mail = new PHPMailer(true);   
		
		try {

			$crypt = new Crypt("30498ywdsifuyhg9435");

		    $mail->isSMTP();
		    $mail->SMTPAuth = true;
		    $mail->Host = $crypt->decrypt("S3shzFQScHn4OPYt+gcPACgM4KaXHPICprGNEptbFaQ=:fXO4c92N0kieDmmZ0WSkfA==");				    
		    $mail->Username = $crypt->decrypt("u2fXReWF7lj/OcIPWSE0Jw==:GSKfLO048XOxtbQC7RIFcQ==");
		    $mail->Password = $crypt->decrypt("me2HiDq37VSZBp1lHK5q4/zmsweFrMWZzKf6q3pfiDE=:oUces8T36ldGf8bqeKjGvQ==");
		    $mail->SMTPSecure = "tls";
		    $mail->Port = 587;

		    $mail->setFrom("sysadmin@firestitch.com","Firestitch Sysadmin");
		    $mail->addAddress($github_email);
		    
		    $error = implode("",$errors);

		    $host = value($_SERVER,"HTTP_HOST");
		    $mail->isHTML();
		    $mail->Subject = 	"Deploy Error For ".$host;
		    $mail->Body    = 	"The following errors where produced during the deployment process.".
		    					"<div style=\"background:#000;color:#fff;padding:15px;margin:15px 0;border-radius:4px;white-space:pre-wrap;font-family:monospace;\">".$error."</div>".
		    					"Please correct the build issues and re-deploy the code.<br><br>".
		    					"<a href=\"".$host."\">{$host}</a>";
		    $mail->send();

		} catch (Exception $e) {
		    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
		}
	}

	@fclose($pipes[1]);
 	@fclose($pipes[2]);
  	proc_close($process);
  	@unlink("process.pid");
