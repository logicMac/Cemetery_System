<?php
session_start();
// Available plots are admin-only — redirect to the cemetery map
header('Location: dashboard.php');
exit;
