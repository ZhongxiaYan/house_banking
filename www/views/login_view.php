<!DOCTYPE html>
<html>
    <head>
        <?php
            require_once $PAGES['util'];
            require_once "$WWW/views/head_header.php";
        ?>
        <style>
            #main {
                margin: 20px;
            }
        </style>
    </head>
    <body>
        <?php
            require_once "$WWW/views/navbar.php";
        ?>
        <div id="main">
            <div style=<?= "color:$color" ?>><?= htmlspecialchars($message) ?></div>
            <form role="form" action="login.php?submission=login" method="post" id="login-form">
                <div class="form-group">
                    <label>Email:</label>
                    <input type="text" class="form-control" name="login-email" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" class="form-control" name="login-password" required>
                </div>
                <button type="submit" class="btn btn-default">Submit</button>
            </form>
        </div>
    </body>
</html>