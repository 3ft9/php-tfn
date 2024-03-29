<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * The storage class. This class implements the storage interface, but passes
	 * all requests through to the configured instance. To get started using this
	 * class call Storage::init and pass it the storage type and configuration
	 * array. If you call any other method without first initialising the class
	 * it will throw an exception.
	 */
	class Storage
	{
		static private $_object = null;

		static public function init($type, array $config)
		{
			$class = '\\TFN\\Storage\\'.$type;
/*
			if (!class_exists($class)) {
				throw new Storage\Exception('Unknown storage type ['.$type.']');
			}
*/
			self::$_object = new $class($config);
		}

		static public function beginTransaction()
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->beginTransaction();
		}

		static public function commitTransaction()
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->commitTransaction();
		}

		static public function rollbackTransaction()
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->rollbackTransaction();
		}

		static public function escape($val, $add_quotes = true)
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->escape($val, $add_quotes);
		}

		static public function exists($table, array $query)
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->exists($table, $query);
		}

		static public function count($table, $query = array())
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->count($table, $query);
		}

		static public function get($table, array $query, $fields = array())
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->get($table, $query, $fields);
		}

		static public function query($table, $query = array(), array $fields = array(), $sort = false, $limit = false, $skip = false)
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->query($table, $query, $fields, $sort, $limit, $skip);
		}

		static public function querySQL($sql)
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->querySQL($sql);
		}

		static public function executeSQL($sql)
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->executeSQL($sql);
		}

		static public function insert($table, array $data, $update_on_duplicate = false)
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->insert($table, $data, $update_on_duplicate);
		}

		static public function remove($table, array $query)
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->remove($table, $query);
		}

		static public function update($table, $where, array $data)
		{
			if (!self::$_object) {
				throw new Storage\Exception('Not yet initialised!');
			}
			return self::$_object->update($table, $where, $data);
		}
	}
