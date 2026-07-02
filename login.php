
<html>
<body>
<?php
session_start(); 
include "menu.php"; 
?>
    <img border=1 src="immagini\logo.png" width="100" height="100" class="dx">
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="StyleRegistrazione.css">

    <form action="testlogin.php" method="POST">

    Username: <input name="user"><br>
    Password: <input type="password" name="pass"><br>
    <button name="login">Login</button>
    </form>
  <div class="footer-info">
            <p>© 2024 NEW ERA</p>
   </div>
</body>
</html>