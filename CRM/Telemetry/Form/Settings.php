<?php

/**
 * Admin settings form at civicrm/admin/telemetry
 */
class CRM_Telemetry_Form_Settings extends CRM_Core_Form {

  public function buildQuickForm() {
    $this->add(
      'text',
      'telemetry_endpoint_url',
      ts('Endpoint URL'),
      ['size' => 70, 'placeholder' => 'https://fudev.local/telemetry/receive'],
      TRUE
    );
    $this->add(
      'text',
      'telemetry_auth_key',
      ts('Auth Key'),
      ['size' => 50]
    );
    $this->add(
      'select',
      'telemetry_environment',
      ts('Environment'),
      [
        ''            => ts('- select -'),
        'production'  => ts('Production'),
        'staging'     => ts('Staging'),
        'development' => ts('Development'),
        'archive'     => ts('Archive'),
      ],
      TRUE
    );
    $this->addButtons([
      ['type' => 'submit', 'name' => ts('Save'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => ts('Cancel')],
    ]);
    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    return [
      'telemetry_endpoint_url' => Civi::settings()->get('telemetry_endpoint_url'),
      'telemetry_auth_key'     => Civi::settings()->get('telemetry_auth_key'),
      'telemetry_environment'  => Civi::settings()->get('telemetry_environment'),
    ];
  }

  public function postProcess() {
    $values = $this->exportValues();
    Civi::settings()->set('telemetry_endpoint_url', trim($values['telemetry_endpoint_url']));
    Civi::settings()->set('telemetry_auth_key', trim($values['telemetry_auth_key']));
    Civi::settings()->set('telemetry_environment', $values['telemetry_environment']);
    CRM_Core_Session::setStatus(ts('Telemetry settings saved.'), ts('Saved'), 'success');
  }

}
