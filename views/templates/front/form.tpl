{*
 * EU Withdrawal Button — withdrawal declaration form (step 1)
 * @license GPL-3.0-or-later
 *}
{extends file=$layout}
{block name='content'}
<section id="euw-form" class="euw-wrap">
  <h1 class="h3">{$euw_label|escape:'html':'UTF-8'}</h1>

  <p class="euw-intro">
    {l s='Ordine' mod='euwithdrawal'}: <strong>{$euw_order_reference|escape:'html':'UTF-8'}</strong>
    &middot; {l s='Termine ultimo per il recesso' mod='euwithdrawal'}: <strong>{$euw_deadline|escape:'html':'UTF-8'}</strong>
  </p>
  <p class="text-muted">{l s='Puoi esercitare il diritto di recesso senza fornire alcuna motivazione. Riceverai una conferma via email su supporto durevole.' mod='euwithdrawal'}</p>

  <form method="post" action="{$euw_action_url|escape:'html':'UTF-8'}" id="euw-withdraw-form" class="euw-form">
    <div class="euw-type form-group">
      <label class="euw-radio">
        <input type="radio" name="euw_type" value="full" checked> {l s='Recedo dall’intero ordine' mod='euwithdrawal'}
      </label>
      <label class="euw-radio">
        <input type="radio" name="euw_type" value="partial"> {l s='Recedo solo da alcuni prodotti' mod='euwithdrawal'}
      </label>
    </div>

    <div class="euw-items" id="euw-items" style="display:none">
      {foreach from=$euw_products item=p}
        <div class="euw-item">
          <label class="euw-item-label">
            <input type="checkbox" name="items[]" value="{$p.id_order_detail|intval}">
            {$p.name|escape:'html':'UTF-8'}{if $p.reference} <span class="text-muted">({$p.reference|escape:'html':'UTF-8'})</span>{/if}
          </label>
          <input type="number" class="euw-qty" name="qty_{$p.id_order_detail|intval}"
                 value="{$p.quantity|intval}" min="1" max="{$p.quantity|intval}">
        </div>
      {/foreach}
    </div>

    <p class="text-muted">{l s='Nel passaggio successivo potrai rivedere e confermare la richiesta.' mod='euwithdrawal'}</p>
    <button type="submit" name="euw_continue" value="1" class="btn btn-primary euw-submit">
      {l s='Continua' mod='euwithdrawal'}
    </button>
  </form>
</section>
{/block}
