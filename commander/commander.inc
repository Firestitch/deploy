<?
	require_once __DIR__.'/../ansi-to-html/vendor/autoload.php';
	use SensioLabs\AnsiConverter\AnsiToHtmlConverter;

	class COMMANDER {

		protected $_output_file = "";
		protected $_output_buffer = "";

		static function create() {
			return new COMMANDER();
		}

		function build($commands,$options=["title"=>"","capture"=>true,"output"=>true]) {

			$capture 			= value($options,"capture",true);
			$output 			= value($options,"output",true);
			$title 				= value($options,"title","Building");
			$this->_output_file = value($options,"output_file");

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

			$buffer = "";
			if($output)
				$this->start();
			else
				ob_start(function($buffer) {});

			if($pid=@file_get_contents("process.pid"))
			    exec("kill ".$pid); //pkill -TERM -P `PID`

			file_put_contents("process.pid",getmypid());

			require_once("commander.template.php");
			@ob_end_flush();

			@unlink("process.pid");
		}

		function start() {
			ob_start(function($buffer) {
				$this->_output_buffer .= $buffer;
				if($this->_output_file) {
					mkdir(dirname($this->_output_file),0777,true);
					file_put_contents($this->_output_file,$this->_output_buffer);
				}
				return $buffer;
			});
		}

		function flush() {
			ob_end_flush();
			$this->start();
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
