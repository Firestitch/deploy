<?
	require("__autoload.inc");

	$branch 		= value($_GET,"branch");
	$action			= value($_GET,"action","build");
	$action_build 	= preg_match("/build/",$action);
	$action_zip	 	= preg_match("/zip/",$action);
	$output			= $action=="build";
	$output_file 	= dirname(__DIR__)."/frontend/dist/index.html";
	$package_file	= dirname(__DIR__)."/frontend/package.json";
	$package_json	= @json_decode(file_get_contents($package_file));
	$package_name	= value($package_json,"name");

	$build_params = [];
	$environment = value($_GET,"environment","dev");
	$build_params[] = "--{$package_name}:env=".$environment;

	$commands = ["npm install --loglevel=error"];
	$commands = ["dir1"];

	if($action_build) {
		$title	= "Building ".ucwords($environment);
		COMMANDER::create()->build($commands,[	"title"=>$title,
												"output"=>$output,
												"output_file"=>$output_file,
												"error_email"=>"ray@firestitch.com",
												"process_key"=>basename(dirname(__DIR__))]);
	}

	if($action_zip)
		COMMANDER::create()->zip(dirname(__DIR__)."/frontend/dist-zip",["ignore"=>"/^\.git/"]);
