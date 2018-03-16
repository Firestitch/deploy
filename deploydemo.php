<?
	require("__autoload.inc");

    $repo = @$_GET["repo"];
    if(!$repo) {
        $payload = json_decode(value($_POST,"payload"));
        $repo = preg_replace("/fs-/","",$payload->repository->name);
    }

    if(!$repo)
    	die("Failed to get repository name");

	$commands = [  	is_os_windows() ? "cd" : "echo \$PWD",
		            is_os_windows() ? "echo %PATH%" : "echo \$PATH",
                    //"cd ../ && git fetch --all",
		            //"cd ../ && git reset --hard origin/master",
		            //"cd ../ && git pull",
		            //"cd ../ && git submodule foreach --recursive git reset --hard origin/master",
		            //"cd ../ && git submodule update --init",
                    "cd ".$repo." && npm install",
                    "cd ".$repo." && npm run demo",
                	"chown -R nginx:nginx ../frontend/dist"];

	COMMANDER::create()->build($commands,["title"=>"Building Demo","output"=>true]);