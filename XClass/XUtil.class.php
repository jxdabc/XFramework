<?php
	class XUtil
	{
		public static function getObjVars($obj) {
			return get_object_vars($obj);
		}

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

		// @param $record_list : [record1, record2, record3,...,recordN]
		// @param $level_list : 
		// 		[
		//			'level_key1' => ['level_value_key11', 'level_value_key12',...,'level_value_key1N'], 
		//			'level_key2' => ['level_value_key21', 'level_value_key22',...,'level_value_key2N'], 
		//			...
		//			'level_keyM' => ['level_value_keyM1', 'level_value_keyM2',...,'level_value_keyMN'] 
		//		]
		public static function recordToTree($record_list, $level_list, $need_return_ordered_array = true)
		{
			$tree = array();

			$level = count($level_list);

			foreach ($record_list as $record) 
			{
				$root = & $tree;
				foreach ($level_list as $level_key => $level_value_keys)
				{
					if (!isset($root[$level_key]))
						$root[$level_key] = array();

					$list = & $root[$level_key];

					$v = $record[$level_key];

					if ($v == NULL || $v == "NULL")
						break;

					if (!isset($list[$v]))
					{
						$list[$v] = array();
						$info = & $list[$v];
						foreach ($level_value_keys as $level_value_key) 
							$info[$level_value_key] = $record[$level_value_key];
					}

					$root = & $list[$v];
				}
			}

			if (!$need_return_ordered_array)
				return $tree;
			
			return self::indexTreeToOrderedTree($tree, array_keys($level_list));
		}

		public static function indexTreeToOrderedTree($tree, $level_list)
		{
			$orderedTree = array();

			$this_level = NULL;
			if ($level_list)
			{
				$this_level = current($level_list);
				if (next($level_list) === FALSE)
					$level_list = NULL;
			}

			foreach ($tree as $item_key => $item_value) 
			{
				if ($item_key != $this_level)
					$orderedTree[$item_key] = $tree[$item_key];
			}

			if ($this_level)
			{
				$orderedTree[$this_level] = array();
				foreach ($tree[$this_level] as $subtree) 
					$orderedTree[$this_level][] 
						= self::indexTreeToOrderedTree($subtree, $level_list);
			}

			return $orderedTree;
		}
	}
?>