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
	case 'comande':
		$res = pg_query($conn, "select * from ordini where " . infoturno() . " order by id;");
		echo "[\n";
		$i = 0;
		while ($row = pg_fetch_assoc($res)) {
			echo "\t{\n";
			echo "\t\t\"id\": " . $row['id'] . ",\n";
			echo "\t\t\"progressivo\": " . $row['progressivo'] . ",\n";
			echo "\t\t\"tavolo\": \"" . $row['numeroTavolo'] . "\",\n";
			echo "\t\t\"esportazione\": " . ($row['esportazione'] == 't' ? 'true' : 'false') . ",\n";
			echo "\t\t\"stato_bar\": \"" . riparaEvasione($conn, $row, 'bar') . "\",\n";
			echo "\t\t\"stato_cucina\": \"" . riparaEvasione($conn, $row, 'cucina') . "\",\n";
			echo "\t\t\"copia_bar\": " . ($row['id_progressivo_bar'] != null ? 'true' : 'false') . ",\n";
			echo "\t\t\"copia_cucina\": " . ($row['id_progressivo_cucina'] != null ? 'true' : 'false') . ",\n";
			echo "\t\t\"ora\": \"" . $row['ora'] . "\",\n";
			echo "\t\t\"cliente\": \"" . ($row['cliente'] == null || empty($row['cliente']) ? '<i>nessun nome</i>' : $row['cliente']) . "\"\n";
			echo "\t}" . ($i < pg_num_rows($res) - 1 ? "," : "") . "\n";
			$i++;
		}
		echo "]";
		break;
	case 'salvatavolo':
		if (!pg_query($conn, "update ordini set \"numeroTavolo\" = '$tavolo' where id = $id;")) {
			echo pg_last_error($conn);
		} else {
			echo '1';
		}
		break;
	case 'evadi':
		if (!pg_query($conn, "BEGIN")) {
			echo 'Transazione non avviata.';
		} else {
			$ok = true;
			$datiordine = pg_fetch_assoc(pg_query($conn, "select * from ordini where id = $id;"));
			/*$statocl = ($datiordine['esportazione'] == 't' ? $azione : 
						($azione == 'evaso' ? ($datiordine['stato_cliente'] == 'ordinato' ? ($datiordine['id_progressivo_cucina'] == null || $datiordine['id_progressivo_bar'] == null ? 'evaso' : 'lavorazione') : 'evaso') :
							($datiordine['stato_cliente'] == 'evaso' ? ($datiordine['id_progressivo_cucina'] == null || $datiordine['id_progressivo_bar'] == null ? 'ordinato' : 'lavorazione') : 'ordinato')));*/
			$statocl = ($datiordine['esportazione'] == 't' ? ($azione == 'evaso' ? 'evaso' : 'lavorazione') : 
						($azione == 'evaso' ? ($datiordine['stato_cliente'] == 'lavorazione' ? ($datiordine['id_progressivo_cucina'] == null || $datiordine['id_progressivo_bar'] == null ? 'evaso' : 'lavorazione') : 'evaso') : ($datiordine['numeroTavolo'] == '' || $datiordine['numeroTavolo'] == 'null' ? 'ordinato' : 'lavorazione')));
			if ($azione == 'ordinato' && $tavolo == 'null')
				$tavolo = '';
			$ok = $ok && pg_query($conn, "update ordini set \"numeroTavolo\" = '$tavolo', stato_$tipo = '$azione', stato_cliente = '$statocl' where id = $id;");
			if ($datiordine['esportazione'] == 't')
				if ($tipo == 'cucina' && $datiordine['id_progressivo_bar'] != null)
					$ok = $ok && pg_query($conn, "update ordini set stato_bar = '$azione' where id = $id;");
			
			$cod = $tipo == 'bar' ? 1 : 2;
			$num = pg_num_rows(pg_query($conn, "select * from evasioni where id_ordine = $id and stato = $cod;"));
			if ($azione == 'evaso') {
				if ($num == 0) {
					$ok = $ok && pg_query($conn, "insert into evasioni (id_ordine, ora, stato) values ($id, " . ($salvaora == 'true' ? "LOCALTIME" : "null") . ", $cod);");
				} else {
					$ok = $ok && pg_query($conn, "update evasioni set ora = " . ($salvaora == 'true' ? "LOCALTIME" : "null") . " where id_ordine = $id and stato = $cod;");
				}
			} else {
				if ($num > 0) {
					$ok = $ok && pg_query($conn, "delete from evasioni where id_ordine = $id and stato = $cod;");
				}
			}
			chiudiTransazione($conn, $ok);
		}
		break;
	case 'impostaordinioggi': // Operazione pericolosa!
		if (!pg_query($conn, "update ordini set \"data\" = '$data';")) {
			echo pg_last_error($conn);
		} else {
			echo '1';
		}
		break;
	case 'inizioturno':
		if (pg_num_rows(pg_query($conn, "select * from shiftstart;")) == 1)
			echo (pg_query($conn, "update shiftstart set \"datetimestart\" = '$data $ora';") ? '1' : pg_last_error($conn));
		else
			echo (pg_query($conn, "insert into shiftstart (\"datetimestart\") values ('$data $ora');") ? '1' : pg_last_error($conn));
		break;
	case 'getstart':
		$res = pg_query($conn, "select * from shiftstart;");
		if (pg_num_rows($res) == 0) {
			$value = date('Y-m-d') . " " . (intval(date('G')) < 17 ? "00" : "17") . ":00:00";
			pg_query($conn, "insert into shiftstart (\"datetimestart\") values ('$value');");
			echo $value;
		} else {
			echo pg_fetch_assoc($res)['datetimestart'];
		}
		break;
	case 'postgres':
		echo 'postgresql://' . $user . ':' . $password . '@' . $server . ':' . $port . '/' . $dbname;
		break;
	case 'chiudicassa':
		$res = pg_query($conn, "SELECT * FROM venduto_per_turno");
		echo "[\n";
		$i = 0;
		while ($row = pg_fetch_assoc($res)) {
			echo "\t{\n";
			echo "\t\t\"data\": \"" . $row['data'] . "\",\n";
			echo "\t\t\"pranzo_cena\": \"" . $row['pranzo_cena'] . "\",\n";
			echo "\t\t\"cassa\": \"" . $row['cassa'] . "\",\n";
			echo "\t\t\"tipo_pagamento\": \"" . $row['tipo_pagamento'] . "\",\n";
			echo "\t\t\"importo_totale\": " . $row['importo_totale'] . "\n";
			echo "\t}" . ($i < pg_num_rows($res) - 1 ? "," : "") . "\n";
			$i++;
		}
		echo "]";
		break;
	case 'reportmodifiche':
		$res = pg_query($conn, "SELECT ordini.id, ordini.progressivo, modifiche.ora, modifiche.agente, modifiche.differenza, modifiche.righemodificate, ordini.tipo_pagamento FROM modifiche join ordini on modifiche.id_ordine = ordini.id WHERE " . infoturno() . " and modifiche.differenza <> 0 and ordini.cassa = '$cassa' ORDER BY ordini.tipo_pagamento;");
		echo "[\n";
		$i = 0;
		$j = 0;
		while ($row = pg_fetch_assoc($res)) {
			echo "\t{\n";
			echo "\t\t\"tipo\": \"esterno\",\n";
			echo "\t\t\"id\": " . $row['id'] . ",\n";
			echo "\t\t\"progressivo\": " . $row['progressivo'] . ",\n";
			echo "\t\t\"ora\": \"" . $row['ora'] . "\",\n";
			echo "\t\t\"agente\": \"" . $row['agente'] . "\",\n";
			echo "\t\t\"differenza\": \"" . $row['differenza'] . "\",\n";
			echo "\t\t\"righemodificate\": " . $row['righemodificate'] . ",\n";
			echo "\t\t\"tipo_pagamento\": \"" . $row['tipo_pagamento'] . "\"\n";
			echo "\t}" . ($i < pg_num_rows($res) - 1 ? "," : "") . "\n";
			$i++;
			$j++;
		}
		
		$res = pg_query($conn, "SELECT ordini.id, ordini.progressivo, modifiche.ora, ordini.cassa, modifiche.differenza, modifiche.righemodificate, ordini.tipo_pagamento FROM modifiche join ordini on modifiche.id_ordine = ordini.id WHERE " . infoturno() . " and modifiche.differenza <> 0 and modifiche.agente = '$cassa' ORDER BY ordini.tipo_pagamento;");
		$i = 0;
		while ($row = pg_fetch_assoc($res)) {
			if ($j > 0) echo ",";
			echo "\t{\n";
			echo "\t\t\"tipo\": \"agente\",\n";
			echo "\t\t\"id\": " . $row['id'] . ",\n";
			echo "\t\t\"progressivo\": " . $row['progressivo'] . ",\n";
			echo "\t\t\"ora\": \"" . $row['ora'] . "\",\n";
			echo "\t\t\"cassa\": \"" . $row['cassa'] . "\",\n";
			echo "\t\t\"differenza\": \"" . $row['differenza'] . "\",\n";
			echo "\t\t\"righemodificate\": " . $row['righemodificate'] . ",\n";
			echo "\t\t\"tipo_pagamento\": \"" . $row['tipo_pagamento'] . "\"\n";
			echo "\t}" . ($i < pg_num_rows($res) - 1 ? "," : "") . "\n";
			$i++;
		}
		echo "]";
		break;
	case 'statistiche':
		echo statistiche($questoturno);
		break;
	case 'evasionirecenti':
		$res = pg_query($conn, "select ordini.id, ordini.data, evasioni.stato, evasioni.ora from ordini inner join evasioni on ordini.id = evasioni.id_ordine where " . infoturno() . " and evasioni.ora is not null order by evasioni.ora desc;");
		echo "[\n";
		$i = 0;
		while ($row = pg_fetch_assoc($res)) {
			echo "\t{\n";
			echo "\t\t\"id\": " . $row['id'] . ",\n";
			echo "\t\t\"tipo\": \"" . ($row['stato'] == 1 ? "bar" : "cucina") . "\",\n";
			$diff = strtotime(date("Y-m-d H:i:s.u")) - strtotime(date("Y-m-d") . ' ' . $row['ora']);
			echo "\t\t\"ora\": \"" . (abs($diff / 60) >= 60 ? round($diff / 3600) . ' or' . (round($diff / 3600) == 1 ? 'a' : 'e') . ', ' : '') . (round($diff / 60) % 60) . ' minut' . ((round($diff / 60) % 60) == 1 ? 'o' : 'i') . " fa\"\n";
			echo "\t}" . ($i < pg_num_rows($res) - 1 ? "," : "") . "\n";
			$i++;
		}
		echo "]";
	default:
		break;
}

pg_close($conn);

?>