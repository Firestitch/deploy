<?
	require("commander/commander.inc");

	function value($arry,$index,$default=null) {
		if(is_object($array))
		    return property_exists($array,$index) ?	$array->$index : $default;
		if(is_array($array))
		    return array_key_exists($index,$arry) ? $arry[$index] : $default;
		return null;

	}

	function p($value) {
		print_r($value);
	}

	function is_os_windows() {
		return substr(PHP_OS,0,3) == 'WIN';
	}