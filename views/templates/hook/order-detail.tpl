{*
 * EU Withdrawal Button — order detail block
 * @license GPL-3.0-or-later
 *}
<div class="euw-order-block card mt-3">
  <div class="card-body">
    <h4 class="euw-title">{l s='Diritto di recesso (UE 2023/2673)' mod='euwithdrawal'}</h4>
    {if $euw_eligible}
      <p class="euw-deadline">
        {l s='Puoi recedere da questo contratto, senza fornire motivazione, entro il' mod='euwithdrawal'}
        <strong>{$euw_deadline|escape:'html':'UTF-8'}</strong>.
      </p>
      <a href="{$euw_url|escape:'html':'UTF-8'}" class="btn btn-primary euw-btn">
        {$euw_label|escape:'html':'UTF-8'}
      </a>
    {elseif $euw_exempt}
      <p class="text-muted euw-exempt">
        {l s='Per i beni di questo ordine il diritto di recesso non si applica:' mod='euwithdrawal'}
        {$euw_exempt_text|escape:'html':'UTF-8'}
      </p>
    {elseif $euw_already}
      <p class="text-muted">{l s='Hai già inviato una richiesta di recesso per questo ordine.' mod='euwithdrawal'}</p>
    {elseif $euw_expired}
      <p class="text-muted">{l s='Il periodo di recesso per questo ordine è scaduto.' mod='euwithdrawal'}</p>
    {else}
      <p class="text-muted">{l s='Il recesso non è disponibile per questo ordine.' mod='euwithdrawal'}</p>
    {/if}
  </div>
</div>
