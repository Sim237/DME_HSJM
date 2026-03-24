<?php
session_start();
if (!isset($_SESSION['counter'])) {
    $_SESSION['counter'] = 0;
}
$_SESSION['counter']++;

echo "<h3>Test de Session</h3>";
echo "Nombre de vues : " . $_SESSION['counter'] . "<br>";
echo "ID de session : " . session_id() . "<br>";
echo "<a href='session_check.php'>Actualiser la page</a>";
echo "<br><br>Si le chiffre n'augmente pas quand vous actualisez, c'est que votre serveur XAMPP ne sauvegarde pas les sessions.";
?>