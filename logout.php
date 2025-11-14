<?php
/**
 * Logout - Sistema de Bomberos del Chocó
 */

session_start();
session_unset();
session_destroy();

// Redirigir al index
header('Location: index.php');
exit();
?>
