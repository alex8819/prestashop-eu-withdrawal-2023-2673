{*
 * EU Withdrawal Button — public receipt verification (audit)
 * @license GPL-3.0-or-later
 *}
{extends file=$layout}
{block name='content'}
<section class="euw-wrap" id="euw-verify">
  <h1 class="h3">{l s='Verifica ricevuta di recesso' mod='euwithdrawal'}</h1>
  <p class="text-muted">{l s='Inserisci il codice di verifica per controllare l’autenticità di una ricevuta di recesso.' mod='euwithdrawal'}</p>

  {if $euw_error}
    <div class="alert alert-danger">{$euw_error|escape:'html':'UTF-8'}</div>
  {/if}

  {if $euw_result}
    <div class="alert alert-success euw-verify-result">
      <p><strong>{l s='Ricevuta valida' mod='euwithdrawal'}</strong></p>
      <ul>
        <li>{l s='Codice' mod='euwithdrawal'}: <strong>{$euw_result.code|escape:'html':'UTF-8'}</strong></li>
        <li>{l s='Data' mod='euwithdrawal'}: {$euw_result.date|escape:'html':'UTF-8'}</li>
        <li>{l s='Ordine' mod='euwithdrawal'}: {$euw_result.order_masked|escape:'html':'UTF-8'}</li>
        <li>{l s='Tipo' mod='euwithdrawal'}: {if $euw_result.type == 'partial'}{l s='parziale' mod='euwithdrawal'}{else}{l s='totale' mod='euwithdrawal'}{/if}</li>
        <li>{l s='Stato' mod='euwithdrawal'}: {$euw_result.status|escape:'html':'UTF-8'}</li>
      </ul>
    </div>
  {/if}

  <form method="post" action="{$euw_action_url|escape:'html':'UTF-8'}" class="euw-form">
    <div class="form-group">
      <label>{l s='Codice di verifica' mod='euwithdrawal'}</label>
      <input type="text" name="code" class="form-control" required
             value="{$euw_code|escape:'html':'UTF-8'}" placeholder="WD-XXXX-XXXXXX">
    </div>
    <button type="submit" name="euw_verify" value="1" class="btn btn-primary">{l s='Verifica' mod='euwithdrawal'}</button>
  </form>
</section>
{/block}
