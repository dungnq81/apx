<?php
defined('BASEPATH') OR exit('No direct script access allowed');

asset_js([
    'addon::tinymce/tinymce.min.js',
    'addon::tinymce/jquery.tinymce.min.js',
    'addon::wysiwyg.js',
], FALSE, 'wysiwyg');
