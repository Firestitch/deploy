<?
	require("commander/commander.inc");

	function value($arry,$index,$default=null) {
		return array_key_exists($index,$arry) ? $arry[$index] : $default;
	}

	function p($value) {
		print_r($value);
	}

	function is_os_windows() {
		return substr(PHP_OS,0,3) == 'WIN';
	}