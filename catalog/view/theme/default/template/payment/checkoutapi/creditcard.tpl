
<h4>Secured credit/debit card payment with Checkout.com</h4>
<div class="widget-container"></div>

<script type="text/javascript">
            window.checkoutIntegrationCurrentConfig= {
                debugMode: true,
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
                showMobileIcons: 'true',
                enableIframePreloading : false,
                cardFormMode: 'cardTokenisation',
                billingDetails: {
                  'addressLine1'  :  "<?php echo $addressLine1 ?>",
                  'addressLine2'  :  "<?php echo $addressLine2 ?>",
                  'postcode'      :  "<?php echo $postcode ?>",
                  'country'       :  "<?php echo $country ?>",
                  'city'          :  "<?php echo $city ?>",
                  'phone'         :  {
                                        "number" : "<?php echo $phone ?>"
                                     }
                },
                subtitle:'Please enter your credit card details',
                widgetContainerSelector: '.widget-container',
                cardTokenised: function(event){
                    if (document.getElementById('cko_cardToken').value.length === 0) {
                        document.getElementById('cko_cardToken').value = event.data.cardToken;
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
                    }
                }
            };

            window.checkoutIntegrationIsReady = window.checkoutIntegrationIsReady || false;

            if (!window.checkoutIntegrationIsReady) {
                window.CKOConfig = {
                    ready: function () {
                        if (window.checkoutIntegrationIsReady) {
                            return false;
                        }

                        if (typeof CKOAPIJS == 'undefined') {
                            return false;
                        }

                        CKOAPIJS.render(window.checkoutIntegrationCurrentConfig);
                        window.checkoutIntegrationIsReady = true;
                    }
                };

                var url = '<?php echo $url ?>';
                var script = document.createElement('script');
                script.src = url;
                script.async = true;
                script.setAttribute('data-namespace', 'CKOAPIJS');
                document.head.appendChild(script);
            } else {
                CKOAPIJS.render(checkoutIntegrationCurrentConfig);
            }
</script>

<div class="content" id="payment">
    <input type="hidden" name="cko_cardToken" id="cko_cardToken" value="">
</div>