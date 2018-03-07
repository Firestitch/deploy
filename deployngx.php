<?
	require("__autoload.inc");

	$environment	= value($_GET,"environment","dev");
	$branch 		= value($_GET,"branch");
	$action			= value($_GET,"action","build");
	$output			= $action=="build";
	$title			= "Building ".ucwords($environment);
	$branch 		= $branch ? $branch : shell_exec("cd ../ && git rev-parse --abbrev-ref HEAD");

	$commands = [ 	is_os_windows() ? "cd" : "echo \$PWD",
		            is_os_windows() ? "echo %PATH%" : "echo \$PATH",
		            "cd ../ && git fetch --all",
		            "cd ../ && git reset --hard origin/".$branch,
		            "cd ../ && git pull",
		            "cd ../ && git submodule foreach --recursive git reset --hard",
		            "cd ../ && git submodule update --init",
		            "git pull",
		            //"cd ../ && git submodule update --init --remote --merge deploy",
		            "cd ../ && git status",
		            "cd ../backend/command && php upgrade.php",
		            "cd ../backend/command && php init.php",
		            "cd ../frontend && npm install",
		            "cd ../frontend && ng build".($environment ? " --env=".$environment : "")."",
	                "chown -R nginx:nginx ../frontend/dist" ];

	if(preg_match("/build/",$action))
		COMMANDER::create()->build($commands,["title"=>$title,"output"=>$output]);

	if(preg_match("/zip/",$action))
		COMMANDER::create()->zip(dirname(__DIR__)."/frontend/dist",["ignore"=>"/^\.git/"]);