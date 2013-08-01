<?php
	class XConfig 
	{
		public static function getConfig() 
		{
			static $loaded = false; 
			if (!$loaded)
			{
				$loaded = true;
				require_once (XCoreConfig::getConfigFile());
			}

			return self::$config;
		}

		private static $config = array();
	}
?>