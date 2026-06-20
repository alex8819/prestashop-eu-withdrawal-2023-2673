{*
 * EU Withdrawal Button — success page
 * @license GPL-3.0-or-later
 *}
{extends file=$layout}
{block name='content'}
<section class="euw-wrap euw-success text-center">
  <i class="material-icons euw-success-icon" aria-hidden="true">check_circle</i>
  <h1 class="h3">{l s='Richiesta di recesso inviata' mod='euwithdrawal'}</h1>
  <p>{l s='Abbiamo registrato la tua richiesta e ti abbiamo inviato una conferma via email (supporto durevole).' mod='euwithdrawal'}</p>
  <a class="btn btn-secondary" href="{$urls.base_url|escape:'html':'UTF-8'}">{l s='Torna al negozio' mod='euwithdrawal'}</a>
</section>
{/block}
