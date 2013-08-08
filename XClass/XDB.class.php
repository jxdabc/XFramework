<?php
	class XDBException extends XException {}
?>
<?php
	class XDBResult
	{
		public function __construct($db, $result)
		{
			$this->db = $db;
			$this->result = $result;
		}

		public function fetchRow($asAssociate = true)
		{
			$row = $this->result->fetch($asAssociate ? 
				PDO::FETCH_ASSOC : PDO::FETCH_NUM);

			$this->db->throwOnError($row, 'Fetching the row falied: ');

			return $row;
		}

		public function free()
		{
			$this->result->closeCursor();
		}

		private $db;
		private $result;
	}
?>
<?php
	class XDB 
	{
		public static function registerDB ($name, $DSN, $username, $password)
		{
			return self::$connects[$name] 
				= new XDB($DSN, $username, $password);
		}

		public static function getDB ($name)
		{
			if (!array_key_exists($name, self::$connects))
				throw new XDBException('DB ' . $name . ' not registered. ');

			return self::$connects[$name];
		}

		private static $connects = array();

		public function __construct ($DSN, $username, $password, $options = NULL)
		{
			if ($options === NULL)
				$options = array (
					PDO::ATTR_PERSISTENT => true
				);

			$this->DSN = $DSN;
			$this->username = $username;
			$this->password = $password;
			$this->options = $options;
		}

		public function execute($sql, $values = array()) 
		{
			$this->connect();

			$sql = $this->buildSQL($sql, $values);
			$affectedRows = $this->db->exec($sql);
			self::throwOnError($affectedRows, 'Executing falied: ');

			return $affectedRows;
		}

		public function query($sql, $values = array())
		{
			$this->connect();

			$sql = $this->buildSQL($sql, $values);
			$result = $this->db->query($sql);
			self::throwOnError($result, 'Querying falied: ');

			return new XDBResult($this, $result);
		}

		public function queryAll($sql, $values = array(), $asAssociate = true)
		{
			$this->connect();

			$sql = $this->buildSQL($sql, $values);
			$result = $this->db->query($sql);
			self::throwOnError($result, 'Querying falied: ');

			$result = $result->fetchAll($asAssociate ? 
				PDO::FETCH_ASSOC : PDO::FETCH_NUM);
			self::throwOnError($result, 'Fetching all rows falied: ');

			return $result;
		}

		public function queryCol($sql, $values = array())
		{
			$this->connect();

			$sql = $this->buildSQL($sql, $values);
			$result = $this->db->query($sql);
			self::throwOnError($result, 'Querying falied: ');

			$result = $result->fetchAll(PDO::FETCH_COLUMN, 0);
			self::throwOnError($result, 'Fetching all rows falied: ');

			return $result;
		}

		public function queryRow($sql, $values = array(), $asAssociate = true)
		{
			$result = $this->query($sql, $values);

			$row = $result->fetchRow($asAssociate);
			$result->free();

			return $row;
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

		public function throwOnError($obj, $msg = '')
		{
			if ($obj !== FALSE)
				return;

			$errorInfo = $this->db->errorInfo();

			$errorMessage = $msg . $errorInfo[2];

			throw new XDBException($errorMessage, $errorInfo[1]);
		}

		public function connect()
		{
			if ($this->connected)
				return;

			$this->connected = true;

			try 
			{
				$this->db = 
					new PDO($this->DSN, $this->username, $this->password, $this->options);
			}
			catch (PDOException $e)
			{
				throw new XDBException($e->getMessage(), $e->getCode());
			}
		}

		public function buildSQL($sql, $values)
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
					$tokens[$k] = $this->quote($value);
				}
				else
					$tokens[$k] = str_replace('\\?', '?', $v);

			return implode($tokens);
		}

		private function quote($val)
		{
			if (is_int($val) || is_float($val))
				return (string)$val;
			if (is_string($val))
				return $this->db->quote($val);
			if (is_null($val))
				return 'NULL';

			throw new XDBException
				('Quote failed with unsupported type. ');			
		}

		private $connected = false;
		private $DSN;
		private $username;
		private $password;
		private $options;

		private $db;
	}
?>