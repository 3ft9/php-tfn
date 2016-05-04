<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 * 
	 * DEPRECATED: Use the Storage class instead.
	 * This class uses the mysql_* PHP functions which
	 * have now been deprecated in PHP and might get
	 * removed at any time.
	 */
	namespace TFN;

	/**
	 * The MySQL class will throw this custom exception when something goes
	 * wrong.
	 */
	class MySQL_Exception extends TFNException { }

	/**
	 * This class wraps access to one or more MySQL databases.
	 * TODO: Usage documentation!
	 */
	class MySQL
	{
		/**
		 * Throw an exception, prefixed with the class name.
		 *
		 * @param string $method The method that threw the exception.
		 * @param string $message The message to pass with it, defaults to "Failed"
		 * @throws Exception
		 */
		private static function throwException($method, $message = 'Failed') {
			throw new MySQL_Exception($method.': '.$message);
		}

		/**
		 * @var array The DB configurations to which this class can provide access.
		 */
		private static $_configurations = array();

		public static function addConfiguration(
			$config,
			$database,
			$username = 'root',
			$password = '',
			$hostname = 'localhost',
			$port = 3306,
			$persistant = false,
			$encoding = 'utf8')
		{
			self::$_configurations[$config] = array(
				'database' => $database,
				'username' => $username,
				'password' => $password,
				'hostname' => $hostname,
				'port' => $port,
				'persistant' => $persistant,
				'encoding' => $encoding,
			);
			self::$_connections[$config] = false;
		}

		/**
		 * @var array The currently active DB classes.
		 */
		static private $_connections = array();

		/**
		 * Create an instance of this class with the given database details.
		 *
		 * @param string $database
		 * @param string $username
		 * @param string $password
		 * @param string $hostname
		 * @param int $port
		 * @param bool $persistant
		 * @return DB Or false on failure
		 */
		public static function & connection($config = 'default')
		{
			// Make sure we know about this configuration
			if (!isset(self::$_configurations[$config])) {
				self::throwException(__METHOD__, 'Unknown configuration "'.$config.'"');
			}

			// Do we already have a connection to this config?
			if (empty(self::$_connections[$config])) {
				self::$_connections[$config] = new self(
					self::$_configurations[$config]['database'],
					self::$_configurations[$config]['username'],
					self::$_configurations[$config]['password'],
					self::$_configurations[$config]['hostname'],
					self::$_configurations[$config]['port'],
					self::$_configurations[$config]['persistant'],
					self::$_configurations[$config]['encoding'],
					$config);

				// Check connection was successful
				if (!self::$_connections[$config]->isConnected()) {
					unset(self::$_connections[$config]);
					self::throwException(__METHOD__, 'Failed to connect to "'.$config.'" DB');
				}
			}

			return self::$_connections[$config];
		}

		/**
		 * Disconnect all connected connections.
		 */
		public static function disconnectAll()
		{
			foreach (array_keys(self::$_connections) as $key) {
				if (self::$_connections[$key]) {
					self::$_connections[$key]->disconnect();
				}
				unset(self::$_connections[$key]);
			}
		}

		/**
		 * Return the time according to the database server in the given
		 * configuration.
		 *
		 * @param string $config The configuration to use.
		 * @throws Exception
		 * @return int The timestamp.
		 */
		static public function getTime($config = false)
		{
			$retval = time();

			// If we weren't supplied with a configuration, find one to use
			if ($config === false) {
				if (count(self::$_connections) == 0) {
					// No connections, can't supply the time
					self::throwException(__METHOD__, 'There are no connection configurations available');
				}
				// Just use the first one in the list
				$config = array_unshift(array_keys(self::$_connections));
			}

			$db = self::connection($config, false);
			if ($db) {
				$sql = 'select unix_timestamp()';

				$query = $db->query($sql);
				if ($query and $db->numRows($query) > 0) {
					$retval = $db->fetchRow($query);
					$db->freeQuery($query);
					$retval = array_shift($retval);
				}
			}

			return $retval;
		}

		/**
		 * @var Resource The database connection
		 */
		private $_conn = null;

		/**
		 * @var string The database name
		 */
		private $_database = '';

		/**
		 * @var string The database username
		 */
		private $_username = 'root';

		/**
		 * @var string The database password
		 */
		private $_password = '';

		/**
		 * @var string The database hostname
		 */
		private $_hostname = 'localhost';

		/**
		 * @var int The database port
		 */
		private $_port = 3306;

		/**
		 * @var bool Whether the connection should be persistant
		 */
		private $_persistant = false;

		/**
		 * @var string The encoding the connection should use
		 */
		private $_encoding = 'utf8';

		/**
		 * @var string The configuration name
		 */
		private $_config = false;

		/**
		 * @var string The last error message
		 */
		private $_lasterror = '';

		/**
		 * Constructor.
		 *
		 * @param string $database
		 * @param string $username
		 * @param string $password
		 * @param string $hostname
		 * @param int $port
		 * @param bool $persistant
		 * @throws Exception
		 */
		private function __construct(
			$database,
			$username = 'root',
			$password = '',
			$hostname = 'localhost',
			$port = false,
			$persistant = false,
			$encoding = false,
			$config = false)
		{
			$this->_database = $database;
			$this->_username = $username;
			$this->_password = $password;
			$this->_hostname = $hostname;
			$this->_port = $port;
			$this->_persistant = $persistant;
			$this->_encoding = $encoding;
			$this->_config = $config;
			$this->connect();
		}

		public function __destruct()
		{
			$this->disconnect();
		}

		/**
		 * Connect to the database.
		 *
		 * @throws Exception
		 */
		private function connect()
		{
			$function = ($this->_persistant ? 'mysql_pconnect' : 'mysql_connect');
			$this->_conn = $function($this->_hostname.($this->_port === false ? '' : ':'.$this->_port), $this->_username, $this->_password);
			if (false === $this->_conn) {
				$this->_lasterror = mysql_error();
				self::throwException(__METHOD__, $this->_lasterror);
			} else {
				if (!mysql_select_db($this->_database, $this->_conn)) {
					$this->_lasterror = mysql_error();
					self::throwException(__METHOD__, $this->_lasterror);
				} else {
					if (!empty($this->_encoding)) {
						// Set the communication encoding
						$this->query('SET NAMES '.$this->_encoding);
					}
				}
			}
		}

		/**
		 * Disconnect from the database.
		 */
		private function disconnect()
		{
			if (!$this->_persistant) {
				@mysql_close($this->_conn);
				$this->_conn = null;
				if ($this->_config !== false) {
					self::$_connections[$this->_config] = false;
				}
			}
		}

		/**
		 * Check to see whether this connection is still valid.
		 * TODO: Figure out why this is calling ping twice!
		 *
		 * @return bool Whether the internal connection is connected.
		 */
		public function isConnected()
		{
			@mysql_ping($this->_conn);
			return @mysql_ping($this->_conn);
		}

		/**
		 * Get the connection thread ID.
		 *
		 * @return int The connection thread ID.
		 */
		public function connectionID()
		{
			return mysql_thread_id($this->_conn);
		}

		/**
		 * Get the last error message.
		 *
		 * @return string The error message.
		 */
		public function getLastError()
		{
			return $this->_lasterror;
		}

		/**
		 * Escape the given string so it's suitable for inclusion in a SQL
		 * statement, including surrounding it with quotes if specified.
		 *
		 * @param string/array $string
		 * @return string
		 */
		public function escape($string, $addquotes = true)
		{
			if (is_array($string)) {
				$retval = array();
				foreach($string as $key => $value) {
					$retval[$key] = $this->__METHOD__($value, $addquotes);
				}
			} else {
				$retval =
					($addquotes ? '"' : '').
					mysql_real_escape_string($string, $this->_conn).
					($addquotes ? '"' : '');
			}

			return $retval;
		}

		/**
		 * Start a transaction in the database.
		 *
		 * @throws Exception
		 */
		public function startTransaction()
		{
			$retval = mysql_query('START TRANSACTION', $this->_conn);
			if (!$retval) {
				$this->_lasterror = mysql_error($this->_conn);
				self::throwException(__METHOD__, $this->_lasterror);
			}
		}

		/**
		 * Commit a transaction in the database.
		 *
		 * @throws Exception
		 */
		public function commitTransaction()
		{
			$retval = mysql_query('COMMIT', $this->_conn);
			if (!$retval) {
				$this->_lasterror = mysql_error($this->_conn);
				self::throwException(__METHOD__, $this->_lasterror);
			}
		}

		/**
		 * Rollback the current transaction in the database.
		 *
		 * @throws Exception
		 */
		public function rollbackTransaction()
		{
			$retval = mysql_query('ROLLBACK', $this->_conn);
			if (!$retval) {
				$this->_lasterror = mysql_error($this->_conn);
				self::throwException(__METHOD__, $this->_lasterror);
			}
		}

		/**
		 * Execute a query on the internal database connection.
		 *
		 * @param string $sql The SQL to execute.
		 * @throws Exception
		 * @return mixed A MySQL result resource or true on a successful query with no result set.
		 */
		public function query($sql)
		{
			$retval = mysql_query($sql, $this->_conn);
			if (!$retval) {
				// If it's a deadlock error, sleep for a bit and try again - special
				// case, but encountered too many times to ignore it
				if (mysql_errno($this->_conn) == '1213') {
					usleep(100000);
					$retval = mysql_query($sql, $this->_conn);
					if (!$retval) {
						$this->_lasterror = mysql_error($this->_conn);
						self::throwException(__METHOD__, $this->_lasterror);
					}
				} else {
					$this->_lasterror = mysql_error($this->_conn);
					self::throwException(__METHOD__, $this->_lasterror);
				}
			}
			return $retval;
		}

		/**
		 * Execute an unbuffered query on the internal database connection.
		 *
		 * @param string $sql The SQL to execute.
		 * @throws Exception
		 * @return mixed A MySQL result resource or true on a successful query with no result set.
		 */
		public function unbufferedQuery($sql)
		{
			$retval = mysql_unbuffered_query($sql, $this->_conn);
			if (!$retval) {
				$this->_lasterror = mysql_error($this->_conn);
				self::throwException(__METHOD__, $this->_lasterror);
			}
			return $retval;
		}

		/**
         * Create a table.
         *
         * @param string $name          The name for the table.
         * @param array  $fields        The field definitions.
         * @param mixed  $primarykey    The field name or array of field names
         *                              that make up the primary key.
         * @param array  $uniqueindexes Array of unique indexes.
         * @param array  $indexes       Array of indexes.
         * @param string $storageengine The storage engine to use (def: InnoDB)
         * @param string $charset       The default charset (def: utf8)
         * @return bool
         */
		public function createTable(
			$name, $fields, $primarykey = false,
			$uniqueindexes = array(), $indexes = array(),
			$storageengine = 'InnoDB', $charset = 'utf8',
			$autoincrementstart = false)
		{
			$retval = false;

			if ($primarykey !== false and !is_array($primarykey)) {
				$primarykey = array($primarykey);
			}

			$sql = 'create table `'.$name.'` (';
			foreach ($fields as $field => $def) {
				$sql .= '`'.$field.'` '.$def.', ';
			}
			$sql = trim($sql, ' ,');
			if ($primarykey !== false) {
				$sql .= ', primary key (`'.implode('`, `', $primarykey).'`)';
			}
			foreach ($uniqueindexes as $indexname => $def) {
				if (!is_array($def)) {
					$def = array($def);
				}
				$sql .= ', unique key `'.$indexname.'` (`'.implode('`, `', $def).'`)';
			}
			foreach ($indexes as $indexname => $def) {
				if (!is_array($def)) {
					$def = array($def);
				}
				$sql .= ', key `'.$indexname.'` (`'.implode('`, `', $def).'`)';
			}
			$sql .= ') ENGINE='.$storageengine.' DEFAULT CHARSET='.$charset;
			if ($autoincrementstart !== false) {
				$sql.= ' AUTO_INCREMENT = '.$autoincrementstart;
			}

			return $this->query($sql);
		}

		/**
         * Drops the given table.
         *
         * @param string $table     The name of the table to drop.
         * @param bool   $if_exists Set to true to not raise an error if the
         *                          table does not exist.
         * @return bool
         */
		public function dropTable($table, $if_exists = false)
		{
			$retval = false;

			$sql = 'drop table ';
			if ($if_exists) {
				$sql .= 'if exists ';
			}
			$sql .= '`'.$table.'`';

			return $this->query($sql);
		}

		/**
		 * Perform a select query and fetch the results into an array.
		 *
		 * @param string $sql The SQL to execute.
		 * @throws Exception
		 * @return array The results.
		 */
		public function select($sql)
		{
			$retval = false;

			$query = $this->query($sql);

			$retval = array();
			while ($row = $this->fetchRow($query)) {
				$retval[] = $row;
			}
			$this->freeQuery($query);

			return $retval;
		}

		/**
		 * Return the number of rows in a result set.
		 *
		 * @param resource $query The result set resource.
		 * @throws Exception
		 * @return int The number of row in the result set.
		 */
		public function numRows($query)
		{
			if (!is_resource($query)) {
				$this->_lasterror = 'The argument must be a resource';
				self::throwException(__METHOD__, 'Argument is not a resource');
			}
			$retval = mysql_num_rows($query);
			if ($retval === false) {
				$this->_lasterror = mysql_error($this->_conn);
				self::throwException(__METHOD__, $this->_lasterror);
			}
			return $retval;
		}

		/**
		 * Fetch a row from the given MySQL result resource.
		 *
		 * @param resource $query The query resource.
		 * @return mixed The row.
		 */
		public function fetchRow($query)
		{
			if (!is_resource($query)) {
				$this->_lasterror = 'The argument must be a resource';
				self::throwException(__METHOD__, 'Argument is not a resource');
			}

			$retval = mysql_fetch_assoc($query);
			if (!$retval && @mysql_error($this->_conn) != '') {
				$this->_lasterror = mysql_error($this->_conn);
				self::throwException(__METHOD__, $this->_lasterror);
			}

			return $retval;
		}

		/**
		 * Fetch a row from the given MySQL result resource.
		 *
		 * @param resource $query The query resource.
		 * @return mixed The row.
		 */
		public function fetchRowArray($query)
		{
			if (!is_resource($query)) {
				$this->_lasterror = 'The argument must be a resource';
				self::throwException(__METHOD__, 'Argument is not a resource');
			}

			$retval = mysql_fetch_array($query);
			if (!$retval && mysql_error($this->_conn) != '') {
				$this->_lasterror = mysql_error($this->_conn);
				self::throwException(__METHOD__, $this->_lasterror);
			}
			return $retval;
		}

		/**
		 * Just a result set to a specific row.
		 *
		 * @param resource $query The query resource.
		 * @param integer $row The row number to which to jump.
		 * @throws Exception
		 */
		public function seek($query, $row)
		{
			if (!is_resource($query)) {
				$this->_lasterror = 'The argument must be a resource';
				self::throwException(__METHOD__, 'Argument is not a resource');
			}

			if (!mysql_data_seek($query, $row)) {
				$this->_lasterror = mysql_error($this->_conn);
				self::throwException(__METHOD__, $this->_lasterror);
			}
		}

		/**
 		 * Free the given MySQL query resource.
 		 *
		 * @param resource $query The query resource to be freed.
		 * @throws Exception
		 */
		public function freeQuery($query)
		{
			if (!is_resource($query)) {
				$this->_lasterror = 'The argument must be a resource';
				self::throwException(__METHOD__, 'Argument is not a resource');
			}

			@mysql_free_result($query);
		}

		/**
		 * Build an array of "key = value" from the provided associative array.
		 *
		 * @param array $fields The associative array containing the fields.
		 * @return array
		 */
		private function buildSet($fields)
		{
			$retval = array();
			foreach ($fields as $key => $val) {
				$retval[] = '`'.$key.'` = '.
					(is_null($val)
						?
					'null'
						:
					(is_array($val) ? (count($val) > 0 ? array_shift($val) : '""') : $this->escape($val)));
			}
			return $retval;
		}

		/**
		 * Execute an insert query on the database.
		 *
		 * @param string $table The table into which to insert data.
		 * @param array $fields The fields to be inserted.
		 * @param array $update_on_duplicate Fields to be updated in the case of a duplicate primary key.
		 * @return mixed The ID inserted if applicable, otherwise true on success.
		 */
		public function insert($table, $fields, $update_on_duplicate = false)
		{
			$retval = false;

			$set = implode(', ', $this->buildSet($fields));
			$sql = 'insert into `'.str_replace('.', '`.`', $table).'` set '.$set;
			if (true === $update_on_duplicate) {
				$sql .= ' on duplicate key update '.$set;
			} elseif (is_array($update_on_duplicate)) {
				$sql.= ' on duplicate key update '.$this->buildSet($update_on_duplicate);
			}

			$retval = $this->query($sql);

			if (!$retval) {
				return false;
			}

			// Get the auto_increment ID if there is one
			$retval = $this->insertID();
			if (!is_numeric($retval)) {
				$retval = true;
			}

			return $retval;
		}

		/**
		 * Execute an update statement on the internal database connection.
		 *
		 * @param string $table
		 * @param array $fields
		 * @param string $where
		 * @return mixed The number of affected rows or false on failure
		 */
		public function update($table, $fields, $where, $order = false, $limit = false)
		{
			$retval = false;

			$sql = 'update `'.$table.'` set '.implode(', ', $this->BuildSet($fields)).' where '.$where;
			if ($order !== false) {
				$sql .= ' order by '.$order;
			}
			if ($limit !== false) {
				$sql .= ' limit '.$limit;
			}

			$retval = $this->query($sql);
			if (!$retval) {
				return false;
			}
			return $this->affectedRows();
		}

		/**
		 * Delete rows from the table that match the given where clauses. Returns
		 * the number of items deleted or false on failure.
		 *
		 * @param string $table
		 * @param string $where
		 * @return int
		 */
		public function delete($table, $where)
		{
			$retval = false;

			$sql = 'delete from `'.$table.'` where '.$where;

			$query = $this->query($sql);

			if (!$query) {
				return false;
			}

			return $this->affectedRows();
		}

		/**
		 * Perform a select query and return the first row from the results.
		 *
		 * @param string $table
		 * @param string $where
		 * @param array $fields
		 * @return mixed
		 */
		public function selectSingleRow($table, $where, $order = false, $fields = false)
		{
			$retval = false;

			$sql = 'select ';
			$sql.= ($fields === false ? '*' : '`'.implode('`, `', $fields).'`');
			if (is_array($where)) {
				$where = implode(' and ', $this->buildSet($where));
			}
			$sql.= ' from `'.$table.'` where '.$where;
			if ($order !== false) {
				$sql.= ' order by '.$order;
			}
			$sql.= ' limit 1';

			$query = $this->query($sql);

			if (!$query) {
				return false;
			}

			$retval = $this->fetchRow($query);
			$this->freeQuery($query);

			return $retval;
		}

		/**
		 * Perform a select query and return the first column from the first row
		 * of the results.
		 *
		 * @param string $table
		 * @param string $where
		 * @param array $fields
		 * @return mixed
		 */
		public function selectSingleRowSingleValue($field, $table, $where = false, $order = false)
		{
			try {
				$res = $this->selectSingleRow($table, $where, $order, array($field));
				return $res[$field];
			} catch (MySQL_Exception $e) {
				self::throwException(__METHOD__, $e->getMessage());
			}
		}

		/**
		 * Perform a select query and return the contents of a single column.
		 *
		 * @param string $table
		 * @param string $where
		 * @param array $fields
		 * @return mixed
		 */
		public function selectSingleColumn($field, $table, $where = false, $order = false)
		{
			$retval = false;

			$sql = 'select `'.$field.'` from `'.$table.'`';
			if ($where !== false) {
				$sql.= ' where '.$where;
			}
			if ($order !== false) {
				$sql.= ' order by '.$order;
			}

			$query = $this->query($sql);

			$retval = array();
			while ($row = $this->fetchRow($query)) {
				$retval[] = array_shift($row);
			}
			$this->freeQuery($query);

			return $retval;
		}

		/**
		 * Lock an item from a table, and return it.
		 *
		 * @param string $table
		 * @param array $where
		 * @param string $lockfield
		 * @param string $lockvalue
		 * @param string $unlockedvalue
		 * @param mixed $order
		 * @param int $num
		 * @return mixed
		 */
		public function lockRow($table, $where, $lockfield, $lockvalue, $unlockedvalue, $order = false, $num = 1)
		{
			$retval = false;

			$where .= ' and (`'.$lockfield.'` ';
			$where .= (is_null($unlockedvalue) ? ' is null' : ' = '.$this->escape($unlockedvalue));
			$where .= ' or `'.$lockfield.'` = '.$this->escape($lockvalue).')';

			$this->update($table, array($lockfield => $lockvalue), $where, $order, $num);

			return $this->selectSingleRow($table, '`'.$lockfield.'` = '.$this->escape($lockvalue), $order);
		}

		/**
		 * Count the number of rows matching the given where clauses.
		 *
		 * @param string $table
		 * @param string $where
		 * @return mixed
		 */
		public function countRows($table, $where = false)
		{
			$retval = false;
			$sql = 'select count(1) from `'.$table.'`';
			if ($where !== false) {
				$sql .= ' where '.$where;
			}

			$query = $this->query($sql);

			$res = $this->fetchRow($query);
			$retval = array_shift($res);

			$this->freeQuery($query);

			return $retval;
		}

		/**
		 * Return the result of the MySQL found_rows function.
		 *
		 * @return mixed
		 */
		public function foundRows()
		{
			$query = $this->query('select found_rows() found');

			$row = $this->fetchRow($query);

			$this->freeQuery($query);

			return $row['found'];
		}

		/**
		 * Get the last inserted autonumber field.
		 *
		 * @return mixed
		 */
		public function insertID()
		{
			$retval = mysql_insert_id($this->_conn);

			if (false === $retval) {
				$this->_lasterror = mysql_error($this->_conn);
				self::throwException(__METHOD__, $this->_lasterror);
			}

			return $retval;
		}

		/**
		 * Get the number of rows affected by the last query.
		 *
		 * @return int
		 */
		public function affectedRows()
		{
			$retval = mysql_affected_rows($this->_conn);

			if (!is_numeric($retval)) {
				$this->_lasterror = mysql_error($this->_conn);
				self::throwException(__METHOD__, $this->_lasterror);
			}

			return $retval;
		}
	}
