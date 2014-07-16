<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	class Storage_PDO implements Storage_Interface
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
				throw new StorageException('Missing required configuration details');
			}

			// Save the configuration for a rainy day.
			$this->_config = $config;

			try {
				// Create the PDO object.
				$this->_conn = new \PDO($config['dsn'], V($config, 'username', ''), V($config, 'password'));
				$this->_conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			} catch (\PDOException $e) {
				throw new StorageException('Failed to connect: '.$e->getMessage());
			}
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
				return intval($this->_conn->query('select count(1) from `'.$table.'` where '.$this->_buildWhere($query, 'and'), \PDO::FETCH_COLUMN, 0)->fetch());
			} catch (\PDOException $e) {
				throw new StorageException('Failed to connect: '.$e->getMessage());
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

		public function query($table, array $query, $fields = false, $sort = false, $limit = false, $skip = false)
		{
			if ($fields) {
				$fields = '`'.implode('`, `', $fields).'`';
			} else {
				$fields = '*';
			}

			try {
				$sql = 'select '.$fields.' from `'.$table.'` where '.$this->_buildWhere($query, 'and');
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
				return $this->_conn->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
			} catch (\PDOException $e) {
				throw new StorageException('Query failed: '.$e->getMessage());
			}
		}

		public function insert($table, array $data)
		{
			try {
				$sql = 'insert into `'.$table.'` set '.$this->_buildWhere($data, ',');
				$this->_conn->query($sql);
				return $this->_conn->lastInsertId();
			} catch (\PDOException $e) {
				throw new StorageException('Query failed: '.$e->getMessage());
			}
		}

		public function remove($table, array $query)
		{
			try {
				$sql = 'delete from `'.$table.'` where '.$this->_buildWhere($query, 'and');
				return $this->_conn->query($sql)->rowCount();
			} catch (\PDOException $e) {
				throw new StorageException('Query failed: '.$e->getMessage());
			}
		}

		public function update($table, array $where, array $data, $create_if_missing = false)
		{
			$retval = false;

			try {
				$sql = 'update `'.$table.'` set '.$this->_buildWhere($data, ',').' where '.$this->_buildWhere($where, 'and');
				return $this->_conn->query($sql)->rowCount();
			} catch (\PDOException $e) {
				throw new StorageException('Query failed: '.$e->getMessage());
			}
		}

		protected function _buildWhere($query, $separator = ',')
		{
			$parts = array();
			foreach ($query as $var => $val) {
				$parts[] = '`'.$var.'` = '.$this->_conn->quote($val);
			}
			return implode(' '.$separator.' ', $parts);
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