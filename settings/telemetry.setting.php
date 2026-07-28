<?php

/**
 * Settings definitions for nz.co.fuzion.telemetry.
 */
return [
  'telemetry_endpoint_url' => [
    'group_name'       => 'Telemetry Settings',
    'group'            => 'telemetry',
    'name'             => 'telemetry_endpoint_url',
    'type'             => 'String',
    'default'          => '',
    'add'              => '1.0',
    'is_domain'        => 1,
    'is_contact'       => 0,
    'description'      => 'URL of the central monitoring receiver endpoint',
    'title'            => 'Telemetry Endpoint URL',
    'html_type'        => 'text',
    'quick_form_type'  => 'Element',
  ],
  'telemetry_auth_key' => [
    'group_name'       => 'Telemetry Settings',
    'group'            => 'telemetry',
    'name'             => 'telemetry_auth_key',
    'type'             => 'String',
    'default'          => '',
    'add'              => '1.0',
    'is_domain'        => 1,
    'is_contact'       => 0,
    'description'      => 'Shared secret sent in X-Telemetry-Key header',
    'title'            => 'Telemetry Auth Key',
    'html_type'        => 'text',
    'quick_form_type'  => 'Element',
  ],
  'telemetry_environment' => [
    'group_name'       => 'Telemetry Settings',
    'group'            => 'telemetry',
    'name'             => 'telemetry_environment',
    'type'             => 'String',
    'default'          => '',
    'add'              => '1.0',
    'is_domain'        => 1,
    'is_contact'       => 0,
    'description'      => 'What kind of site this is (production, staging, development, archive)',
    'title'            => 'Telemetry Environment',
    'html_type'        => 'select',
    'quick_form_type'  => 'Element',
  ],
];
