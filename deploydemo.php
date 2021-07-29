<?php
require "__autoload.php";

$payload = @json_decode(value($_POST, "payload"));
$github_email = value($payload, ["pusher", "email"], "");
$repo = value($_GET, "repo", value($payload, ["repository", "name"]));
$repo = preg_replace("/(fs-|ngx-|-)/", "", $repo);

if (!$repo) {
	die("Failed to get repository name");
}

$config = [
	"repo" => $repo,
];

@mkdir("processes");
run_process("deploydemo-process.php", $config, $github_email, "processes/" . $repo . ".pid");
