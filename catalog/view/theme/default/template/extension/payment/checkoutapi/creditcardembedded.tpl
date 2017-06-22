<script type="text/javascript">
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.async = true;
    script.src = '<?php echo $url ?>';   
    document.getElementsByClassName('ckoiframe')[0].appendChild(script);
</script>

<div class="ckoiframe"></div>

<div class="buttons">
    <div class="pull-right">
        <div class="payment-holder cko-expand"> 
            <form class="widget-container" >
                <script type="text/javascript">
                    setTimeout(function(){
                        Checkout.init({
                            debug: true,
                            publicKey: '<?php echo $publicKey ?>', 
                            appMode: 'embedded',
                            theme: '<?php echo $theme ?>',
                            themeOverride: '<?php echo $customCss ?>',
                            lightboxActivated: function(){
                                document.getElementById('cko-iframe-id').style.position = "relative";
                                $('.cko-md-overlay').remove();
                                document.getElementById('button-confirm').style.display='block';
                            },
                            cardFormValidationChanged: function (event) {
                                document.getElementById("button-confirm").disabled = !Checkout.isCardFormValid();
                            },
                            cardTokenised: function(event) {
                                if (document.getElementById('cko-card-token').value.length === 0) {
                                    document.getElementById('cko-card-token').value = event.data.cardToken;
                                    document.getElementById('payment-form').submit();
                                }
                            }
                        });

                    }, 1000);
                </script>
            </form>
        </div>
        
        <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="btn btn-primary" style="display: none;" disabled  />
    </div>
</div>

<script>
    var submitButton = document.getElementById("button-confirm");
    submitButton.addEventListener("click", function () {
        if (Checkout.isCardFormValid()) Checkout.submitCardForm();
    });
</script>

<form id="payment-form" method="POST" action='<?php echo $redirectUrl ?>'>
    <div class="content" id="payment">
        <input type="hidden" name="cko-card-token" id="cko-card-token" value="">
    </div>
</form>
