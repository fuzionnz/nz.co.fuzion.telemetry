<?php

/**
 * API action called by the CiviCRM scheduled job.
 * Can also be triggered manually: cv api Telemetry.send
 */
function civicrm_api3_telemetry_send($params) {
  $collector = new CRM_Telemetry_Collector();
  $data      = $collector->collect();

  $sender = new CRM_Telemetry_Sender();
  $sent   = $sender->send($data);

  return civicrm_api3_create_success(
    ['sent' => $sent, 'site_url' => $data['site_url']],
    $params,
    'Telemetry',
    'send'
  );
}

function _civicrm_api3_telemetry_send_spec(&$spec) {
  // No required params — runs silently on cron
}
