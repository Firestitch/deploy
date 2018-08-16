<?
	require("__autoload.inc");

	$branch 		= value($_GET,"branch");
	$action			= value($_GET,"action","build");
	$output			= $action=="build";
	$branch 		= $branch ? $branch : trim(shell_exec("cd ../ && git rev-parse --abbrev-ref HEAD"));
	$output_file 	= dirname(__DIR__)."/frontend/dist/index.html";
	$package_file	= dirname(__DIR__)."/frontend/package.json";
	$package_json	= @json_decode(file_get_contents($package_file));
	$package_name	= value($package_json,"name");

	$build_params = [];

	$environment = value($_GET,"environment","dev");
	$build_params[] = "--{$package_name}:env=".$environment;

	if($device=value($_GET,"device"))
		$build_params[] = "--{$package_name}:device=".$device;


	if($payload=COMMANDER::get_github_payload()) {
		// ref eg. refs/heads/master
		preg_match("/([^\\/]+)$/",value($payload,"ref"),$matches);
		$github_branch = value($matches,1);

		if($branch!==$github_branch)
			die("Branches do not match. Local Branch: ".$branch.", Github Branch: ".$github_branch);
	}

	$commands = [ 	is_os_windows() ? "cd" : "echo \$PWD",
		            is_os_windows() ? "echo %PATH%" : "echo \$PATH",
		            "cd ../ && git fetch --all",
		            "cd ../ && git reset --hard origin/".$branch,
		            "cd ../ && git pull",
		            "cd ../ && git submodule foreach --recursive git reset --hard origin/master",
		            "cd ../ && git submodule update --init",
		            "cd ../deploy && git reset --hard origin/master",
		            "cd ../deploy && git pull origin master",
		            "cd ../ && git status",
		            "cd ../backend/command && php upgrade.php",
		            "cd ../backend/command && php init.php",
		            "cd ../frontend && npm install",
		            "cd ../frontend && npm run build ".implode(" ",$build_params),
	                "chown -R nginx:nginx ../frontend/dist" ];

	if(preg_match("/build/",$action)) {
		$title	= "Building ".ucwords($environment);
		COMMANDER::create()->build($commands,[	"title"=>$title,
												"output"=>$output,
												"output_file"=>$output_file,
												"process_key"=>basename(dirname(__DIR__))]);
	}

	if(preg_match("/zip/",$action))
		COMMANDER::create()->zip(dirname(__DIR__)."/frontend/dist",["ignore"=>"/^\.git/"]);