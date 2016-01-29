<?php

require_once $PAGES['util'];

?>

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

        <?php if ($logged_in): ?>

            <ul class="nav navbar-nav">
                <li class=<?= $page === 'index.php' ? 'active' : ''; ?>><a href="index.php">Home</a></li>
                <li class=<?= $page === 'balance.php' ? 'active' : ''; ?>><a href="balance.php">View Balance</a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">

            <?php if ($curr_user->is_admin): ?>

                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">View as <?= e($view_user->name); ?><span class="caret"></span></a>
                    <ul class="dropdown-menu">
                    
                    <?php foreach ($all_users as $id => $user): ?>
                        <li><a href=<?= '?user=' . $id ?>><?= $user->name . ($user->is_active ? '' : ' (inactive)') ?></a></li> 
                    <?php endforeach; ?>
                    
                    </ul>
                </li>
                <li class=<?= $page === 'admin.php' ? 'active' : '' ?>><a href="admin.php">Manage Users</a></li>
                <p class="navbar-text">Logged in as <?= e($curr_user->name); ?> (admin)</p>

            <?php else: ?>

                <p class="navbar-text">Logged in as <?= e($curr_user->name); ?></p>
            
            <?php endif; ?>

                <li><a href="changeinfo.php">Edit Info</a></li>
                <li><a href="login.php?submission=logout">Logout</a></li>
            </ul>
        <?php else: ?>
            <ul class="nav navbar-nav navbar-right">
                <li class=<?= $page === 'login.php' ? 'active' : '' ?>><a href="login.php">Login</a></li>
                <li class=<?= $page === 'register.php' ? 'active' : '' ?>><a href="register.php">Register</a></li>
            </ul>
        <?php endif; ?>
        </div>
    </div>
</nav>