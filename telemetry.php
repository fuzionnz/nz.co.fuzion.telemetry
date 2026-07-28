<?php

/**
 * Main hooks file for nz.co.fuzion.telemetry extension.
 */

function telemetry_civicrm_config(&$config) {
  $extRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR;
  $include_path = $extRoot . PATH_SEPARATOR . get_include_path();
  set_include_path($include_path);

  $template = CRM_Core_Smarty::singleton();
  $template->addTemplateDir($extRoot . 'templates');
}

function telemetry_civicrm_xmlMenu(&$files) {
  $files[] = __DIR__ . '/xml/Menu/telemetry.xml';
}

function telemetry_civicrm_install() {}
function telemetry_civicrm_enable() {}
function telemetry_civicrm_disable() {}
function telemetry_civicrm_uninstall() {}

/**
 * Adds Telemetry Settings link under Administer > System Settings.
 */
function telemetry_civicrm_navigationMenu(&$menu) {
  $item = [
    'label'      => ts('Telemetry Settings'),
    'name'       => 'telemetry_settings',
    'url'        => 'civicrm/telemetry',
    'permission' => 'administer CiviCRM',
    'operator'   => 'OR',
    'separator'  => 0,
  ];

  foreach ($menu as $key => &$node) {
    if (isset($node['attributes']['name']) && $node['attributes']['name'] === 'System Settings') {
      $node['child'][] = ['attributes' => $item];
      return;
    }
    if (!empty($node['child'])) {
      _telemetry_insert_nav($node['child'], $item);
    }
  }
}

function _telemetry_insert_nav(&$nodes, $item) {
  foreach ($nodes as &$node) {
    if (isset($node['attributes']['name']) && $node['attributes']['name'] === 'System Settings') {
      $node['child'][] = ['attributes' => $item];
      return;
    }
    if (!empty($node['child'])) {
      _telemetry_insert_nav($node['child'], $item);
    }
  }
}
