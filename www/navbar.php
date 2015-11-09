<?php

$page = basename($_SERVER['PHP_SELF']);
echo '
<nav class="navbar navbar-default">
	<div class="container-fluid">
	    <div class="navbar-header">
	        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse-1" aria-expanded="false">
		        <span class="sr-only">Toggle navigation</span>
		        <span class="icon-bar"></span>
		        <span class="icon-bar"></span>
		        <span class="icon-bar"></span>
	      	</button>
	        <a class="navbar-brand" href="index.php">House Banking</a>
	    </div>
    	<div class="collapse navbar-collapse" id="navbar-collapse-1">';
if (isset($curr_user)) { // logged in
	echo    '<ul class="nav navbar-nav">
				<li' . ($page === 'index.php' ? ' class="active"' : '') . '><a href="index.php">Home</a></li>
        		<li' . ($page === 'balance.php' ? ' class="active"' : '') . '><a href="balance.php">View Balance</a></li>
      		</ul>
      		<ul class="nav navbar-nav navbar-right">' . 
				($curr_user->is_admin ? '<li' . ($page === 'admin.php' ? ' class="active"' : '') . '><a href="admin.php">Manage Users</a></li>' : '') .
      			'<p class="navbar-text">Logged in as ' . $curr_user->name . ($curr_user->is_admin ? ' (admin)' : '') . '</p> 
      			<li><a href="index.php?submission=logout">Logout</a></li>';
} else {
	echo 	'<ul class="nav navbar-nav navbar-right">
				<li' . ($page === 'login.php' ? ' class="active"' : '') . '><a href="login.php">Login</a></li>
				<li' . ($page === 'register.php' ? ' class="active"' : '') . '><a href="register.php">Register</a></li>';
}
echo '
      		</ul>
    	</div>
	</div>
</nav>
';

?>