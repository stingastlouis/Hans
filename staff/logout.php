<?php
session_start();

unset($_SESSION['staff_id']);
unset($_SESSION['staff_name']);
unset($_SESSION['role']);
header('Location: login.php');
exit;
