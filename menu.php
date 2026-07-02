<html>
 <ul>
<li><a href="index.php"><p class="changeColor">HOME</p></a></li>
<li><a href="IT_ASSISTENT.php"><p class="changeColor">NETWORK</p></a></li>
<li><a href="NETWORK_ASSISTENT.php"><p class="changeColor">RADIOASTRONOMY</p></a></li>
<li><a href="WEB_DESING.php"><p class="changeColor">WEB DESING</p></a></li>
<li><a href="registrazione.php"><p class="changeColor">REGISTRATI</p></a></li>
<li><a href="login.php"><p class="changeColor">LOGIN</p></a></li>

</ul>
	
</html>



<?php
session_start();


if (isset($_SESSION['utente'])) {
	echo "utente: ".$_SESSION['utente']." - ";
	echo "<a href='logout.php'>LOGOUT</a>";
} 

?>