<?php
	class XCoreEnv 
	{
		public static function getBaseRequestURI()
		{
			static $init = false;
			static $baseURI = '';
			
			if (!$init)
			{
				$baseURI = $_SERVER['REQUEST_URI'];
				if (array_key_exists('PATH_INFO', $_SERVER))
					$baseURI = substr($baseURI, 0, strpos($baseURI, $_SERVER['PATH_INFO']) + 1);
				else
					$baseURI = '/';
			}

			return $baseURI;
		}
	}
?>