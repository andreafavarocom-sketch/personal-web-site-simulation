<html>
<body>
<?php
session_start(); 
include "menu.php"; 
?>

<link rel="stylesheet" href="style.css"> 
<br><br>

<img border=1 src="immagini\logo.png" width="100" height="100" class="dx">
<link rel="stylesheet" href="StyleRegistrazione.css">


<form action="chackRegister.php" method="post" enctype="multipart/form-data">
  <div space="1">
    <p>insert name</p>
    <input type="text" name="user" placeholder="Mario"><br><br>

    <p>insert password</p>
    <input type="password" name="pass" id='password' placeholder="*******"><br><br>

    <p>confirm password</p>
    <input type="password" name="passc" id='password1' placeholder="*******"><br><br>
    <p>insert date</p>
    <input type="date" name="datanascita">
    
    <p>describe itself</p>
    <textarea id="bio" name="bio" rows="5" cols="40" placeholder="Parla un po' di te..."></textarea>
    
    <p>upload photo</p>
    <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">

    <p>who are you</p>
    <input type="radio" name="role" value="admin"> Admin
    <input type="radio" name="role" value="user"> User

    <br><br>
    <button type="submit">register</button>
  </div>
</form>
<p id="messagio"></p>
  <div class="footer-info">
            <p>© 2024 NEW ERA</p>
        </div>
</body>
</html>