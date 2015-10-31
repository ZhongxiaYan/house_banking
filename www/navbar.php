<?php

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
    	<div class="collapse navbar-collapse" id="navbar-collapse-1">
      		';
if (isset($user_id)) { // logged in
	echo    '<ul class="nav navbar-nav">
        		<li class="active"><a href="balance.php">View Balance<span class="sr-only">(current)</span></a></li>
      		</ul>
      		<ul class="nav navbar-nav navbar-right">
      			<p class="navbar-text">Logged in as ' . $id_to_user[$user_id] . '</p> 
      			<li><a href="index.php?submission=logout">Logout</a></li>';
} else {
	echo 	'<ul class="nav navbar-nav navbar-right">
				<li><a href="login.php">Login</a></li>
				<li><a href="register.php">Register</a></li>';
}
echo '
      		</ul>
    	</div>
	</div>
</nav>
';

?>