<?php

/**
 * Collects all telemetry data from the local CiviCRM site.
 */
class CRM_Telemetry_Collector {

  public function collect() {
    return [
      'site_key'          => $this->getSiteKey(),
      'site_url'          => CRM_Utils_System::baseURL(),
      'environment'       => Civi::settings()->get('telemetry_environment') ?: 'unknown',
      'reported_at'       => date('Y-m-d H:i:s'),
      'civicrm_version'   => CRM_Utils_System::version(),
      'php_version'       => phpversion(),
      'smarty_version'    => $this->getSmartyVersion(),
      'outbound_mail'     => $this->getOutboundMail(),
      'extensions'        => $this->getExtensions(),
      'payment_processors' => $this->getPaymentProcessors(),
      'system_checks'     => $this->getSystemCheckErrors(),
    ];
  }

  private function getSiteKey() {
    return Civi::settings()->get('site_id') ?: md5(CRM_Utils_System::baseURL());
  }

  private function getSmartyVersion() {
    if (defined('Smarty::SMARTY_VERSION')) {
      return Smarty::SMARTY_VERSION;
    }
    if (defined('Smarty\Smarty::SMARTY_VERSION')) {
      return \Smarty\Smarty::SMARTY_VERSION;
    }
    try {
      $smarty = CRM_Core_Smarty::singleton();
      if (isset($smarty->_version)) {
        return $smarty->_version;
      }
      $class = get_class($smarty);
      if (defined($class . '::SMARTY_VERSION')) {
        return constant($class . '::SMARTY_VERSION');
      }
    }
    catch (Exception $e) {}
    return 'unknown';
  }

  private function getOutboundMail() {
    $backend = Civi::settings()->get('mailing_backend');
    $option  = $backend['outBound_option'] ?? NULL;
    $map = [
      0 => 'Sendmail',
      1 => 'SMTP',
      2 => 'Disabled',
      3 => 'Redirect to Email',
      4 => 'Log Only',
      5 => 'Gmail/OAuth',
    ];
    return $map[$option] ?? 'Unknown (' . $option . ')';
  }

  private function getExtensions() {
    $result = civicrm_api3('Extension', 'get', [
      'status'  => 'installed',
      'options' => ['limit' => 0],
    ]);

    // Get cached remote versions for upgrade detection
    $remoteVersions = [];
    try {
      $browser = CRM_Extension_System::singleton()->getBrowser();
      if ($browser->isEnabled()) {
        foreach ($browser->getExtensions() as $key => $info) {
          $remoteVersions[$key] = $info->version ?? '';
        }
      }
    }
    catch (Exception $e) {}

    $extensions = [];
    foreach ($result['values'] as $ext) {
      $key     = $ext['key'] ?? '';
      $version = $ext['version'] ?? '';
      $hasUpgrade = FALSE;
      if (!empty($remoteVersions[$key]) && !empty($version)) {
        $hasUpgrade = version_compare($remoteVersions[$key], $version, '>');
      }
      $extensions[] = [
        'key'         => $key,
        'label'       => $ext['label'] ?? $key,
        'version'     => $version,
        'has_upgrade' => $hasUpgrade,
      ];
    }
    return $extensions;
  }

  private function getPaymentProcessors() {
    $result = civicrm_api3('PaymentProcessor', 'get', [
      'is_active' => 1,
      'is_test'   => 0,
      'options'   => ['limit' => 0],
      'return'    => ['name', 'payment_processor_type_id'],
    ]);
    $processors = [];
    foreach ($result['values'] as $pp) {
      $processors[] = [
        'name' => $pp['name'],
        'type' => $pp['payment_processor_type_id'] ?? 'unknown',
      ];
    }
    return $processors;
  }

  private function getSystemCheckErrors() {
    $errors = [];
    try {
      $checks = CRM_Utils_Check::singleton()->checkAll([], FALSE);
      foreach ($checks as $check) {
        $level = $check->getLevel();
        // Only report warning and above
        if (in_array($level, ['warning', 'error', 'critical', 'alert', 'emergency'])) {
          $errors[] = [
            'name'    => $check->getName(),
            'level'   => $level,
            'message' => strip_tags($check->getMessage()),
          ];
        }
      }
    }
    catch (Exception $e) {
      // Don't let system check failures stop telemetry sending
    }
    return $errors;
  }

}
