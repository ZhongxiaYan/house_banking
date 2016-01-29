<!DOCTYPE html>
<html>
    <head>
        <?php
            require_once "$WWW/views/head_header.php";
        ?>
        <script src="js/changeinfo.js"></script>
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
            <form role="form" action="changeinfo.php?submission=change" method="post" id="change-form">
                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" class="form-control" name="change-first-name" value=<?= $curr_user->first_name ?> required>
                </div>
                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" class="form-control" name="change-last-name" value=<?= $curr_user->last_name ?> required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="text" class="form-control" name="change-email" value=<?= $curr_user->email ?> id="email" required>
                </div>
                <div class="form-group">
                    <label>Password (Leave blank to keep):</label>
                    <input type="password" class="form-control" name="change-password" id="password">
                </div>
                <div class="form-group">
                    <label>Reenter Password (Leave blank to keep):</label>
                    <input type="password" class="form-control" name="change-password-2" id="password-2">
                </div>
                <div class="form-group">
                    <label>Current Password:</label>
                    <input type="password" class="form-control" name="current-password" required>
                </div>
                <input type="hidden" class="session-token" name="session-token" value=<?= htmlspecialchars($user_session_token) ?>>
                <button type="submit" class="btn btn-default">Save</button>
            </form>
        </div>
    </body>
</html>