<?php
function btnaggiorna() {
	return '<div class="row"><div class="col"><span id="btnupdatemonitor"></span>&emsp;
			<small><i>Nuovo aggiornamento tra <span id="prossimoagg"></span></i></small></div>
			<div class="col-auto mt-1"><div class="btn-group" role="group">
				<button class="btn btn-sm btn-outline-success" onclick="impostaTutti(1);">Monitora tutto</button>
				<button class="btn btn-sm btn-outline-danger" onclick="impostaTutti(0);">Ignora tutto</button>
			</div></div></div>';
}
function monitorbody() {
	return '<div class="row">
		<div class="col-3">
			<h4><strong id="titoloSagra01"><i class="bi bi-hdd-fill"></i> Sagra01</strong></h4>
			<div id="statos1"></div>
		</div>
		<div class="col-3">
			<h5 id="titoloCassa1Server1"><i class="bi bi-hdd-network-fill"></i> Cassa1</h5>
			<div id="statoc1"></div>
		</div>
		<div class="col-3">
			<h5 id="titoloCassa2Server1"><i class="bi bi-hdd-network-fill"></i> Cassa2</h5>
			<div id="statoc2"></div>
		</div>
		<div class="col-3">
			<h5 id="titoloCassa3Server1"><i class="bi bi-hdd-network-fill"></i> Cassa2</h5>
			<div id="statoc3"></div>
		</div>
	</div><br>
	<div class="row">
		<div class="col-3" id="monitorSagra01"></div>
		<div class="col-3" id="monitorCassa1Server1"></div>
		<div class="col-3" id="monitorCassa2Server1"></div>
		<div class="col-3" id="monitorCassa3Server1"></div>
	</div>
	<hr>
	<div class="row">
		<div class="col-3">
			<h4><strong id="titoloSagra02"><i class="bi bi-hdd-fill"></i> Sagra02</strong></h4>
			<div id="statos2"></div>
		</div>
		<div class="col-3">
			<h5 id="titoloCassa1Server2"><i class="bi bi-hdd-network-fill"></i> Cassa1</h5>
		</div>
		<div class="col-3">
			<h5 id="titoloCassa2Server2"><i class="bi bi-hdd-network-fill"></i> Cassa2</h5>
		</div>
		<div class="col-3">
			<h5 id="titoloCassa3Server2"><i class="bi bi-hdd-network-fill"></i> Cassa2</h5>
		</div>
	</div>
	<div class="row">
		<div class="col-3" id="monitorSagra02"></div>
		<div class="col-3" id="monitorCassa1Server2"></div>
		<div class="col-3" id="monitorCassa2Server2"></div>
		<div class="col-3" id="monitorCassa3Server2"></div>
	</div>';
}
?>

<div class="offcanvas offcanvas-start" tabindex="-1" id="modalmonitor" style="width: 75%;">
	<div class="offcanvas-header">
		<h5 class="modal-title">Monitoraggio dei backup automatici</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body" style="padding-top: 0px;">
		<?php
		if ($pagina == 'pannello') {
			echo btnaggiorna();
			echo '<br>';
			echo monitorbody();
		}
		?>
	</div>
</div>

<script>
updateModalMonitor();
setInterval(tickMonitor, 1000);
var frequenza = 30;

// Preparazione offcanvas monitoraggio
var modalMonitor = document.getElementById('modalmonitor');
modalMonitor.addEventListener('show.bs.offcanvas', aperturaMonitor);
$('.nav-pills a[data-bs-target="#tabmonitoraggio"]').on('show.bs.tab', aperturaMonitor);

function aperturaMonitor() {
	$('.toastmonitor').remove();
}

var prossimoupdate = frequenza;
function tickMonitor() {
	prossimoupdate -= 1;
	$('#prossimoagg').html(prossimoupdate + (prossimoupdate == 1 ? ' secondo' : ' secondi'));
	if (prossimoupdate == 0)
		updateModalMonitor();
}

var errs = new Array(false, false);
var errgenerale = false;
var to = 2000; // Timeout
var timeconnect = new Array(1, 1); // Millisecondi di connessione ininterrotta dei due server
var oraprec = null;

function updateModalMonitor() {
	prossimoupdate = frequenza;
	$('#prossimoagg').html(prossimoupdate + ' secondi');
	var ora = new Date();
	
	$('.tooltip').remove();
	$('#btnupdatemonitor').html('<button class="btn btn-info disabled" id="buttonupdatemonitor" onclick="updateModalMonitor();"><div class="spinner-border spinner-border-sm"></div> Aggiornamento...</button>');
	$('.toastmonitor').remove();
	
	errs = new Array();
	errs[0] = false;
	errs[1] = false;
	errgenerale = false;
	
	infostato('s1');
	infostato('s2');
	checkDumpLocale(1, ora);
}

var dumpLocaleExec = [null, null]; // Data di esecuzione dello script
var dumpLocale = [null, null]; // Data del file

function checkDumpLocale(server, ora) {
	$('#titoloSagra0' + server).html('<span class="text-info"><i class="bi bi-hourglass-split"></i> Sagra0' + server + '</span>');
	
	$.ajax({
		url: "http://sagra0" + server + "/pannello/php/getfile.php?file=logDumpLocale.txt",
		success: function(res) {
			// Titolo
			if (server == 1)
				$('#titoloSagra0' + server).html('<i class="bi bi-hdd-fill"></i> Sagra0' + server + '</strong>');
			
			// Info di connessione
			if (oraprec != null) {
				if (timeconnect[server - 1] == 0) {
					timeconnect[server - 1] += 1;
					mostratoast(true, '<i class="bi bi-check-circle-fill"></i>&emsp;Sagra0' + server + ' è tornato in rete!');
					risolto('s' + server + 'nr');
				} else
					timeconnect[server - 1] += (ora - oraprec);
			}
			
			// Nuovi dati
			var righe = res.split("\n");
			if (typeof righe[0] !== 'undefined' && typeof righe[2] !== 'undefined') {
				dumpLocaleExec[server - 1] = new Date(parseInt(righe[0]) * 1000);
				dumpLocale[server - 1] = new Date(righe[2].split(" ")[5] + 'T' + righe[2].split(" ")[6]);
			}
		},
		error: function() { // Server non raggiungibile
			// Titolo
			$('#titoloSagra0' + server).html('<span class="text-danger"><i class="bi bi-exclamation-octagon-fill"></i> Sagra0' + server + '</span>');
			
			// Registrazione
			errs[server - 1] = true;
			
			// Segnalazione, solo se è richiesto dal cookie
			if (getCookie('monitors' + server) == 1) {
				toastmonitor('<i class="bi bi-exclamation-octagon-fill"></i>&emsp;<strong>Sagra0' + server + '</strong> non raggiungibile.<br><u>Non ricaricare la pagina</u>');
				allarme('s' + server + 'nr');
			}
			
			// Info di connessione
			timeconnect[server - 1] = 0;
		},
		complete: function() {
			// Aggiornamento degli orari
			if (dumpLocaleExec[server - 1] != null ||
				dumpLocale[server - 1] != null) {
				$('#monitorSagra0' + server).html(
				(dumpLocaleExec[server - 1] != null ? spanmon('Ultima esecuzione del dump') + '<i class="bi bi-files"></i> ' + mostraOra(dumpLocaleExec[server - 1], ora, 120) + '</span><br>' : '') + 
				(dumpLocale[server - 1] != null ? spanmon('Orario del file') + '<i class="bi bi-watch"></i> ' + mostraOra(dumpLocale[server - 1], ora, 120) + '</span>' : ''));
			} else {
				$('#monitorSagra0' + server).html('<i>Nessuna informazione sulla creazione del dump</i><br>');
			}
			
			// Ritardi di schedulazione
			if (!errs[server - 1]) { // Da controllare solo se il server è raggiungibile, altrimenti è normale che le date siano obsolete
				var dumpfermo = new Date(ora - dumpLocaleExec[server - 1]) >= (120 + 60) * 1000;
				var filevecchio = new Date(ora - dumpLocale[server - 1]) >= (120 + 60) * 1000
				
				// Registrazione
				errs[server - 1] = dumpfermo || filevecchio;
				
				// Segnalazione
				if (getCookie('monitors' + server) == 1) {
					if (dumpfermo) {
						toastmonitor('<i class="bi bi-exclamation-circle-fill"></i>&emsp;<strong>Sagra0' + server + ':</strong> dump non eseguito' + (filevecchio ? ' e file locale obsoleto' : ''));
					} else if (filevecchio) {
						toastmonitor('<i class="bi bi-exclamation-circle-fill"></i>&emsp;<strong>Sagra0' + server + ':</strong> dump locale obsoleto');
					}
					
					allarme('s' + server + 'dumpfermo', !dumpfermo);
					allarme('s' + server + 'filevecchio', !filevecchio);
				}
			}
			
			// Prossimo step
			$('[data-bs-toggle="tooltip"]').tooltip();
			
			if (server == 1) {
				checkDumpLocale(2, ora);
			} else {
				checkCopiaDump(2, ora);
			}
		},
		timeout: to
	});
}

var copiaDumpExec = [null, null];
var copiaDump = [null, null];

function checkCopiaDump(server, ora) {
	$.ajax({
		url: "http://sagra0" + server + "/pannello/php/getfile.php?file=lista_dump_sagra0" + server + ".txt",
		success: function(res) {
			// Titolo
			$('#titoloSagra0' + server).html('<i class="bi bi-hdd-fill"></i> Sagra0' + server);
			
			// Nuovi dati
			var righe = res.split("\n");
			if (typeof righe[0] !== 'undefined' && typeof righe[2] !== 'undefined') {
				copiaDumpExec[server - 1] = new Date(parseInt(righe[0]) * 1000);
				copiaDump[server - 1] = new Date(righe[2].split(" ")[5] + 'T' + righe[2].split(" ")[6]);
			}
		},
		
		// Nessun controllo sull'irraggiungibilità del server, già fatto in checkDumpLocale
		
		complete: function() {
			// Aggiornamento degli orari
			if (copiaDumpExec[server - 1] != null ||
				copiaDump[server - 1] != null) {
				$('#monitorSagra0' + server).append('<br><br>' +
				(copiaDumpExec[server - 1] != null ? spanmon('Ultima copia del dump di Sagra0' + (server == 2 ? '1' : '0')) + '<i class="bi bi-file-earmark-arrow-down"></i> ' + mostraOra(copiaDumpExec[server - 1], ora, 120) + '</span><br>' : '') +
				(copiaDump[server - 1] != null ? spanmon('Orario del file') + '<i class="bi bi-watch"></i> ' + mostraOra(copiaDump[server - 1], ora, 240) + '</span>': ''));
			} else {
				$('#monitorSagra0' + server).append('<i>Nessuna informazione sulla copia del dump</i>');
			}
			
			// Ritardi di schedulazione
			if (!errs[server - 1]) { // Da controllare solo se il server (viene esaminato solo il 2) è raggiungibile, altrimenti è normale che le date siano obsolete
				var copiaferma = new Date(ora - copiaDumpExec[server - 1]) >= (120 + 60) * 1000;
				var copiavecchia = new Date(ora - copiaDump[server - 1]) >= (240 + 60) * 1000;
				
				// La copia obsoleta dev'essere considerata un problema solo se:
				copiavecchia = copiavecchia && // La data è effettivamente vecchia
							   !errs[server == 2 ? 0 : 1] && // L'altro server non riscontra problemi in questo momento (irraggiungibile, dump fermo o copia vecchia)
							   (timeconnect[server == 2 ? 0 : 1] >= (120 + 60) * 1000); // L'altro server è stato raggiungibile nei 2 + 1 minuti precedenti (quindi il problema risiede in questo server che non va a prendersi il dump)
				
				// Segnalazione
				if (getCookie('monitors' + server) == 1) {
					if (copiaferma) {
						toastmonitor('<i class="bi bi-exclamation-circle-fill"></i>&emsp;<strong>Sagra0' + server + ':</strong> copia del dump non eseguita' + (copiavecchia ? ' e file precedente obsoleto' : ''));
					} else if (copiavecchia) {
						toastmonitor('<i class="bi bi-exclamation-circle-fill"></i>&emsp;<strong>Sagra0' + server + ':</strong> copia del dump obsoleta');
					}
					
					allarme('s' + server + 'copiaferma', !copiaferma);
					allarme('s' + server + 'copiavecchia', !copiavecchia);
				}
			}
			
			// Prossimo step
			$('[data-bs-toggle="tooltip"]').tooltip();
			updateCassa(1, 1, ora);
		},
		timeout: to
	});
}

var mod = [[null, null, null],
		   [null, null, null]]; // Ultime modifiche dei file, in Sagra01 e Sagra02
var copia = [[null, null, null], // Di Sagra01 in Sagra01
			 [null, null, null]]; // Di Sagra02 in Sagra02

function updateCassa(server, cassa, ora) {
	if (server == 1) {
		infostato('c' + cassa);
	}
	$('#titoloCassa' + cassa + 'Server' + server).html('<span class="text-info"><i class="bi bi-hourglass-split"></i> Cassa' + cassa + '</span>');
	
	$.ajax({
		url: "http://sagra0" + server + "/pannello/php/getfile.php?file=lista_dump_cassa" + cassa + ".txt",
		success: function(res) {
			// Nuovi dati
			var righe = res.split("\n");
			mod[server - 1][cassa - 1] = new Date(parseInt(righe[0]) * 1000);
			
			var j = 1; // Scorre le righe
			var n = 1; // Conta le occorrenze ancora da trovare
			while (n > 0) {
				if (typeof righe[j] !== 'undefined' && righe[j].includes('/') && !righe[j].includes('<DIR>')) {
					if (righe[j].includes('Sagra0' + server)) {
						copia[server - 1][cassa - 1] = new Date(righe[j].substring(6, 10) + '-' + righe[j].substring(3, 5) + '-' + righe[j].substring(0, 2) + 'T' + righe[j].substring(12, 17) + ':00');
						n--;
					}
				}
				j++;
			}
		},
		complete: function() {
			// Titolo
			$('#titoloCassa' + cassa + 'Server' + server).html('<i class="bi bi-hdd-network-fill"></i> Cassa' + cassa);
			
			// Aggiornamento degli orari
			if (mod[server - 1][cassa - 1] != null ||
				copia[server - 1][cassa - 1] != null) {
				$('#monitorCassa' + cassa + 'Server' + server).html(
					(mod[server - 1][cassa - 1] != null ? spanmon('Ultima copia del dump') + '<i class="bi bi-file-earmark-arrow-down"></i> ' + mostraOra(mod[server - 1][cassa - 1], ora, 300) + '</span><br>' : '') + 
					(copia[server - 1][cassa - 1] != null ? spanmon('Orario del file') + '<i class="bi bi-watch"></i> ' + mostraOra(copia[server - 1][cassa - 1], ora, 420) + '</span>' : ''));
			} else {
				$('#monitorCassa' + cassa + 'Server' + server).html('<i>Nessuna informazione sulla copia del dump</i>');
			}
			
			// Ritardi di schedulazione
			if (!errs[server - 1]) { // Da controllare solo se il server è stato raggiunto, altrimenti è impossibile che le date siano aggiornate (né quella della copia, né quella dell'ultima esecuzione, anche se lo script è stato lanciato in orario)
				var copiaferma = new Date(ora - mod[server - 1][cassa - 1]) >= (300 + 60) * 1000;
				var copiavecchia = new Date(ora - copia[server - 1][cassa - 1]) >= (420 + 60) * 1000;
				
				copiaferma = copiaferma && timeconnect[server - 1] >= (300 + 60) * 1000;
				
				// La copia obsoleta dev'essere considerata un problema solo se:
				copiavecchia = copiavecchia && // La data è effettivamente vecchia
							   // Il server non riscontra problemi in questo momento (irraggiungibile, dump fermo o copia vecchia), già controllato nell'if
							   timeconnect[server - 1] >= (300 + 60) * 1000; // Il server è stato raggiungibile nei 5 + 1 minuti precedenti (quindi il problema risiede nella cassa che non va a prendersi il dump)
				
				// Segnalazione
				if (getCookie('monitorc' + cassa) == 1) {
					if (copiaferma) {
						toastmonitor('<i class="bi bi-exclamation-circle-fill"></i>&emsp;<strong>Cassa' + cassa + ':</strong> copia del dump di Sagra0' + server + ' non eseguita' + (copiavecchia ? ' e file precedente obsoleto' : ''));
					} else if (copiavecchia) {
						toastmonitor('<i class="bi bi-exclamation-circle-fill"></i>&emsp;<strong>Cassa' + cassa + ':</strong> copia del dump di Sagra0' + server + ' obsoleta');
					}
					
					allarme('s' + server + 'c' + cassa + 'copiaferma', !copiaferma);
					allarme('s' + server + 'c' + cassa + 'copiavecchia', !copiavecchia);
				}
			}
				
			// Prossimo step
			$('[data-bs-toggle="tooltip"]').tooltip();
			
			if (server == 1)
				updateCassa(2, cassa, ora);
			else {	
				if (cassa < 3)
					updateCassa(1, cassa + 1, ora);
				else { // Fine del giro
					oraprec = ora;
					$('#btnupdatemonitor').html('<button class="btn btn-info" id="buttonupdatemonitor" onclick="updateModalMonitor();"><i class="bi bi-arrow-clockwise"></i> Aggiorna tutto</button>');
				}
			}
		},
		timeout: to
	});
}

var allarmi = {};
function allarme(id, risolto = false) {
	if (allarmi[id] != risolto) {
		allarmi[id] = risolto;
		if (!risolto && !errgenerale) {
			errgenerale = true;
			document.getElementById('sallarme').play();
		}
	}
}

function risolto(id) {
	allarme(id, true);
}

function spanmon(testo) {
	return '<span class="tmon" data-bs-toggle="tooltip" data-bs-placement="top" title="' + testo + '">';
}

function toastmonitor(msg) {
	$('#divtoast').append('<div class="toast bg-danger text-white toastmonitor" id="tno' + (tno) + '" role="alert" style="border-radius: 10px; margin: 10px 0px 0px 0px;"' + (!($('<?php echo ($pagina == 'pannello' ? '#modalmonitor' : '#tabmonitoraggio'); ?>').hasClass('show')) ? ' data-bs-autohide="false"' : '') + '><div class="d-flex">' +
	'<div class="toast-body">' + msg +
	(!($('<?php echo ($pagina == 'pannello' ? '#modalmonitor' : '#tabmonitoraggio'); ?>').hasClass('show')) ? '<br><br><button class="btn btn-sm btn-light" <?php echo ($pagina == 'pannello' ? 'data-bs-toggle="offcanvas" data-bs-target="#modalmonitor"' : 'onclick="apritab(\\\'#tabmonitoraggio\\\');"'); ?>>Apri monitoraggio</button>' : '') + '</div>' +
	'<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>');
	$("#tno" + tno).toast("show");
	tno++;
}

function infostato(quale) {
	if (getCookie('monitor' + quale) == 1) {
		$('#stato' + quale).html('<i class="text-success"><i class="bi bi-check-circle-fill"></i> Monitorato</i>&emsp;<button class="btn btn-sm btn-outline-danger" onclick="toggleMonitor(\'' + quale + '\');">Ignora</button><br>');
	} else {
		$('#stato' + quale).html('<i class="text-danger"><i class="bi bi-exclamation-diamond-fill"></i> Ignorato</i>&emsp;<button class="btn btn-sm btn-success" onclick="toggleMonitor(\'' + quale + '\');">Monitora</button><br>');
	}
}

function mostraOra(data1, ora, periodo) {
	var diff = new Date(ora - data1);
	var out = '';
	if (diff >= 3600 * 24 * 1000) // Più di 24 ore
		out += (data1.getDate() < 10 ? '0': '') + data1.getDate() + '/' + ((data1.getMonth() + 1) < 10 ? '0' : '') + (data1.getMonth() + 1) + '/' + data1.getFullYear() + ' ';
	out += (data1.getHours() < 10 ? '0' : '') + data1.getHours() + ':' + (data1.getMinutes() < 10 ? '0' : '') + data1.getMinutes();
	out += ' <strong class="text-' + (diff >= (periodo + 60) * 1000 ? 'danger' : (diff >= periodo * 1000 ? 'warning' : 'success')) + '">';
	if (diff >= 3600 * 24 * 1000)
		out += '(più di un giorno fa)';
	else if (diff >= 3600 * 1000)
		out += '(più di un\'ora fa)';
	else
		out += '(' + (diff.getMinutes() > 0 ? diff.getMinutes() + ' min ' : '') + (diff.getSeconds() > 0 ? diff.getSeconds() + ' sec ' : '') + 'fa)';
	out += '</strong>';
	return out;
}

function toggleMonitor(quale) {
	if (getCookie('monitor' + quale) == 1) {
		setCookie('monitor' + quale, 0);
	} else {
		setCookie('monitor' + quale, 1);
	}
	infostato(quale);
}

function impostaTutti(stato) {
	monitor = ['s1', 's2', 'c1', 'c2', 'c3'];
	for (var i = 0; i < monitor.length; i++) {
		setCookie('monitor' + monitor[i], stato);
		infostato(monitor[i]);
	}
}

</script>