<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	interface Storage_Interface
	{
		public function __construct(array $config);
		public function escape($val);
		public function exists($table, array $query);
		public function count($table, array $query = array());
		public function get($table, array $query, $fields = false);
		public function query($table, $query = array(), array $fields = array(), $sort = false, $limit = false, $skip = false);
		public function querySQL($sql);
		public function insert($table, array $data);
		public function remove($table, array $query);
		public function update($table, $where, array $data, $create_if_missing = false);
	}
