<?php
session_start();
session_destroy();
setcookie('auth_token', $token, time() - 3600, "/");
header("Location: view_recipes.php?message=logout");
exit();
