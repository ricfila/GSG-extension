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
			$num = pg_num_rows(pg_query($conn, "select * from passaggi_stato where id_ordine = $id and stato = $cod;"));
			if ($azione == 'evaso') {
				if ($num == 0) {
					$ok = $ok && pg_query($conn, "insert into passaggi_stato (id_ordine, ora, stato) values ($id, " . ($salvaora == 'true' ? "LOCALTIME" : "null") . ", $cod);");
				} else {
					$ok = $ok && pg_query($conn, "update passaggi_stato set ora = " . ($salvaora == 'true' ? "LOCALTIME" : "null") . " where id_ordine = $id and stato = $cod;");
				}
			} else {
				if ($num > 0) {
					$ok = $ok && pg_query($conn, "delete from passaggi_stato where id_ordine = $id and stato = $cod;");
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
		$res = pg_query($conn, "SELECT ordini.data, public.pranzo_cena(ora) AS pranzo_cena, cassa, tipo_pagamento, sum(\"totalePagato\" - resto) AS importo_totale
			FROM ordini
			GROUP BY cassa, ordini.data, (pranzo_cena(ora)), tipo_pagamento
			ORDER BY ordini.data, (pranzo_cena(ora)) DESC, cassa, tipo_pagamento;");
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
		$res = pg_query($conn, "SELECT ordini.id, ordini.progressivo, modifiche.ora, modifiche.agente, modifiche.differenza, modifiche.righemodificate, ordini.tipo_pagamento FROM modifiche join ordini on modifiche.id_ordine = ordini.id WHERE " . infoturno() . " and modifiche.differenza <> 0 and modifiche.agente <> modifiche.cassanuova and ordini.cassa = '$cassa' ORDER BY ordini.tipo_pagamento;");
		echo "[\n";
		$i = 0;
		while ($row = pg_fetch_assoc($res)) {
			if ($i > 0) echo ",";
			echo "\t{\n";
			echo "\t\t\"tipo\": \"esterno\",\n";
			echo "\t\t\"id\": " . $row['id'] . ",\n";
			echo "\t\t\"progressivo\": " . $row['progressivo'] . ",\n";
			echo "\t\t\"ora\": \"" . $row['ora'] . "\",\n";
			echo "\t\t\"agente\": \"" . $row['agente'] . "\",\n";
			echo "\t\t\"differenza\": \"" . $row['differenza'] . "\",\n";
			echo "\t\t\"righemodificate\": " . $row['righemodificate'] . ",\n";
			echo "\t\t\"tipo_pagamento\": \"" . $row['tipo_pagamento'] . "\"\n";
			echo "\t}\n";
			$i++;
		}
		
		$res = pg_query($conn, "SELECT ordini.id, ordini.progressivo, modifiche.ora, ordini.cassa, modifiche.differenza, modifiche.righemodificate, ordini.tipo_pagamento FROM modifiche join ordini on modifiche.id_ordine = ordini.id WHERE " . infoturno() . " and modifiche.differenza <> 0 and modifiche.agente <> modifiche.cassanuova and modifiche.agente = '$cassa' ORDER BY ordini.tipo_pagamento;");
		while ($row = pg_fetch_assoc($res)) {
			if ($i > 0) echo ",";
			echo "\t{\n";
			echo "\t\t\"tipo\": \"agente\",\n";
			echo "\t\t\"id\": " . $row['id'] . ",\n";
			echo "\t\t\"progressivo\": " . $row['progressivo'] . ",\n";
			echo "\t\t\"ora\": \"" . $row['ora'] . "\",\n";
			echo "\t\t\"cassa\": \"" . $row['cassa'] . "\",\n";
			echo "\t\t\"differenza\": \"" . $row['differenza'] . "\",\n";
			echo "\t\t\"righemodificate\": " . $row['righemodificate'] . ",\n";
			echo "\t\t\"tipo_pagamento\": \"" . $row['tipo_pagamento'] . "\"\n";
			echo "\t}\n";
			$i++;
		}
		echo "]";
		break;
	case 'statistiche':
		echo statistiche($questoturno);
		break;
	case 'evasionirecenti':
		$res = pg_query($conn, "select ordini.id, ordini.data, passaggi_stato.stato, passaggi_stato.ora from ordini inner join passaggi_stato on ordini.id = passaggi_stato.id_ordine where " . infoturno() . " and passaggi_stato.ora is not null order by passaggi_stato.ora desc;");
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
		break;
	case 'reportarticoli':
		if ($tipo == 'Articoli') { // Report degli articoli
			$query = "SELECT righe.descrizionebase as descrizione, righe_articoli.desc_tipologia as tipologia,
					ordini.data, pranzo_cena(ordini.ora) as turno, SUM(quantita) as qta
				FROM righe
				JOIN ordini ON righe.id_ordine = ordini.id
				JOIN righe_articoli ON righe_articoli.id_riga = righe.id
				JOIN articoli ON righe.descrizionebase = articoli.descrizione ";
			if ($turno == 'true') {
				$query .= "WHERE " . infoturno() . " ";
			}
			$query .= "GROUP BY ordini.data, turno, righe.descrizionebase, righe_articoli.desc_tipologia, articoli.posizione
				ORDER BY articoli.posizione, ordini.data, turno DESC;";
		} else { // Report degli ingredienti
			$query = "SELECT righe_ingredienti.descrizione, dati_ingredienti.settore as tipologia,
					ordini.data, pranzo_cena(ordini.ora) as turno,
					CASE WHEN COALESCE(dati_ingredienti.divisore, 1) = 1 THEN SUM(righe_ingredienti.quantita) ELSE
					ROUND(SUM(COALESCE(righe_ingredienti.quantita::decimal, 0) / dati_ingredienti.divisore), 2) END as qta
				FROM righe_ingredienti
				JOIN ingredienti ON righe_ingredienti.descrizionebreve = ingredienti.descrizionebreve
				JOIN dati_ingredienti ON dati_ingredienti.id_ingrediente = ingredienti.id
				JOIN righe_articoli ON righe_ingredienti.id_riga_articolo = righe_articoli.id
				JOIN righe ON righe_articoli.id_riga = righe.id
				JOIN ordini ON righe.id_ordine = ordini.id " .
				($turno == 'true' ? "WHERE " . infoturno() . " " : "") .
				"GROUP BY ordini.data, turno, righe_ingredienti.descrizione, dati_ingredienti.settore, righe_ingredienti.posizione, dati_ingredienti.divisore
				ORDER BY dati_ingredienti.settore, righe_ingredienti.descrizione, ordini.data, turno DESC;";
		}
		$res = pg_query($conn, $query);
		$out = array();
		$turni = array();
		while ($row = pg_fetch_assoc($res)) {
			$t = $row['data'] . ',' . ($row['turno'] == 'pranzo' ? 0 : 1);
			if (!in_array($t, $turni))
				array_push($turni, $t);
			$out[] = $row;
		}

		$outservizio = array();
		if ($servizio == 'true') {
			$query = array(
				"SELECT ordini.data, pranzo_cena(ordini.ora) as turno, 'Ordini' as descrizione, 'Servizio' as tipologia, count(*) as qta FROM ordini" . ($turno == 'true' ? " WHERE " . infoturno() : "") . " GROUP BY ordini.data, turno ORDER BY ordini.data, turno DESC;", // Ordini
				"SELECT ordini.data, pranzo_cena(ordini.ora) as turno, 'Coperti' as descrizione, 'Servizio' as tipologia, sum(coperti) as qta FROM ordini" . ($turno == 'true' ? " WHERE " . infoturno() : "") . " GROUP BY ordini.data, turno ORDER BY ordini.data, turno DESC;", // Coperti
				"SELECT ordini.data, pranzo_cena(ordini.ora) as turno, 'Asporti' as descrizione, 'Servizio' as tipologia, count(*) as qta FROM ordini WHERE esportazione" . ($turno == 'true' ? " and " . infoturno() : "") . " GROUP BY ordini.data, turno ORDER BY ordini.data, turno DESC;", // Asporti
				"SELECT ordini.data, pranzo_cena(ordini.ora) as turno, 'Contanti' as descrizione, 'Servizio' as tipologia, sum(ordini.\"totalePagato\" - ordini.resto) as qta FROM ordini WHERE tipo_pagamento = 'CONTANTI'" . ($turno == 'true' ? " and " . infoturno() : "") . " GROUP BY ordini.data, turno ORDER BY ordini.data, turno DESC;", // Contanti
				"SELECT ordini.data, pranzo_cena(ordini.ora) as turno, 'POS' as descrizione, 'Servizio' as tipologia, sum(ordini.\"totalePagato\" - ordini.resto) as qta FROM ordini WHERE tipo_pagamento = 'POS'" . ($turno == 'true' ? " and " . infoturno() : "") . " GROUP BY ordini.data, turno ORDER BY ordini.data, turno DESC;", // POS
				"SELECT ordini.data, pranzo_cena(ordini.ora) as turno, 'Incasso totale' as descrizione, 'Servizio' as tipologia, sum(ordini.\"totalePagato\" - ordini.resto) as qta FROM ordini" . ($turno == 'true' ? " WHERE " . infoturno() : "") . " GROUP BY ordini.data, turno ORDER BY ordini.data, turno DESC;" // Totale incassi
			);
			foreach ($query as $q) {
				$res = pg_query($conn, $q);
				while ($row = pg_fetch_assoc($res)) {
					$outservizio[] = $row;
				}
			}
		}
		echo json_encode(array("turni"=> $turni, "vendite"=> $out, "servizio"=> $outservizio));
		break;
	default:
		break;
}

pg_close($conn);

?>