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
                    <?php if ($checkoutapipayment_test_mode == 'sandbox') { ?>
                    <option value="sandbox" selected="selected"><?php echo $text_mode_sandbox; ?></option>
                    <?php } else { ?>
                    <option value="sandbox"><?php echo $text_mode_sandbox; ?></option>
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
            <label class="col-sm-2 control-label" for="input-integration"><?php echo $entry_integration_type; ?></label>
            <div class="col-sm-10">
                <select name="checkoutapipayment_integration_type" id="input-integration" class="form-control">
                    <?php if ($checkoutapipayment_integration_type == 'pci') { ?>
                    <option value="pci" selected="selected"><?php echo $text_integration_pci; ?></option>
                    <?php } else { ?>
                    <option value="pci"><?php echo $text_integration_pci; ?></option>
                    <?php } ?>
                    <?php if ($checkoutapipayment_integration_type == 'hosted') { ?>
                    <option value="hosted" selected="selected"><?php echo $text_integration_hosted; ?></option>
                    <?php } else { ?>
                    <option value="hosted"><?php echo $text_integration_hosted; ?></option>
                    <?php } ?>
                    <?php if ($checkoutapipayment_integration_type == 'embedded') { ?>
                    <option value="embedded" selected="selected"><?php echo $text_integration_embedded; ?></option>
                    <?php } else { ?>
                    <option value="embedded"><?php echo $text_integration_embedded; ?></option>
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

        <div class="form-group">
            <label class="col-sm-2 control-label" for="input-mode"><?php echo $entry_3D_secure; ?></label>
            <div class="col-sm-10">
                <select name="checkoutapipayment_3D_secure" id="input-3d-mode" class="form-control">
                    <?php if ($checkoutapipayment_3D_secure == 'no') { ?>
                    <option value="no" selected="selected"><?php echo $text_3D_no; ?></option>
                    <?php } else { ?>
                    <option value="no"><?php echo $text_3D_no; ?></option>
                    <?php } ?>
                    <?php if ($checkoutapipayment_3D_secure == 'yes') { ?>
                    <option value="yes" selected="selected"><?php echo $text_3D_yes; ?></option>
                    <?php } else { ?>
                    <option value="yes"><?php echo $text_3D_yes; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <fieldset>
            <legend><?php echo $text_button_settings; ?></legend>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-integration"><?php echo $entry_payment_mode; ?></label>
                <div class="col-sm-10">
                    <select name="checkoutapipayment_payment_mode" id="input-integration" class="form-control">
                        <?php if ($checkoutapipayment_payment_mode == 'cards') { ?>
                        <option value="cards" selected="selected"><?php echo $text_paymentMode_cards; ?></option>
                        <?php } else { ?>
                        <option value="cards"><?php echo $text_paymentMode_cards; ?></option>
                        <?php } ?>
                        <?php if ($checkoutapipayment_payment_mode == 'localpayments') { ?>
                        <option value="localpayments" selected="selected"><?php echo $text_paymentMode_lp; ?></option>
                        <?php } else { ?>
                        <option value="localpayments"><?php echo $text_paymentMode_lp; ?></option>
                        <?php } ?>
                        <?php if ($checkoutapipayment_payment_mode == 'mixed') { ?>
                        <option value="mixed" selected="selected"><?php echo $text_paymentMode_mix; ?></option>
                        <?php } else { ?>
                        <option value="mixed"><?php echo $text_paymentMode_mix; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-title"><?php echo $entry_title; ?></label>
                <div class="col-sm-10">
                    <input type="text" name="checkoutapipayment_title" value="<?php echo $checkoutapipayment_title; ?>" placeholder="<?php echo $entry_title; ?>" id="input-title" class="form-control" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-logo-url"><?php echo $entry_logo_url; ?></label>
                <div class="col-sm-10">
                    <input type="text" name="checkoutapipayment_logo_url" value="<?php echo $checkoutapipayment_logo_url; ?>" placeholder="<?php echo $entry_logo_url; ?>" id="input-logo-url" class="form-control" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-theme-color"><?php echo $entry_theme_color; ?></label>
                <div class="col-sm-10">
                    <input type="text" name="checkoutapipayment_theme_color" value="<?php echo $checkoutapipayment_theme_color; ?>" placeholder="<?php echo $entry_theme_color; ?>" id="input-theme-color" class="form-control" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-button-label"><?php echo $entry_button_label; ?></label>
                <div class="col-sm-10">
                    <input type="text" name="checkoutapipayment_button_label" value="<?php echo $checkoutapipayment_button_label; ?>" placeholder="<?php echo $entry_button_label; ?>" id="input-button-label" class="form-control" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-currency-format"><?php echo $entry_currency_format; ?></label>
                <div class="col-sm-10">
                    <select name="checkoutapipayment_currency_format" id="input-currency-format" class="form-control">
                        <?php if ($checkoutapipayment_currency_format == 'code') { ?>
                        <option value="true" selected="selected"><?php echo $text_code; ?></option>
                        <?php } else { ?>
                        <option value="true"><?php echo $text_code; ?></option>
                        <?php } ?>
                        <?php if ($checkoutapipayment_currency_format == 'symbol') { ?>
                        <option value="false" selected="selected"><?php echo $text_symbol; ?></option>
                        <?php } else { ?>
                        <option value="false"><?php echo $text_symbol; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
         </fieldset>

          <fieldset>
            <legend><?php echo $text_embedded_settings; ?></legend>
             <div class="form-group">
                <label class="col-sm-2 control-label" for="input-theme-setting"><?php echo $entry_embedded_theme; ?></label>
                <div class="col-sm-10">
                    <select name="checkoutapipayment_embedded_theme" id="input-integration" class="form-control">
                        <?php if ($checkoutapipayment_embedded_theme == 'standard') { ?>
                        <option value="standard" selected="selected"><?php echo $text_theme_standard; ?></option>
                        <?php } else { ?>
                        <option value="standard"><?php echo $text_theme_standard; ?></option>
                        <?php } ?>
                        <?php if ($checkoutapipayment_embedded_theme == 'simple') { ?>
                        <option value="simple" selected="selected"><?php echo $text_theme_simple; ?></option>
                        <?php } else { ?>
                        <option value="simple"><?php echo $text_theme_simple; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
            <label class="col-sm-2 control-label" for="input-custom-css"><?php echo $entry_custom_css; ?></label>
            <div class="col-sm-10">
                <input type="text" name="checkoutapipayment_custom_css" value="<?php echo $checkoutapipayment_custom_css; ?>" placeholder="<?php echo $entry_custom_css; ?>" id="input-custom-css" class="form-control" />
            </div>
        </div>
        </fieldset>
    </form>
</div>
</div>
</div>
</div>

<?php echo $footer; ?>