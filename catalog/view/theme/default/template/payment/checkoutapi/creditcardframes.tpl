<link rel="stylesheet" type="text/css" href="catalog/view/theme/default/stylesheet/checkout_frames.css" />

<style type="text/css">
    .center {
        display: block;
        margin-left: auto;
        margin-right: auto;
        width: 50%;
</style>

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
    <?php } ?>

      <script type="text/javascript"> 
        checkoutHideNewNoPciCard();
        function checkoutHideNewNoPciCard() {
            jQuery('.checkoutapipayment-new-card').attr("checked",false);
            jQuery('.apmSelected').removeClass('apmLab');
            jQuery('.frames-container').hide();
            jQuery('#save-card').hide();
        }

        function checkoutShowNewNoPciCard() {
        } 

        jQuery('.checkoutapipayment-saved-card').on("click", function() {
            jQuery('.frames-container').hide();
            // jQuery('.apmSelected').removeClass('apmLab');
            jQuery('#save-card').hide();
        });

        jQuery('.checkoutapipayment-new-card').on("click", function() {
            jQuery('.frames-container').show();
            jQuery('.apmSelected').removeClass('apmLab');
            jQuery('#save-card').show();
        });

        jQuery('.alt-payment').on("change", function() { 
            jQuery('.apmSelected').removeClass('apmLab');
            jQuery(this).closest('.apmSelected').addClass('apmLab');
            jQuery('.frames-container').hide();
            jQuery('#save-card').hide();
        });

    </script>



 <?php } else if($alternativePayment == 'yes'){?>
    <?php if($save_card == 'no' || $this->session->data['customer_login'] == 'no' ){ ?>
            <label for="checkoutapipayment-new-card">
            <input id="checkoutapipayment-new-card" class= "checkoutapipayment-new-card" type="radio" name="cko-rad-button"  value="new_card"/> Use New card</label>
            <br>
    <?php } ?>
<?php } ?>

    <!-- form will be added here -->
    <img id="cko-loading" src="catalog/view/theme/default/image/payment/checkoutapi/load.gif" class="center" style="width: 50px;"/>

    <script type="text/javascript">
        var payNowButton = document.getElementById('button-confirm');
        setTimeout(function(){ 
            window.framesCurrentConfig = {
                publicKey: '<?php echo $publicKey;?>',
                containerSelector: '.frames-container',
                cardTokenised: function(event) {
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
                                beforeSend: function () { console.log('before');
                                    $('#button-confirm').attr('disabled', true);
                                },

                                complete: function () {
                                    $('#button-confirm').attr('disabled', false);
                                    $('.attention').remove();
                                },
                                success: function (json) {

                                    if (json['error']) {
                                        alert(json['error']);
                                        Frames.init(window.checkout);
                                        $('.attention').remove();
                                    }

                                    if (json['success']) {
                                        location = json['success'];
                                    }
                                }
                            });
                        }
                },
                cardSubmitted: function (){
                    $('.buttons').before('<div class="attention">' +
                                    '<img src="catalog/view/theme/default/image/loading.gif" alt="" />' +
                                    '<?php echo $textWait ?>'
                                    +'</div>');
                },
                frameActivated: function() {
                    jQuery('#cko-loading').hide();
                    //jQuery('#save-card').show();
                }
            };

            window.frameIsReady = window.frameIsReady || false;

            if (!window.frameIsReady) {
                window.CKOConfig = { 
                    namespace: 'Frames',
                    ready: function() {
                        if (typeof Frames == 'undefined') { 
                            return false;
                        }
                        delete window.CKOConfig;

                        Frames.init(window.framesCurrentConfig);

                        window.frameIsReady = true;
                    }
                };

                var script = document.createElement('script');
                script.type = "text/javascript";
                script.src = '<?php echo $url;?>';
                script.async = true;
                document.getElementById('frames-container').appendChild(script);
            } else {
                Frames.init(window.framesCurrentConfig);
            }

        },3000);
    </script>
<br>

<?php if($save_card == 'yes' && $this->session->data['customer_login'] == 'yes'){ ?>
    <label id="save-card" for="save-card" style="display: none;"> Save card for future payments
        <input type="checkbox" name="save-card" id="save-card" style="position: absolute;margin-top: 0px;" />
    </label>
    <br><br>
<?php } ?>


<div class="frames-container" id="frames-container" style="width: 49%;">
</div>

<?php if ($alternativePayment == 'yes' ){ ?>
<div class="altPayment">
    <br>
    <label for="alternative-payment"><h3>Alternative Payment</h3></label>
    <br>
    <div style="display: flex;">
        <?php foreach($this->session->data['localPayment'] as $index ){
                if($index['name']) {
                   $lpName = strtolower($index['name']);
                    $imgUrl = "https://cdn.checkout.com/sandbox/img/lp_logos/".$lpName.".png";
                    $apmId = "checkoutapiframes-apm-".$lpName;
                    ?>
                
                    <div class="apmSelected" style="margin-right: 10px;">
                    <label class="apmLabel">
                        <img id="imgTe" src="<?php echo $imgUrl;?>" style="width: 50px;"/>
                        <input class="alt-payment" type="radio" id="alt-payment" value="<?php echo $lpName; ?>" name="cko-rad-button"/>
                    </label>
                    </div>
                 
                <?php } 
        }?>
    </div>
    <br><br>

    <div id="ckoModal" class="ckoModal" >
        <!-- Modal content -->
        <div class="cko-modal-content" style="width: 30%;">
            <span class="close" onclick="jQuery('.ckoModal').hide();">x</span>
            <p><h1><span  id="lpName"></span ></h1></p>
            <br>
            <div id="idealInfo" style="display: none;">
                <label for="issuerId" >Issuer ID
                <select id="issuer" >
                    <?php if (isset($this->session->data['idealPaymentInfo'])) {
                        foreach ($this->session->data['idealPaymentInfo'] as $key=>$item){ ?>
                            <option value="<?php echo $item->value;?>"> <?php echo $item->key; ?> </option>
                        <?php }
                    } ?>
                </select>
            </label>
            </div>
            <div id="boletoInfo">
                <div class="boleto-row"><label for="boletoDate" >Date of birth</label>
                <input type="date" id="boletoDate" name="boletoDate" /></div>
                <div class="boleto-row"></div>
                <div class="boleto-row"><label for="cpf" >CPF</label>
                <input type="text" id="cpf" name="cpf" /></div>
                <div class="boleto-row"></div>
                <div class="boleto-row"><label for="custName" ">Customer Name</label>
                <input type="text" id="custName" name="custName" required /></div>
            </div>
            <div id="qiwiInfo" style="display: none;">
                <label for="walletId">Wallet Id
                    <input type="text" id="walletId" name="walletId" placeholder="+44 phone number" />
                </label>
            </div>
            <button type="button" id="mybtn" style="margin-top: 50px;">Continue</button>
        </div>
    </div>

     <script type="application/javascript">
        var modal = document.getElementById('ckoModal');

        //When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
           if (event.target == modal) {
               modal.style.display = "none";
           }
        }
    </script>

</div>

<script type="text/javascript"> 
    checkoutHideNewNoPciCard();
    function checkoutHideNewNoPciCard() {
        jQuery('.checkoutapipayment-new-card').attr("checked",false);
        jQuery('.apmSelected').removeClass('apmLab');
        jQuery('.frames-container').hide();
    }

    function checkoutShowNewNoPciCard() {
    } 

    jQuery('.checkoutapipayment-saved-card').on("click", function() {
    });

    jQuery('.checkoutapipayment-new-card').on("click", function() {
        jQuery('.frames-container').show();
        jQuery('.apmSelected').removeClass('apmLab');
    });

    jQuery('.alt-payment').on("change", function() { 
        jQuery('.apmSelected').removeClass('apmLab');
        jQuery(this).closest('.apmSelected').addClass('apmLab');
        jQuery('.frames-container').hide();
    });
</script>

<?php } ?>

<br><br>
<!-- confirm order button -->
<div class="buttons">
    <div class="right">
        <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="button" />
    </div>
</div>

<input type="hidden" id="cko-card-token" name="cko-card-token" value=""/>
<input type="hidden" id="cko-lp-lpName" name="cko-lp-lpName" value=""/>
<input type="hidden" id="cko-lp-issuerId" name="cko-lp-issuerId" value=""/>
<input type="hidden" id="save-card-checkbox" name="save-card-checkbox" value=""/>
<input type="hidden" id="entity_id" name="entity_id" value="" />
<input type="hidden" id="cko-payment" name="cko-payment" value="">


<!-- Confirm order button click -->
<script type="text/javascript">
jQuery('#button-confirm').click(function(event){

    if(jQuery('.checkoutapipayment-new-card').is(':checked')){
        document.getElementById('cko-payment').value = 'new_card';
        if (Frames.isCardValid()) Frames.submitCard();

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


    } else if(jQuery('.alt-payment').length > 0 && jQuery('.alt-payment').is(':checked')){
        document.getElementById('cko-payment').value = 'alternative_payment';
        if(jQuery('.alt-payment:checked').val() == 'ideal' || jQuery('.alt-payment:checked').val() == 'qiwi' ||
            jQuery('.alt-payment:checked').val() == 'boleto'){
            // Open modal
            var modal = document.getElementById('ckoModal');
            modal.style.display = "block";

            var selectedLpName = jQuery('.alt-payment:checked').val();
            jQuery("#lpName").text("Pay with "+selectedLpName);

            if(selectedLpName == 'ideal'){
                jQuery('#idealInfo').show();
                jQuery('#boletoInfo').hide();
                jQuery('#qiwiInfo').hide();
            } else if(selectedLpName == 'boleto'){
                jQuery('#idealInfo').hide();
                jQuery('#boletoInfo').show();
                jQuery('#qiwiInfo').hide();
            } else if(selectedLpName == 'qiwi'){
                jQuery('#qiwiInfo').show();
                jQuery('#boletoInfo').hide();
                jQuery('#idealInfo').hide();
            }
        } else {
            var selectedLpName = jQuery('.alt-payment:checked').val();
            document.getElementById('cko-lp-lpName').value = selectedLpName;

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
        }

        jQuery('#mybtn').on('click', function(e) {
            if(selectedLpName == 'ideal'){
                var e = document.getElementById("issuer");
                var value = e.options[e.selectedIndex].value;
                var text = e.options[e.selectedIndex].text;

                document.getElementById('cko-lp-issuerId').value = value;
                console.log(document.getElementById('cko-lp-issuerId').value);

            } else if(selectedLpName == 'boleto'){
                if(document.getElementById('boletoDate').value == ""){
                    alert('Please enter correct date');
                    return false;
                }

                if(document.getElementById('cpf').value == ""){
                    alert('Please enter your CPF');
                    return false;
                }

                if(document.getElementById('custName').value == ""){
                    alert('Please enter your customer name');
                    return false;
                }

            } else if(selectedLpName == 'qiwi'){
                if(document.getElementById('walletId').value == ""){
                    alert('Please enter your Wallet Id');
                    return false;
                }
            }

            modal.style.display = "none";

            document.getElementById('cko-lp-lpName').value = selectedLpName;

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

        });

    } else {
        document.getElementById('cko-payment').value = 'new_card';
        if (Frames.isCardValid()) Frames.submitCard();
    }
});
</script>
