<?
	require_once __DIR__.'/../ansi-to-html/vendor/autoload.php';
	use SensioLabs\AnsiConverter\AnsiToHtmlConverter;

	class COMMANDER {

		const PROCESS_FILENAME = "process.pid";

		protected $_output_file 	= "";
		protected $_output_buffer 	= "";
		protected $_output 			= true;

		static function create() {
			return new COMMANDER();
		}

		function build($commands,$options=["title"=>"","capture"=>true,"output"=>true]) {

			$this->_options		= $options;
			$title 				= $this->get_option("title","Building");
			$this->_output 		= $this->get_option("output",true);
			$this->_output_file = $this->get_option("output_file");

			$this->log("BUILD-START");

			foreach($commands as $index=>$command)
				$commands[$index] .= " 2>&1";

			$converter = new AnsiToHtmlConverter();

			header('X-Accel-Buffering: no'); //Disables Nginx's gzip/buffering and allows for output streaming
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
			ini_set('output_buffering', 'off');
			ini_set('implicit_flush', true);
			date_default_timezone_set('America/Toronto');
			ini_set('max_execution_time', 300);

			if($this->_output_file)
				@unlink($this->_output_file);

			$this->start();

			//if($pid=@file_get_contents(self::PROCESS_FILENAME))
			//    exec("kill ".$pid); //pkill -TERM -P `PID`

			//file_put_contents(self::PROCESS_FILENAME,getmypid());

			require_once("commander.template.php");
			@ob_end_flush();

			if($this->_output_file && !is_file($this->_output_file)) {
				@mkdir(dirname($this->_output_file),0777,true);
				@file_put_contents($this->_output_file,$this->_output_buffer);
			}

			$this->log("BUILD-END");

			//@unlink(self::PROCESS_FILENAME);
		}

		function get_option($name,$default=null) { return value($this->_options,$name,$default); }


		function start() {
			ob_start(function($buffer) {
				$this->_output_buffer .= $buffer;
				return $this->_output ? $buffer : '';
			});
		}

		function flush() {
			@ob_end_flush();
			$this->start();
		}

		function log($message) {

			$log = [];
			$log[] = date("M j G:i:s");
			$log[] = "\"".$this->get_option("title")."\"";
			$log[] = "\"".$message."\"";

			file_put_contents("/var/log/deploy", implode(" ",$log)."\n", FILE_APPEND);
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
