<?
	require("__autoload.inc");

	$branch 		= value($_GET,"branch");
	$action			= value($_GET,"action","build");
	$action_build 	= preg_match("/build/",$action);
	$action_zip	 	= preg_match("/zip/",$action);
	$output			= $action=="build";
	$branch 		= $branch ? $branch : trim(shell_exec("cd ../ && git rev-parse --abbrev-ref HEAD"));
	$output_file 	= dirname(__DIR__)."/frontend/dist/index.html";
	$package_file	= dirname(__DIR__)."/frontend/package.json";
	$package_json	= @json_decode(file_get_contents($package_file));
	$package_name	= value($package_json,"name");
	$payload 		= COMMANDER::get_github_payload();
	$github_email 	= value($payload,["pusher","email"],"Unknown");
	$github_name 	= value($payload,["pusher","name"],"Unknown");

	$build_params = [];
	$environment = value($_GET,"environment","dev");
	$build_params[] = "--{$package_name}:env=".$environment;

	if($action_zip) {
		$build_params[] = "--{$package_name}:outputpath=dist-zip";
		$output_file 	= dirname(__DIR__)."/frontend/dist-zip/index.html";
	}

	if($device=value($_GET,"device"))
		$build_params[] = "--{$package_name}:device=".$device;	

	if($payload) {
		// ref eg. refs/heads/master
		preg_match("/([^\\/]+)$/",value($payload,"ref"),$matches);
		$github_branch = value($matches,1);

		if($branch!==$github_branch)
			die("Branches do not match. Local Branch: ".$branch.", Github Branch: ".$github_branch);
	}

	// Aded 2>&1 to all git commands because git redirect output to error output even if its not an error
	$commands = [ 	is_os_windows() ? "echo %PATH%" : "echo \$PATH",
		            "whoami",
		            "echo \"GitHub User: $github_name <$github_email>\"",
		            "pwd",
		            "cd ../ && git fetch --all 2>&1",
		            "cd ../ && git reset --hard origin/".$branch."  2>&1",
		            "cd ../ && git pull origin ".$branch." 2>&1",
		            "cd ../ && git submodule foreach --recursive git reset --hard origin/master 2>&1",
		            "cd ../ && git submodule foreach 'cd \$toplevel && git submodule update --force --init \$name' 2>&1",
		            //"cd ../deploy && git reset --hard origin/master 2>&1",
		            //"cd ../deploy && git pull origin master 2>&1",
		            "cd ../backend/command && php upgrade.php",
		            "cd ../backend/command && php init.php",
		            "cd ../frontend && npm install --loglevel=error",
		            "cd ../frontend && npm rebuild node-sass",
		            "cd ../frontend && npm run build ".implode(" ",$build_params),
	                "chown -R nginx:nginx ../frontend/dist" ];

	if($action_build) {
		$title	= "Building ".ucwords($environment);
		COMMANDER::create()->build($commands,[	"title"=>$title,
												"output"=>$output,
												"output_file"=>$output_file,
												"error_email"=>$github_email,
												"process_key"=>basename(dirname(__DIR__))]);
	}

	if($action_zip)
		COMMANDER::create()->zip(dirname(__DIR__)."/frontend/dist-zip",["ignore"=>"/^\.git/"]);