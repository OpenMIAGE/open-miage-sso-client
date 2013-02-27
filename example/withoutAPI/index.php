<?php
session_start();
?>
<html>
    <body>
        <h1>
            My Example Of Page that using OpenM_SSO for connection (WithOut API usage).
        </h1>
        <?php
        if (isset($_SESSION["connected"]) && $_SESSION["connected"] == "OK")
            echo "<h2>I'm Connected \o/ and my id is: " . $_SESSION["id"] . "</h2>";
        else {?>
        You're not connected click on : <a href="login.php">login</a>,
        <?php }
        ?>
        <a href="logout.php">logout</a>
    </body>    
</html>