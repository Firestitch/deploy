<?
	require_once __DIR__.'/../vendor/autoload.php';
	use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use MiladRahimi\PhpCrypt\Crypt;

	class COMMANDER {

		protected $_output_file 	= "";
		protected $_output_buffer 	= "";
		protected $_output 			= true;

		static function create() {
			return new COMMANDER();
		}

		static function get_github_payload() {
			$payload = @json_decode(value($_POST,"payload"));
			return $payload ? $payload : [];
		}

		function build($commands,$options=["title"=>"","capture"=>true,"output"=>true,"error_email"=>""]) {

			$this->_options		= $options;
			$title 				= $this->get_option("title","Building");
			$this->_output 		= $this->get_option("output",true);
			$this->_output_file = $this->get_option("output_file");
			$this->_process_key = $this->get_option("process_key");
			$errors				= [];

			$this->log("BUILD-START");

			// foreach($commands as $index=>$command)
			// 	$commands[$index] .= " 2>&1";

			$converter = new AnsiToHtmlConverter();

			header('X-Accel-Buffering: no'); //Disables Nginx's gzip/buffering and allows for output streaming
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
			ini_set('output_buffering', 'off');
			ini_set('implicit_flush', true);
			date_default_timezone_set('America/Toronto');
			ignore_user_abort(true);
			ini_set('max_execution_time', 300);
			set_time_limit(300);

			if($this->_output_file)
				@unlink($this->_output_file);

			$this->start();

			@mkdir("processes");
			$md5_process_key = md5($this->_process_key);
			$process_file = $this->_process_key ? "processes/".$md5_process_key : null;

			if($process_file) {
				if($data=@file_get_contents($process_file)) {

					$pid = value(@json_decode($data),"pid");

			    	if($pid) {
			    		$this->log("PROCESS-KILL ".$pid);
			    		
			    		$this->kill_pid($pid);

			    		// Kills any ng processes that have a root parent
			    		// Did this because of orphaned ng processes that tied up memory
			    		exec("pgrep -P 1 ^ng$ | xargs kill");
			    	}
				}

				$data = ["pid"=>getmypid(),"process_key"=>$this->_process_key];
				file_put_contents($process_file,json_encode($data));
			}

			require_once("commander.template.php");
			@ob_end_flush();

			if($this->_output_file && !is_file($this->_output_file)) {
				@mkdir(dirname($this->_output_file),0777,true);
				@file_put_contents($this->_output_file,$this->_output_buffer);
			}

			$this->log("BUILD-END");

			if($process_file)
				@unlink($process_file);

			if($errors && ($error_email=value($options,"error_email"))) {

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
				    $mail->addAddress($error_email);
				    
				    $host = value($_SERVER,"HTTP_HOST");
				    $mail->isHTML();
				    $mail->Subject = 	"Deploy Error For ".$host;
				    $mail->Body    = 	"The following errors where produced during the deployment process.".
				    					"<div style=\"background:#000;padding:15px;margin:15px 0;border-radius:4px;\">".implode("",$errors)."</div>".
				    					"Please correct the build issues and re-deploy the code.<br><br>".
				    					"<a href=\"".$host."\">{$host}</a>";
				    $mail->send();

				} catch (Exception $e) {
				    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
				}
			}
		}

		function kill_pid($root_pid) {
    		$pids = [];
    		exec('pstree -pn '.$root_pid.' | grep -o "([[:digit:]]*)" | grep -o "[[:digit:]]*"',$pids);
			$pid = array_pop($pids);
			if($pid) {
	    		exec("kill -9 ".$pid);
	    		$this->kill_pid($root_pid);
	    	}
		}

		function get_option($name,$default=null) { return value($this->_options,$name,$default); }


		function start() {
			ob_start(function($buffer) {
				$this->_output_buffer .= $buffer;
				return $this->_output ? $buffer : '';
			});
		}

		// function register_pid($pid) {
		// 	$this->_pids[$pid] = true;
		// }

		// function unregister_pid($pid) {
		// 	unset($this->_pids[$pid]);
		// }

		function flush() {
			@ob_end_flush();
			$this->start();
		}

		function log($message) {

			if(!is_dir("/var/log/nginx/"))
				return;

			$log = [];
			$log[] = date("M j H:i:s");
			$log[] = value($_SERVER,"HTTP_HOST");
			$log[] = "\"".$this->get_option("title")."\"";
			$log[] = $message;

			file_put_contents("/var/log/nginx/deploy.log", implode(" ",$log)."\n", FILE_APPEND);
		}

		function zip($dir,$options=[]) {

			$dir = rtrim(str_replace("\\","/",$dir),"\\")."/";

			if(!is_dir($dir))
				throw new Exception("Directory does not exist");

			$zip_file = sys_get_temp_dir()."/".value($_SERVER,"HTTP_HOST").".zip";
			@unlink($zip_file);

			// create recursive directory iterator
			$files =  new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

			$zip = new ZipArchive();
			if ($zip->open($zip_file,ZipArchive::CREATE) === true) {

				foreach($files as $name => $file) {

					$path = str_ireplace($dir,"",str_replace("\\","/",$file));

					if(($ignore=value($options,"ignore")) && preg_match($ignore,$path)) {
						continue;
					}

					if(is_file($file->getRealPath()))
						$zip->addFile($file->getRealPath(),$path);

					elseif(is_dir($file->getRealPath()))
						$zip->addEmptyDir($path);
				}

			    @$zip->close();
			    header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.basename($zip_file).'"');
				header("Content-Transfer-Encoding: binary");
				header('Content-Length: '.filesize($zip_file));
				flush();
				@readfile($zip_file);
				@unlink($zip_file);
				die;

			} else {
			    die($zip->getStatusString());
			}
		}

	}
