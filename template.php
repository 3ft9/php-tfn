<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * The Template class will throw this custom exception when something goes
	 * wrong.
	 */
	class Template_Exception extends TFNException { }

	/**
	 * This class provides a very simple PHP-based templating system.
	 * TODO: Usage documentation!
	 */
	class Template
	{
		/**
		 * Factory method to return a new instance of the Template class
		 *
		 * @param string $tplroot
		 * @return Template
		 */
		public static function create($tplroot)
		{
			return new self($tplroot);
		}

		/**
		 * Run a template
		 *
		 * @param string $____filename Absolute template filename.
		 * @param array $____data An array of variables to make available to the template.
		 * @param bool $____return Set to true to return the output, false to output it.
		 * @throws Template_Exception
		 * @return string
		 */
		public static function run($____filename, $____data = array(), $____return = false)
		{
			$____retval = '';

			if (file_exists($____filename)) {
				if ($____return) {
					ob_start();
				}

				extract($____data);
				require($____filename);

				if ($____return) {
					$____retval = ob_get_contents();
					ob_end_clean();
				}
			} else {
				throw new Template_Exception('Template not found: '.$____filename);
			}

			return $____retval;
		}

		/**
		 * The root directory for templates
		 *
		 * @var string
		 */
		protected $_tplroot = '';

		/**
		 * Internal variables, available to templates as $this->[var].
		 *
		 * @var array
		 */
		protected $_data = array();

		/**
		 * Constructor. Pass in the full path to the template root.
		 *
		 * @param string $tplroot The root template directory.
		 */
		public function __construct($tplroot)
		{
			$this->setTplRoot($tplroot);
		}

		/**
		 * Set the template root.
		 *
		 * @param string $tplroot The root template directory.
		 */
		public function setTplRoot($tplroot)
		{
			$this->_tplroot = str_replace('//', '/', $tplroot.'/');
		}

		/**
		 * Magic function that returns a value from the internal data array.
		 *
		 * @param mixed $var
		 * @return mixed
		 */
		public function __get($var)
		{
			if (isset($this->_data[$var]))
				return $this->_data[$var];
			return null;
		}

		/**
		 * Magic function to set a value in the internal data.
		 *
		 * @param string $var
		 * @param mixed $val
		 */
		public function __set($var, $val)
		{
			$this->_data[$var] = $val;
		}

		/**
		 * Magic function called by isset/empty on member variables.
		 *
		 * @param string $var
		 */
		public function __isset($var)
		{
			return isset($this->_data[$var]);
		}

		/**
		 * Magic function called by unset on member variables.
		 *
		 * @param string $var
		 */
		public function __unset($var)
		{
			unset($this->_data[$var]);
		}

		/**
		 * Checks whether a given template exists.
		 *
		 * @param string $tpl
		 * @return bool
		 */
		public function exists($tpl)
		{
			return file_exists($this->_tplroot.$tpl);
		}

		/**
		 * Render a template.
		 *
		 * Note that this function has a potential hole because we don't check that the
		 * passed template filename moves us outside the template root. This would be a
		 * waste of cycles since we control what's passed to this function, but it means
		 * it's particularly important to validate any externally sourced variable before
		 * using it to refer to a template.
		 *
		 * @param string $____tpl The template filename relative to _tplroot.
		 * @param array $____data An array of variables to be made available to the template.
		 * @param boolean $____return Pass in true to return the output instead of echoing it.
		 */
		public function render($____tpl, $____data = array(), $____return = false)
		{
			$____retval = '';

			$____tplfilename = $this->_tplroot.$____tpl;

			// Make sure the template exists
			if (!file_exists($____tplfilename)) {
				throw new Template_Exception('Template not found: "'.$____tplfilename.'"');
			}

			// Capture the output if we're buffered
			if ($____return) {
				ob_start();
			}

			extract($____data);
			require $____tplfilename;

			if ($____return) {
				$____retval = ob_get_contents();
				ob_end_clean();
			}

			return $____retval;
		}

		/**
		 * Run a template and assign the output to a variable in the internal data.
		 *
		 * @param string $var The variable where the template output should be stored.
		 * @param string $tpl The template file to run, relative to _tplroot.
		 * @param array $data The data to be made available to the template.
		 */
		public function renderToVar($var, $tpl, $data = array())
		{
			$this->_data[$var] = $this->render($tpl, $data, true);
		}

		/**
		 * Run a template and append the output to a variable in the internal data.
		 *
		 * @param string $var The variable where the template output should be stored.
		 * @param string $tpl The template file to run, relative to _tplroot.
		 * @param array $data The data to be made available to the template.
		 */
		public function renderAndAppendToVar($var, $tpl, $data = array())
		{
			$this->_data[$var] .= $this->render($tpl, $data, true);
		}

		/**
		 * Return an HTML-escaped version of the given string.
		 *
		 * @param string $str The string to escape.
		 * @return string     The escaped string.
		 */
		public function escapeHTML($str)
		{
			return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
		}
	}
