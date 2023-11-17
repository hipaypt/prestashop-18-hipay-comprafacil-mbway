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

<div id="hipay_mbway_admin_order" class="panel">
    <div class="panel-heading">
      {l s='MB WAY payment information' mod='hipaymbway'} 
    </div>

    <div class="table-responsive">
        <table class="table">
            <tbody>
                <tr class="">
                    <td valign=top><img src="{$mbway_logo|escape:'html':'UTF-8'}" alt="MB WAY - HIPAY" border="0" /></td>
                    <td valign="top" width="100%">
                        <span style="font-weight: bold;text-align: left;">{l s='Mobile:' mod='hipaymbway'} </span>
                        {$phone|escape:'htmlall':'UTF-8'}<br>

                        <span style="font-weight: bold;text-align: left;">{l s='Reference:' mod='hipaymbway'} </span>
                        {$reference|escape:'htmlall':'UTF-8'}<br>

                        <span style="font-weight: bold;text-align: left;">{l s='Amount:' mod='hipaymbway'} </span>
                        {$amount|escape:'htmlall':'UTF-8'}<br>

                        <span style="font-weight: bold;text-align: left;">{l s='Status:' mod='hipaymbway'} </span>
                        {$status|escape:'htmlall':'UTF-8'}<br>

                        <span style="font-weight: bold;text-align: left;">{l s='Notification:' mod='hipaymbway'} </span>
                        {$notification|escape:'htmlall':'UTF-8'}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>