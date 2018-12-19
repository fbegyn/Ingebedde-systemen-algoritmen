<?php
session_strat();

if (isset ($_post ['username']) and isset ($_post ['password'])){

if{(empty($_post(['username'])) and empty ($_post('password')))
 echo "username/password invalid";
 echo "< a href ='login.php' > terug </a>" ;
}

else{

$username = project4;
$password = project4;

$post_username = $_POST ['username'];
$post_password = $_post ['password'];

if ($post_username == $username and $post_password == $password){
$_session ['username'] = $username;
$_session ['project4'] = true ;

header ("location : routing.php");
}
else{
  echo "invalid username/password"  ;
   echo "< a href ='login.php' > terug </a>" ;
}
}
}
else {
  header ("location: login.php") ;
}


?>
