<?
	require("commander/commander.inc");

	function value($arry,$index,$default=null) {
		return isset($arry[$index]) && $arry[$index] ? $arry[$index] : $default;
	}

	function p($value) {
		print_r($value);
	}

	function is_os_windows() {
		return substr(PHP_OS,0,3) == 'WIN';
	}