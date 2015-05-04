$('#button-confirm').bind('click', function() {
    $.ajax({
        url: 'index.php?route=payment/checkoutapipayment/send',
        type: 'post',
        data: $('#payment :input'),
        dataType: 'json',
        beforeSend: function() {
            $('#button-confirm').attr('disabled', true);
            $('#payment').before('<div class="attention"><img src="catalog/view/theme/default/image/loading.gif" alt="" /></div>');
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
});