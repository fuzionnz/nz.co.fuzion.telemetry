<?php

return [
  [
    'name'   => 'nz.co.fuzion.telemetry:Job:SendTelemetryReport',
    'entity' => 'Job',
    'params' => [
      'version'       => 3,
      'name'          => 'Send Site Telemetry Report',
      'description'   => 'Collects CiviCRM site stats and sends to central monitoring dashboard',
      'run_frequency' => 'Daily',
      'api_entity'    => 'Telemetry',
      'api_action'    => 'send',
      'parameters'    => '',
    ],
  ],
];
