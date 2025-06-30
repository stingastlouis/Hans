<?php
session_start();

unset($_SESSION['customerId']);
unset($_SESSION['customer_fullname']);
unset($_SESSION['customer_email']);

header("Location: index.php");
exit;
