<?
	require("__autoload.inc");

    $repo = @$_GET["repo"];
    if(!$repo) {
        $payload = json_decode(value($_POST,"payload"));
        $repo = $payload->repository->name;
    }

    $repo = preg_replace("/(fs-|-)/","",$repo);

    if(!$repo)
    	die("Failed to get repository name");

	$commands = [  	is_os_windows() ? "cd" : "echo \$PWD",
		            is_os_windows() ? "echo %PATH%" : "echo \$PATH",
                    "cd ../ && git fetch --all",
		            "cd ../ && git reset --hard origin/master",
		            "cd ../ && git pull",
		            "cd ../ && git submodule foreach --recursive git reset --hard origin/master",
		            "cd ../ && git submodule update --recursive --remote --init",
                    "cd ../".$repo." && npm install",
                    "cd ../".$repo." && npm run demo:build",
                	"chown -R nginx:nginx ../frontend/dist"];

	COMMANDER::create()->build($commands,["title"=>"Building ".ucfirst($repo)." Demo","output"=>true]);