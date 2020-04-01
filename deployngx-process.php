<?
require "__autoload.inc";

$config = json_decode($argv[1]);
$branch = value($config, "branch");
$action = value($config, "action", "build");
$github_branch = value($config, "github_branch");
$action_build = preg_match("/build/", $action);
$action_zip = preg_match("/zip/", $action);
$output = $action == "build";
$branch = $branch ? $branch : trim(shell_exec("cd ../ && git rev-parse --abbrev-ref HEAD"));
$dir = dirname(__DIR__) . "/";
$frontend_dir = $dir . "frontend/";
$backend_dir = $dir . "backend/";
$output_file = $frontend_dir . "dist/index.html";
$package_file = $frontend_dir . "package.json";
$package_json = @json_decode(file_get_contents($package_file));
$package_name = value($package_json, "name");
$build_start_date = date("F j, Y, g:i a e");

$build_params = [];
$environment = value($config, "environment", "dev");
$build_params[] = "--{$package_name}:env=" . $environment;

if ($action_zip) {
	$build_params[] = "--{$package_name}:outputpath=dist-zip";
	$output_file = $frontend_dir . "dist-zip/index.html";
}

if ($platform = value($config, "platform")) {
	$build_params[] = "--{$package_name}:platform=" . $platform;
}

if ($github_branch && $branch !== $github_branch) {
	die("Branches do not match. Local Branch: " . $branch . ", Github Branch: " . $github_branch);
}

// Aded 2>&1 to all git commands because git redirect output to error output even if its not an error
$commands = [
	is_os_windows() ? "echo %PATH%" : "echo \$PATH",
	"pwd",
	"cd ../ && git fetch --all 2>&1",
	"cd ../ && git reset --hard origin/" . $branch . "  2>&1",
	"cd ../ && git pull origin " . $branch . " 2>&1",
	"cd ../ && git submodule foreach --recursive git reset --hard origin/master 2>&1",
	"cd ../ && git submodule foreach 'cd \$toplevel && git submodule update --force --init \$name' 2>&1",
	"rm -rf ../frontend/dist",
	"mkdir ../frontend/dist",
	"cp pages/building.html ../frontend/dist/index.html",
	"sed -i 's/{{build_start_date}}/" . $build_start_date . "/' ../frontend/dist/index.html",
	"sed -i 's/{{process_id}}/" . getmypid() . "/' ../frontend/dist/index.html",
	"cd ../frontend && npm install --loglevel=error",
	"cd ../frontend && npm rebuild node-sass",
	"cd ../frontend && npm run build " . implode(" ", $build_params),
	"chown -R nginx:nginx ../frontend/dist",
];

if (is_file($backend_dir . "command/upgrade.php")) {
	$commands[] = "cd ../backend/command && php upgrade.php";
}

if (is_file($backend_dir . "command/init.php")) {
	$commands[] = "cd ../backend/command && php init.php";
}

if ($action_build) {
	$title = "Building " . ucwords($environment);
	COMMANDER::create()->build($commands, ["title" => $title,
		"output" => $output,
		"output_file" => $output_file,
		"process_key" => basename(dirname(__DIR__))]);
}

if ($action_zip) {
	COMMANDER::create()->zip($frontend_dir . "dist-zip", ["ignore" => "/^\.git/"]);
}
