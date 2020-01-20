<!DOCTYPE html>
<html lang="<?php echo lang_code();?>" class="no-js">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2.0" />
    <title><?php echo $template['title'] ?> | <?php echo $this->setting->site_name; ?></title>
    <meta name="robots" content="noindex, nofollow" />
    <link rel="shortcut icon" href="<?php echo site_url()?>favicon.ico" />
    <?php file_partial('metadata'); ?>
</head>
<body data-ip="<?php echo ip_address()?>">
<header>
    <?php file_partial('header'); ?>
</header>
<main role="main">
    <div class="grid-container main-container">
        <?php
        file_partial('notices');
        echo $template['body'];

        ?>
    </div>
</main>
<footer>
    <?php file_partial('footer'); ?>
</footer>
<?php asset_js('app.js', FALSE, 'app'); ?>
</body>
</html>
