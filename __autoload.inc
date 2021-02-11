<?php
	require "vendor/autoload.php";
	require("commander/commander.inc");

	use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use MiladRahimi\PhpCrypt\Crypt;

	header("X-Accel-Buffering: no"); //Disables Nginx's gzip/buffering and allows for output streaming
	ignore_user_abort(true);
	ini_set("display_errors", 1);
	ini_set("output_buffering", "off");
	ini_set("implicit_flush", true);

	function run_process($script, $config, $notification_email, $process_file) {

		$is_windows = stripos(PHP_OS, 'WIN') !== false;
		$config 	= addslashes(json_encode($config));
		$cmd 		= 'php '.$script.' "'.$config.'"';

		if($process_file && !$is_windows) {
			$process = @file_get_contents($process_file);

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

		if($process_file && !$is_windows) {
			$status = proc_get_status($process);
			$pid = value($status,"pid");
			$group_process = trim(shell_exec("ps --pid ".$pid." -o pgrp --no-headers"));
			file_put_contents($process_file,$group_process);
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

		if($errors && $notification_email) {

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

			    $mail->setFrom("sysadmin@firestitch.com","Firestitch Deployment");
			    $mail->addAddress($notification_email);

			    $error = implode("",$errors);

			    $host = value($_SERVER,"HTTP_HOST");
			    $mail->isHTML();
			    $mail->Subject = 	"Deploy Error For ".$host;
			    $mail->Body    = 	"The following errors where produced during the deployment process.<br><br>".
			    					"Command: ".$cmd."<br><br>".
			    					"<div style=\"background:#000;color:#fff;padding:15px;margin:15px 0;border-radius:4px;white-space:pre-wrap;font-family:monospace;\">".$error."</div>";
			    $mail->send();

			} catch (Exception $e) {
			    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
			}
		}

		@fclose($pipes[1]);
	 	@fclose($pipes[2]);
	  	proc_close($process);

	  	if($process_file)
	  		@unlink($process_file);
	}

	function value($var,$index,$default=null) {

		if($var===null)
			return $default;

		$is_array = is_array($index);

		if($is_array && count($index)==1) {
			$index = array_shift($index);
			$is_array = is_array($index);
		}

		if($is_array) {

			$first_index = @$index[0];

			if($first_index!==null) {

				$vars = null;
				if(is_array($var))
					$vars = @$var[$first_index];

				elseif(is_object($var)) {

		        	if(is_a($var,"ArrayAccess"))
		        		$vars = $var[$first_index];
		        	else
		        		$vars = @$var->$first_index;
				}

				if($vars!==null) {

					array_shift($index);

					if(count($index)==1)
						$index = $index[0];

					if(is_array($vars) || is_object($vars))
						return value($vars,$index,$default);

					return $default;
				}
			}

			return $default;

        } elseif(is_array($var)) {

            if(@array_key_exists($index,$var))
                return $var[$index];

        } elseif(is_object($var)) {

        	if(is_a($var,"ArrayAccess")) {

        		if(isset($var[$index]))
        			return $var[$index];

        	} elseif(isset($var->$index))
        		return $var->$index;
        }

		return $default;
	}

	function p($value) {
		print_r($value);
	}

	function is_os_windows() {
		return substr(PHP_OS,0,3) == 'WIN';
	}