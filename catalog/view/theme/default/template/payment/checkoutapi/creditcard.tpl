
<?php if($save_card == 'yes' && $this->session->data['customer_login'] == 'yes' ){
    if(!empty($this->session->data['cardLists'])){ 
        foreach($this->session->data['cardLists'] as $key=>$value ){ ?>
            <label for="checkoutapipayment-saved-card-<?php echo $value['entity_id']; ?>">
            <input id="checkoutapipayment-saved-card-<?php echo $value['entity_id']; ?>" class="checkoutapipayment-saved-card" type="radio" name="cko-rad-button" value="<?php echo $value['entity_id']; ?>"/> xxxx-<?php echo $value['card_number'].' '. $value['card_type']; ?></label>   
            <br>
        <?php } ?>
        <label for="checkoutapipayment-new-card">
        <input id="checkoutapipayment-new-card" class= "checkoutapipayment-new-card" type="radio" name="cko-rad-button"  value="new_card"/> Use New card</label>
        <br>

    <?php } else { ?>
        <label for="checkoutapipayment-new-card">
            <input id="checkoutapipayment-new-card" class= "checkoutapipayment-new-card" type="radio" name="cko-rad-button"  value="new_card"/> Use New card</label>
            <br>
            <script type="text/javascript">  console.log('here');
                setTimeout(function(){ 
                    checkoutHideNewNoPciCard();
                    function checkoutHideNewNoPciCard() {
                        jQuery('.checkoutapipayment-new-card').attr("checked",true);
                        jQuery('.widget-container').show();
                        jQuery('#save-card').show();
                    }
                 }, 1000);
            </script>
    <?php } ?>

    <script type="text/javascript"> 
        checkoutHideNewNoPciCard();
        function checkoutHideNewNoPciCard() {
            jQuery('.checkoutapipayment-new-card').attr("checked",false);
            //jQuery('.apmSelected').removeClass('apmLab');
            jQuery('.widget-container').hide();
            jQuery('#save-card').hide();
        }

        function checkoutShowNewNoPciCard() {

        } 

        jQuery('.checkoutapipayment-saved-card').on("click", function() {
            jQuery('.widget-container').hide();
            jQuery('#save-card').hide();
        });

        jQuery('.checkoutapipayment-new-card').on("click", function() {
            jQuery('.widget-container').show();
            jQuery('#save-card').show();
            //jQuery('.apmSelected').removeClass('apmLab');
        });
    </script>

<?php } ?>


<div class="widget-container" align="center"></div>
<?php if($save_card == 'yes' && $this->session->data['customer_login'] == 'yes'){ ?>
    <label id="save-card" for="save-card" style="display: none;"> Save card for future payments
        <input type="checkbox" name="save-card" id="save-card" style="position: absolute;margin-top: 0px;" />
    </label>
    <br><br>
<?php } ?>

<script type="text/javascript">

    $.ajax({
        url: '<?php echo $url ?>',
        dataType: 'script',
        cache: true,
        beforeSend: function(){
            window.CKOConfig = {
                debugMode: true,
                renderMode: 2,
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
                title: '<?php echo $title ?>',
                forceMobileRedirect: true,
                subtitle:'Please enter your credit card details',
                widgetContainerSelector: '.widget-container',
                cardFormMode: 'cardTokenisation',
                cardTokenised: function(event){
                    if (document.getElementById('cko-card-token').value.length === 0 || document.getElementById('cko-card-token').value != event.data.cardToken) {
                        document.getElementById('cko-card-token').value = event.data.cardToken;
                        
                        if(jQuery('input[name="save-card"]:checked').length == 1){
                            document.getElementById('save-card-checkbox').value = 1;
                        }

                        $.ajax({
                            url: 'index.php?route=payment/checkoutapipayment/send',
                            type: 'post',
                            data: $('.payment :input'),
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
                                    CheckoutIntegration.render(window.CKOConfig)
                                }


                                if (json['success']) {
                                    location = json['success'];
                                }
                            }
                        });
                    }

                },

                ready: function(){
                    jQuery('.cko-loader').show();
                },
                widgetRendered: function(){

                    jQuery('.cko-loader').hide();
                   if(typeof CheckoutIntegration !='undefined') {

                       if(!CheckoutIntegration.isMobile()){

                           jQuery('#checkoutapi-button').hide();
                       }
                       else {
                           jQuery('.widget-container').hide();


                           jQuery('#checkoutapi-button').attr('href', CheckoutIntegration.getRedirectionUrl()+'&trackId=<?php echo $trackId?>').show();
                       }
                   }
                },

            }
        },
        success: function() {
            //Checkout.render();
        }

    });
</script>
<p class="cko-loader" style="display:none">
<img src="catalog/view/theme/default/image/loading.gif" alt="" />
</p>
<!-- confirm order button -->
<div class="buttons">
    <div class="right">
        <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="button" />
    </div>
</div>

<input type="hidden" name="cko_cc_paymenToken" id="cko-cc-paymenToken" value="">
<input type="hidden" id="cko-card-token" name="cko-card-token" value=""/>
<input type="hidden" id="cko-payment" name="cko-payment" value="">
<input type="hidden" id="save-card-checkbox" name="save-card-checkbox" value=""/>
<input type="hidden" id="entity_id" name="entity_id" value="" />

<script type="text/javascript">
    jQuery('#button-confirm').click(function(event){
        if(jQuery('.checkoutapipayment-new-card').length > 0){
            if(jQuery('.checkoutapipayment-new-card').is(':checked')){
                document.getElementById('cko-payment').value = 'new_card';
                CheckoutIntegration.open();
            }

        } else if(jQuery('.checkoutapipayment-saved-card').is(':checked')){
            document.getElementById('cko-payment').value = 'saved_card';
            document.getElementById('entity_id').value = jQuery('.checkoutapipayment-saved-card:checked').val();

            $.ajax({
                url: 'index.php?route=payment/checkoutapipayment/send',
                type: 'post',
                data: $('.payment :input'),
                dataType: 'json',
                beforeSend: function () {
                    $('#button-confirm').attr('disabled', true);
                    $('.buttons').before('<div class="attention">' +
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
                        Frames.init(window.checkout);
                    }

                    if (json['success']) {
                        location = json['success'];
                    }
                }
            });
        } else {
            CheckoutIntegration.open();
        }
    });
</script>