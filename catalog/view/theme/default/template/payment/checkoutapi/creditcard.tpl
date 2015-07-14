
<h4>Secured credit/debit card payment with Checkout.com</h4>
<div class="widget-container"></div>

<div class="buttons">
<a  class="button" href="#" id="checkoutapi-button">Pay now </a>
</div>

<script type="text/javascript">

    $.ajax({
        url: '<?php echo $url ?>',
        dataType: 'script',
        cache: true,
        beforeSend: function(){
            window.CKOConfig = {
                debugMode: false,
                renderMode: 0,
                namespace: 'CheckoutIntegration',
                publicKey: '<?php echo $publicKey ?>',
                paymentToken: "<?php echo $paymentToken ?>",
                value: '<?php echo $amount ?>',
                currency: '<?php echo $order_currency ?>',
                customerEmail: '<?php echo $email ?>',
                customerName: '<?php echo $name ?>',
                paymentMode: '<?php echo $paymentMode ?>',
                logoUrl: '<?php echo $logoUrl?>',
                themeColor:'<?php echo $themeColor?>',
                buttonColor:'<?php echo $buttonColor?>',
                iconColor:'<?php echo $iconColor?>',
                useCurrencyCode:'<?php echo $currencyFormat?>',
                billingDetails: {
                  'addressLine1'  :  "<?php echo $addressLine1 ?>",
                  'addressLine2'  :  "<?php echo $addressLine2 ?>",
                  'postcode'      :  "<?php echo $postcode ?>",
                  'country'       :  "<?php echo $country ?>",
                  'city'          :  "<?php echo $city ?>",
                  'phone'         :  {
                                        'number' : "<?php echo $phone ?>",
                                     },
                },
                forceMobileRedirect: true,
                subtitle:'Please enter your credit card details',
                widgetContainerSelector: '.widget-container',
                cardCharged: function(event){
                    document.getElementById('cko-cc-paymenToken').value = event.data.paymentToken;
                    console.log(event);
                    $.ajax({
                        url: 'index.php?route=payment/checkoutapipayment/send',
                        type: 'post',
                        data: $('#payment :input'),
                        dataType: 'json',
                        beforeSend: function() {
                                        $('#button-confirm').attr('disabled', true);
                                        $('#payment').button('loading');
                                    },
                        complete: function() {
                            $('#button-confirm').attr('disabled', false);
                            $('.attention').button('reset');
                        },
                        success: function(json) {
                            if (json['error']) {
                                alert(json['error']);
                            }

                            if (json['redirect']) {
                                location = json['redirect'];
                            }
                        }
                    });

                },
                ready: function() {

                    if(typeof CheckoutIntegration !='undefined') {
                       if(!CheckoutIntegration.isMobile()){
                           jQuery('#checkoutapi-button').hide();
                       }
                       else {
                           jQuery('.widget-container').hide();
                           jQuery('#checkoutapi-button').attr('href', CheckoutIntegration.getRedirectionUrl()+'&trackId=<?php echo $trackId?>');
                       }
                    }

                }
            }
        },
        success: function() {
          //  Checkout.render();
        }

    });
</script>

<div class="content" id="payment">
    <input type="hidden" name="cko_cc_paymenToken" id="cko-cc-paymenToken" value="">
</div>