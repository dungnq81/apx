<?php
defined('BASEPATH') OR exit('No direct script access allowed');

?>
<div class="off-canvas position-left" id="offCanvasMenu" data-off-canvas data-transition="overlap">
	<button class="close-button" aria-label="Close menu" type="button" data-close>
		<span aria-hidden="true">&times;</span>
	</button>
</div>
<div class="off-canvas-content" data-off-canvas-content>
	<div class="title-bar hide-for-medium">
        <button class="menu-icon" type="button" data-open="offCanvasMenu"></button>
		<span class="title-bar-title"><a class="logo-m" href="#"><strong>APX Cms</strong></a></span>
	</div>
	<div class="top-bar show-for-medium">
		<div class="top-bar-left">
			<div class="nav-title"><a class="logo-w" href="#"><strong>APX Cms</strong></a></div>
		</div>
		<div class="top-bar-right">
            <ul class="main-nav dropdown menu" data-dropdown-menu>
                <li><?php echo anchor('admin', 'Dashboard', is_dashboard() ? 'class="current"' : '') ?></li>
                <li><a href="<?php echo site_url('admin/settings')?>" title="Settings">Settings</a></li>
                <li><a href="<?php echo site_url('admin/pages')?>" title="Pages">Pages</a></li>
                <li>
                    <a href="<?php echo current_url() . '#'?>" title="Users">Users</a>
                    <ul class="menu nested">
                        <li><a href="<?php echo site_url('admin/groups')?>" title="Groups">Groups</a></li>
                        <li><a href="<?php echo site_url('admin/permissions')?>" title="Permissions">Permissions</a></li>
                        <li><a href="<?php echo site_url('admin/users')?>" title="Users">Users</a></li>
                    </ul>
                </li>
            </ul>
		</div>
	</div>
</div>
