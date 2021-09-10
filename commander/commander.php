<?php
require_once __DIR__ . '/../vendor/autoload.php';

use SensioLabs\AnsiConverter\AnsiToHtmlConverter;

class Commander {

	protected $_output_file = "";
	protected $_output_buffer = "";
	protected $_output = true;
	protected $_errors = [];
	protected $_failed = false;

	static function create() {
		return new Commander();
	}

	function build($commands, $options = ["title" => "", "capture" => true, "output" => true]) {
		$title = $this->get_option("title", "Building");
		$this->_options = $options;
		$this->_output = $this->get_option("output", true);
		$this->_output_file = $this->get_option("output_file");
		$this->_process_key = $this->get_option("process_key");
		$errors = [];

		$this->log("BUILD-START");

		$converter = new AnsiToHtmlConverter();

		error_reporting(E_ALL);
		date_default_timezone_set('America/Toronto');

		ini_set("max_execution_time", 300);
		set_time_limit(300);

		if ($this->_output_file)
			@unlink($this->_output_file);

		$this->start();
		$self = $this;
		require_once("commander.template.php");
		@ob_end_flush();

		if ($this->_output_file && $this->_failed) {
			@mkdir(dirname($this->_output_file), 0777, true);
			@file_put_contents($this->_output_file, $this->_output_buffer);
		}

		$this->log("BUILD-END");

		if ($this->_failed) {
			fwrite(STDERR, $this->_output_buffer);
			exit(1);
		}
	}

	function get_option($name, $default = null) {
		return value($this->_options, $name, $default);
	}

	function start() {
		ob_start(function ($buffer) {
			$this->_output_buffer .= $buffer;
			return $this->_output ? $buffer : '';
		});
	}

	function flush() {
		@ob_end_flush();
		$this->start();
	}

	function log($message) {

		if (!is_dir("/var/log/nginx/"))
			return;

		$log = [];
		$log[] = date("M j H:i:s");
		$log[] = value($_SERVER, "HTTP_HOST");
		$log[] = "\"" . $this->get_option("title") . "\"";
		$log[] = $message;

		file_put_contents("/var/log/nginx/deploy.log", implode(" ", $log) . "\n", FILE_APPEND);
	}
}
