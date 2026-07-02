<html>
<body>
<?php
session_start();
unset($_SESSION["UTENTE"]);
unset($_SESSION["LIVELLO"]);
session_destroy();
include "menu.php";
echo "<br/><a href='index.php'>logout eseguito. accedi al sito</a>";
?>
</body>
</html>