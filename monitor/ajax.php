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
		$res = pg_query($conn, "SELECT ingredienti.descrizione,
				SUM(CASE WHEN subq.evaso THEN 0 ELSE COALESCE(subq.qta, 0) END) as qta_attiva,
				SUM(CASE WHEN subq.evaso THEN COALESCE(subq.qta, 0) ELSE 0 END) as qta_evasa,
				SUM(CASE WHEN subq.evaso THEN 0 ELSE COALESCE(subq.comande, 0) END) as comande_attive,
				SUM(CASE WHEN subq.evaso THEN COALESCE(subq.comande, 0) ELSE 0 END) as comande_evase
			FROM ingredienti
			LEFT JOIN (
				SELECT righe_ingredienti.descrizionebreve,
					CEIL(SUM(COALESCE(righe_ingredienti.quantita::decimal, 0) / COALESCE(dati_ingredienti.divisore, 1))) as qta,
					(CASE (CASE WHEN (CASE WHEN righe_articoli.copia_cucina THEN 'cucina' ELSE 'bar' END) = 'cucina'
						THEN ordini.stato_cucina ELSE ordini.stato_bar END)
						WHEN 'evaso' THEN true ELSE false
					END) as evaso,
					COUNT(DISTINCT ordini.id) as comande
				FROM righe_ingredienti
				JOIN ingredienti ON righe_ingredienti.descrizionebreve = ingredienti.descrizionebreve
				LEFT JOIN dati_ingredienti ON ingredienti.id = dati_ingredienti.id_ingrediente
				JOIN righe_articoli ON righe_ingredienti.id_riga_articolo = righe_articoli.id
				JOIN righe ON righe_articoli.id_riga = righe.id
				JOIN ordini ON righe.id_ordine = ordini.id
				WHERE " . infoturnopalmare() . "
				GROUP BY righe_ingredienti.descrizionebreve, evaso
			) as subq ON ingredienti.descrizionebreve = subq.descrizionebreve
			LEFT JOIN dati_ingredienti ON ingredienti.id = dati_ingredienti.id_ingrediente
			WHERE COALESCE(dati_ingredienti.monitora, true) and dati_ingredienti.settore = '$settore'
			GROUP BY ingredienti.descrizione;");
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