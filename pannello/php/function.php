<?php

function infoturno() {
	global $data, $data2, $ora;
	return "ordini.data >= '$data' and ordini.data < '$data2' and ordini.ora >= '$ora'" . ($ora == '00:00' ? " and ordini.ora < '17:00'" : "");
}

function infoturnopalmare() {
	global $conn;
	$dt = explode(" ", pg_fetch_assoc(pg_query($conn, "select * from shiftstart;"))['datetimestart']);
	$data2 = new DateTime($dt[0]);
	$data2 = $data2->modify('+1 day')->format('Y-m-d');
	return "ordini.data >= '" . $dt[0] . "' and ordini.data < '$data2' and ordini.ora > '" . $dt[1] . "'" . ($dt[1] == '00:00:00' ? " and ordini.ora < '17:00'" : "");
}

function statistiche($questoturno, $soloquestoturno = false) {
	global $conn;
	$res = pg_query($conn, "select * from ordini" . ($soloquestoturno ? " where " . infoturno() : "") . " order by data, ora;");
	$data = null;
	$turno = 'pranzo';
	$lista = '<div class="nav flex-colum nav-pills" role="tablist" aria-orientation="vertical">';
	$giorni = array('Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato');
	$dati = array();
	$tipi = array('bar', 'cucina');
	$maxdiff = array('bar' => 0, 'cucina' => 0);
	$max_x = array('bar' => '', 'cucina' => '');
	$datigraf = array();
	$arrotonda = 3;
	while ($row = pg_fetch_assoc($res)) {
		if ($row['data'] != $data || ($turno == 'pranzo' && intval(substr($row['ora'], 0, 2)) >= 17)) {  // Cambio di turno
			$data = $row['data'];
			$turno = intval(substr($row['ora'], 0, 2)) < 17 ? 'pranzo' : 'cena';
			$oracomanda = date_create($row['data']);
			$dati[$data . $turno]['titolo'] = $giorni[date_format($oracomanda, 'w')] . ' ' . date_format($oracomanda, 'j') . ' ' . $turno;
			$attivo = ($data . $turno) == $questoturno;
			$lista .= '<button class="nav-link w-100' . ($attivo ? ' active' : '') . '" id="v-pills-' . $data . $turno . '-tab" data-bs-toggle="pill" data-bs-target="#v-pills-' . $data . $turno . '" type="button" role="tab" aria-controls="v-pills-' . $data . $turno . '" aria-selected="' . ($attivo ? 'true' : 'false') . '" style="text-align: left;">' . $dati[$data . $turno]['titolo'] . '</button>';
			$dati[$data . $turno]['bar'] = array('somma' => array(), 'n' => array(), 'min' => null, 'max' => null, 'ne' => 0);
			$dati[$data . $turno]['cucina'] = array('somma' => array(), 'n' => array(), 'min' => null, 'max' => null, 'ne' => 0);
		}
		foreach ($tipi as $tipo) {
			if ($row['id_progressivo_' . $tipo] != null) { // C'è la comanda bar/cucina
				$evasione = riparaEvasione($conn, $row, $tipo);
				if ($evasione == 'ordinato') { // Non evasa
					$dati[$data . $turno][$tipo]['ne'] += 1;
				} else if ($evasione != '') { // Evasa con orario
					$res2 = pg_query($conn, "select * from evasioni where id_ordine = " . $row['id'] . " and (stato = 0 or stato = 10);");
					if (pg_num_rows($res2) == 1)
						$ora1 = pg_fetch_assoc($res2)['ora'];
					else
						$ora1 = $row['ora'];
					$diff = strtotime($row['data'] . ' ' . $evasione) - strtotime($row['data'] . ' ' . $ora1);
					
					if (!isset($dati[$data . $turno][$tipo]['somma'][substr($ora1, 0, 2)]))
						$dati[$data . $turno][$tipo]['somma'][substr($ora1, 0, 2)] = $diff;
					else
						$dati[$data . $turno][$tipo]['somma'][substr($ora1, 0, 2)] += $diff;
					
					if (!isset($dati[$data . $turno][$tipo]['n'][substr($ora1, 0, 2)]))
						$dati[$data . $turno][$tipo]['n'][substr($ora1, 0, 2)] = 1;
					else
						$dati[$data . $turno][$tipo]['n'][substr($ora1, 0, 2)] += 1;
					
					if ($dati[$data . $turno][$tipo]['min'] == null)
						$dati[$data . $turno][$tipo]['min'] = $diff;
					else if ($diff < $dati[$data . $turno][$tipo]['min'])
						$dati[$data . $turno][$tipo]['min'] = $diff;
					
					if ($dati[$data . $turno][$tipo]['max'] == null)
						$dati[$data . $turno][$tipo]['max'] = $diff;
					else if ($diff > $dati[$data . $turno][$tipo]['max'])
						$dati[$data . $turno][$tipo]['max'] = $diff;
					
					$minuti = round($diff / (60 * $arrotonda));
					if (!isset($datigraf[$tipo][$data . $turno][$minuti]))
						$datigraf[$tipo][$data . $turno][$minuti] = 1;
					else
						$datigraf[$tipo][$data . $turno][$minuti] += 1;
					
					if ($minuti > $maxdiff[$tipo]) {
						$maxdiff[$tipo] = $minuti;
						$max_x[$tipo] = $data . $turno;
					}
				}
			}
		}
	}
	$lista .= '<button class="nav-link w-100" id="v-pills-complessive-tab" data-bs-toggle="pill" data-bs-target="#v-pills-complessive" type="button" role="tab" aria-controls="v-pills-complessive" aria-selected="false" style="text-align: left;">Statistiche complessive</button>';
	$lista .= '</div><br><i class="bi bi-info-circle-fill"></i> Gli intervalli indicati corrispondono al tempo che intercorre tra l\'associazione dell\'ordine al tavolo e la sua evasione.';
	$tabs = '<div class="tab-content" style="padding-right: 20px; padding-left: 5px;">';
	$ristrette = array();
	$graf = array();
	foreach ($dati as $turno => $comande) {
		$tabs .= '<div id="v-pills-' . $turno . '" class="tab-pane fade' . ($turno == $questoturno ? ' show active' : '') . '" role="tabpanel" aria-labelledby="v-pills-' . $turno . '-tab">';
		$tabs .= '<h4>' . $dati[$turno]['titolo'] . '</h4>';
		
		// Grafici
		$tabs .= '<p>I seguenti grafici rappresentano, ad intervalli di ' . $arrotonda . ' minuti, quante comande sono state evase nei tempi indicati in basso.</p><div class="row"><div class="col-6"><strong>Comande bar evase</strong><br><canvas id="chartbar' . $turno . '" style="width: 100%;"></canvas></div><div class="col-6"><strong>Comande cucina evase</strong><canvas id="chartcucina' . $turno . '" style="width: 100%;"></canvas></div></div>';
		$tabs .= '<script>';
		foreach ($tipi as $tipo) {
			$max = round($comande[$tipo]['max'] / (60 * $arrotonda));
			$graf[$tipo][$turno]['x'] = '[';
			$graf[$tipo][$turno]['y'] = '[';
			for ($i = 0; $i <= $max; $i++) {
				$graf[$tipo][$turno]['x'] .= ($i * $arrotonda) . ($i < $max ? ', ' : '');
				$graf[$tipo][$turno]['y'] .= (isset($datigraf[$tipo][$turno][$i]) ? $datigraf[$tipo][$turno][$i] : '0') . ($i < $max ? ', ' : '');
			}
			$graf[$tipo][$turno]['x'] .= ']';
			$graf[$tipo][$turno]['y'] .= ']';
			$tabs .= 'new Chart("chart' . $tipo . $turno . '", {
				type: "line",
				data: {
					labels: ' . $graf[$tipo][$turno]['x'] . ',
					datasets: [{ 
						data: ' . $graf[$tipo][$turno]['y'] . ',
						borderColor: "' . ($tipo == 'bar' ? '#66ccff' : '#ffb366') . '",
						label: "Comande ' . $tipo . '",
						fill: false
					}]
				},
				options: {
					legend: {display: false},
					scales: {
						xAxes: [{
							scaleLabel: {
								display: true,
								labelString: "Minuti"
							}
						}]
					}
				}
			});';
		}
		$tabs .= '</script>';
		
		// Medie
		$tabs .= '<br><h4>Tempi medi</h4><table class="table table-hover">';
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
		$dati[$turno]['bar']['media'] = ($nbar == 0 ? '-' : round(($totbar / $nbar) / 60));
		$dati[$turno]['cucina']['media'] = ($ncucina == 0 ? '-' : round(($totcucina / $ncucina) / 60));
		$tabs .= '</tbody><tfoot><tr><td class="p-2">Totale</td><td class="p-2"><strong>' . $dati[$turno]['bar']['media'] . ' minuti</strong> ' . quantecomande($nbar) . '</td><td class="p-2"><strong>' . $dati[$turno]['cucina']['media'] . ' minuti</strong> ' . quantecomande($ncucina) . '</td></tr>';
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
		$tabs .= '</tfoot></table></div>';
	}
	$tabs .= '<div id="v-pills-complessive" class="tab-pane fade" role="tabpanel" aria-labelledby="v-pills-complessive-tab"><h4>Statistiche complessive</h4><br>';
	if ($max_x['bar'] == '' || $max_x['cucina'] == '') {
		$tabs .= 'Le statistiche complessive non sono calcolabili.</div>';
	} else {
		$tabs .= '<strong>Comande bar evase</strong><br><canvas id="chartbar" style="width: 100%;"></canvas><br>';
		$tabs .= '<strong>Comande cucina evase</strong><br><canvas id="chartcucina" style="width: 100%;"></canvas><br>';
		$tabs .= '</div><script>';
		$colori = array('#ff6666', '#ffb366', '#ffff66', '#66ff66', '#66ffff', '#66b3ff', '#6666ff', '#b366ff', '#ff66ff', '#ff66b3');
		foreach ($tipi as $tipo) {
			$tabs .= 'var g' . $tipo . ' = new Chart("chart' . $tipo . '", {
				type: "line",
				data: {
					labels: ' . $graf[$tipo][$max_x[$tipo]]['x'] . ',
					datasets: [';
					$i = 0;
					foreach ($dati as $turno => $comande) {
						$tabs .= ($i > 0 ? ', ' : '') . '{ 
							data: ' . $graf[$tipo][$turno]['y'] . ',
							borderColor: "' . $colori[$i] . '",
							label: "' . $comande['titolo'] . ' (' . $comande[$tipo]['media'] . ' min)",
							fill: false
						}';
						$i++;
					}
					$tabs .= ']
				},
				options: {
					legend: {display: true},
					scales: {
						xAxes: [{
							scaleLabel: {
								display: true,
								labelString: "Minuti"
							}
						}]
					}
				}
			});';
		}
		$tabs .= '</script>';
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
		else {
			if ($msg != 'no')
				echo ($msg ? '<span class="text-success">Operazione completata con successo</span>' : '1');
			return true;
		}
	} else {
		echo 'Operazione non riuscita.';
		if (!pg_query($conn, "ROLLBACK;"))
			echo ' Annullamento delle modifiche fallito.';
	}
	return false;
}

function righeAfferite($res) {
	if ($res == false) {
		return false;
	} else {
		echo pg_affected_rows($res) . ' righe afferite<br>';
		return true;
	}
}


/*

<!DOCTYPE html>
<html>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
<body>
<canvas id="myChart" style="width:100%;max-width:600px"></canvas>

<script>
var xValues = [100,200,300,400,500,600,700,800,900,1000,2000,10000];

new Chart("myChart", {
  type: "line",
  data: {
    labels: xValues,
    datasets: [{ 
      data: [860,1140,1060,1060,1070,1110,1330,2210,7830,2478],
      borderColor: "red",
      fill: false
    }, { 
      data: [1600,1700,1700,1900,2000,2700,4000,5000,6000,7000],
      borderColor: "green",
      fill: false
    }, { 
      data: [300,700,2000,5000,6000,4000,2000,1000,200,100],
      borderColor: "blue",
      label: "Bblue",
      fill: false
    }]
  },
  options: {
    legend: {display: true}
  }
});
</script>

*/

?>