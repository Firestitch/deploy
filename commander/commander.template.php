<!DOCTYPE HTML>
<html lang="en-US">
	<head>
	    <meta charset="UTF-8">
	</head>
	<body error="false">

		<style>
		  .dn { display: none; }
		  body { background-color: #000000; color: #FFFFFF; font-weight: bold; padding: 0 10px; font-family: monospace; }

		  .error { color: red; }
		  .error * { color: red !important; }
		  .success { color: green; }
		  a, a:hover, a:link {
		      color: #1863C8;
		      text-decoration: none;
		  }
		  .command {
		  	color: #729FCF;
		  }
		  .prompt {
		  	color: #6BE234;
		  }
		</style>

		<h1><?=$title?></h1>
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

		  function error(id) {
		  	$("body").attr('error','true');

		  	if(id) {
		  		$("#" + id).addClass('error');
		  	}
		  }
		</script>

		<div class="output">
		<? foreach($commands AS $command) { ?>

	        <span class="prompt">$</span> <span class="command"><?=$command?></span>

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

				echo "<pre>";

	          	if (is_resource($process)) {

	          		$guid = uniqid();
	          		echo '<span id="'.$guid.'">';
		            while($output=fgets($pipes[1])) {
						echo $converter->convert($output);

		                // do {
		                // 	$arr = proc_get_status($process);

		                // 	if($arr["exitcode"]>0) {
		                // 		echo "<script>error('".$guid."')</script>";
		                // 	}

		                // } while($arr["running"]);

		                @ob_flush();
		                flush();
		            }

		            echo '</span>';

		         	while($output=fgets($pipes[2])) {
						echo '<div class="error">'.$output.'</div><script>error()</script>';
						@ob_flush();
						flush();
		         	}
		        }

	          	echo "</pre>";
	          	@fclose($pipes[0]);
	        	@fclose($pipes[1]);
	         	@fclose($pipes[2]);
	          	proc_close($process);
	        ?>

	        <? @ob_flush() ?>
	        <? flush() ?>
		<? } ?>
		</div>

		<h1 id="success" class="success dn">Build Complete</h1>
		<h1 id="error" class="error dn" error="false">Build Failed</h1>

		<script>
			$("body[error='true']").length ? $("#error").show() : $("#success").show();
		</script>

	</body>
</html>
