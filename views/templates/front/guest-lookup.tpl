{*
 * EU Withdrawal Button — guest lookup (order reference + email)
 * @license GPL-3.0-or-later
 *}
{extends file=$layout}
{block name='content'}
<section class="euw-wrap" id="euw-lookup">
  <h1 class="h3">{l s='Recesso per clienti senza account' mod='euwithdrawal'}</h1>
  <p class="text-muted">{l s='Inserisci il riferimento del tuo ordine e l’email usata per l’acquisto.' mod='euwithdrawal'}</p>

  {if $euw_error}
    <div class="alert alert-danger">{$euw_error|escape:'html':'UTF-8'}</div>
  {/if}

  <form method="post" action="{$euw_action_url|escape:'html':'UTF-8'}" class="euw-form">
    <div class="form-group">
      <label>{l s='Riferimento ordine' mod='euwithdrawal'}</label>
      <input type="text" name="reference" class="form-control" required
             value="{$euw_reference|escape:'html':'UTF-8'}" placeholder="ABCDEFGHI">
    </div>
    <div class="form-group">
      <label>{l s='Email dell’ordine' mod='euwithdrawal'}</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <button type="submit" name="euw_lookup" value="1" class="btn btn-primary">
      {l s='Continua' mod='euwithdrawal'}
    </button>
  </form>
</section>
{/block}
