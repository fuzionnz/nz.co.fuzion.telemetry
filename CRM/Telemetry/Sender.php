<?php

/**
 * POSTs collected telemetry data to the configured receiver endpoint.
 */
class CRM_Telemetry_Sender {

  public function send(array $data) {
    $endpointUrl = Civi::settings()->get('telemetry_endpoint_url');
    $authKey     = Civi::settings()->get('telemetry_auth_key');

    if (empty($endpointUrl)) {
      CRM_Core_Error::debug_log_message('Telemetry: no endpoint URL configured, skipping.');
      return FALSE;
    }

    $payload = json_encode($data);

    $ch = curl_init($endpointUrl);
    curl_setopt_array($ch, [
      CURLOPT_POST           => TRUE,
      CURLOPT_POSTFIELDS     => $payload,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_TIMEOUT        => 15,
      CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload),
        'X-Telemetry-Key: ' . $authKey,
      ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
      CRM_Core_Error::debug_log_message('Telemetry: curl error - ' . $error);
      return FALSE;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
      CRM_Core_Error::debug_log_message('Telemetry: receiver returned HTTP ' . $httpCode);
      return FALSE;
    }

    CRM_Core_Error::debug_log_message('Telemetry: report sent successfully (HTTP ' . $httpCode . ')');
    return TRUE;
  }

}
