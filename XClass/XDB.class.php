<?php
	class XDBException extends XException {}
?>
<?php
	interface IXDBDB
	{
		public function connect();

		public function execute($sql, $values = array());
		public function query($sql, $values = array());
		public function queryAll($sql, $values = array(), $asAssociate = true);
		public function queryCol($sql, $values = array());
		public function queryRow($sql, $values = array(), $asAssociate = true);
		public function queryOne($sql, $values = array());

		public function queryObjects($class, $sql, $values = array());
		public function insertObject($obj, $table);
		public function updateObject($obj, $table);
		public function deleteObject($obj, $table);
	}

	interface IXDBResult
	{
		public function fetchRow($asAssociate = true);
		public function free();
	}
?>
<?php

	abstract class AbstractXDBDB implements IXDBDB
	{
		public function execute($sql, $values = array())
		{
			$result = $this->query($sql, $values);
			if ($result instanceof IXDBResult)
				$result->free();

			if (is_int($result))
				return $result;

			return 0;
		}

		public function queryAll($sql, $values = array(), $asAssociate = true)
		{
			$rtn = array();

			$result = $this->query($sql, $values);
			while ($row = $result->fetchRow($asAssociate))
				$rtn[] = $row;

			$result->free();

			return $rtn;
		}

		public function queryCol($sql, $values = array())
		{
			$rtn = array();

			$result = $this->query($sql, $values);
			while ($row = $result->fetchRow(false))
				$rtn[] = $row[0];

			$result->free();

			return $rtn;
		}

		public function queryRow($sql, $values = array(), $asAssociate = true)
		{
			$result = $this->query($sql, $values);

			if (!($rtn = $result->fetchRow($asAssociate)))
				$rtn = NULL;

			$result->free();

			return $rtn;
		}

		public function queryOne($sql, $values = array())
		{
			$result = $this->queryRow($sql, $values, false);

			if ($result)
				$result = $result[0];

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

		protected function buildSQL($sql, $values) 
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

//		PHP 5.3 does not like the follows. Jxd, 2013/8/11
//		abstract public function query($sql, $values = array());
//		abstract public function connect();
		abstract protected function quote($value);
	}

	class XDBResult
	{
		public function __construct($result)
		{
			$this->result = $result;
		}

		public function fetchRow($asAssociate = true)
		{
			$row = $this->result->fetch($asAssociate ? 
				PDO::FETCH_ASSOC : PDO::FETCH_NUM);

			return $row;
		}

		public function free()
		{
			$this->result->closeCursor();
		}

		private $result;
	}
?>
<?php
	class XDB 
	{
		public static function registerDB ($name, $DSN, $username, $password)
		{
			return self::$connects[$name] 
				= self::factory($DSN, $username, $password);
		}

		public static function getDB ($name)
		{
			if (!array_key_exists($name, self::$connects))
				throw new XDBException('DB ' . $name . ' not registered. ');

			return self::$connects[$name];
		}

		public static function factory($DSN, $username, $password)
		{
			$parsedDSN = self::parseDSN($DSN);
			$driverClass = 'XDBDriver_' . $parsedDSN['driver'];

			return new $driverClass($parsedDSN, $username, $password);
		}

		private static function parseDSN($DSN)
		{
			$parsedDSN = array();

			$s = explode(':', $DSN);
			
			$parsedDSN['driver'] = $s[0];
			$s = $s[1];

			$s = explode(';', $s);
			$slen = count($s);

			for ($i = 0; $i < $slen; $i++)
			{
				$ss = explode('=', $s[$i]);
				$k = $ss[0];
				$v = $ss[1];

				$parsedDSN[$k] = $v;
			}

			return $parsedDSN;
		}

		private static $connects = array();
	}
?>