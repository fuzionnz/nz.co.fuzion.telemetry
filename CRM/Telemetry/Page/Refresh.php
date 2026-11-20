<?php

/** #33573 - lets the dashboard ask this site to send its report now, instead of waiting for the daily job. */
class CRM_Telemetry_Page_Refresh extends CRM_Core_Page {

  public function run() {
    $key = Civi::settings()->get('telemetry_auth_key');
    $sent = $_SERVER['HTTP_X_TELEMETRY_KEY'] ?? '';
    if (!$key || !hash_equals($key, $sent)) {
      CRM_Utils_System::sendJSONResponse(['error' => 'Forbidden'], 403);
    }
    $data = (new CRM_Telemetry_Collector())->collect();
    $ok = (new CRM_Telemetry_Sender())->send($data);
    CRM_Utils_System::sendJSONResponse(['sent' => $ok]);
  }

}
