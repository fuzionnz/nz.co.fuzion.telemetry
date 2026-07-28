<div class="crm-block crm-form-block crm-telemetry-settings-form-block">
  <h3>{ts}CiviCRM Site Telemetry{/ts}</h3>
  <p class="description">
    {ts}This extension sends a daily snapshot of this site's stats to your central monitoring dashboard.{/ts}
  </p>

  <table class="form-layout-compressed">
    <tr class="crm-telemetry-form-block-endpoint_url">
      <td class="label">{$form.telemetry_endpoint_url.label} <span class="crm-marker">*</span></td>
      <td>
        {$form.telemetry_endpoint_url.html}
        <br/><span class="description">{ts}The receiver URL on your central dashboard (e.g. https://fudev.local/telemetry/receive){/ts}</span>
      </td>
    </tr>
    <tr class="crm-telemetry-form-block-auth_key">
      <td class="label">{$form.telemetry_auth_key.label}</td>
      <td>
        {$form.telemetry_auth_key.html}
        <br/><span class="description">{ts}Shared secret sent as X-Telemetry-Key header. Must match the receiver configuration.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-telemetry-form-block-environment">
      <td class="label">{$form.telemetry_environment.label} <span class="crm-marker">*</span></td>
      <td>
        {$form.telemetry_environment.html}
        <br/><span class="description">{ts}What kind of site this is. Used to filter sites on the dashboard (e.g. Production only).{/ts}</span>
      </td>
    </tr>
  </table>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
