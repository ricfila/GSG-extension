<?php
header("Access-Control-Allow-Origin: *");
if (!isset($_GET['file'])) {
	echo '0- Nome del file non specificato.';
	exit;
}

// Restituisce il file richiesto nella seguente cartella:
$dir = '';
for ($i = 0; $i < count(explode('/', __FILE__)) - 2; $i++)
	$dir .= '../';
$dir .= ''; // Directory dove è presente il file di dump
$nome = $dir . $_GET['file'];
$file = fopen($nome, "r") or die ("0- Accesso non riuscito.");

echo filectime($nome) . "\n";

while (!feof($file)) {
	echo str_replace("\n", "", str_replace("\r", "", fgets($file))) . "\n";
	//echo fgets($file) . "\n";
}
fclose($file);

?>
