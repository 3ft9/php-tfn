<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN\Storage;

	/**
	 * All expections thrown by the Storage class will be of this type.
	 */
	class Exception extends \TFN\TFNException {
		const NOT_FOUND = 'not-found';
	}
