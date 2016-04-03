<!DOCTYPE html>
<html>
    <head>
        <?php
            require_once $PAGES['util'];
            require_once "$SRC/views/head_header.php";
        ?>
        <script src="js/login.js"></script>
        <style>
            #main {
                margin: 20px;
            }

            #recovery-submit {
                display: none;
            }
        </style>
    </head>
    <body>
        <?php
            require_once "$SRC/views/navbar.php";
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
            <button type="button" class="btn btn-link" id="show-recovery">Forgot your password?</button>
            <form role="form" action="login.php?submission=recover" method="post" id="recovery-form" hidden>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="text" class="form-control" name="login-email" id="login-email" required>
                </div>
                <button type="button" class="btn btn-default" id="generate-recovery-code">Send Recovery Code</button>
                <div class="form-group" hidden>
                    <label>Recovery Code:</label>
                    <input type="text" class="form-control" name="recovery-code" required>
                </div>
                <button type="submit" class="btn btn-default" id="recovery-submit">Recover</button>
            </form>
        </div>
    </body>
</html>