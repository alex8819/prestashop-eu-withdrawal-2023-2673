{*
 * EU Withdrawal Button — review & confirm (step 2)
 * @license GPL-3.0-or-later
 *}
{extends file=$layout}
{block name='content'}
<section id="euw-review" class="euw-wrap">
  <h1 class="h3">{l s='Rivedi e conferma il recesso' mod='euwithdrawal'}</h1>
  <p class="euw-intro">
    {l s='Ordine' mod='euwithdrawal'}: <strong>{$euw_order_reference|escape:'html':'UTF-8'}</strong>
    &middot; {l s='Termine ultimo per il recesso' mod='euwithdrawal'}: <strong>{$euw_deadline|escape:'html':'UTF-8'}</strong>
  </p>

  <div class="euw-summary">
    <h4>{l s='Riepilogo' mod='euwithdrawal'}</h4>
    <p>{l s='Tipo di recesso' mod='euwithdrawal'}:
      <strong>{if $euw_type == 'partial'}{l s='parziale' mod='euwithdrawal'}{else}{l s='totale' mod='euwithdrawal'}{/if}</strong>
    </p>
    <ul class="euw-summary-list">
      {foreach from=$euw_items item=it}
        <li>{$it.quantity|intval}&times; {$it.product_name|escape:'html':'UTF-8'}{if $it.product_reference} <span class="text-muted">({$it.product_reference|escape:'html':'UTF-8'})</span>{/if}</li>
      {/foreach}
    </ul>
  </div>

  <div class="euw-modelform">
    <h4>{l s='Dichiarazione e modulo di recesso (Allegato I-B)' mod='euwithdrawal'}</h4>
    <pre class="euw-decl">{$euw_declaration|escape:'html':'UTF-8'}</pre>
  </div>

  <form method="post" action="{$euw_submit_url|escape:'html':'UTF-8'}" id="euw-confirm-form" class="euw-form"
        data-confirm="{l s='Confermi l’invio della richiesta di recesso? L’operazione sarà registrata e riceverai una conferma via email.' mod='euwithdrawal'}">
    <input type="hidden" name="euw_type" value="{$euw_type|escape:'html':'UTF-8'}">
    {foreach from=$euw_items item=it}
      <input type="hidden" name="items[]" value="{$it.id_order_detail|intval}">
      <input type="hidden" name="qty_{$it.id_order_detail|intval}" value="{$it.quantity|intval}">
    {/foreach}

    <div class="euw-ack form-group">
      <label>
        <input type="checkbox" name="euw_acknowledge" value="1" required>
        {l s='Confermo di voler esercitare il diritto di recesso per i beni sopra indicati. Nessuna motivazione è richiesta; riceverò una conferma via email su supporto durevole.' mod='euwithdrawal'}
      </label>
    </div>

    <button type="submit" name="euw_confirm" value="1" class="btn btn-primary euw-submit">
      {if isset($euw_confirm_label)}{$euw_confirm_label|escape:'html':'UTF-8'}{else}{l s='Conferma il recesso' mod='euwithdrawal'}{/if}
    </button>
    <a href="{$euw_back_url|escape:'html':'UTF-8'}" class="btn btn-secondary euw-back">{l s='Indietro' mod='euwithdrawal'}</a>
  </form>
</section>
{/block}
