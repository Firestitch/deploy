<?
	//Disables Nginx's gzip/buffering and allows for output streaming
	header('X-Accel-Buffering: no');
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	ini_set('output_buffering', 'off');
	ini_set('implicit_flush', true);
	date_default_timezone_set('America/Toronto');

	$pid = @file_get_contents("process.pid");
	if($pid)
	      exec("kill ".$pid);

	@unlink("process.pid");
	@mkdir("deploys");
	$build = @$_GET["build"] ? $_GET["build"] : (@$argv[1] ? $argv[1] : "development");
	$branch = @$_GET["branch"] ? $_GET["branch"] : (@$argv[2] ? $argv[2] : "master");

	if(!@$argv && @file_get_contents("php://input")) {
		$cmd = "php deployngx.php ".$build." > deploys/".date("Y-m-d\TH:i:s")." 2>&1 & echo $!";
		$pid = shell_exec($cmd);
		die("Process: ".$pid."\nBuild: ".$build."\nDate: ".date("Y-m-dTH:i:s"));
	}

	file_put_contents("process.pid",getmypid());

	$is_development = $build=='development';
	$is_staging     = $build=='staging';

	if(!$branch)
		$branch = shell_exec("cd ../ && git rev-parse --abbrev-ref HEAD");

	$commands = [ 'echo $PWD',
	            'echo $PATH',
	            'cd ../ && git fetch --all 2>&1',
	            'cd ../ && git reset --hard origin/'.$branch.' 2>&1',
	            'cd ../ && git pull 2>&1',
	            'cd ../ && git submodule foreach --recursive git reset --hard 2>&1',
	            'cd ../ && git submodule update --init 2>&1',
	            'cd ../ && git submodule update --init --remote --merge deploy 2>&1',
	            'cd ../ && git status 2>&1',
	            'cd ../frontend && npm install 2>&1'];

	/*if($is_development || $is_staging) {
	  $commands[] = 'cd ../backend/command && php upgrade.php 2>&1';
	  $commands[] = 'cd ../backend/command && php init.php 2>&1';
	}*/

	$commands = array_merge($commands,
	                      [  'cd ../frontend && ng build',
	                         'chown -R nginx:nginx ../frontend/dist']);
?>
<!DOCTYPE HTML>
<html lang="en-US">
	<head>
	    <meta charset="UTF-8">
	</head>
	<body>

	<style>
	  .dn { display: none; }
	  body { background-color: #000000; color: #FFFFFF; font-weight: bold; padding: 0 10px; font-family: monospace; }

	  #done-error { color: red; }
	  #done-success { color: green; }
	  a, a:hover, a:link {
	      color: #1863C8;
	      text-decoration: none;
	  }
	</style>

	<h1>Building <?=ucwords($build)?></h1>
	<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
	<script>
	  var down = function() {
	    setTimeout(function() {
	      if($(".done").length) return;
	      $('body').animate({scrollTop: $(document).height()}, 'fast');
	      down();
	    },1000);
	  }
	  //down();
	</script>

	<div class="output">
	<? foreach($commands AS $command) { ?>

	        <span style="color: #6BE234;">$</span> <span style="color: #729FCF;"><?=$command?></span>

	        <? @ob_flush() ?>
	        <? flush() ?>

	        <?
	          $descriptorspec = array(
	             0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
	             1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
	             2 => array("pipe", "w")    // stderr is a pipe that the child will write to
	          );

	          flush();
	          $process = proc_open($command, $descriptorspec, $pipes, realpath('./'));
	          @fclose($pipes[0]);
	          echo "<pre>";

	          if (is_resource($process)) {
	              while($s=fgets($pipes[1])) {
	                  print htmlentities(preg_replace("/\[\d+m/",'',$s));
	                  $arr = proc_get_status($process);
	                  @ob_flush();
	                  flush();
	              }
	          }

	          echo "</pre>";

	          @fclose($pipes[1]);
	          @fclose($pipes[2]);
	          proc_close($process);
	        ?>

	        <? @ob_flush() ?>
	        <? flush() ?>
	<? } ?>
	</div>

	<h1 id="done-success" class="done dn"><?=ucwords($build)?> Build Complete!</h1>
	<div id="done-error" class="done dn">
	  <h1>Error in <?=ucwords($build)?> Build!</h1>
	  <a href="<?=$_SERVER["REQUEST_URI"]?>?resolve=true">Auto resolve with NPM and Bower update</a>
	</div>

	<script>

	  if($(".output").text().toLowerCase().indexOf('aborted due to warnings')>0)
	      $("#done-error").show();
	  else
	      $("#done-success").show();
	</script>


	</body>
</html>
