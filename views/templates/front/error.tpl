{*
 * EU Withdrawal Button — error page
 * @license GPL-3.0-or-later
 *}
{extends file=$layout}
{block name='content'}
<section class="euw-wrap euw-error">
  <div class="alert alert-danger">{$euw_error|escape:'html':'UTF-8'}</div>
  <a class="btn btn-secondary" href="{$urls.base_url|escape:'html':'UTF-8'}">{l s='Torna al negozio' mod='euwithdrawal'}</a>
</section>
{/block}
