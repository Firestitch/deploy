<?
	require("__autoload.inc");

    $repo = @$_GET["repo"];

    if(!$repo) {
        $repo = value(COMMANDER::get_github_payload(),["repository","name"]);
        //file_put_contents("payload.json",value($_POST,"payload"));
    }

    $repo 			= preg_replace("/(fs-|ngx-|-)/","",$repo);
    $output_file 	= dirname(__DIR__)."/".$repo."/demo/index.html";

    if(!$repo)
    	die("Failed to get repository name");

	$commands = [  	is_os_windows() ? "cd" : "echo \$PWD",
		            is_os_windows() ? "echo %PATH%" : "echo \$PATH",
                    "cd ../ && git fetch --all 2>&1",
		            "cd ../ && git reset --hard 2>&1",
		            "cd ../ && git pull 2>&1",
		            "cd ../ && git submodule update deploy",
		            "cd ../ && git submodule init ".$repo,
		            "cd ../".$repo." && git rev-parse --abbrev-ref HEAD",
		            "cd ../".$repo." && git reset --hard",
		            "cd ../".$repo." && git pull",
		            "cd ../".$repo." && npm rebuild node-sass",
                    "cd ../".$repo." && npm install --loglevel=error",
                    "cd ../".$repo." && npm run demo:build"];

	COMMANDER::create()->build($commands,[	"title"=>"Building ".ucfirst($repo)." Demo",
											"output"=>true,
											"output_file"=>$output_file,
											"process_key"=>$repo]);