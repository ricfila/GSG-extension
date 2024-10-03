<?php
if (!isset($_GET['a']))
	exit;

require "../connect.php";
require "../pannello/php/function.php";

$conn = pg_connect((filter_var($server, FILTER_VALIDATE_IP) ? "hostaddr" : "host") . "=$server port=$port dbname=$dbname user=$user password=$password connect_timeout=5") or die('Connessione al database non riuscita.');
if (pg_connection_status($conn) == PGSQL_CONNECTION_BAD) {
	exit('Errore di connessione al database.');
}

foreach ($_GET as $k => $v)
	$$k = pg_escape_string($conn, $v);
setlocale(LC_ALL, 'it_IT');

switch ($a) {
	case 'ingredienti':
		$res = pg_query($conn, "SELECT righe_ingredienti.descrizionebreve as descrizionebreve,
				(CASE (CASE WHEN (CASE WHEN righe_articoli.copia_cucina THEN 'cucina' ELSE 'bar' END) = 'cucina'
					THEN ordini.stato_cucina ELSE ordini.stato_bar END)
					WHEN 'evaso' THEN true ELSE false
				END) as evaso,
				CEIL(SUM(righe_ingredienti.quantita::decimal / COALESCE(dati_ingredienti.divisore, 1))) as qta, 
				count(DISTINCT ordini.id) as comande
			FROM righe_ingredienti
			JOIN righe_articoli ON righe_ingredienti.id_riga_articolo = righe_articoli.id
			JOIN righe ON righe_articoli.id_riga = righe.id
			JOIN ordini ON righe.id_ordine = ordini.id
			JOIN ingredienti ON righe_ingredienti.descrizionebreve = ingredienti.descrizionebreve
			LEFT JOIN dati_ingredienti ON ingredienti.id = dati_ingredienti.id_ingrediente
			WHERE " . infoturnopalmare() . " and ordini.ora < LOCALTIME
				and dati_ingredienti.settore = '$settore'
			GROUP BY righe_ingredienti.descrizionebreve, evaso;");
		$out = array();
		while ($row = pg_fetch_assoc($res)) {
			$out[] = $row;
		}
		echo json_encode($out);
		break;
	default:
		break;
}

pg_close($conn);

?>