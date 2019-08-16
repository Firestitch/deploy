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
                    "cd ../ && git --quiet fetch --all 2>&1",
		            "cd ../ && git --quiet reset --hard 2>&1",
		            "cd ../ && git --quiet pull 2>&1",
		            "cd ../deploy && git --quiet reset --hard",
		            "cd ../ && git --quiet submodule update --remote deploy",
		            "cd ../ && git --quiet submodule init ".$repo,
		            "cd ../".$repo." && git --quiet rev-parse --abbrev-ref HEAD",
		            "cd ../".$repo." && git --quiet reset --hard",
		            "cd ../ && git --quiet submodule update --remote ".$repo,
		            "cd ../".$repo." && git --quiet submodule init",
		            "cd ../".$repo." && git --quiet submodule update --recursive",
		            "cd ../".$repo." && npm rebuild node-sass",
                    "cd ../".$repo." && npm install --loglevel=error",
                    "cd ../".$repo." && npm run demo:build"];

	COMMANDER::create()->build($commands,[	"title"=>"Building ".ucfirst($repo)." Demo",
											"output"=>true,
											"output_file"=>$output_file,
											"error_email"=>$github_email,
											"process_key"=>$repo]);