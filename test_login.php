<?php
/**
 * Test de proceso_login.php - Archivo de prueba
 */

echo "<h2>Test de Autenticación</h2>";

// Simular una solicitud POST
$test_data = [
    'username' => 'comandante',
    'password' => 'ComandanteSeguro2025!',
    'rol' => 'comandante'
];

echo "<h3>Datos de Prueba:</h3>";
echo "<pre>";
print_r($test_data);
echo "</pre>";

// Incluir el archivo de proceso_login.php
echo "<h3>Respuesta del Servidor:</h3>";

// Simular $_POST
$_POST = $test_data;

// Capturar la salida
ob_start();
include 'proceso_login.php';
$output = ob_get_clean();

// Mostrar la respuesta
echo "<pre>";
echo htmlspecialchars($output);
echo "</pre>";

// Verificar si es JSON válido
echo "<h3>Análisis JSON:</h3>";
$json = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<p><strong style='color: green;'>✅ JSON Válido</strong></p>";
    echo "<pre>";
    print_r($json);
    echo "</pre>";
} else {
    echo "<p><strong style='color: red;'>❌ Error en JSON:</strong> " . json_last_error_msg() . "</p>";
}

?>
