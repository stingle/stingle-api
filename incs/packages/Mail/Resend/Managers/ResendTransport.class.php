<?php

/**
 * Mail transport that delivers messages through the Resend (https://resend.com) HTTP API.
 *
 * Selected by setting mailParams.<config>.transport = 'ResendTransport' in the Mail config.
 * The framework instantiates transports with no arguments, so configuration (API key, URL)
 * is read here from the Mail/Resend plugin config.
 */
class ResendTransport implements MailTransportInterface {

	protected $config = null;

	public function __construct() {
		$this->config = ConfigManager::getConfig('Mail', 'Resend')->AuxConfig;
	}

	public function send(Mail $mail, $configName = null) {
		$toAddresses = array();
		foreach ($mail->getToAddresses() as $address) {
			if (!empty($address['name'])) {
				$toAddresses[] = $this->formatAddress($address['address'], $address['name']);
			}
			else {
				$toAddresses[] = $address['address'];
			}
		}

		if (empty($toAddresses)) {
			return false;
		}

		$payload = array(
			'from' => $this->formatAddress($mail->from, $mail->fromName),
			'to' => $toAddresses,
			'subject' => $mail->subject,
		);

		if (!empty($mail->htmlBody)) {
			$payload['html'] = $mail->htmlBody;
		}
		if (!empty($mail->textBody)) {
			$payload['text'] = $mail->textBody;
		}

		$replyTo = array();
		foreach ($mail->getReplyToAddresses() as $address) {
			$replyTo[] = $address['address'];
		}
		if (!empty($replyTo)) {
			$payload['reply_to'] = $replyTo;
		}

		$headers = array();
		foreach ($mail->getCustomHeaders() as $header) {
			$headers[$header['name']] = $header['value'];
		}
		if (!empty($headers)) {
			$payload['headers'] = $headers;
		}

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $this->config->apiUrl,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer ' . $this->config->apiKey,
				'Content-Type: application/json',
			),
		));

		$response = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$curlErr = curl_error($curl);
		curl_close($curl);

		if ($response === false || $httpCode < 200 || $httpCode >= 300) {
			DBLogger::logCustom('resend', 'Resend send failed (HTTP ' . $httpCode . '): ' . ($curlErr ? $curlErr : $response));
			return false;
		}

		return true;
	}

	protected function formatAddress($email, $name = '') {
		$name = trim((string)$name);
		if ($name === '') {
			return $email;
		}
		// Strip characters that would break the "Name <email>" header form.
		$name = str_replace(array('"', "\r", "\n", '<', '>'), '', $name);
		return '"' . $name . '" <' . $email . '>';
	}

}
