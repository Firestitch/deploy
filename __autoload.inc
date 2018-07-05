<?
	require("commander/commander.inc");

	function value($arry,$index,$default=null) {
		if(is_object($arry))
		    return property_exists($arry,$index) ?	$arry->$index : $default;
		if(is_array($arry))
		    return array_key_exists($index,$arry) ? $arry[$index] : $default;
		return null;
	}

	function p($value) {
		print_r($value);
	}

	function is_os_windows() {
		return substr(PHP_OS,0,3) == 'WIN';
	}