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

 <form action="{$action}" id="payment-form" method="POST">
    <p>
      {l s='You can enter an alternative payment phone number, associated with your MB WAY App.' mod='hipaymbway'}
    </p>

    <p>
      <input type="text" size="11" autocomplete="off" name="altPhoneNumber" id="altPhoneNumber" maxlength="9">
    </p>

    <p>
      {l s='Plase ensure you have the MB WAY App installed and activated for your phone number.' mod='hipaymbway'}
    </p>
</form>
