<?php echo $header; ?>
<?php if ($success) { ?>
<div class="success"><?php echo $success; ?></div>
<?php } ?>
<?php echo $column_left; ?><?php echo $column_right; ?>
<div id="content"><?php echo $content_top; ?>
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <h1><?php echo $heading_title; ?></h1>
<div class="content">
  <?php if(!empty($this->session->data['cardLists'])){ 
        foreach($this->session->data['cardLists'] as $key=>$value ){ ?>
            <label for="checkoutapipayment-saved-card-<?php echo $value['entity_id']; ?>">
            <input id="checkoutapipayment-saved-card-<?php echo $value['entity_id']; ?>" class="checkoutapipayment-saved-card" type="radio" name="cko-rad-button" value="<?php echo $value['entity_id']; ?>"/> xxxx-<?php echo $value['card_number'].' '. $value['card_type']; ?></label>   
            <br>
        <?php } ?>
  <?php } ?>
  <br><br>
  <div class="buttons">
    <div class="left">
        <input type="button" value="Delete Card" id="button-confirm" class="button" />
    <div>
  </div>

  <input type="hidden" id="entity_id" name="entity_id" value=0 />

</div>
  
<?php echo $content_bottom; ?></div>
<?php echo $footer; ?> 

<script type="text/javascript">
  jQuery('#button-confirm').click(function(event){
    document.getElementById('entity_id').value = jQuery('.checkoutapipayment-saved-card:checked').val();

    if(document.getElementById('entity_id').value != 'undefined'){
      $.ajax({
          url: 'index.php?route=payment/checkoutapipayment/deleteCard',
          type: 'post',
          data: $('.content :input'),
          dataType: 'json',
          beforeSend: function () {
              alert('Are you sure you want to delete this card?');
          },

          complete: function () {
             
          },
          success: function (json) {

              if (json['success']) {
                  location.reload();
              } else {
                  alert('An error has occured while deleting your card.');
              }
          }
      });
    }
  });
</script>