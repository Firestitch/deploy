<!DOCTYPE HTML>
<html lang="en-US">
	<head>
	    <meta charset="UTF-8">
		<style>
			<?=file_get_contents(dirname(__FILE__) . "/styles.css")?>
		</style>
	</head>
	<body>

		<h1><?=$title?></h1>
		<h2>Built on <?=date("F j, Y, g:i a e")?></h2>

		<div class="output">
			<?foreach ($commands as $command) {
	?>

		        <span class="prompt">$</span> <span class="command"><?=$command?></span>

		        <?php
	$this->flush();

	$descriptorspec = array(
		0 => ["pipe", "r"],
		1 => ["pipe", "w"],
		2 => ["pipe", "w"],
	);

	$this->flush();
	$process = proc_open($command, $descriptorspec, $pipes, realpath('./'));
	@fclose($pipes[0]);

	echo "<pre>";

	if (is_resource($process)) {

		while ($string = fgets($pipes[1])) {
			echo trim($converter->convert($string));
			$this->flush();
		}

		while ($string = fgets($pipes[2])) {
			if (trim($string)) {
				$string = trim($converter->convert($string));
				echo "<div class=\"error\">$string</div>";
				$this->flush();
				$errors[] = $string;
			}

			$exitcode = value(proc_get_status($process),"exitcode");

			if($exitcode > 0)
				$this->_failed = true;
		}
	}

	echo "</pre>";

	@fclose($pipes[1]);
	@fclose($pipes[2]);
	proc_close($process);
	?>

    <?$this->flush()?>

    <?php
	if ($errors) {
		break;
	}
	?>
			<?}?>
		</div>

		<?if ($this->_failed) {?>
			<h1 class="error" error="false">Build Failed</h1>
		<?} else {?>
			<h1 class="success">Build Successful</h1>
		<?}?>

	</body>
</html>
