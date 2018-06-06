<?
	require("__autoload.inc");

	$environment	= value($_GET,"environment","dev");
	$branch 		= value($_GET,"branch");
	$action			= value($_GET,"action","build");
	$aot			= value($_GET,"aot","true")==="true";
	$output			= $action=="build";
	$title			= "Building ".ucwords($environment);
	$branch 		= $branch ? $branch : shell_exec("cd ../ && git rev-parse --abbrev-ref HEAD");
	$output_file 	= dirname(__DIR__)."/frontend/dist/index.html";

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
		            "cd ../frontend && ng build".($environment ? " --env=".$environment : "")." ".($aot ? "--aot" : ""),
	                "chown -R nginx:nginx ../frontend/dist" ];

	if(preg_match("/build/",$action))
		COMMANDER::create()->build($commands,["title"=>$title,"output"=>$output,"output_file"=>$output_file]);

	if(preg_match("/zip/",$action))
		COMMANDER::create()->zip(dirname(__DIR__)."/frontend/dist",["ignore"=>"/^\.git/"]);