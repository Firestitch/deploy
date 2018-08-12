<?
	require("__autoload.inc");

	$dir = dirname(__DIR__)."/";

	$zip_file = $dir."package.zip";
	@unlink($zip_file);

	message("Creating deploy zip archive...");
	$start_time = time();

	try {
		$zip = new ZipArchive();
		if ($zip->open($zip_file,ZipArchive::CREATE) === true) {
			message("Adding data/config.ini to zip archive");
			addDir($zip, $dir."backend");
			addDir($zip, $dir."framework");
			addDir($zip, $dir."frontend/dist");
			$zip->addFile($dir."data/config.ini","data/config.ini");
		} else {
			message("Failed to create zip ".$zip_file);
		}
		$zip->close();
	} catch(Exception $e) {
		message("ERROR: ".$e->getMessage());
	}

	message("Zip process took ".(time() - $start_time)." seconds");
	message("Zip file created {$zip_file}");

	function addDir($zip, $dir) {
		message("Adding ".basename($dir)." to zip archive");

		// create recursive directory iterator
		$files =  new RecursiveIteratorIterator(
	        new RecursiveDirectoryIterator($dir),
	        RecursiveIteratorIterator::LEAVES_ONLY
	    );

		foreach($files as $name => $file) {

			$relative = basename($dir).str_ireplace(str_replace("\\","/",$dir),"",str_replace("\\","/",$file));

			if(is_file($file->getRealPath()))
			 	$zip->addFile($file->getRealPath(),$relative);

			elseif(is_dir($file->getRealPath()))
			 	$zip->addEmptyDir($relative);
		}
	}

	function message($message) {
		echo "{$message}\n\n";
	}