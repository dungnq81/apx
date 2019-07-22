<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (validation_errors()) :
    $msg = validation_errors();
endif;
if(!empty($msg)) :

?>
<div data-abide-error class="alert callout error-alert" data-closable>
    <div><?php echo $msg;?></div>
    <button class="close-button" type="button" data-close>
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif;?>
