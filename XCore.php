<?php
	// Core config
	class XCoreConfig
	{
		public static function getClassPath()
		{
			self::init();
			return self::$classPath;
		}

		public static function getConfigFile()
		{
			self::init();
			return self::$configFile;
		}

		public static function getCommonFile()
		{
			self::init();
			return self::$commonFile;
		}

		public static function isDebug()
		{
			self::init();
			return self::$debug;
		}
		
		public static function getErrorPages()
		{
			self::init();
			return self::$errorPages;
		}
		
		public static function getDefaultAction()
		{
			self::init();
			return self::$defaultAction;
		}

		private static function init()
		{
			static $init = false;
			if (!$init)
			{
				$init = true;
				require_once ('XCoreConfig.cnf');
			}
		}

		private static $classPath;
		private static $configFile;
		private static $commonFile;
		private static $defaultAction;
		private static $errorPages = array();
		private static $debug;
	}

	// Class Loader
	function __autoload($class)
	{
		$classPath = XCoreConfig::getClassPath();
		foreach ($classPath as $path) 
		{
			$path = rtrim($path, ' /\\');
			$path = $path . '/' . $class . '.class.php';
			if (is_file($path))
			{
				require_once($path);
				return;
			}
		}
	}

	try
	{
		require_once(XCoreConfig::getCommonFile());
		XCoreRouter::go();
	}
	catch (Exception $e)
	{
		XCoreRouter::goErrorPageAndDie(500, $e);
	}

?>
