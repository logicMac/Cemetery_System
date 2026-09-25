<?php
session_start();
// Reservations are not accepted — redirect to the cemetery map
header('Location: dashboard.php');
exit;
