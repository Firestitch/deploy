<?
	require("__autoload.inc");

	$environment	= value($_GET,"environment","development");
	$branch 		= value($_GET,"branch");
	$output 		= value($_GET,"output");

	if($output=="zip") {

		COMMANDER::create()->zip(dirname(__DIR__)."/frontend/dist",["ignore"=>"/^\.git/"]);

	} else {

		if(!$branch)
			$branch = shell_exec("cd ../ && git rev-parse --abbrev-ref HEAD");

		$commands = [ 	is_os_windows() ? "cd" : "echo \$PWD",
			            is_os_windows() ? "echo %PATH%" : "echo \$PWD",
			            "cd ../ && git fetch --all",
			            "cd ../ && git reset --hard origin/".$branch,
			            "cd ../ && git pull",
			            "cd ../ && git submodule foreach --recursive git reset --hard",
			            "cd ../ && git submodule update --init",
			            "cd ../ && git submodule update --init --remote --merge deploy",
			            "cd ../ && git status",
			            "cd ../backend/command && php upgrade.php",
			            "cd ../backend/command && php init.php",
			            "cd ../frontend && npm install",
			            "cd ../frontend && ng build".($environment ? " --env=".$environment : "")."",
		                "chown -R nginx:nginx ../frontend/dist" ];

		COMMANDER::create()->run($commands,["title"=>"Building ".ucwords($environment)]);
	}