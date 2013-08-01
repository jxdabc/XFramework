<?php @require_once('MDB2.php'); ?>
<?php
	class XDBException extends XException {}
?>
<?php
	class XDBResult
	{
		public function __construct($result)
		{
			$this->result = $result;
		}

		public function fetchRow($asAssociate = true)
		{
			$row = $this->result->fetchRow($asAssociate ? 
				MDB2_FETCHMODE_ASSOC : MDB2_FETCHMODE_ORDERED);

			XDB::throwOnError($row, 'Fetching the row falied: ');

			return $row;
		}

		public function free()
		{
			$this->result->free();
		}

		private $result;
	}
?>
<?php
	class XDB 
	{
		public static function registerDB ($name, $DSN)
		{
			$options = array (
				'debug' => XCoreConfig::isDebug() ? 2 : 0,
				'persistent' => true
			);
			
			@$db = MDB2::factory($DSN, $options);

			self::throwOnError($db, 'Creating the DB object failed: ');

			return self::$connects[$name] = new XDB($db);
		}

		public static function getDB ($name)
		{
			if (!array_key_exists($name, self::$connects))
				throw new XDBException('DB ' . $name . ' not registered. ');

			return self::$connects[$name];
		}

		private static $connects = array();

		public function __construct ($db)
		{
			$this->db = $db;
		}

		public function execute($sql, $values = array()) 
		{
			$sql = $this->buildSQL($sql, $values);
			@$affectedRows = $this->db->exec($sql);
			self::throwOnError($affectedRows, 'Executing falied: ');

			return $affectedRows;
		}

		public function query($sql, $values = array())
		{
			$sql = $this->buildSQL($sql, $values);
			@$result = $this->db->query($sql);
			self::throwOnError($result, 'Querying falied: ');

			return new XDBResult($result);
		}

		public function queryAll($sql, $values = array(), $asAssociate = true)
		{
			$sql = $this->buildSQL($sql, $values);
			@$result = $this->db->queryAll($sql, NULL, $asAssociate ? 
				MDB2_FETCHMODE_ASSOC : MDB2_FETCHMODE_ORDERED);
			self::throwOnError($result, 'Querying falied: ');
			return $result;
		}

		public function queryCol($sql, $values = array())
		{
			$sql = $this->buildSQL($sql, $values);
			@$result = $this->db->queryCol($sql);
			self::throwOnError($result, 'Querying falied: ');
			return $result;
		}

		public function queryRow($sql, $values = array(), $asAssociate = true)
		{
			$sql = $this->buildSQL($sql, $values);
			@$result = $this->db->queryRow($sql, null, $asAssociate ? 
				MDB2_FETCHMODE_ASSOC : MDB2_FETCHMODE_ORDERED);
			self::throwOnError($result, 'Querying falied: ');
			return $result;
		}

		public function queryOne($sql, $values = array())
		{
			$sql = $this->buildSQL($sql, $values);
			@$result = $this->db->queryOne($sql);
			self::throwOnError($result, 'Querying falied: ');
			return $result;
		}

		public function queryObjects($class, $sql, $values = array())
		{
			$result = array();
			$data = $this->queryAll($sql, $values);

			$dataSize = count($data);
			for ($i = 0; $i < $dataSize; $i++)
			{
				$obj = new $class();
				XUtil::fillObjWithArray($obj, $data[$i]);
				$result[] = $obj;
			}

			return $result;
		}

		public function insertObject($obj, $table)
		{
			$sql[0] = "INSERT INTO {$table}(";
			$sql[1] = 'VALUES(';

			$values = array();

			$properties = get_object_vars($obj);
			foreach ($properties as $k => $v)
			{
				if (count($values) != 0) {$sql[0] .= ', '; $sql[1] .= ', ';}
				$sql[0] .= $k; $sql[1] .= '?';
				$values[] = $v;
			}

			$sql[0] .= ') '; $sql[1] .= ');';
			$sql = implode($sql);

			return $this->execute($sql, $values);
		}

		public function updateObject($obj, $table)
		{
			$PK = $obj->getPK();
			$properties = get_object_vars($obj);

			$sql = "UPDATE {$table} SET ";
			$values = array();
			
			foreach ($properties as $k => $v)
				if ($k != $PK)
				{
					if (count($values) != 0) $sql .= ', ';
					$sql .= "{$k}=?";

					$values[] = $v;
				}

			$sql .= " WHERE {$PK}=?;";
			$values[] = $obj->$PK;

			return $this->execute($sql, $values);
		}

		public function deleteObject($obj, $table)
		{
			$PK = $obj->getPK();

			return $this->execute("DELETE FROM {$table} WHERE {$PK}=?;", 
				array($obj->$PK));
		}

		public function listTableFields($table)
		{
			self::throwOnError(@$this->db->loadModule('Manager'), 
				'Loadding Manager module failed: ');
			$fields = @$this->db->manager->listTableFields($table);
			self::throwOnError($fields, 'Getting table fields failed: ');

			self::throwOnError(@$this->db->loadModule('Reverse', null, true),
				'Loadding Reverse module failed: ');

			foreach ($fields as $field) 
			{
				$def = @$this->db->getTableFieldDefinition($table, $field);
				self::throwOnError($def,
				"Getting the definition for {$table}.{$field} failed: ");
				$result[$field] = $def[0];
			}
			
			return $result;
		}

		public static function throwOnError($obj, $msg = '')
		{
			if (@!MDB2::isError($obj))
				return;

			$errorMessage = $msg . 
				(XCoreConfig::isDebug() ? 
				$obj->getUserInfo() : $obj->getMessage());

			throw new XDBException($errorMessage);
		}

		private function buildSQL($sql, $values)
		{
			$tokens = preg_split('/((?<!\\\\)\\?)/', $sql, -1,
				PREG_SPLIT_DELIM_CAPTURE);

			$placeHolderCount = (count($tokens) - 1) / 2;
			$placeHolderCount = (int)$placeHolderCount;

			if ($placeHolderCount != count($values))
				throw new XDBException
				('BuildSQL failed with place holder value unconformity. ');

			$valueIndex = 0;
			foreach ($tokens as $k => $v)
				if ($v == '?')
				{
					$value = $values[$valueIndex++];
					if (is_string($value) && $value == '')
						$tokens[$k] = '\'\'';
					else
						@$tokens[$k] = (string)
						$this->db->quote($value);
				}
				else
					$tokens[$k] = str_replace('\\?', '?', $v);

			return implode($tokens);
		}

		private $db;
	}
?>