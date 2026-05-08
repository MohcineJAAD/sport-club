<?php
session_start();
session_destroy();
header("Location: /sport-club/login.php");
exit();
