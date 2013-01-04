<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * Define the log levels.
	 */
	if (!defined('TFN_ERROR')) {
		define('TFN_ERROR', 'error');
	}
	if (!defined('TFN_WARNING')) {
		define('TFN_WARNING', 'warning');
	}
	if (!defined('TFN_NOTICE')) {
		define('TFN_NOTICE', 'notice');
	}
	if (!defined('TFN_DEBUG')) {
		define('TFN_DEBUG', 'debug');
	}

	/**
	 * Basic stdout logging class.
	 */
	class Logger
	{
		/**
		 * The singleton instance.
		 * @var \TFN\Logger
		 */
		protected static $_instance = null;

		/**
		 * The current indent level.
		 * @var int
		 */
		protected $_indent = 0;

		/**
		 * The timestamp when logging was started.
		 * @var int
		 */
		protected $_start = 0;

		/**
		 * The text to be repeated $indent times before each line.
		 * @var int
		 */
		protected $_indent_text = '  ';

		/**
		 * Text to be prefixed to each log message.
		 * @var int
		 */
		protected $_prefix = '';

		/**
		 * Return the singleton instance, creating it if it doesn't exist.
		 * @return \TFN\Logger
		 */
		public static function getInstance()
		{
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Construct a logger object.
		 */
		protected function __construct()
		{
			$this->_start = microtime(true);
		}

		/**
		 * Format and echo a log message.
		 * @param string $message
		 * @param int $indent
		 */
		public function write($message, $indent = 0)
		{
			echo $this->format($message, $indent);
		}

		/**
		 * Format a log message by prefixing it with the current time, the prefix,
		 * and any applicable indents.
		 * @param string $message
		 * @param int $indent
		 * @return string
		 */
		public function format($message, $indent = 0)
		{
			$time = str_pad(number_format((microtime(true)-$this->_start), 3, '.', ''), 10, ' ', STR_PAD_LEFT).': '.$this->_prefix;
			if ($this->_indent > 0 or $indent > 0) {
				$time .= str_repeat($this->_indent_text, $this->_indent + $indent);
			}
			return $time.str_replace("\n", "\n".str_repeat(' ', strlen($time)), trim($message))."\n";
		}

		/**
		 * Increment the current indent count.
		 */
		public function indent()
		{
			$this->_indent++;
		}

		/**
		 * Decrement the current indent count.
		 */
		public function unindent()
		{
			if ($this->_indent > 0) {
				$this->_indent--;
			}
		}

		/**
		 * Set the indent text.
		 * @param string $indent_text
		 */
		public function setIndentText($indent_text)
		{
			$this->_indent_text = $indenttext;
		}

		/**
		 * Set the current prefix.
		 * @param string $prefix
		 */
		public function setPrefix($prefix)
		{
			$this->_prefix = $prefix.': ';
		}

		/**
		 * Clear the current prefix.
		 */
		public function clearPrefix()
		{
			$this->_prefix = '';
		}
	}
