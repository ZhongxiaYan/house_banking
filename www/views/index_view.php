<!DOCTYPE html>
<html>
    <head>
        <?php

        require_once "$WWW/views/head_header.php";

        ?>
        <script src="js/index.js"></script>
        <script src="js/json.js"></script>
        <style>
            #main {
                margin: 20px;
            }
            
            .editting-cell {
                background-color: gray;
                cursor: pointer;
            }

            .editting-cell:hover {
                background-color: lightyellow;
            }

            .editting-cell-covered {
                background-color: lightyellow;
            }

            .red {
                color: red;
            }

            .green {
                color: green;
            }

            .selected-cell {
                background-color: yellow;
                cursor: pointer;
            }

            .table thead tr th {
                background-color: wheat;
            }

            .deposit {
                background-color: #b8d1f3;
            }

            .transaction-repeat {
                background-color: #cc99ff;
            }

            .transaction-single {
                background-color: #ffccff;
            }

        </style>
    </head>
    <body>
        <?php

        require_once 'navbar.php';
        
        ?>
        <div id="main">
            <?php

            require_once "$LIB/util.php";

            require_once "$WWW/views/editable_table_view.php";
            require_once "$WWW/views/house_table_view.php";
                    
            ?>
            
        </div>
    </body>
</html>