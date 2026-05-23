<?php
session_start();
if ($role != 'admin'){
    header('Location: ../index.php');
}
?>