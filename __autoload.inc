<?
	require("commander/commander.inc");

	function value($var,$index,$default=null) {

		if($var===null)
			return $default;

		$is_array = is_array($index);

		if($is_array && count($index)==1) {
			$index = array_shift($index);
			$is_array = is_array($index);
		}

		if($is_array) {

			$first_index = @$index[0];

			if($first_index!==null) {

				$vars = null;
				if(is_array($var))
					$vars = @$var[$first_index];

				elseif(is_object($var)) {

		        	if(is_a($var,"ArrayAccess"))
		        		$vars = $var[$first_index];
		        	else
		        		$vars = @$var->$first_index;
				}

				if($vars!==null) {

					array_shift($index);

					if(count($index)==1)
						$index = $index[0];

					if(is_array($vars) || is_object($vars))
						return value($vars,$index,$default);

					return $default;
				}
			}

			return $default;

        } elseif(is_array($var)) {

            if(@array_key_exists($index,$var))
                return $var[$index];

        } elseif(is_object($var)) {

        	if(is_a($var,"ArrayAccess")) {

        		if(isset($var[$index]))
        			return $var[$index];

        	} elseif(isset($var->$index))
        		return $var->$index;
        }

		return $default;
	}

	function p($value) {
		print_r($value);
	}

	function is_os_windows() {
		return substr(PHP_OS,0,3) == 'WIN';
	}