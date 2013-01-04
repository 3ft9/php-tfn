<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * All expections thrown by the Google class will be of this type.
	 */
	class GoogleException extends TFNException { }

	/**
	 * An interface to Google API services. Note that this usage is technically
	 * against Google's terms of usage unless you display a map using the results.
	 * You are not permitted to store the results of these queries!
	 */
	class Google
	{
		protected static function getClient()
		{
			static $_client = false;
			if ($_client === false) {
				$_client = new HttpClient('http://maps.googleapis.com/maps/api/');
			}
			return $_client;
		}

		public static function geocode($address)
		{
			try {
				$res = self::getClient()->get('geocode/json', array('sensor' => 'false', 'address' => $address));
				$data = json_decode($res, true);
				if (!empty($data['status']) && $data['status'] == 'OK' && !empty($data['results'])) {
					return $data['results'];
				}
				throw new GoogleException('Non-OK status: '.$res);
			} catch (HttpClientException $e) {
				throw new GoogleException($e->getMessage());
			}
		}
	}
