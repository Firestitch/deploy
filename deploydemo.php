<?
	require("__autoload.inc");

    $repo 			= @$_GET["repo"];
    $payload 		= COMMANDER::get_github_payload();

    if(!$repo) 
        $repo = value($payload,["repository","name"]);

    $repo 			= preg_replace("/(fs-|ngx-|-)/","",$repo);
    $output_file 	= dirname(__DIR__)."/".$repo."/demo/index.html";
    $github_email 	= value($payload,["pusher","email"],"");
    $github_name 	= value($payload,["pusher","name"],"Unknown");	

    if(!$repo)
    	die("Failed to get repository name");

	$commands = [  	is_os_windows() ? "cd" : "echo \$PWD",
		            is_os_windows() ? "echo %PATH%" : "echo \$PATH",
		            "echo \"GitHub User: $github_name $github_email\"",
                    "cd ../ && git fetch --all 2>&1",
		            "cd ../ && git reset --hard 2>&1",
		            "cd ../ && git pull 2>&1",
		            "cd ../deploy && git reset --hard",
		            "cd ../ && git submodule --quiet update --remote deploy",
		            "cd ../ && git submodule --quiet init ".$repo,
		            "cd ../".$repo." && git rev-parse --abbrev-ref HEAD",
		            "cd ../".$repo." && git reset --hard",
		            "cd ../ && git submodule --quiet update --remote ".$repo,
		            "cd ../".$repo." && git submodule --quiet init",
		            "cd ../".$repo." && git submodule --quiet update --recursive",
		            "cd ../".$repo." && npm rebuild node-sass",
                    "cd ../".$repo." && npm install --loglevel=error",
                    "cd ../".$repo." && npm run demo:build"];

	COMMANDER::create()->build($commands,[	"title"=>"Building ".ucfirst($repo)." Demo",
											"output"=>true,
											"output_file"=>$output_file,
											"error_email"=>$github_email,
											"process_key"=>$repo]);