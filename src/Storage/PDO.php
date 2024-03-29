<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN\Storage;
	
	use \TFN\Dict;

	class PDO implements ImplementationInterface
	{
		/**
		 * We store our configuration here.
		 *
		 * @var array
		 */
		protected $_config = null;

		/**
		 * This is our PDO instance.
		 *
		 * @var PDO
		 */
		protected $_conn = null;

		/**
		 * Create a new instance, connecting to the database with the given config.
		 *
		 * @param array $config The configuration details.
		 */
		public function __construct(array $config)
		{
			// Validate that the configuration contains everything we need.
			if (empty($config['dsn'])) {
				throw new \TFN\Storage\Exception('Missing required configuration details');
			}

			// Save the configuration for a rainy day.
			$this->_config = $config;

			try {
				// Create the PDO object.
				$this->_conn = new \PDO($config['dsn'], Dict::get($config, 'username', ''), Dict::get($config, 'password'));
				$this->_conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			} catch (\PDOException $e) {
				throw new \TFN\Storage\Exception('Failed to connect: '.$e->getMessage());
			}
		}

		/**
		 * Begin a transaction. This will throw an exception if the underlying
		 * database doesn't support transactions.
		 */
		public function beginTransaction()
		{
			try {
				$this->_conn->beginTransaction();
			} catch (\PDOException $e) {
				throw new \TFN\Storage\Exception('Failed to start transaction: '.$e->getMessage());
			}
		}

		/**
		 * Commit a transaction. This will throw an exception if the underlying
		 * database doesn't support transactions.
		 */
		public function commitTransaction()
		{
			try {
				$this->_conn->commit();
			} catch (\PDOException $e) {
				throw new \TFN\Storage\Exception('Failed to start transaction: '.$e->getMessage());
			}
		}

		/**
		 * Rollback transaction. This will throw an exception if the underlying
		 * database doesn't support transactions.
		 */
		public function rollbackTransaction()
		{
			try {
				$this->_conn->rollback();
			} catch (\PDOException $e) {
				throw new \TFN\Storage\Exception('Failed to start transaction: '.$e->getMessage());
			}
		}

		/**
		 * Escape the supplied value for use in a SQL query. This method should
		 * add quotes as required.
		 *
		 * @param  string  $val The value to be escaped.
		 * @return string       The escaped value.
		 */
		public function escape($val, $add_quotes = true)
		{
			if ($add_quotes) {
				return $this->_conn->quote($val);
			}
			return substr($this->_conn->quote($val), 1, -1);
		}

		/**
		 * Returns true if there are any rows matching the filter provided.
		 *
		 * @param  string  $table The name of the table.
		 * @param  array   $query The filter.
		 * @return boolean        True if matching rows exist.
		 */
		public function exists($table, array $query)
		{
			return $this->count($table, $query) > 1;
		}

		/**
		 * Count the number of rows matching the filter provided.
		 *
		 * @param  string  $table The name of the table.
		 * @param  array   $query The filter.
		 * @return integer        The number of matching rows.
		 */
		public function count($table, array $query = array())
		{
			try {
				return intval($this->_conn->query('select count(1) from `'.$table.'`'.($query ? ' where '.$this->_buildWhere($query, 'and') : ''), \PDO::FETCH_COLUMN, 0)->fetch());
			} catch (\PDOException $e) {
				throw new \TFN\Storage\Exception('Failed to connect: '.$e->getMessage());
			}
		}

		/**
		 * Get a single row from the table.
		 *
		 * @param  string  $table  The name of the table.
		 * @param  array   $query  The filter.
		 * @param  mixed   $fields The fields to fetch.
		 * @return array           The first matching row as an associative array.
		 */
		public function get($table, array $query, $fields = false)
		{
			$res = $this->query($table, $query, $fields, false, false, 1);
			if (is_array($res) && count($res) > 0) {
				return array_shift($res);
			}
			return array();
		}

		public function query($table, $query = array(), array $fields = array(), $sort = false, $limit = false, $skip = false)
		{
			if ($fields) {
				$fields = '`'.implode('`, `', $fields).'`';
			} else {
				$fields = '*';
			}

			try {
				$sql = 'select '.$fields.' from `'.$table.'`';
				if ($query) {
					$sql .= ' where '.$this->_buildWhere($query, 'and');
				}
				if ($sort) {
					$sortorder = array();
					foreach ($sort as $field => $direction) {
						$sortorder[] = '`'.$field.'` '.$direction;
					}
					$sql .= ' order by '.implode(',', $sortorder);
				}
				if ($limit) {
					$limit = intval($limit);
					$skip = intval($skip);
					$sql .= ' limit '.$skip.', '.$limit;
				}
				return $this->querySQL($sql);
			} catch (\PDOException $e) {
				throw new \TFN\Storage\Exception('Query failed: '.$e->getMessage());
			}
		}

		public function querySQL($sql)
		{
			try {
				return $this->_conn->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
			} catch (\PDOException $e) {
				throw new \TFN\Storage\Exception('Query failed: '.$e->getMessage());
			}
		}

		public function executeSQL($sql)
		{
			try {
				return $this->_conn->exec($sql);
			} catch (\PDOException $e) {
				throw new \TFN\Storage\Exception('Query failed: '.$e->getMessage());
			}
		}

		public function insert($table, array $data, $update_on_duplicate = false)
		{
			try {
				$set = $this->_buildWhere($data, ',');
				$sql = 'insert into `'.$table.'` set '.$set;
				if ($update_on_duplicate) {
					$sql .= ' on duplicate key update '.$set;
				}
				$this->_conn->query($sql);
				return $this->_conn->lastInsertId();
			} catch (\PDOException $e) {
				throw new \TFN\Storage\Exception('Query failed: '.$e->getMessage());
			}
		}

		public function remove($table, array $query)
		{
			try {
				$sql = 'delete from `'.$table.'` where '.$this->_buildWhere($query, 'and');
				return $this->_conn->query($sql)->rowCount();
			} catch (\PDOException $e) {
				throw new \TFN\Storage\Exception('Query failed: '.$e->getMessage());
			}
		}

		public function update($table, $where, array $data)
		{
			$retval = false;

			try {
				$sql = 'update `'.$table.'` set '.$this->_buildWhere($data, ',').' where '.$this->_buildWhere($where, 'and');
				return $this->_conn->query($sql)->rowCount();
			} catch (\PDOException $e) {
				throw new \TFN\Storage\Exception('Query failed: '.$e->getMessage());
			}
		}

		protected function _buildWhere($query, $separator = ',')
		{
			if (is_array($query)) {
				$parts = array();
				foreach ($query as $var => $val) {
					$parts[] = '`'.$var.'` = '.(is_array($val) ? $val[0] : $this->_conn->quote($val));
				}
				return implode(' '.$separator.' ', $parts);
			} else {
				return $query;
			}
		}

		protected function _getErrorString()
		{
			// Make sure we successfully connected, allowing for the fact that the
			// ->connect_errno member variable was broken until PHP 5.2.9.
			if (PHP_VERSION_ID >= 50209) {
				if ($this->_conn->errno) {
					return '['.$this->_conn->errno.'] '.$this->_conn->error;
				} else {
					return 'No error';
				}
			} else {
				if (mysqli_errno()) {
					return '['.mysqli_errno().'] '.mysqli_error();
				} else {
					return 'No error';
				}
			}
		}
	}
