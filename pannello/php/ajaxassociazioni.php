<?php
if (!isset($_GET['a']))
	exit;
if (!isset($_COOKIE['login']))
	exit;

require "../../connect.php";

$conn = pg_connect((filter_var($server, FILTER_VALIDATE_IP) ? "hostaddr" : "host") . "=$server port=$port dbname=$dbname user=$user password=$password connect_timeout=5") or die('Connessione al database non riuscita.');
if (pg_connection_status($conn) == PGSQL_CONNECTION_BAD) {
	echo 'Errore di connessione al database.';
}

include "function.php";

foreach ($_GET as $k => $v)
	$$k = pg_escape_string($conn, $v);
setlocale(LC_ALL, 'it_IT');

switch ($a) {
	case 'associazionirecenti':
		$res = pg_query($conn, "SELECT ordini.id, ordini.progressivo, ordini.\"numeroTavolo\", ordini.esportazione, ordini.id_progressivo_bar, ordini.id_progressivo_cucina, ordini.cliente, ordini.cassiere, ordini.ora as ora, passaggi_stato.ora as associazione, passaggi_stato.stato
		FROM ordini LEFT JOIN passaggi_stato ON ordini.id = passaggi_stato.id_ordine
		WHERE " . infoturno() . " and (
			(ordini.esportazione = 't' and passaggi_stato.stato is null) or 
			(passaggi_stato.stato is null and ordini.\"numeroTavolo\" <> '') or 
			(passaggi_stato.stato = 0 and (EXTRACT(HOUR FROM (LOCALTIME - passaggi_stato.ora)) * 3600 +
									EXTRACT(MINUTE FROM (LOCALTIME - passaggi_stato.ora)) * 60 +
									EXTRACT(SECOND FROM (LOCALTIME - passaggi_stato.ora))) > 30))
		and ordini.stato_bar <> 'evaso' and ordini.stato_cucina <> 'evaso'
		ORDER BY " . ($order == 'ora' ? "ordini.esportazione desc, ordini.cassiere, passaggi_stato.ora" : "ordini.progressivo") . ($desc == '1' ? ' desc' : '') . ";");
		echo "[\n";
		$i = 0;
		while ($row = pg_fetch_assoc($res)) {
			echo "\t{\n";
			echo "\t\t\"id\": " . $row['id'] . ",\n";
			echo "\t\t\"progressivo\": " . $row['progressivo'] . ",\n";
			echo "\t\t\"tavolo\": \"" . $row['numeroTavolo'] . "\",\n";
			echo "\t\t\"esportazione\": " . ($row['esportazione'] == 't' ? 'true' : 'false') . ",\n";
			echo "\t\t\"copia_bar\": " . ($row['id_progressivo_bar'] != null ? 'true' : 'false') . ",\n";
			echo "\t\t\"copia_cucina\": " . ($row['id_progressivo_cucina'] != null ? 'true' : 'false') . ",\n";
			echo "\t\t\"cliente\": \"" . ($row['cliente'] == null || empty($row['cliente']) ? '<i>nessun nome</i>' : $row['cliente']) . "\",\n";
			echo "\t\t\"cameriere\": \"" . $row['cassiere'] . "\",\n";
			echo "\t\t\"ora\": \"" . ($row['esportazione'] == 't' || $row['stato'] == null ? $row['ora'] : $row['associazione']) . "\"\n";
			echo "\t}" . ($i < pg_num_rows($res) - 1 ? "," : "") . "\n";
			$i++;
		}
		echo "]";
		break;
	case 'ultimo':
		$res = pg_query($conn, "select * from ordini where " . infoturno() . " order by ora desc;");
		$row = pg_fetch_assoc($res);
		echo "{\n";
		echo "\t\"id\": " . $row['id'] . ",\n";
		echo "\t\"progressivo\": " . $row['progressivo'] . ",\n";
		echo "\t\"esportazione\": " . ($row['esportazione'] == 't' ? 'true' : 'false') . ",\n";
		echo "\t\"copia_bar\": " . ($row['id_progressivo_bar'] != null ? 'true' : 'false') . ",\n";
		echo "\t\"copia_cucina\": " . ($row['id_progressivo_cucina'] != null ? 'true' : 'false') . ",\n";
		echo "\t\"cliente\": \"" . ($row['cliente'] == null || empty($row['cliente']) ? '<i>nessun nome</i>' : $row['cliente']) . "\",\n";
		echo "\t\"ora\": \"" . $row['ora'] . "\"\n";
		echo "}";
		break;
	case 'lavorazione':
		if ($esportazione == 'true') {
			if (pg_query($conn, "insert into passaggi_stato (id_ordine, ora, stato) values ($id, LOCALTIME, 10);"))
				echo '1';
		} else {
			if (pg_query($conn, "update passaggi_stato set stato = 10 where id_ordine = $id and stato = 0;"))
				echo '1';
		}
		break;
	case 'recupera':
		if ($esportazione == 'true') {
			if (pg_query($conn, "delete from passaggi_stato where id_ordine = $id and stato = 10;"))
				echo '1';
		} else {
			if (pg_query($conn, "update passaggi_stato set stato = 0 where id_ordine = $id and stato = 10;"))
				echo '1';
		}
		break;
	default:
		break;
}

pg_close($conn);

?>