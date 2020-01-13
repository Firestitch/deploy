<?	
	require("__autoload.inc");
	
	$github_payload = @json_decode(value($_POST,"payload"));
	$github_email 	= value($github_payload,["pusher","email"],"");
	$repo			= value($_GET,"repo",value($payload,["repository","name"]));
    $repo 			= preg_replace("/(fs-|ngx-|-)/","",$repo);

    if(!$repo)
    	die("Failed to get repository name");

	$config = [
		"repo"=>$repo
	];

	run_process("deploydemo-process.php", $config, $github_email);

