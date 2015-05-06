<div class="content" id="payment">
   <table class ="form">
       <tr>
           <td><?php echo $entry_cc_owner;?></td>
           <td><input type="text" name="cc_owner" id="cc_owner" value=""/>
               <span id="NameError" class="error"></span>
           </td>
       </tr>
       <tr>
           <td><?php echo $entry_cc_number; ?></td>
           <td><input type="text" name="cc_number" id="cc_number" value="" />
               <span id="CCError" class="error" style="display: none;"></span>
               <div class="cards-left">
                   <img id="img_Visa" src="catalog/view/theme/default/image/payment/checkoutapi/cc-visa.png" alt="Visa"  class="cc-icon disable">
                   <img id="img_mastercard" src="catalog/view/theme/default/image/payment/checkoutapi/cc-mastercard.png" alt="MasterCard" class="cc-icon disable">
                   <img id="img_discover" src="catalog/view/theme/default/image/payment/checkoutapi/cc-discover.png" alt="Discover" class="cc-icon disable">
                   <img id="img_amex" src="catalog/view/theme/default/image/payment/checkoutapi/cc-amex.png" alt="American Expres" class="cc-icon disable">
                   <img id="img_jcb" src="catalog/view/theme/default/image/payment/checkoutapi/cc-jcb.png" alt="JCB" class="cc-icon disable">
                   <img id="img_diners" src="catalog/view/theme/default/image/payment/checkoutapi/cc-diners.png" alt="Diners Club" class="cc-icon disable">
               </div>
           </td>
       </tr>
       <tr>
           <td><?php echo $entry_cc_expire_date; ?></td>
           <td><select name="cc_expire_date_month" id="cc_expire_date_month">
                   <?php foreach ($months as $month) { ?>
                   <option value="<?php echo $month['value']; ?>"><?php echo $month['text']; ?></option>
                   <?php } ?>
               </select>

               <select name="cc_expire_date_year" id="cc_expire_date_year">
                   <?php foreach ($year_expire as $year) { ?>
                   <option value="<?php echo $year['value']; ?>"><?php echo $year['text']; ?></option>
                   <?php } ?>
               </select>
           </td>
       </tr>
       <tr>
           <td><?php echo $entry_cc_cvv2; ?></td>
           <td><input type="text" name="cc_cvv2" id="cc_cvv2" value="" size="3" />
               <span id="CVVError" class="error" style="display: none;"></span>
           </td>
       </tr>
   </table>
</div>
<div class="buttons">
    <div class="right">
        <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="button" />
    </div>
</div>

<script type="text/javascript">
    function styleCreditCard(e){switch(e){case"mastercard":document.getElementById("img_mastercard").className="cc-icon";break;case"visa":document.getElementById("img_Visa").className="cc-icon";break;case"amex":document.getElementById("img_amex").className="cc-icon";break;case"DinersClub":document.getElementById("img_diners").className="cc-icon";break;case"Discover":document.getElementById("img_discover").className="cc-icon";break;case"JCB":document.getElementById("img_jcb").className="cc-icon";break;default:document.getElementById("img_Visa").className="cc-icon disable";document.getElementById("img_mastercard").className="cc-icon disable";document.getElementById("img_amex").className="cc-icon disable";document.getElementById("img_diners").className="cc-icon disable";document.getElementById("img_discover").className="cc-icon disable";document.getElementById("img_jcb").className="cc-icon disable"}}function checkCreditCard(e){var t=new Array;var n=guessCreditCard(e);t[0]={name:"visa",length:"13,16",prefixes:"4",checkdigit:true};t[1]={name:"mastercard",length:"16",prefixes:"51,52,53,54,55",checkdigit:true};t[2]={name:"DinersClub",length:"14,16",prefixes:"36,38,54,55",checkdigit:true};t[3]={name:"CarteBlanche",length:"14",prefixes:"300,301,302,303,304,305",checkdigit:true};t[4]={name:"amex",length:"15",prefixes:"34,37",checkdigit:true};t[5]={name:"Discover",length:"16",prefixes:"6011,622,64,65",checkdigit:true};t[6]={name:"JCB",length:"16",prefixes:"35",checkdigit:true};t[7]={name:"enRoute",length:"15",prefixes:"2014,2149",checkdigit:true};t[8]={name:"Solo",length:"16,18,19",prefixes:"6334,6767",checkdigit:true};t[9]={name:"Switch",length:"16,18,19",prefixes:"4903,4905,4911,4936,564182,633110,6333,6759",checkdigit:true};t[10]={name:"Maestro",length:"12,13,14,15,16,18,19",prefixes:"5018,5020,5038,6304,6759,6761,6762,6763",checkdigit:true};t[11]={name:"VisaElectron",length:"16",prefixes:"4026,417500,4508,4844,4913,4917",checkdigit:true};t[12]={name:"LaserCard",length:"16,17,18,19",prefixes:"6304,6706,6771,6709",checkdigit:true};if(e.length==0){ccErrorNo=1;return false}var r=-1;for(var i=0;i<t.length;i++){if(n.toLowerCase()==t[i].name.toLowerCase()){r=i;break}}if(r==-1){ccErrorNo=0;return false}e=e.replace(/\s/g,"");var s=e;var o=/^[0-9]{13,19}$/;if(!o.exec(s)){ccErrorNo=2;return false}if(t[r].checkdigit){var u=0;var a="";var f=1;var l;for(i=s.length-1;i>=0;i--){l=Number(s.charAt(i))*f;if(l>9){u=u+1;l=l-10}u=u+l;if(f==1){f=2}else{f=1}}if(u%10!=0){ccErrorNo=3;return false}}if(s=="5490997771092064"){ccErrorNo=5;return false}var c=false;var h=false;var p;var d=new Array;var v=new Array;d=t[r].prefixes.split(",");for(i=0;i<d.length;i++){var m=new RegExp("^"+d[i]);if(m.test(s))h=true}if(!h){ccErrorNo=3;return false}v=t[r].length.split(",");for(f=0;f<v.length;f++){if(s.length==v[f])c=true}if(!c){ccErrorNo=4;return false}styleCreditCard(n);return true}function guessCreditCard(e){var t="unknown";if(/^5[1-5]/.test(e)){t="mastercard"}else if(/^4/.test(e)){t="visa"}else if(/^3[47]/.test(e)){t="amex"}else if(/(^36|^38|^54|^55)/.test(e)){t="DinersClub"}else if(/^30[0-5]/.test(e)){t="CarteBlanche"}else if(/(^6011|^622|^64|^65)/.test(e)){t="Discover"}else if(/^35/.test(e)){t="JCB"}return t}var ccErrorNo=0;var cName="";var ccErrors=new Array;ccErrors[0]="Unknown card type";ccErrors[1]="Credit card number is Required";ccErrors[2]="Credit card number is in invalid format";ccErrors[3]="Credit card number is invalid";ccErrors[4]="Credit card number has an inappropriate number of digits";ccErrors[5]="Warning! This credit card number is associated with a scam attempt";

    $('#button-confirm').bind('click', function() {
        $("#NameError").hide();
        $("#CCError").hide();
        $("#CVVError").hide();

        var isValid=true;
        if(($('#cc_owner').val().trim().length == 0))
        {
            isValid = false;
            $("#NameError").text("Name is Required").show();
        }

        if(($('#cc_number').val().trim().length == 0))
        {
            isValid = false;
            $("#CCError").text("Card Number is Required").show();
        }

        if(($('#cc_cvv2').val().trim().length == 0))
        {
            isValid = false;
            $("#CVVError").text("CVV is Required").show();
        }
        else if(!/^[0-9]{3,4}$/.test($('#cc_cvv2').val()))
        {
            isValid = false;
            $("#CVVError").text("Invalid CVV code entered").show();
        }

        if (!checkCreditCard($('#cc_number').val(), cName))
        {
            isValid = false;
            $("#CCError").text(ccErrors[ccErrorNo]).show()
        }

        if(isValid) {


                                $.ajax({
                                    url: 'index.php?route=payment/checkoutapipayment/send',
                                    type: 'post',
                                    data: $('#payment :input'),
                                    dataType: 'json',
                                        beforeSend: function() {
                    $('#button-confirm').attr('disabled', true);
                    $('#payment').before('<div class="attention"><img src="catalog/view/theme/default/image/loading.gif" alt="" /> <?php echo $text_wait; ?></div>');
                },
                complete: function() {
                    $('#button-confirm').attr('disabled', false);
                    $('.attention').remove();
                },
                success: function(json) {

                    if (json['error']) {
                        alert(json['error']);
                    }

                    if (json['success']) {
                        location = json['success'];
                    }
                }
            });
        }
    });
</script>

<script type="text/javascript">

    $(document).ready(function(){

        $("#cc_owner").bind("keyup blur", function(){

            if(($('#cc_owner').val().trim().length == 0))
            {
                $("#NameError").text("Name is Required").show()
            }
            else
                $("#NameError").hide()
        });


        $("#cc_cvv2").bind("blur", function(){

            if(($('#cc_cvv2').val().trim().length == 0))
            {
                $("#CVVError").text("CVV is Required").show()
            }
            else if(!/^[0-9]{3,4}$/.test($('#cc_cvv2').val()))
            {
                $("#CVVError").text("Invalid CVV code entered").show()
            }
            else
                $("#CVVError").hide()
        });


        $("#cc_number").keyup(function(){
            styleCreditCard('');
            $("#CCError").hide()
        });

        $("#cc_number").blur(function(){
            if (!checkCreditCard($('#cc_number').val(), cName))
                $("#CCError").text(ccErrors[ccErrorNo]).show()
        });

        $('#cvv_help').click(function(){
            $("#cvv_help_img").show();
            $('#cvv_help').unbind();
        });
    });

</script>

<style type="text/css">
    img.cc-icon {
        border: 1px solid #CCCCCC;
        float: left;
        margin-right: 5px;
    }

    img.disable {
        opacity: 0.3;
    }

    #cvc-info {
        background-color: #BDE5F8;
        border: 1px solid #00529B;
        border-radius: 3px 3px 3px 3px;
        color: #00529B !important;
        display: none;
        font-style: normal;
        padding: 8px;
        text-decoration: none;
        width: 300px;
        position:absolute;
    }

    .stripe-info{

    }

    .stripe-info:hover + #cvc-info {
        display: block;
    }

</style>