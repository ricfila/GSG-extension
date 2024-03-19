<?php

function infoturno() {
	global $data, $data2, $ora;
	return "ordini.data >= '$data' and ordini.data < '$data2' and ordini.ora >= '$ora'" . ($ora == '00:00' ? " and ordini.ora < '17:00'" : "");
}

function statistiche($questoturno, $soloquestoturno = false) {
	global $conn;
	$res = pg_query($conn, "select * from ordini" . ($soloquestoturno ? " where " . infoturno() : "") . " order by data, ora;");
	$data = null;
	$turno = 'pranzo';
	$lista = '<ul id="navbarstat" class="nav nav-pills flex-colum">';
	$giorni = array('Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato');
	$dati = array();
	$tipi = array('bar', 'cucina');
	while ($row = pg_fetch_assoc($res)) {
		if ($row['data'] != $data || ($turno == 'pranzo' && intval(substr($row['ora'], 0, 2)) >= 17)) {
			$data = $row['data'];
			$turno = intval(substr($row['ora'], 0, 2)) < 17 ? 'pranzo' : 'cena';
			$oracomanda = date_create($row['data']);
			$dati[$data . $turno]['titolo'] = $giorni[date_format($oracomanda, 'w')] . ' ' . date_format($oracomanda, 'j') . ' ' . $turno;
			$lista .= '<li class="nav-item w-100"><a class="nav-link' . (($data . $turno) == $questoturno ? ' active' : '') . '" href="#' . $data . $turno . '" >' . $dati[$data . $turno]['titolo'] . '</a></li>';
			$dati[$data . $turno]['bar'] = array('somma' => array(), 'n' => array(), 'min' => null, 'max' => null, 'ne' => 0);
			$dati[$data . $turno]['cucina'] = array('somma' => array(), 'n' => array(), 'min' => null, 'max' => null, 'ne' => 0);
		}
		foreach ($tipi as $tipo) {
			if ($row['id_progressivo_' . $tipo] != null) { // C'è la comanda bar/cucina
				$evasione = riparaEvasione($conn, $row, $tipo);
				if ($evasione == 'ordinato') { // Non evasa
					$dati[$data . $turno][$tipo]['ne'] += 1;
				} else if ($evasione != '') { // Evasa con orario
					$diff = strtotime($row['data'] . ' ' . $evasione) - strtotime($row['data'] . ' ' . $row['ora']);
					
					if (!isset($dati[$data . $turno][$tipo]['somma'][substr($row['ora'], 0, 2)]))
						$dati[$data . $turno][$tipo]['somma'][substr($row['ora'], 0, 2)] = $diff;
					else
						$dati[$data . $turno][$tipo]['somma'][substr($row['ora'], 0, 2)] += $diff;
					
					if (!isset($dati[$data . $turno][$tipo]['n'][substr($row['ora'], 0, 2)]))
						$dati[$data . $turno][$tipo]['n'][substr($row['ora'], 0, 2)] = 1;
					else
						$dati[$data . $turno][$tipo]['n'][substr($row['ora'], 0, 2)] += 1;
					
					if ($dati[$data . $turno][$tipo]['min'] == null)
						$dati[$data . $turno][$tipo]['min'] = $diff;
					else if ($diff < $dati[$data . $turno][$tipo]['min'])
						$dati[$data . $turno][$tipo]['min'] = $diff;
					
					if ($dati[$data . $turno][$tipo]['max'] == null)
						$dati[$data . $turno][$tipo]['max'] = $diff;
					else if ($diff > $dati[$data . $turno][$tipo]['max'])
						$dati[$data . $turno][$tipo]['max'] = $diff;
				}
			}
		}
	}
	$lista .= '</ul><br><i class="bi bi-info-circle-fill"></i> Gli intervalli indicati corrispondono al tempo che intercorre tra l\'emissione dell\'ordine e la sua evasione.';
	$tabs = '<div tabindex="2" style="padding-right: 20px; padding-left: 5px;" data-bs-offset="200" data-bs-target="#navbarstat">';
	$ristrette = array();
	foreach ($dati as $turno => $comande) {
		$tabs .= '<h4 id="' . $turno . '">' . $dati[$turno]['titolo'] . '</h4><table class="table table-hover">';
		$tabs .= '<thead class="table-light"><tr><th></th><th><i class="bi bi-droplet"></i> Comande bar</th><th><i class="bi bi-flag"></i> Comande cucina</th></thead><tbody>';
		
		// Allineamento
		$primocucina = array_key_first($comande['cucina']['somma']);
		$primobar = array_key_first($comande['bar']['somma']);
		while ($primobar < $primocucina) {
			$dati[$turno]['cucina']['somma'][$primocucina - 1] = 0;
			$dati[$turno]['cucina']['n'][$primocucina - 1] = 0;
			$primocucina--;
		}
		while ($primocucina < $primobar) {
			$dati[$turno]['bar']['somma'][$primobar - 1] = 0;
			$dati[$turno]['bar']['n'][$primobar - 1] = 0;
			$primobar--;
		}
		$ultimocucina = array_key_last($comande['cucina']['somma']);
		$ultimobar = array_key_last($comande['bar']['somma']);
		while ($ultimobar > $ultimocucina) {
			$dati[$turno]['cucina']['somma'][$ultimocucina + 1] = 0;
			$dati[$turno]['cucina']['n'][$ultimocucina + 1] = 0;
			$ultimocucina++;
		}
		while ($ultimocucina > $ultimobar) {
			$dati[$turno]['bar']['somma'][$ultimobar + 1] = 0;
			$dati[$turno]['bar']['n'][$ultimobar + 1] = 0;
			$ultimobar++;
		}
		
		$totbar = 0;
		$nbar = 0;
		$totcucina = 0;
		$ncucina = 0;
		for ($i = $primobar; $i <= $ultimobar; $i++) {
			if (!isset($comande['bar']['somma'][$i]) && !isset($comande['cucina']['somma'][$i]))
				continue;
			$tabs .= '<tr><td class="p-2">Ore ' . $i . '</td>' .
			'<td class="p-2"><strong>' . (!isset($comande['bar']['somma'][$i]) || $comande['bar']['somma'][$i] == 0 ? ' - ' : round(($comande['bar']['somma'][$i] / $comande['bar']['n'][$i]) / 60) . ' minuti</strong> ' . quantecomande($comande['bar']['n'][$i])) . '</strong></td>' .
			'<td class="p-2"><strong>' . (!isset($comande['cucina']['somma'][$i]) || $comande['cucina']['somma'][$i] == 0 ? ' - ' : round(($comande['cucina']['somma'][$i] / $comande['cucina']['n'][$i]) / 60) . ' minuti</strong> ' . quantecomande($comande['cucina']['n'][$i])) . '</strong></td>' .
			'</tr>';
			if (isset($comande['bar']['somma'][$i])) {
				$totbar += $comande['bar']['somma'][$i];
				$nbar += $comande['bar']['n'][$i];
			}
			if (isset($comande['cucina']['somma'][$i])) {
				$totcucina += $comande['cucina']['somma'][$i];
				$ncucina += $comande['cucina']['n'][$i];
			}
		}
		$tabs .= '</tbody><tfoot><tr><td class="p-2">Totale</td><td class="p-2"><strong>' . ($nbar == 0 ? '-' : round(($totbar / $nbar) / 60)) . ' minuti</strong> ' . quantecomande($nbar) . '</td><td class="p-2"><strong>' . ($ncucina == 0 ? '-' : round(($totcucina / $ncucina) / 60)) . ' minuti</strong> ' . quantecomande($ncucina) . '</td></tr>';
		if ($turno == $questoturno) {
			$ristrette['bar']['media'] = ($nbar == 0 ? null : ($totbar / $nbar) / 60);
			$ristrette['cucina']['media'] = ($ncucina == 0 ? null : ($totcucina / $ncucina) / 60);
		}
		
		$tabs .= '<tr><td class="p-2">Estremi</td><td class="p-2"><i>Minimo:</i> <strong>' . round($comande['bar']['min'] / 60) . ' minuti</strong> - <i>Massimo:</i> <strong>' . round($comande['bar']['max'] / 60) . ' minuti</strong></td><td class="p-2"><i>Minimo:</i> <strong>' . round($comande['cucina']['min'] / 60) . ' minuti</strong> - <i>Massimo:</i> <strong>' . round($comande['cucina']['max'] / 60) . ' minuti</strong></td></tr>';
		if ($turno == $questoturno) {
			$ristrette['bar']['massimo'] = $comande['bar']['max'] / 60;
			$ristrette['cucina']['massimo'] = $comande['cucina']['max'] / 60;
		}
		
		if ($comande['bar']['ne'] > 0 || $comande['cucina']['ne'] > 0)
			$tabs .= '<tr><td class="p-2">Non evase</td><td class="p-2">' . quantecomande($comande['bar']['ne'], false) . '</td><td class="p-2">' . quantecomande($comande['cucina']['ne'], false) . '</td></tr>';
		$tabs .= '</tfoot></table><br>';
	}
	$tabs .= '</div>';
	if ($soloquestoturno)
		return $ristrette;
	else
		return '<div class="row h-100"><div class="col-3">' . $lista . '</div><div class="col-9" style="height: 100%;"><div class="d-flex flex-column h-100"><div class="flex-grow-1" style="overflow-y: auto; scroll-behavior: smooth;">' . $tabs . '</div></div></div></div>';
}

function quantecomande($num, $corsivo = true) {
	return ($corsivo ? '<i>(' : '') . $num . ' comand' . ($num == 1 ? 'a' : 'e') . ($corsivo ? ')</i>' : '');
}

function riparaEvasione($conn, $row, $tipo) {
	$cod = $tipo == 'bar' ? 1 : 2;
	if ($row['stato_' . $tipo] == 'ordinato' || ($tipo == 'bar' & ($row['esportazione'] == 't' && $row['id_progressivo_cucina'] != null))) {
		return 'ordinato';
	} else {
		$res = pg_query($conn, "select * from evasioni where id_ordine = " . $row['id'] . " and stato = $cod;");
		if (pg_num_rows($res) == 1) {
			return pg_fetch_assoc($res)['ora'];
		} else {
			pg_query($conn, "insert into evasioni (id_ordine, ora, stato) values (" . $row['id'] . ", null, $cod);");
			return '';
		}
	}
}

function ordineevaso($row) {
	return $row['esportazione'] == 't' ? ($row[$row['id_progressivo_cucina'] != null ? 'stato_cucina' : 'stato_bar'] == 'ordinato' ? false : true) : (($row['id_progressivo_cucina'] != null && $row['stato_cucina'] == 'ordinato') || ($row['id_progressivo_bar'] != null && $row['stato_bar'] == 'ordinato') ? false : true);
}

function chiudiTransazione($conn, $ok, $msg = false) {
	if ($ok) {
		if (!pg_query($conn, "COMMIT;"))
			echo 'Operazione riuscita ma salvataggio delle modifiche fallito.';
		else
			echo ($msg ? '<span class="text-success">Operazione completata con successo</span>' : '1');
	} else {
		echo 'Operazione non riuscita.';
		if (!pg_query($conn, "ROLLBACK;"))
			echo ' Annullamento delle modifiche fallito.';
	}
}

function righeAfferite($res) {
	if ($res == false) {
		return false;
	} else {
		echo pg_affected_rows($res) . ' righe afferite<br>';
		return true;
	}
}

?>