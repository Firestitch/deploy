<?php
require("__autoload.php");

$config 		= json_decode($argv[1]);
$repo 			= value($config, "repo");
$output_file 	= dirname(__DIR__) . "/" . $repo . "/demo/index.html";

$commands = [
	is_os_windows() ? "cd" : "echo \$PWD",
	is_os_windows() ? "echo %PATH%" : "echo \$PATH",
	"cd ../ && git fetch --all 2>&1",
	"cd ../ && git reset --hard 2>&1",
	"cd ../ && git pull 2>&1",
	"cd ../deploy && git reset --hard",
	"cd ../ && git submodule update --force --remote deploy  2>&1",
	"cd ../ && git submodule init " . $repo . "  2>&1",
	"cd ../" . $repo . " && git rev-parse --abbrev-ref HEAD",
	"cd ../ && git submodule update --force --remote " . $repo . " 2>&1",
	"cd ../" . $repo . " && git submodule init  2>&1",
	"cd ../" . $repo . " && git submodule update --recursive  2>&1",
	"cd ../" . $repo . " && npm install --loglevel=error",
	"cd ../" . $repo . " && npm run demo:build"
];

COMMANDER::create()->build($commands, [
	"title" => "Building " . ucfirst($repo) . " Demo",
	"output" => true,
	"output_file" => $output_file,
	"process_key" => $repo
]);
