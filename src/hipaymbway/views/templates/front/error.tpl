{**
 * HiPay MB WAY Prestashop
 *
 * 2019 HiPay Portugal
 *
 * NOTICE OF LICENSE
 *
 * @author    HiPay Portugal <portugal@hipay.com>
 * @copyright 2019 HiPay Portugal
 * @license   https://github.com/hipaypt/hipaypt-mbway-prestashop/blob/master/LICENSE
 *}

{extends "$layout"}

{block name="content"}
    <div>
        <p>{l s='An error occurred' mod='hipaymbway'}:</p>
        
        <ul class="alert alert-warning">
            <li>{$error|escape:'htmlall':'UTF-8'}</li>
        </ul>
    </div>

    <div>
        <p>{l s='Please try again.' mod='hipaymbway'}</p>
    </div>
{/block}