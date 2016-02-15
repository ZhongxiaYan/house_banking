<!DOCTYPE html>
<html>
    <head>
        <?php
            require_once $PAGES['util'];
            require_once "$SRC/views/head_header.php";
        ?>

        <script src="js/register.js"></script>
        <style>
            #main {
                margin: 20px;
            }
        </style>
    </head>
    <body>
        <?php
            require_once 'navbar.php';
        ?>
        <div id="main">
            <div style="color:red"><?= $message ?></div>
            
            <form role="form" action="register.php?submission=register" method="post" id="register-form">
                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" class="form-control" name="register-first-name" required>
                </div>
                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" class="form-control" name="register-last-name" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="text" class="form-control" name="register-email" id="email" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" class="form-control" name="register-password" id="password" required>
                </div>
                <div class="form-group">
                    <label>Reenter Password:</label>
                    <input type="password" class="form-control" name="register-password-2" id="password-2" required>
                </div>
                <div class="form-group">
                    <label>Secret Code:</label>
                    <input type="text" class="form-control" name="register-code" required>
                </div>
                <button type="submit" class="btn btn-default">Submit</button>
            </form>
        </div>
    </body>
</html>