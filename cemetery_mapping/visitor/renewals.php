<?php
session_start();
// Renewals are done in person at the Treasurer's office (OR-based) — redirect to the cemetery map
header('Location: dashboard.php');
exit;
