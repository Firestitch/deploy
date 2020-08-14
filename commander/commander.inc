<?
	require_once __DIR__.'/../vendor/autoload.php';
	use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
	
	class COMMANDER {

		protected $_output_file 	= "";
		protected $_output_buffer 	= "";
		protected $_output 			= true;
		protected $_errors			= [];
		protected $_failed			= false;

		static function create() {
			return new COMMANDER();
		}

		function build($commands,$options=["title"=>"","capture"=>true,"output"=>true]) {

			$this->_options		= $options;
			$title 				= $this->get_option("title","Building");
			$this->_output 		= $this->get_option("output",true);
			$this->_output_file = $this->get_option("output_file");
			$this->_process_key = $this->get_option("process_key");
			$errors				= [];

			$this->log("BUILD-START");

			$converter = new AnsiToHtmlConverter();

			error_reporting(E_ALL);
			date_default_timezone_set('America/Toronto');

			ini_set("max_execution_time", 300);
			set_time_limit(300);

			if($this->_output_file)
				@unlink($this->_output_file);

			$this->start();

			require_once("commander.template.php");
			@ob_end_flush();

			if($this->_output_file && $this->_failed) {
				@mkdir(dirname($this->_output_file),0777,true);
				@file_put_contents($this->_output_file,$this->_output_buffer);
			}

			$this->log("BUILD-END");

			if($this->_failed)
				foreach($errors as $error)
					fwrite(STDERR, $error);
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
