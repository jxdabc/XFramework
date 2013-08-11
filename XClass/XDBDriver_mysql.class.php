<?php
	class XDBDriver_mysql extends AbstractXDBDB
	{
		public function __construct($parsedDSN, $username, $password)
		{
			$this->DSN = $parsedDSN;
			$this->username = $username;
			$this->password = $password;
		}

		public function connect()
		{
			if ($this->db !== NULL)
				return;

			$host = $this->DSN['host'];
			$dbname = '';
			$port = NULL;

			if (!array_key_exists('persistent', $this->DSN) ||
				$this->DSN['persistent'])
				$host = 'p:' . $host;

			if (array_key_exists('dbname', $this->DSN))
				$dbname = $this->DSN['dbname'];

			if (array_key_exists('port', $this->DSN))
				$port = $this->DSN['port'];

			// Suppress the 'server gone away' warning, 
			// generated by mysqlnd PACKET_READ_HEADER_AND_BODY
			// in mysqlnd_wireprotocol.c
			// Jxd, 2013/8/11
			$this->db = @new mysqli($host, 
				$this->username, $this->password, 
				$dbname, $port);

			if (array_key_exists('charset', $this->DSN))
				$this->throwOnError(
					$this->db->set_charset($this->DSN['charset']),
					'Failed to change character set: ');
				

			if ($this->db->connect_error) 
			{
				throw new XDBException('Failed to connect: ' . $this->db->connect_error);
				$this->db = NULL;
			}
		}

		public function query($sql, $values = array())
		{
			$this->connect();

			$sql = $this->buildSQL($sql, $values);

			$result = $this->db->query($sql, MYSQLI_USE_RESULT);

			$this->throwOnError($result, 'Failed to query: ');

			if ($result instanceof mysqli_result)
				return new XDBDriverResult_mysql($result, $this);
			else
				return $this->db->affected_rows;
		}

		public function queryAll($sql, $values = array(), $asAssociate = true)
		{
			$this->connect();

			$sql = $this->buildSQL($sql, $values);

			$result = $this->db->query($sql, MYSQLI_USE_RESULT);

			$this->throwOnError($result, 'Failed to query: ');

			$all = $result->fetch_all($asAssociate ? MYSQLI_ASSOC : MYSQLI_NUM);

			$this->throwOnError($all, 'Failed to fetch all: ');

			return $all;
		}

		public function throwOnError($obj, $errorMessageExtra)
		{
			if ($obj !== FALSE) return;

			$errorInfo = $this->db->error;
			$errorCode = $this->db->errno;

			$errorMessage = $errorMessageExtra . $errorInfo;

			throw new XDBException($errorMessage, $errorCode);
		}

		protected function quote($val)
		{
			if (is_int($val) || is_float($val))
				return (string)$val;
			if (is_string($val))
				return '\'' . $this->db->real_escape_string($val) . '\'';
			if (is_null($val))
				return 'NULL';

			throw new XDBException
				('Quote failed with unsupported type. ');
		}

		private $username;
		private $password;
		private $DSN;

		private $db = NULL;
	}

	class XDBDriverResult_mysql implements IXDBResult 
	{
		public function __construct($result, $db)
		{
			$this->db = $db;
			$this->result = $result;
		}

		public function fetchRow($asAssociate = true)
		{
			if ($asAssociate)
				$row = $this->result->fetch_assoc();
			else
				$row = $this->result->fetch_row();

			$this->db->throwOnError($row, 'Failed to fetch row: ');

			return $row;
		}

		public function free()
		{
			$this->result->free();
		}

		private $db;
		private $result;
	}
?>