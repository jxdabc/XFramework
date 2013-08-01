<?php
	class XUtil
	{
		public static function fillObjWithArray($obj, $array, $trim = false)
		{
			$properties = get_object_vars($obj);

			foreach ($array as $k => $v)
				if (array_key_exists($k, $properties))
				{
					if ($trim && is_string($v))
						$v = trim($v);
						$obj->$k = $v;
				}
		}

		public static function fillObjWithArrayAndTrim($obj, $array)
		{
			$properties = get_object_vars($obj);

			foreach ($array as $k => $v)
				if (array_key_exists($k, $properties))
					if (is_string($v))
						$obj->$k = trim($v);
		}

		public static function objToArray($obj)
		{
			return 
				$properties = get_object_vars($obj);
		}
		
		public static function objArrayToArray($objArray)
		{
			$result = array();
			foreach ($objArray as $k => $v)
				$result[$k] = self::objToArray($v);
			return $result;
		}

		public static function isAllPropertySet($obj) 
		{
			$properties = get_object_vars($obj);
			foreach ($properties as $k => $v)
				if (!isset($obj->$k))
					return false;
			return true;
		}

		public static function rm($dir) 
		{
			if (!file_exists($dir)) return true;
			if (!is_dir($dir) || is_link($dir)) return unlink($dir);
			foreach (scandir($dir) as $item)
			{
				if ($item == '.' || $item == '..') continue;
				if (!self::rm($dir . "/" . $item)) return false; 
			}
			return rmdir($dir);
		}
	}
?>