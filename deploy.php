<?
	require("__autoload.inc");

	$environment	= value($_GET,"environment","development");
	$branch 		= value($_GET,"branch");
	$action			= value($_GET,"action","build");
	$output			= $action=="build";
	$title			= "Building ".ucwords($environment);
	$minify 		= value($_GET,"minify")=="true";
	$branch 		= $branch ? $branch : shell_exec("cd ../ && git rev-parse --abbrev-ref HEAD");

	$commands = [ 	is_os_windows() ? "cd" : "echo \$PWD",
		            is_os_windows() ? "echo %PATH%" : "echo \$PATH",
		            "cd ../ && git fetch --all 2>&1",
		            "cd ../ && git reset --hard origin/".$branch." 2>&1",
		            "cd ../ && git pull 2>&1",
		            "cd ../ && git submodule foreach --recursive git reset --hard origin/master 2>&1",
		            "cd ../ && git submodule update --init 2>&1",
		            "cd ../ && git status 2>&1",
		            "cd ../frontend && bower prune 2>&1",
		            "cd ../frontend && bower update 2>&1",
		            "cd ../frontend && grunt build:".$environment.($minify ? "" : " --nomin")." 2>&1",
	                "chown -R nginx:nginx ../frontend/dist"];

	if($environment=="development" || $environment=="staging") {
  		$commands[] = "cd ../backend/command && php upgrade.php";
   		$commands[] = "cd ../backend/command && php init.php";
	}

	if(preg_match("/build/",$action))
		COMMANDER::create()->build($commands,["title"=>$title,"output"=>$output]);

	if(preg_match("/zip/",$action))
		COMMANDER::create()->zip(dirname(__DIR__)."/frontend/dist",["ignore"=>"/^\.git/"]);


