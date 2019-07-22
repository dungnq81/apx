<?php
defined('BASEPATH') OR exit('No direct script access allowed');

asset_css([
    'addon::codemirror/codemirror.min.css',
], FALSE, 'codemirror');

asset_js([
    'addon::codemirror/codemirror.min.js',
    'addon::codemirror/mode/xml.js',
    'addon::codemirror/mode/htmlmixed.js',
    'addon::codemirror/addon/active-line.js',
    'addon::codemirror/addon/closebrackets.js',
    'addon::codemirror/addon/closetag.js',
    'addon::codemirror.js',
], FALSE, 'codemirror');
