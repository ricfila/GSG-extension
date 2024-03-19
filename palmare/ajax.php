<?php
if (!isset($_GET['a']))
	exit;
if (!isset($_COOKIE['cameriere']))
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
$giorni = array('domenica', 'lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato');

ksort($_COOKIE);
foreach ($_COOKIE as $k => $v) {
	if (str_starts_with($k, 'action')) {
		$azione = explode("_", $v);
		if ($azione[0] == '0') { // Salva il tavolo
			if (pg_num_rows(pg_query($conn, "select * from evasioni where id_ordine = " . $azione[1] . " and (stato = 0 or stato = 10);")) == 0) {
				if (!pg_query($conn, "BEGIN")) {
					exit('Transazione non avviata.');
				} else {
					$ok = true;
					$ok = $ok && pg_query($conn, "update ordini set \"numeroTavolo\" = '" . $azione[2] . "', cassiere = '" . pg_escape_string($conn, $_COOKIE['cameriere']) . "' where id = " . $azione[1] . ";");
					$ok = $ok && pg_query($conn, "insert into evasioni (id_ordine, ora, stato) values (" . $azione[1] . ", LOCALTIME, 0);");
					if (chiudiTransazione($conn, $ok, 'no')) {
						cancellaCookie($k);
					} else
						exit('');
				}
			} else { // Tavolo già salvato
				cancellaCookie($k);
			}
		} else if ($azione[0] == '-') { // Dissocia
			if (pg_fetch_assoc(pg_query($conn, "select * from evasioni where id_ordine = " . $azione[1] . " order by stato;"))['stato'] == 0) {
				if (pg_num_rows(pg_query($conn, "select * from evasioni where id_ordine = " . $azione[1] . " and stato = 0;")) == 1) {
					if (!pg_query($conn, "BEGIN")) {
						exit('Transazione non avviata.');
					} else {
						$ok = true;
						$ok = $ok && pg_query($conn, "update ordini set \"numeroTavolo\" = '', cassiere = '' where id = " . $azione[1] . ";");
						$ok = $ok && pg_query($conn, "delete from evasioni where id_ordine = " . $azione[1] . " and stato = 0;");
						if (chiudiTransazione($conn, $ok, 'no')) {
							cancellaCookie($k);
						} else
							exit('');
					}
				} else { // Tavolo già dissociato
					cancellaCookie($k);
				}
			} else {
				$row = pg_fetch_assoc(pg_query($conn, "select * from ordini where id = " . $azione[1] . ";"));
				cancellaCookie($k);
				exit('L\'ordine <strong>' . $row['progressivo'] . '</strong> di <strong>' . $row['cliente'] . '</strong> è già stato preso in carico: impossibile dissociarlo dal tavolo. Rivolgersi all\'ufficio comande.');
			}
		}
	}
}

switch ($a) {
	case 'invio':
		echo '1';
		break;
	case 'lista':
		$res = pg_query($conn, "select * from ordini where " . infoturnopalmare() . " and esportazione = false and stato_cucina <> 'evaso' and stato_bar <> 'evaso' and \"numeroTavolo\" = '' order by progressivo;");
		echo "[\n";
		$i = 0;
		while ($row = pg_fetch_assoc($res)) {
			echo "\t{\n";
			echo "\t\t\"id\": " . $row['id'] . ",\n";
			echo "\t\t\"progressivo\": " . $row['progressivo'] . ",\n";
			echo "\t\t\"ora\": \"" . $row['ora'] . "\",\n";
			echo "\t\t\"cliente\": \"" . ($row['cliente'] == null || empty($row['cliente']) ? '<i>nessun nome</i>' : $row['cliente']) . "\"\n";
			echo "\t}" . ($i < pg_num_rows($res) - 1 ? "," : "") . "\n";
			$i++;
		}
		echo "]";
		break;
	case 'ultimi':
		$res = pg_query($conn, "select ordini.id, ordini.progressivo, ordini.cassiere, ordini.cliente, ordini.coperti, ordini.data, ordini.ora, evasioni.ora as associazione, evasioni.stato, ordini.stato_bar, ordini.stato_cucina, ordini.id_progressivo_bar, ordini.id_progressivo_cucina, ordini.\"numeroTavolo\", ordini.esportazione from ordini join evasioni on ordini.id = evasioni.id_ordine where " . infoturnopalmare() . " and ordini.esportazione = false and ordini.cassiere = '" . pg_escape_string($conn, $_COOKIE['cameriere']) . "' and (evasioni.stato = 0 or evasioni.stato = 10) order by evasioni.ora desc;");
		echo "[\n";
		$i = 0;
		while ($row = pg_fetch_assoc($res)) {
			$oracomanda = date_create($row['data']);
			echo "\t{\n";
			echo "\t\t\"id\": " . $row['id'] . ",\n";
			echo "\t\t\"progressivo\": " . $row['progressivo'] . ",\n";
			echo "\t\t\"cameriere\": \"" . $row['cassiere'] . "\",\n";
			echo "\t\t\"cliente\": \"" . ($row['cliente'] == null || empty($row['cliente']) ? '<i>nessun nome</i>' : $row['cliente']) . "\",\n";
			echo "\t\t\"coperti\": " . ($row['esportazione'] == 't' ? 0 : $row['coperti']) . ",\n";
			echo "\t\t\"data\": \"" . $giorni[date_format($oracomanda, 'w')] . ' ' . date_format($oracomanda, 'j') . "\",\n";
			echo "\t\t\"ora\": \"" . $row['ora'] . "\",\n";
			echo "\t\t\"associazione\": \"" . $row['associazione'] . "\",\n";
			echo "\t\t\"stato\": " . $row['stato'] . ",\n";
			echo "\t\t\"stato_bar\": \"" . riparaEvasione($conn, $row, 'bar') . "\",\n";
			echo "\t\t\"stato_cucina\": \"" . riparaEvasione($conn, $row, 'cucina') . "\",\n";
			echo "\t\t\"copia_bar\": " . ($row['id_progressivo_bar'] != null ? 'true' : 'false') . ",\n";
			echo "\t\t\"copia_cucina\": " . ($row['id_progressivo_cucina'] != null ? 'true' : 'false') . ",\n";
			echo "\t\t\"tavolo\": \"" . $row['numeroTavolo'] . "\",\n";
			echo "\t\t\"esportazione\": " . ($row['esportazione'] == 't' ? 'true' : 'false') . "\n";
			echo "\t}" . ($i < pg_num_rows($res) - 1 ? "," : "") . "\n";
			$i++;
		}
		echo "]";
		break;
	case 'cerca':
		if (isset($id)) {
			$condizioni = "id = $id";
		} else if (isset($num)) {
			$condizioni = "progressivo = $num";
		} else if (isset($tav)) {
			$condizioni = "\"numeroTavolo\" like '%$tav%'";
		} else {
			$condizioni = "cliente ilike '%$nome%'";
		}
		$res = pg_query($conn, "select * from ordini where " . (isset($tav) ? infoturnopalmare() . " and " : "") . $condizioni . ";");
		echo "[\n";
		$i = 0;
		while ($row = pg_fetch_assoc($res)) {
			$res2 = pg_query($conn, "select * from evasioni where id_ordine = " . $row['id'] . " and (stato = 0 or stato = 10);");
			$row2 = pg_fetch_assoc($res2);
			$assoc = (pg_num_rows($res2) == 1 ? $row2['ora'] : 'null');
			$stato = (pg_num_rows($res2) == 1 ? $row2['stato'] : "\"null\"");
			$oracomanda = date_create($row['data']);
			echo "\t{\n";
			echo "\t\t\"id\": " . $row['id'] . ",\n";
			echo "\t\t\"progressivo\": " . $row['progressivo'] . ",\n";
			echo "\t\t\"cameriere\": \"" . $row['cassiere'] . "\",\n";
			echo "\t\t\"cliente\": \"" . ($row['cliente'] == null || empty($row['cliente']) ? '<i>nessun nome</i>' : $row['cliente']) . "\",\n";
			echo "\t\t\"coperti\": " . ($row['esportazione'] == 't' ? 0 : $row['coperti']) . ",\n";
			echo "\t\t\"data\": \"" . $giorni[date_format($oracomanda, 'w')] . ' ' . date_format($oracomanda, 'j') . "\",\n";
			echo "\t\t\"ora\": \"" . $row['ora'] . "\",\n";
			echo "\t\t\"associazione\": \"" . $assoc . "\",\n";
			echo "\t\t\"stato\": " . $stato . ",\n";
			echo "\t\t\"stato_bar\": \"" . riparaEvasione($conn, $row, 'bar') . "\",\n";
			echo "\t\t\"stato_cucina\": \"" . riparaEvasione($conn, $row, 'cucina') . "\",\n";
			echo "\t\t\"copia_bar\": " . ($row['id_progressivo_bar'] != null ? 'true' : 'false') . ",\n";
			echo "\t\t\"copia_cucina\": " . ($row['id_progressivo_cucina'] != null ? 'true' : 'false') . ",\n";
			echo "\t\t\"tavolo\": \"" . $row['numeroTavolo'] . "\",\n";
			echo "\t\t\"esportazione\": " . ($row['esportazione'] == 't' ? 'true' : 'false') . "\n";
			echo "\t}" . ($i < pg_num_rows($res) - 1 ? "," : "") . "\n";
			$i++;
		}
		echo "]";
	default:
		break;
}

pg_close($conn);

function cancellaCookie($nome) {
	unset($_COOKIE[$nome]);
	setcookie($nome, '', -1, '/');
}

?>