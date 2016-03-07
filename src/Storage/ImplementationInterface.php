<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN\Storage;

	interface ImplementationInterface
	{
		public function __construct(array $config);
		public function beginTransaction();
		public function commitTransaction();
		public function rollbackTransaction();
		public function escape($val, $add_quotes = true);
		public function exists($table, array $query);
		public function count($table, array $query = array());
		public function get($table, array $query, $fields = false);
		public function query($table, $query = array(), array $fields = array(), $sort = false, $limit = false, $skip = false);
		public function querySQL($sql);
		public function executeSQL($sql);
		public function insert($table, array $data, $update_on_duplicate = false);
		public function remove($table, array $query);
		public function update($table, $where, array $data);
	}
