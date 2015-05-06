
<h4>Please select a Credit / Debit card</h4>
    <div class="widget-container"></div>
    <div class="content" id="payment">
    <input type="hidden" name="cko_cc_paymenToken" id="cko-cc-paymenToken" value="">
    </div>



    <script type="text/javascript">

        $.ajax({
            url: 'https://www.checkout.com/cdn/js/checkout.js',
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
                    paymentMode: 'card',
                    title: '{$store}',
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
                            beforeSend: function () {
                                $('#button-confirm').attr('disabled', true);
                                $('#payment').before('<div class="attention">' +
                                '<img src="catalog/view/theme/default/image/loading.gif" alt="" />' +
                                '<?php echo $textWait ?>'
                                +'</div>');
                            },
                            complete: function () {
                                $('#button-confirm').attr('disabled', false);
                                $('.attention').remove();
                            },
                            success: function (json) {

                                if (json['error']) {
                                    alert(json['error']);
                                }

                                if (json['success']) {
                                    location = json['success'];
                                }
                            }
                        });

                    },
                    ready: function() {

                    }
                }
            },
            success: function() {
              //  Checkout.render();
            }

        });
    </script>