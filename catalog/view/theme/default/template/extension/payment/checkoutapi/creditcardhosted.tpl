<form id="payment-form" style="display:none" action="<?php echo $url ?>" method="POST">
    <input name="publicKey" value="<?php echo $publicKey ?>"/>
    <input name="paymentToken" value="<?php echo $paymentToken ?>"/>
    <input name="customerEmail" value="<?php echo $email ?>"/>
    <input name="value" value="<?php echo $amount ?>"/>
    <input name="cardFormMode" value="cardTokenisation"></input>
    <input name="currency" value="<?php echo $order_currency ?>"></input>
    <input name="paymentMode" value="<?php echo $paymentMode ?>"/>
    <input name="contextId" value="<?php echo $trackId ?>"/>
    <input name="useCurrencyCode" value="<?php echo $currencyFormat ?>"/>
    <input name="redirectUrl" value="<?php echo $redirectUrl ?>" />
    <input name="cancelUrl" value="<?php echo $cancelUrl ?>" />
    <input name="logoUrl" value="<?php echo $logoUrl ?>"/>
    <input name="themeColor" value="<?php echo $themeColor ?>"/>
    <input name="buttonLabel" value="<?php echo $buttonLabel ?>">
    <input name="title" value="<?php echo $title ?>"/>
</form>

<div class="buttons">
    <div class="pull-right">
        <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="btn btn-primary" onclick="submit()" />
    </div>
</div>

<script>
    function submit(){
        document.getElementById('payment-form').submit();
    }
</script>
