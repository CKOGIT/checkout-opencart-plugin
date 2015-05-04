<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-checkoutapipayment" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
<div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="alert alert-info"><i class="fa fa-info-circle"></i> <?php echo $text_checkoutapipayment_join; ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
    </div>

<div class="panel-body">
    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-checkoutapipayment" class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
            <div class="col-sm-10">
                <select name="checkoutapipayment_status" id="input-status" class="form-control">
                    <?php if ($checkoutapipayment_status) { ?>
                    <option value="1" selected="selected"><?php echo $text_status_on; ?></option>
                    <option value="0"><?php echo $text_status_off; ?></option>
                    <?php } else { ?>
                    <option value="1"><?php echo $text_status_on; ?></option>
                    <option value="0" selected="selected"><?php echo $text_status_off; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="input-mode"><?php echo $entry_test_mode; ?></label>
            <div class="col-sm-10">
                <select name="checkoutapipayment_test_mode" id="input-mode" class="form-control">
                    <?php if ($checkoutapipayment_test_mode == 'test') { ?>
                    <option value="test" selected="selected"><?php echo $text_mode_test; ?></option>
                    <?php } else { ?>
                    <option value="test"><?php echo $text_mode_test; ?></option>
                    <?php } ?>
                    <?php if ($checkoutapipayment_test_mode == 'preprod') { ?>
                    <option value="preprod" selected="selected"><?php echo $text_mode_prod; ?></option>
                    <?php } else { ?>
                    <option value="preprod"><?php echo $text_mode_prod; ?></option>
                    <?php } ?>
                    <?php if ($checkoutapipayment_test_mode == 'live') { ?>
                    <option value="live" selected="selected"><?php echo $text_mode_live; ?></option>
                    <?php } else { ?>
                    <option value="live"><?php echo $text_mode_live; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-secret-key"><?php echo $entry_secret_key; ?></label>
            <div class="col-sm-10">
                <input type="text" name="checkoutapipayment_secret_key" value="<?php echo $checkoutapipayment_secret_key; ?>" placeholder="<?php echo $entry_secret_key; ?>" id="input-secret-key" class="form-control" />
                <?php if ($error_secret_key) { ?>
                <div class="text-danger"><?php echo $error_secret_key; ?></div>
                <?php } ?>
            </div>
        </div>
        <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-merchant-id"><?php echo $entry_public_key; ?></label>
            <div class="col-sm-10">
                <input type="text" name="checkoutapipayment_public_key" value="<?php echo $checkoutapipayment_public_key; ?>" placeholder="<?php echo $entry_public_key; ?>" id="input-public-key" class="form-control" />
                <?php if ($error_public_key) { ?>
                <div class="text-danger"><?php echo $error_public_key; ?></div>
                <?php } ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="input-mode"><?php echo $entry_localpayment_enable; ?></label>
                <div class="col-sm-10">
                    <select name="checkoutapipayment_localpayment_enable" id="input-mode" class="form-control">
                    <?php if ($checkoutapipayment_localpayment_enable == 'no') { ?>
                    <option value="no" selected="selected"><?php echo $text_localPayment_no; ?></option>
                    <?php } else { ?>
                    <option value="no"><?php echo $text_localPayment_no; ?></option>
                    <?php } ?>
                    <?php if ($checkoutapipayment_localpayment_enable == 'yes') { ?>
                    <option value="yes" selected="selected"><?php echo $text_localPayment_yes; ?></option>
                    <?php } else { ?>
                    <option value="yes"><?php echo $text_localPayment_yes; ?></option>
                    <?php } ?>
                    </select>
                </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="input-mode"><?php echo $entry_pci_enable; ?></label>
            <div class="col-sm-10">
                <select name="checkoutapipayment_pci_enable" id="input-pci-mode" class="form-control">
                    <?php if ($checkoutapipayment_pci_enable == 'no') { ?>
                    <option value="yes" selected="selected"><?php echo $text_pci_yes; ?></option>
                    <?php } else { ?>
                    <option value="yes"><?php echo $text_pci_yes; ?></option>
                    <?php } ?>
                    <?php if ($checkoutapipayment_pci_enable == 'no') { ?>
                    <option value="no" selected="selected"><?php echo $text_pci_no; ?></option>
                    <?php } else { ?>
                    <option value="no"><?php echo $text_pci_no; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="input-payment-action"><?php echo $entry_payment_action; ?></label>
            <div class="col-sm-10">
                <select name="checkoutapipayment_payment_action" id="input-mode" class="form-control">
                    <?php if ($checkoutapipayment_payment_action == 'authorization') { ?>
                    <option value="authorization" selected="selected"><?php echo $text_auth_only; ?></option>
                    <?php } else { ?>
                    <option value="authorization"><?php echo $text_auth_only; ?></option>
                    <?php } ?>
                    <?php if ($checkoutapipayment_payment_action == 'capture') { ?>
                    <option value="capture" selected="selected"><?php echo $text_auth_capture; ?></option>
                    <?php } else { ?>
                    <option value="capture"><?php echo $text_auth_capture; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="form-group ">
            <label class="col-sm-2 control-label" for="input-autocapture-delay"><?php echo $entry_autocapture_delay; ?></label>
            <div class="col-sm-10">
                <input type="text" name="checkoutapipayment_autocapture_delay" value="0" id="input_autocapture_delay" class="form-control" />
            </div>
        </div>
        <div class="form-group ">
            <label class="col-sm-2 control-label" for="input-gateway-timeout"><?php echo $entry_gateway_timeout; ?></label>
            <div class="col-sm-10">
                <input type="text" name="checkoutapipayment_gateway_timeout" value="0" id="input_gateway_timeout" class="form-control" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="input-order-status"><?php echo $entry_successful_order_status; ?></label>
            <div class="col-sm-10">
                <select name="checkoutapipayment_checkout_successful_order" id="input-order-status" class="form-control">
                    <?php foreach($order_statuses as $order_status) { ?>
                    <?php if ($order_status['order_status_id'] == $checkoutapipayment_checkout_successful_order) { ?>
                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                    <?php } else { ?>
                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                    <?php } ?>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="input-order-status"><?php echo $entry_failed_order_status; ?></label>
            <div class="col-sm-10">
                <select name="checkoutapipayment_checkout_failed_order" id="input-order-status" class="form-control">
                    <?php foreach($order_statuses as $order_status) { ?>
                    <?php if ($order_status['order_status_id'] == $checkoutapipayment_checkout_failed_order) { ?>
                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                    <?php } else { ?>
                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                    <?php } ?>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="input-sort-order"><?php echo $entry_sort_order; ?></label>
            <div class="col-sm-10">
                <input type="text" name="checkoutapipayment_sort_order" value="<?php echo $checkoutapipayment_sort_order; ?>" id="input-sort-order" class="form-control" />
            </div>
        </div>
    </form>
</div>
</div>
</div>
</div>
<script type="text/javascript"><!--
    $('#button-ip-add').on('click', function() {
        var ip = $.trim($('#input-ip').val());

        if (ip) {
            $('#ip-allowed').append('<div><i class="fa fa-minus-circle"></i> ' + ip + '<input type="hidden" name="amazon_checkout_ip_allowed[]" value="' + ip + '" /></div>');
        }

        $('#input-ip').val('');
    });

    $('#ip-allowed').delegate('.fa-minus-circle', 'click', function() {
        $(this).parent().remove();
    });

    $('input[name=\'amazon_checkout_cron_job_token\']').on('keyup', function() {
        $('#input-cron-job-url').val('<?php echo $store; ?>index.php?route=payment/amazon_checkout/cron&token=' + $(this).val());
    });
    //--></script></div>
<?php echo $footer; ?>