<?
	require_once __DIR__.'/../ansi-to-html/vendor/autoload.php';
	use SensioLabs\AnsiConverter\AnsiToHtmlConverter;

	class COMMANDER {

		static function create() {
			return new COMMANDER();
		}

		function run($commands,$options=["title"=>"","capture"=>true]) {

			$capture = value($options,"capture",true);

			foreach($commands as &$command)
				$command .= " 2>&1";

			$converter = new AnsiToHtmlConverter();

			//Disables Nginx's gzip/buffering and allows for output streaming
			header('X-Accel-Buffering: no');
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
			ini_set('output_buffering', 'off');
			ini_set('implicit_flush', true);
			date_default_timezone_set('America/Toronto');

			$title = value($options,"title","Building");

			if($pid=@file_get_contents("process.pid"))
			    exec("kill ".$pid);

			file_put_contents("process.pid",getmypid());
			include("commander.template.php");
			@unlink("process.pid");
		}

		function zip($dir,$options=[]) {

			$dir = rtrim(str_replace("\\","/",$dir),"\\")."/";

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