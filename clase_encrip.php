<?php
class ENCR
{
	private static $api_url = 'https://trazapp.minex.com.co/TrazDoc/bot/Minexdocus/enc.php';
	private static $auth_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczpcL1wvdHJhemFwcC5taW5leC5jb20uY28iLCJhdWQiOiJodHRwczpcL1wvdHJhemFwcC5taW5leC5jb20uY28iLCJpYXQiOjE3MjYwNjY2ODksImV4cCI6MTE3MjYwNjY2ODgsImRhdGEiOm51bGx9.XboIEvytOnNlkpR_5hsDEvM0jGZtsKyV-mc_F5bxKa4';

	/**
	 * Realiza una petición HTTP a la API
	 */
	private static function makeApiRequest($data)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, self::$api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . self::$auth_token
		));
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);

		curl_close($ch);

		if ($error) {
			trigger_error("Error en la petición cURL: " . $error, E_USER_ERROR);
			return false;
		}

		if ($http_code !== 200) {
			trigger_error("Error HTTP: " . $http_code, E_USER_ERROR);
			return false;
		}

		$decoded_response = json_decode($response, true);

		if ($decoded_response === null) {
			trigger_error("Error al decodificar JSON", E_USER_ERROR);
			return false;
		}

		return $decoded_response;
	}

	/**
	 * Encripta un string usando la API externa
	 */
	public static function encript($string)
	{
		$base64_string = base64_encode($string);

		$data = array(
			'dato' => $base64_string
		);

		$response = self::makeApiRequest($data);

		if ($response === false) {
			error_log("Error en encriptación: No se pudo realizar la petición a la API");
			return false;
		}

		if (isset($response[0]['encrip'])) {
			return $response[0]['encrip'];
		} else {
			error_log("Error en encriptación: Respuesta de API inválida");
			return false;
		}
	}

	/**
	 * Desencripta un string usando la API externa
	 */
	public static function descript($string)
	{
		$data = array(
			'dato' => $string
		);

		$response = self::makeApiRequest($data);

		if ($response === false) {
			error_log("Error en desencriptación: No se pudo realizar la petición a la API");
			return false;
		}

		if (isset($response[0]['descrip'])) {
			return base64_decode($response[0]['descrip']);
		} else {
			error_log("Error en desencriptación: Respuesta de API inválida");
			return false;
		}
	}
}