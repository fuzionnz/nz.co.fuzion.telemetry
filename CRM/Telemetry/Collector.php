<?php

/**
 * Collects all telemetry data from the local CiviCRM site.
 */
class CRM_Telemetry_Collector {

  public function collect() {
    return [
      'site_key'          => $this->getSiteKey(),
      'site_url'          => CRM_Utils_System::baseURL(),
      'server'            => gethostname() ?: 'unknown',
      'environment'       => Civi::settings()->get('telemetry_environment') ?: 'unknown',
      'reported_at'       => date('Y-m-d H:i:s'),
      'civicrm_version'   => CRM_Utils_System::version(),
      'php_version'       => phpversion(),
      'cms'               => $this->getCms(),
      'cms_version'       => $this->getCmsVersion(),
      'refresh_url'       => CRM_Utils_System::url('civicrm/telemetry/refresh', NULL, TRUE, NULL, FALSE, TRUE),
      'smarty_version'    => $this->getSmartyVersion(),
      'outbound_mail'     => $this->getOutboundMail(),
      'extensions'        => $this->getExtensions(),
      'modules'           => $this->getModules(),
      'payment_processors' => $this->getPaymentProcessors(),
      'system_checks'     => $this->getSystemCheckErrors(),
    ];
  }

  private function getSiteKey() {
    return Civi::settings()->get('site_id') ?: md5(CRM_Utils_System::baseURL());
  }

  /** #33573 - CMS name + version so the dashboard can filter sites by CMS release. */
  private function getCms() {
    if (CIVICRM_UF === 'WordPress') {
      return 'WordPress';
    }
    return (CIVICRM_UF === 'Drupal8' || CIVICRM_UF === 'Drupal') ? 'Drupal' : CIVICRM_UF;
  }

  private function getCmsVersion() {
    if (CIVICRM_UF === 'WordPress') {
      return function_exists('get_bloginfo') ? get_bloginfo('version') : 'unknown';
    }
    if (class_exists('\Drupal')) {
      return \Drupal::VERSION;
    }
    return defined('VERSION') ? VERSION : 'unknown';
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

  /** Drupal modules or WordPress plugins, split into contrib/custom for the dashboard. */
  private function getModules() {
    if (CIVICRM_UF === 'WordPress') {
      return $this->getWordPressPlugins();
    }
    if (CIVICRM_UF === 'Drupal8' || CIVICRM_UF === 'Drupal') {
      return class_exists('\Drupal') ? $this->getDrupal8Modules() : $this->getDrupal7Modules();
    }
    return [];
  }

  private function getDrupal8Modules() {
    $modules = [];
    try {
      $list = \Drupal::service('extension.list.module')->getList();
      $statusByModule = $this->getDrupalUpdateStatusMap();
      foreach ($list as $name => $module) {
        if (!empty($module->info['hidden']) || strpos($module->getPath(), 'core/') === 0) {
          continue;
        }
        $modules[] = [
          'key'     => $name,
          'label'   => $module->info['name'] ?? $name,
          'version' => $module->info['version'] ?? '',
          'enabled' => (bool) $module->status,
          // Not on wordpress.org-style contrib/custom split — Drupal convention is a
          // /custom/ subfolder for bespoke modules, anything else counts as contrib.
          'package' => strpos($module->getPath(), '/custom/') !== FALSE ? 'custom' : 'contrib',
          'status'  => $statusByModule[$name] ?? 'unknown',
        ];
      }
    }
    catch (Exception $e) {}
    return $modules;
  }

  /**
   * Module name => 'ok'|'upgrade'|'unsupported'|'unknown', using Drupal core's Update
   * Manager. Reads whatever it already has cached — never triggers a live fetch, since
   * telemetry cron shouldn't be making its own drupal.org calls.
   * @see \Drupal\update\UpdateManagerInterface
   */
  private function getDrupalUpdateStatusMap() {
    $map = [];
    try {
      if (!\Drupal::hasService('module_handler') || !\Drupal::moduleHandler()->moduleExists('update')) {
        return $map;
      }
      \Drupal::moduleHandler()->loadInclude('update', 'inc', 'update.compare');
      $available = update_get_available(FALSE);
      if (empty($available)) {
        return $map;
      }
      $projects = update_calculate_project_data($available);
      $badStatuses = [
        \Drupal\update\UpdateManagerInterface::NOT_SECURE,
        \Drupal\update\UpdateManagerInterface::REVOKED,
        \Drupal\update\UpdateManagerInterface::NOT_SUPPORTED,
      ];
      foreach ($projects as $project) {
        $status = $project['status'] ?? NULL;
        if (in_array($status, $badStatuses, TRUE)) {
          $label = 'unsupported';
        }
        elseif ($status === \Drupal\update\UpdateManagerInterface::NOT_CURRENT) {
          $label = 'upgrade';
        }
        elseif ($status === \Drupal\update\UpdateManagerInterface::CURRENT) {
          $label = 'ok';
        }
        else {
          $label = 'unknown';
        }
        foreach ($project['includes'] ?? [] as $moduleName => $moduleLabel) {
          $map[$moduleName] = $label;
        }
      }
    }
    catch (Exception $e) {}
    return $map;
  }

  private function getDrupal7Modules() {
    $modules = [];
    try {
      if (!function_exists('system_rebuild_module_data')) {
        return [];
      }
      foreach (system_rebuild_module_data() as $name => $module) {
        if (!empty($module->info['hidden']) || strpos($module->filename, 'sites/') !== 0) {
          continue;
        }
        $modules[] = [
          'key'     => $name,
          'label'   => $module->info['name'] ?? $name,
          'version' => $module->info['version'] ?? '',
          'enabled' => (bool) $module->status,
          'package' => strpos($module->filename, '/custom/') !== FALSE ? 'custom' : 'contrib',
        ];
      }
    }
    catch (Exception $e) {}
    return $modules;
  }

  /**
   * Contrib vs custom for WP: reuses the update_plugins transient WP already maintains —
   * plugins matched against wordpress.org show up in its response/no_update arrays, so
   * anything absent from both wasn't found on wordpress.org and counts as custom.
   */
  private function getWordPressPlugins() {
    $modules = [];
    try {
      if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }
      $allPlugins = get_plugins();
      $activePlugins = (array) get_option('active_plugins', []);
      if (is_multisite()) {
        $activePlugins = array_merge($activePlugins, array_keys((array) get_site_option('active_sitewide_plugins', [])));
      }

      $onOrg = [];
      $hasUpdate = [];
      $updateData = get_site_transient('update_plugins');
      if ($updateData) {
        foreach ((array) ($updateData->no_update ?? []) as $file => $info) {
          $onOrg[$file] = TRUE;
        }
        foreach ((array) ($updateData->response ?? []) as $file => $info) {
          $onOrg[$file] = TRUE;
          $hasUpdate[$file] = TRUE;
        }
      }

      foreach ($allPlugins as $file => $info) {
        $modules[] = [
          'key'     => $file,
          'label'   => $info['Name'] ?? $file,
          'version' => $info['Version'] ?? '',
          'enabled' => in_array($file, $activePlugins, TRUE),
          'package' => isset($onOrg[$file]) ? 'contrib' : 'custom',
          'status'  => isset($hasUpdate[$file]) ? 'upgrade' : (isset($onOrg[$file]) ? 'ok' : 'unknown'),
        ];
      }
    }
    catch (Exception $e) {}
    return $modules;
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
