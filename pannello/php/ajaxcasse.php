<?php
if (!isset($_GET['a']))
	exit;
if (!isset($_COOKIE['logincasse']) && $_GET['a'] != 'listacasse')
	exit;

require "../../connect.php";

$conn = pg_connect((filter_var($server, FILTER_VALIDATE_IP) ? "hostaddr" : "host") . "=$server port=$port dbname=$dbname user=$user password=$password connect_timeout=5") or die('Connessione al database non riuscita.');
if (pg_connection_status($conn) == PGSQL_CONNECTION_BAD) {
	echo 'Errore di connessione al database.';
}

include "function.php";

foreach ($_GET as $k => $v) {
	if (!is_array($v))
		$$k = pg_escape_string($conn, $v);
	else
		$$k = $v;
}
if (isset($_COOKIE['logincasse']))
	$logincasse = pg_escape_string($conn, $_COOKIE['logincasse']);
setlocale(LC_ALL, 'it_IT');

switch ($a) {
	case 'ultimiordini':
		$res = pg_query($conn, "select * from ordini where " . infoturno() . " order by cassa, ora desc;");
		if (pg_num_rows($res) > 0) {
			echo "[\n";
			$cassa = null;
			$i = $evasi = 0;
			while ($row = pg_fetch_assoc($res)) {
				if ($row['cassa'] != $cassa || (empty($row['cassa']) && $i == 0)) {
					if ($cassa != null) {
						echo "\t\t],\n";
						echo "\t\t\"totale\": " . $i . ",\n";
						echo "\t\t\"evasi\": " . $evasi . "\n";
						echo "\t},\n";
					}
					$cassa = $row['cassa'];
					echo "\t{\n";
					echo "\t\t\"cassa\": \"" . $cassa . "\",";
					echo "\t\t\"ordini\": [\n";
					$i = 0;
					$evasi = 0;
				} else {
					echo ",\n";
				}
				echo "\t\t\t{\n";
				echo "\t\t\t\t\"id\": " . $row['id'] . ",\n";
				echo "\t\t\t\t\"progressivo\": " . $row['progressivo'] . ",\n";
				echo "\t\t\t\t\"ora\": \"" . $row['ora'] . "\",\n";
				echo "\t\t\t\t\"cliente\": \"" . $row['cliente'] . "\",\n";
				echo "\t\t\t\t\"esportazione\": " . ($row['esportazione'] == 't' ? 'true' : 'false') . ",\n";
				$evaso = ordineevaso($row);
				echo "\t\t\t\t\"evaso\": " . ($evaso ? "true" : "false") . "\n";
				if ($evaso)
					$evasi++;
				echo "\t\t\t}";
				$i++;
			}
			echo "\t\t],\n";
			echo "\t\t\"totale\": " . $i . ",\n";
			echo "\t\t\"evasi\": " . $evasi . "\n";
			echo "\t}\n";
			echo "]";
		} else
			echo "[]";
		break;
	case 'righecomanda':
		if ($identificatocon == 'ID')
			$id = $num;
		else {
			$res = pg_query($conn, "select id from ordini where progressivo = $num and " . infoturno() . ";");
			if (pg_num_rows($res) == 1) {
				$id = pg_fetch_assoc($res)['id'];
			} else {
				echo 'Ordine non trovato. Assicurarsi che sia stato emesso in questo turno.';
				break;
			}
		}
		
		$res = pg_query($conn, "select * from ordini where id = $id;");
		if (pg_num_rows($res) != 1) {
			echo 'Ordine inesistente';
			break;
		}
		$giorni = array('dom', 'lun', 'mar', 'mer', 'gio', 'ven', 'sab');
		$mesi = array('gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic');
		
		$row = pg_fetch_assoc($res);
		$datacomanda = date_create($row['data']);
		echo "[\n";
		echo "\t{\n";
		echo "\t\t\"tipo\": \"ordine\",\n";
		echo "\t\t\"id\": " . $row['id'] . ",\n";
		echo "\t\t\"progressivo\": " . $row['progressivo'] . ",\n";
		echo "\t\t\"tavolo\": \"" . addcslashes($row['numeroTavolo'], '"') . "\",\n";
		echo "\t\t\"data\": \"" . $giorni[date_format($datacomanda, 'w')] . ' ' . date_format($datacomanda, 'j') . ' ' . $mesi[date_format($datacomanda, 'n') - 1] . ' ' . date_format($datacomanda, 'Y') . "\",\n";
		echo "\t\t\"ora\": \"" . $row['ora'] . "\",\n";
		echo "\t\t\"cliente\": \"" . addcslashes($row['cliente'], '"') . "\",\n";
		echo "\t\t\"coperti\": \"" . $row['coperti'] . "\",\n";
		echo "\t\t\"esportazione\": " . ($row['esportazione'] == 't' ? 'true' : 'false') . ",\n";
		echo "\t\t\"totalePagato\": " . $row['totalePagato'] . ",\n";
		echo "\t\t\"resto\": " . $row['resto'] . ",\n";
		echo "\t\t\"cassa\": \"" . $row['cassa'] . "\",\n";
		echo "\t\t\"tipo_pagamento\": \"" . $row['tipo_pagamento'] . "\",\n";
		echo "\t\t\"menu_omaggio\": " . ($row['menu_omaggio'] == 't' ? 'true' : 'false') . ",\n";
		echo "\t\t\"note\": \"" . addcslashes($row['note'], '"') . "\",\n";
		echo "\t\t\"questoturno\": " . ($identificatocon == 'ID' ? (pg_num_rows(pg_query($conn, "select * from ordini where id = " . $row['id'] . " and " . infoturno() . ";")) == 1 ? "true" : "false") : "true") . ",\n";
		
		$res = pg_query($conn, 'select * from tipo_pagamenti;');
		echo "\t\t\"pagamenti\": [\n";
		$i = 0;
		while ($row = pg_fetch_assoc($res)) {
			echo "\t\t\t\"" . $row['tipo_pagamento'] . "\"" . ($i < pg_num_rows($res) - 1 ? "," : "") . "\n";
			$i++;
		}
		echo "\t\t],\n";
		
		$res = pg_query($conn, 'select * from sconti where is_percentuale = false;');
		echo "\t\t\"sconti\": [\n";
		$i = 0;
		while ($row = pg_fetch_assoc($res)) {
			echo "\t\t\t{\"id\": " . $row['id'] . ", \"descrizione\": \"" . $row['descrizione'] . "\", \"valore\": " . $row['valore'] . "}"	. ($i < pg_num_rows($res) - 1 ? "," : "") . "\n";
			$i++;
		}
		echo "\t\t]\n";
		echo "\t}";
		
		// Righe articoli
		$res = pg_query($conn, "select * from righe join righe_articoli on righe.id = righe_articoli.id_riga where id_ordine = $id and type = 'riga_articolo' order by righe.id;");
		while ($row = pg_fetch_assoc($res)) {
			echo ",\n";
			echo "\t{\n";
			echo "\t\t\"tipo\": \"riga_articolo\",\n";
			echo "\t\t\"id\": " . $row['id_riga'] . ",\n";
			echo "\t\t\"quantita\": " . $row['quantita'] . ",\n";
			echo "\t\t\"descrizione\": \"" . addcslashes($row['descrizione'], '"') . "\",\n";
			echo "\t\t\"prezzo_unitario\": " . $row['prezzo'] . ",\n";
			echo "\t\t\"tipologia\": \"" . addcslashes($row['desc_tipologia'], '"') . "\",\n";
			echo "\t\t\"note\": \"" . addcslashes($row['note'], '"') . "\"\n";
			echo "\t}";
		}
		
		// Righe sconti
		// Vengono gestiti solo gli sconti a quota fissa, bisogna fare delle prove anche con gli sconti percentuali
		$res = pg_query($conn, "select righe_sconto.id_riga, righe_sconto.valore, righe.descrizione from righe join righe_sconto on righe.id = righe_sconto.id_riga where id_ordine = $id and type = 'riga_sconto' order by righe.id;");
		while ($row = pg_fetch_assoc($res)) {
			echo ",\n";
			echo "\t{\n";
			echo "\t\t\"tipo\": \"riga_sconto\",\n";
			echo "\t\t\"id\": " . $row['id_riga'] . ",\n";
			echo "\t\t\"valore\": " . $row['valore'] . ",\n";
			echo "\t\t\"descrizione\": \"" . addcslashes($row['descrizione'], '"') . "\"\n";
			echo "\t}";
		}
		echo "\n";
		echo "]";
		break;
	case 'salvaordine':
		if (!pg_query($conn, "BEGIN")) {
			echo 'Transazione non avviata.';
		} else {
			$ok = pg_query($conn, "update ordini set \"numeroTavolo\" = '$tavolo', cliente = '$cliente', coperti = " . (empty($coperti) ? "null" : $coperti) . ", esportazione = $esportazione, \"totalePagato\" = $totale, resto = 0, cassa = '$cassa', tipo_pagamento = '$tipo_pagamento', menu_omaggio = $menu_omaggio where id = $id;");
			if (isset($righe)) {
				$righemod = count($righe);
				foreach ($righe as $idriga => $qta) {
					$idrigaarticolo = pg_fetch_assoc(pg_query($conn, "select id from righe_articoli where id_riga = $idriga;"))['id'];
					if ($qta == 0) {
						$ok = $ok && pg_query($conn, "delete from righe_ingredienti where id_riga_articolo = $idrigaarticolo;");
						$ok = $ok && pg_query($conn, "delete from righe_articoli where id_riga = $idriga;");
						$ok = $ok && pg_query($conn, "delete from righe where id = $idriga;");
					} else {
						$qtavecchia = pg_fetch_assoc(pg_query($conn, "select quantita from righe where id = $idriga;"))['quantita'];
						$ok = $ok && pg_query($conn, "update righe_ingredienti set quantita = (righe_ingredienti.quantita * $qta / $qtavecchia) where id_riga_articolo = $idrigaarticolo;");
						$ok = $ok && pg_query($conn, "update righe set quantita = $qta where id = $idriga;");
					}
				}
			} else
				$righemod = 0;
			if (isset($righenote)) {
				foreach ($righenote as $idriga => $nota) {
					if ($idriga == 'noteordine') {
						$ok = $ok && pg_query($conn, "update ordini set note = '" . pg_escape_string($conn, $nota) . "' where id = $id;");
					} else {
						$ok = $ok && pg_query($conn, "update righe_articoli set note = '" . pg_escape_string($conn, $nota) . "' where id_riga = $idriga;");
					}
				}
			}
			if (isset($delsconti))
				foreach($delsconti as $idrigas) {
					$ok = $ok && pg_query("delete from righe_sconto where id_riga = $idrigas;");
					$ok = $ok && pg_query("delete from righe where id = $idrigas;");
				}
			/*
			if (isset($addsconti))
				foreach($addsconti as $idrigas) {
					$ok = $ok && pg_query("insert into righe () values () returning id;");
					
				}
			*/
			
			//$_SERVER['REMOTE_ADDR']
			$ok = $ok && pg_query($conn, "insert into modifiche (id_ordine, ora, agente, differenza, righeModificate, cassaVecchia, cassaNuova) values ($id, LOCALTIME, '$logincasse', " . ($totale - $totalevecchio) . ", $righemod, '$cassavecchia', '$cassa');");
			chiudiTransazione($conn, $ok);
		}
		break;
	case 'ultimevendite':
		$ore = (int)($minuti / 60);
		$minuti = (int)($minuti % 60);
		$res = pg_query($conn, "SELECT righe_ingredienti.descrizionebreve as descrizionebreve, ceil(sum(righe_ingredienti.quantita::decimal / COALESCE(dati_ingredienti.divisore, 1))) as qta, CASE WHEN righe_articoli.copia_cucina THEN 'cucina' ELSE 'bar' END as copia, count(DISTINCT ordini.id) as comande, COALESCE(dati_ingredienti.divisore, 1) as divisore
		FROM righe_ingredienti
		JOIN righe_articoli ON righe_ingredienti.id_riga_articolo = righe_articoli.id
		JOIN righe ON righe_articoli.id_riga = righe.id
		JOIN ordini ON righe.id_ordine = ordini.id
		JOIN ingredienti ON righe_ingredienti.descrizionebreve = ingredienti.descrizionebreve
		LEFT JOIN dati_ingredienti ON ingredienti.id = dati_ingredienti.id_ingrediente
		WHERE " . infoturno() . " and ordini.ora > LOCALTIME - '$ore:$minuti' and (
		CASE (CASE WHEN (CASE WHEN righe_articoli.copia_cucina THEN 'cucina' ELSE 'bar' END) = 'cucina' THEN ordini.stato_cucina ELSE ordini.stato_bar END)
			WHEN 'evaso' THEN 0 ELSE 1
		END) = 1
		GROUP BY righe_ingredienti.descrizionebreve, righe_articoli.copia_cucina, dati_ingredienti.divisore;");
		echo "[";
		$inizio = true;
		while ($row = pg_fetch_assoc($res)) {
			if (!$inizio)
				echo ", ";
			else
				$inizio = false;
			echo "{\"descrizione\": \"" . $row['descrizionebreve'] . "\",";
			echo "\"qta\": " . $row['qta'] . ",";
			echo "\"copia\": \"" . $row['copia'] . "\",";
			echo "\"comande\": " . $row['comande'] . ",";
			echo "\"divisore\": " . $row['divisore'] . "}";
		}
		echo "]";
		break;
	case 'statisticheristrette':
		$ristrette = statistiche($questoturno, true);
		echo '<small><i class="bi bi-droplet"></i> Bar: ' . (isset($ristrette['bar']['media']) && $ristrette['bar']['media'] != null ? round($ristrette['bar']['media']) . ' minuti, al massimo ' . round($ristrette['bar']['massimo']) . ' minuti' : 'nessuna comanda evasa') . '<br>';
		echo '<i class="bi bi-flag"></i> Cucina: ' . (isset($ristrette['cucina']['media']) && $ristrette['cucina']['media'] != null ? round($ristrette['cucina']['media']) . ' minuti, al massimo ' . round($ristrette['cucina']['massimo']) . ' minuti' : 'nessuna comanda evasa') . '</small>';
		break;
	case 'sequenze':
		$res = pg_query($conn, "SELECT fixsequences();");
		if ($res != false) {
			echo pg_fetch_assoc($res)['fixsequences'] . '<br>';
		} else
			echo '<span class="text-danger">Richiesta fallita per un motivo sicuramente preoccupante: </span>' . pg_last_error($conn);
		
		break;
	case 'ripristinagiacenze':
		if (!pg_query($conn, "BEGIN;")) {
			echo 'Transazione non avviata.';
		} else {
			$ok = true;
			
			$res = pg_query($conn, "UPDATE articoli SET id_giacenza = null;");
			$ok = $ok && righeAfferite($res);
			
			$res = pg_query($conn, "UPDATE ingredienti SET id_giacenza = null;");
			$ok = $ok && righeAfferite($res);
			
			$res = pg_query($conn, "DELETE FROM giacenze;");
			$ok = $ok && righeAfferite($res);
			
			chiudiTransazione($conn, $ok, true);
		}
		break;
	case 'svuotaevasioni':
		$res = pg_query($conn, "DELETE FROM passaggi_stato;");
		if (!righeAfferite($res))
			echo '<span class="text-danger">Qualcosa è andato storto: </span>' . pg_last_error($conn);
		
		break;
	case 'ordinaarticoli':
		if (!pg_query($conn, "BEGIN;")) {
			echo 'Transazione non avviata.';
		} else {
			$ok = true;
			$res = pg_query($conn, "SELECT articoli.id as id, tipologie.posizione as posizione FROM articoli JOIN tipologie ON articoli.id_tipologia = tipologie.id ORDER BY tipologie.posizione" . (!empty($order) && $order != null ? ", articoli.$order" : "") . ";");
			$ok = $ok && righeAfferite($res);
			
			$i = 1;
			while ($row = pg_fetch_assoc($res)) {
				$ok = $ok && pg_query($conn, "UPDATE articoli SET posizione = $i WHERE id = " . $row['id'] . ";");
				$i++;
			}
			chiudiTransazione($conn, $ok, true);
		}
		break;
	case 'listacasse':
		$res = pg_query($conn, "SELECT cassa FROM public.ordini GROUP BY cassa ORDER BY cassa;");
		echo '[';
		$i = 0;
		while ($row = pg_fetch_assoc($res)) {
			echo ($i != 0 ? ', ' : '') . '"' . $row['cassa'] . '"';
			$i++;
		}
		echo ']';
		break;
	case 'ingredienti':
		$res = pg_query($conn, "SELECT ingredienti.id, ingredienti.descrizione, ingredienti.descrizionebreve, dati_ingredienti.settore, dati_ingredienti.divisore, dati_ingredienti.monitora, giacenze.scorta_iniziale as giacenza
			FROM ingredienti
			LEFT JOIN dati_ingredienti ON ingredienti.id = dati_ingredienti.id_ingrediente
			LEFT JOIN giacenze ON ingredienti.id_giacenza = giacenze.id
			ORDER BY $orderby;");
		$out = array();
		while ($row = pg_fetch_assoc($res)) {
			$out[] = $row;
		}
		echo json_encode($out);
		break;
	case 'salvainfoing':
		$ok = true;
		if ($id == null) {
			$res = pg_query($conn, "INSERT INTO ingredienti (descrizione, descrizionebreve, id_giacenza, prezzo) VALUES ('$descrizione', $descrizionebreve', null, 0) RETURNING id;");
			if ($res != false) {
				$id = pg_fetch_assoc($res)['id'];
				$ok = $ok && pg_query($conn, "INSERT INTO dati_ingredienti (id_ingrediente, divisore, settore, monitora) VALUES ($id, $divisore, '$settore', $monitora);");
			}
		} else {
			$ok = $ok && pg_query($conn, "UPDATE ingredienti SET descrizione = '$descrizione', descrizionebreve = '$descrizionebreve' WHERE id = $id;");
			if (pg_num_rows(pg_query("SELECT * FROM dati_ingredienti WHERE id_ingrediente = $id;")) == 1) {
				$ok = $ok && pg_query($conn, "UPDATE dati_ingredienti SET divisore = $divisore, settore = '$settore', monitora = $monitora WHERE id_ingrediente = $id;");
			} else {
				$ok = $ok && pg_query($conn, "INSERT INTO dati_ingredienti (id_ingrediente, divisore, settore, monitora) VALUES ($id, $divisore, '$settore', $monitora);");
			}
		}
		if ($ok)
			echo '1';
		else
			echo pg_last_error($conn);
		break;
	case 'salvagiacenza':
		$infinito = ($giacenza == '' || $giacenza == null);
		if ($giacenza != null) {
			$divisore = pg_fetch_assoc(pg_query($conn, "SELECT * FROM dati_ingredienti WHERE id_ingrediente = $id;"))['divisore'];
			$giacenza = $giacenza * $divisore;
		}
		
		$id_giacenza = pg_fetch_assoc(pg_query($conn, "SELECT * FROM ingredienti WHERE id = $id;"))['id_giacenza'];
		//$id_giacenza = null;

		$ok = true;
		if ($id_giacenza == null) {
			$res = pg_query($conn, "INSERT INTO giacenze (scorta_iniziale, data_disponibilita) VALUES (" . ($infinito ? "null" : $giacenza) . ", " . ($infinito ? "null" : "LOCALTIMESTAMP") . ") RETURNING id;");
			if ($res != false) {
				$id_giacenza = pg_fetch_assoc($res)['id'];
				$ok = $ok && pg_query($conn, "UPDATE ingredienti SET id_giacenza = $id_giacenza WHERE id = $id;");
			}
		} else {
			$ok = $ok && pg_query($conn, "UPDATE giacenze SET scorta_iniziale = " . ($infinito ? "null" : $giacenza) . ", data_disponibilita = " . ($infinito ? "null" : "LOCALTIMESTAMP") . " WHERE id = $id_giacenza;");
		}
		if ($ok)
			echo '1';
		else
			echo pg_last_error($conn);
		break;
	case 'qtavendute':
		$res = pg_query($conn, "SELECT * FROM giacenze JOIN ingredienti ON giacenze.id = ingredienti.id_giacenza WHERE ingredienti.id = $id;");
		if (pg_num_rows($res) == 0) {
			echo 0; break;
		}
		$row = pg_fetch_assoc($res);
		if ($row['scorta_iniziale'] == 0) {
			echo 0; break;
		}
		$data = explode(" ", $row['data_disponibilita'])[0];
		$ora = explode(" ", $row['data_disponibilita'])[1];
		echo pg_fetch_assoc(pg_query($conn, "SELECT COALESCE(SUM(righe_ingredienti.quantita), 0) as somma
			FROM righe_ingredienti
			JOIN righe_articoli ON righe_ingredienti.id_riga_articolo = righe_articoli.id
			JOIN righe ON righe_articoli.id_riga = righe.id
			JOIN ordini ON righe.id_ordine = ordini.id
			WHERE righe_ingredienti.descrizionebreve = '" . $row['descrizionebreve'] . "' and ordini.data >= '$data' and ordini.ora >= '$ora';"))['somma'];
	default:
		break;
}

pg_close($conn);

?>