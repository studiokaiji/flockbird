<?php if (Auth::check()): ?>
			<div class="pull-right navbar-btn btn-group">
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
					<i class="icon-user"></i> <?php echo Auth::get_screen_name(); ?>
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu" role="menu">
<?php foreach (Config::get('navigation.admin.secure_user_dropdown') as $name => $path): ?>
					<li<?php if (Uri::string().'/' == $path): ?><?php echo ' class="active"'; ?><?php endif; ?>><?php echo anchor_icon($path, $name); ?></li>
<?php endforeach; ?>
				</ul>
			</div>
<?php else: ?>
			<div class="pull-right navbar-btn btn-group">
				<a class="btn btn-default dropdown-toggle" data-toggle="dropdown" href="#">
					<i class="icon-user"></i> <?php echo Auth::get_screen_name(); ?>
					<span class="caret"></span>
				</a>
				<ul class="dropdown-menu">
<?php foreach (Config::get('navigation.admin.insecure_user_dropdown') as $name => $path): ?>
					<li<?php if (Uri::string().'/' == $path): ?><?php echo ' class="active"'; ?><?php endif; ?>><?php echo anchor_icon($path, $name); ?></li>
<?php endforeach; ?>
				</ul>
			</div>
<?php endif; ?>
