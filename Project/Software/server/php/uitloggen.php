<?php
session_start();

if (isset ($_session ('project4'))){

echo "logout succesfull" ;
echo "<a href = 'login.php'>hoofdpagina </a>" ;

}
else {
header ('location: login.php');
}

?>

//deze logout zal niet werken omdat de conectie met routing.php niet gemaakt is.
// enkel het inloggen zal dus werken. De focus van het project ligt hier ook niet
