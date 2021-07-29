<?
require("__autoload.php");

$github_payload = @json_decode(value($_POST, "payload"));
$github_email 	= value($github_payload, ["pusher", "email"], "");
$branch 		= value($_GET, "branch");
$action			= value($_GET, "action", "build");
$environment	= value($_GET, "environment", "dev");
$platform		= value($_GET, "platform");

preg_match("/([^\\/]+)$/", value($github_payload, "ref"), $matches);
$github_branch = value($matches, 1);

$config = [
	"github_branch" => $github_branch,
	"branch" => $branch,
	"action" => $action,
	"environment" => $environment,
	"platform" => $platform
];

run_process("deployngx-process.php", $config, $github_email, "process.pid");
