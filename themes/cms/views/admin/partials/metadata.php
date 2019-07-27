<?php
defined('BASEPATH') OR exit('No direct script access allowed');

?>
<meta http-equiv="x-dns-prefetch-control" content="on"/>
<link rel="dns-prefetch" href="https://ajax.googleapis.com"/>
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com"/>
<link rel="dns-prefetch" href="https://fonts.googleapis.com" />
<link rel="dns-prefetch" href="https://use.fontawesome.com" />
<!--<link rel="preconnect" href="//www.google-analytics.com" crossorigin />
<link rel="preconnect" href="//www.googletagmanager.com" crossorigin />-->
<script>
    var apx = {'lang': {}};
    var BASE_URL = "<?php echo site_url('/');?>";
    var BASE_URI = "<?php echo site_uri('/');?>";
    var ADMIN_LANG = "<?php echo ADMIN_LANG;?>";
    var LANG = "<?php echo LANG;?>";
    apx.admin_theme_url = "<?php echo site_url($this->admin_theme->web_path . '/'); ?>";
    apx.csrf_cookie_name = "<?php echo config_item('cookie_prefix') . config_item('csrf_cookie_name'); ?>";
</script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto+Condensed%3A400%2C400i%2C700%2C700i%7CRoboto+Slab%3A400%2C700%7CRoboto%3A400%2C400i%2C500%2C500i%2C700%2C700i&#038;display=swap" />
<link rel="stylesheet" href="https://use.fontawesome.com/5016a31c8c.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.5.3/css/foundation.min.css" />
<?php asset_css('app.css'); ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/what-input/5.2.3/what-input.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.5.3/js/foundation.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-scrollTo/2.1.2/jquery.scrollTo.min.js"></script>
<?php
asset_js(['addon::current-device.min.js', 'addon::jquery/jquery.query.min.js', 'global.js']);
echo $template['metadata'];
