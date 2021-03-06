<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="<?php echo lang_code();?>" class="no-js">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2.0" />
    <link rel="dns-prefetch" href="https://ajax.googleapis.com"/>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com"/>
    <link rel="dns-prefetch" href="https://fonts.googleapis.com" />
    <title>Đăng nhập | <?php echo $this->setting->site_name; ?></title>
    <meta name="robots" content="noindex, nofollow"/>
    <link rel="shortcut icon" href="<?php echo site_url()?>favicon.ico"/>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Noto+Sans:400,400i,700,700i|Roboto+Mono:400,400i,500,500i,700,700i|Roboto:400,400i,500,500i,700,700i|Roboto+Condensed:400,400i,700,700i&display=swap&subset=vietnamese" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.5.3/css/foundation.min.css" />
    <?php asset_css(['addon::fontawesome.min.css', 'login.css']); ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <?php asset_js(['addon::what-input.min.js', 'addon::current-device.min.js', 'addon::jquery/jquery.query.min.js', 'addon::jquery/jquery.cookie.min.js']);?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.5.3/js/foundation.min.js"></script>
    <script>
        var apx = {'lang': {}};
        var DEFAULT_LANG = "<?php echo ADMIN_LANG;?>";
        apx.csrf_cookie_name = "<?php echo config_item('cookie_prefix') . config_item('csrf_cookie_name'); ?>";
    </script>
</head>
<body>
<div class="grid-container login-wrapper">
    <div class="grid-x align-center">
        <div class="cell small-10 medium-8 large-4">
            <h1>APX CMS</h1>
            <div id="login">
                <?php echo form_open('admin/login?_action=login', ['id' => "login_form", 'data-abide novalidate class' => "login-form"]) ?>
                <div>
                    <label for="identity">Tên đăng nhập</label>
                    <input id="identity" type="text" name="identity" required pattern="^(.*\S+.*)$">
                </div>
                <div>
                    <label for="password">Mật khẩu</label>
                    <input autocomplete="new-password" id="password" type="password" name="password" required pattern="^(.*\S+.*)$">
                </div>
                <div>
                    <input type="checkbox" name="remember" id="remember"/>
                    <label for="remember">Nhớ đăng nhập</label>
                </div>
                <div class="group-btn">
                    <?php echo form_hidden('_action', 'login'); ?>
                    <button type="submit" class="g-recaptcha button" data-sitekey="<?php echo $this->setting->recaptcha_sitekey; ?>" data-callback="loginSubmit">Đăng nhập</button>
                </div>
                <?php file_partial('notices');?>
                <?php echo form_close();?>
            </div>
        </div>
        <div class="copyright text-center cell">Copyright &copy 2015 - <?php echo date('Y'); ?> APX Team. &nbsp; Rendered in {elapsed_time} sec. using {memory_usage}.</div>
    </div>
</div>
<script src="https://www.google.com/recaptcha/api.js?hl=<?php echo lang_code(); ?>" async defer></script>
<?php asset_js('login.js', FALSE, 'loginjs') ?>
<script>function loginSubmit(e) {document.getElementById("login_form").submit();}</script>
</body>
</html>
