<?php
$errors = [];
?>
<!DOCTYPE HTML>
<html lang="en-US">

<head>
	<meta charset="UTF-8">
	<style>
		<?php echo file_get_contents(dirname(__FILE__) . "/styles.css") ?>
	</style>
</head>

<body>
	<div class="body-container">
		<h1><?php echo $title ?></h1>
		<h2>Built on <?php echo date("F j, Y, g:i a e") ?></h2>

		<div class="output">
			<?php foreach ($commands as $command) { ?>

				<span class="prompt">$</span> <span class="command"><?php echo $command ?></span>

				<?php
				$self->flush();

				$descriptorspec = array(
					0 => ["pipe", "r"],
					1 => ["pipe", "w"],
					2 => ["pipe", "w"],
				);

				$self->flush();
				$process = proc_open($command, $descriptorspec, $pipes, realpath('./'));
				@fclose($pipes[0]);

				echo "<pre>";

				if (is_resource($process)) {

					while ($string = fgets($pipes[1])) {
						echo trim($converter->convert($string));
						$self->flush();
					}

					while ($string = fgets($pipes[2])) {
						if (trim($string)) {
							$string = trim($converter->convert($string));
							echo "<div class=\"error\">$string</div>";
							$self->flush();
							$errors[] = $string;
						}
					}

					$exitcode = value(proc_get_status($process), "exitcode");

					if ($exitcode > 0)
						$self->_failed = true;
				}

				echo "</pre>";
				@fclose($pipes[1]);
				@fclose($pipes[2]);
				proc_close($process);
				?>

				<?php $self->flush() ?>

			<?php } ?>
		</div>

		<?php if ($self->_failed) { ?>
			<h1 class="error" error="false">Build Failed</h1>
		<?php } else { ?>
			<h1 class="success">Build Successful</h1>
		<?php } ?>
	</div>
</body>

</html>