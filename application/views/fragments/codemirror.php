<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// css
asset_css([
    'addon::codemirror/codemirror.min.css',
], FALSE, 'codemirror');

// js
asset_js([
    'addon::codemirror/codemirror.min.js',
    'addon::codemirror/mode/xml.js',
    'addon::codemirror/mode/htmlmixed.js',
    'addon::codemirror/addon/active-line.js',
    'addon::codemirror/addon/closebrackets.js',
    'addon::codemirror/addon/closetag.js',
    'addon::codemirror/addon/autorefresh.js',
    'addon::codemirror.js',
], FALSE, 'codemirror');
