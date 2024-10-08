var tabmostrata = 0;
var idattuale = null; // Id dell'ordine su cui si sta lavorando
var ordini = [];

$("#tabevadere").click(function() {
	if (tabmostrata != 0) {
		document.getElementById('tab0').innerHTML = '';
		document.getElementById('tab1').innerHTML = '';
	}
	tabmostrata = 0;
	getComande();
});
$("#tabevase").click(function() {
	if (tabmostrata != 1) {
		document.getElementById('tab0').innerHTML = '';
		document.getElementById('tab1').innerHTML = '';
	}
	tabmostrata = 1;
	getComande();
});

function aggiornatabs(loading = false) {
	$("#tabevadere").html((loading && tabmostrata == 0 ? '<div class="spinner-border spinner-border-sm"></div>' : '<i class="bi bi-cart3"></i>') + ' Ordinate');
	$("#tabevadere").attr('title', sumordini['tab0'] + ' comande');
	$("#tabevase").html((loading && tabmostrata == 1 ? '<div class="spinner-border spinner-border-sm"></div>' : '<i class="bi bi-check2-circle"></i>') + ' Evase');
	$("#tabevase").attr('title', sumordini['tab1'] + ' comande');
}

// Definisce se un ordine è evaso (completamente) oppure no
// Ordinato: tab0; Evaso: tab1
function getTarget(res) {
	if (res.esportazione) {
		if (res.copia_cucina)
			return (res.stato_cucina == "ordinato" ? "tab0" : "tab1");
		else
			return (res.stato_bar == "ordinato" ? "tab0" : "tab1");
	} else {
		if (res.copia_cucina && res.stato_cucina == "ordinato")
			return "tab0";
		if (res.copia_bar && res.stato_bar == "ordinato")
			return "tab0";
		return "tab1";
	}
}

var sumordini = {tab0: 0, tab1: 0};

// Richiede al server le comande da mettere sulla lista a sinistra
function getComande(chiama = null) {
	aggiornatabs(true);
	if (chiama == null) {
		$('#start').show();
		$('#normal').hide();
	}
	ordini = [];
	$.getJSON("php/ajax.php?a=comande&" + infoturno())
	.done(function(json) {
		document.getElementById('tab0').innerHTML = '';
		document.getElementById('tab1').innerHTML = '';
		var target;
		var delay = {tab0: (tabmostrata == 1 ? 0.5 : 0), tab1: (tabmostrata == 0 ? 0.5 : 0)};
		sumordini = {tab0: 0, tab1: 0};
		try {
			$.each(json, function(i, res) {
				ordini[res.id] = res;
				target = getTarget(res);
				$('#' + target).append(
					'<a class="dropdown-item ordine" id="riga' + res.id + '" style="animation-delay: ' + delay[target] + 's;" onclick=\'selezione(' + res.id + ');\'><div class="row">' +
					'<div class="col-4">' + numero(res.id, res.progressivo) + '</div>' +
					'<div class="col-4 text-center">' + (res.esportazione ? (res.copia_cucina ? (res.stato_cucina == 'ordinato' ? '<i class="bi bi-bag"></i>' : '<i class="bi bi-bag-fill"></i>') : (res.stato_bar == 'ordinato' ? '<i class="bi bi-bag"></i>' : '<i class="bi bi-bag-fill"></i>')) : (res.copia_bar ? (res.stato_bar == 'ordinato' ? '<i class="bi bi-droplet"></i>' : '<i class="bi bi-droplet-fill"></i>') : '&emsp;') + '&nbsp;' + (res.copia_cucina ? (res.stato_cucina == 'ordinato' ? '<i class="bi bi-flag"></i>' : '<i class="bi bi-flag-fill"></i>') : '&emsp;')) + '</div>' +
					'<div class="col-4 text-end">' + res.ora.substring(0, 5) + '</div>' +
					'</div></a>');
				delay[target] += 0.02;
				sumordini[target] += 1;
			});
			if (json.length == 0) {
				var msg = 'Nessun ordine per il turno selezionato.';
				$("#tab0").html(msg);
				$("#tab1").html(msg);
			}
			if (sumordini['tab0'] == 0)
				$("#tab0").html('Nessun ordine da evadere');
			if (sumordini['tab1'] == 0)
				$("#tab1").html('Nessun ordine evaso');
		} catch (err) {
			var msg = '<span class="text-danger"><strong>Errore durante la richiesta:</strong></span>' + json;
			$("#tab0").html(msg);
			$("#tab1").html(msg);
			if (!($('#buttonupdatemonitor').hasClass('disabled')))
				updateModalMonitor();
		}
		if (chiama != null)
			selezione(chiama);
	})
	.fail(function(jqxhr, textStatus, error) {
		var msg = '<span class="text-danger"><strong>Errore durante la richiesta:</strong></span>' + jqxhr.responseText;
		$("#tab0").html(msg);
		$("#tab1").html(msg);
		if (!($('#buttonupdatemonitor').hasClass('disabled')))
			updateModalMonitor();
	})
	.always(function() {
		aggiornatabs();
	});
}

// Apre l'ordine selezionato nella schermata di destra
function selezione(ordine) {
	idattuale = ordine;
	// Righe sulla sinistra
	$('.dropdown-item').removeClass('active');
	document.getElementById('riga' + idattuale).classList.add('active');
	
	// Notifica avvenuta evasione
	if (ordine = idattuale && lasttype != null) {
		document.getElementById(lastaction == 'evaso' ? 'sevadi' : 'sripristina').play();
		mostratoast(true, (lastaction == 'evaso' ? '<i class="bi bi-star-fill"></i>&emsp;Comanda evasa' : '<i class="bi bi-box-arrow-left"></i>&emsp;Comanda ripristinata') + " con successo!");
	}
	
	// Gestione contenitori
	$('#start').hide();
	$('#normal').show();
	
	// Compilazione del contenitore normale
	$('#num').html(numero(idattuale, ordini[idattuale].progressivo));
	$('#nomecliente').html('(' + ordini[idattuale].cliente + ')');
	mostraTavolo();
	
	var bar, cucina;
	if (ordini[idattuale].copia_bar && (!ordini[idattuale].esportazione || (ordini[idattuale].esportazione && !ordini[idattuale].copia_cucina))) {
		if (ordini[idattuale].stato_bar == 'ordinato') {
			$('#iconabar').html('<i class="bi bi-droplet"></i>');
			bar = '<button class="btn btn-lg btn-info w-100 ' + (lasttype == 'bar' ? 'btnrip' : 'divev') + '" style="font-size: 2em;" onclick="evadi(\'bar\', \'evaso\');"><i class="bi bi-star"></i> Evadi</button>';
		} else {
			$('#iconabar').html('<i class="bi bi-droplet-fill"></i>');
			bar = '<div class="alert alert-info ' + (lasttype == 'bar' ? 'alertev' : 'divev') + '"><div class="row"><div class="col-7"><h5><strong>Evasa</strong>';
			bar += (ordini[idattuale].stato_bar != '' ? '<br>alle ore ' + ordini[idattuale].stato_bar.substring(0, 5) : '') + '</h5></div><div class="col-5 d-flex align-items-center text-right"><button class="btn btn-info" onclick="evadi(\'bar\', \'ordinato\');"><i class="bi bi-box-arrow-left"></i>&emsp;Ripristina</button></div></div></div>';
		}
	} else {
		$('#iconabar').html('<i class="bi bi-droplet"></i>');
		bar = '<p>Per quest\'ordine non è presente la comanda del bar.</p>';
	}
	if (ordini[idattuale].copia_cucina) {
		if (ordini[idattuale].stato_cucina == 'ordinato') {
			$('#iconacucina').html('<i class="bi bi-flag"></i>');
			cucina = '<button class="btn btn-lg btn-warning w-100 ' + (lasttype == 'cucina' ? 'btnrip' : 'divev') + '" style="font-size: 2em;" onclick="evadi(\'cucina\', \'evaso\');"><i class="bi bi-star"></i> Evadi</button>';
		} else {
			$('#iconacucina').html('<i class="bi bi-flag-fill"></i>');
			cucina = '<div class="alert alert-warning ' + (lasttype == 'cucina' ? 'alertev' : 'divev') + '"><div class="row"><div class="col-7"><h5><strong>Evasa</strong>';
			cucina += (ordini[idattuale].stato_cucina != '' ? '<br>alle ore ' + ordini[idattuale].stato_cucina.substring(0, 5) : '') + '</h5></div><div class="col-5 d-flex align-items-center text-right"><button class="btn btn-warning" onclick="evadi(\'cucina\', \'ordinato\');"><i class="bi bi-box-arrow-left"></i>&emsp;Ripristina</button></div></div></div>';
		}
	} else {
		$('#iconacucina').html('<i class="bi bi-flag"></i>');
		cucina = '<p>Per quest\'ordine non è presente la comanda della cucina.</p>';
	}
	$('#comandabar').html(bar);
	$('#comandacucina').html(cucina);
	
	document.getElementById('salvaoraevasione').checked = stessoTurno(data, new Date());
	lasttype = null;
	lastaction = null;
}

// Aggiorna la casella di testo del tavolo
function tav(stringa) {
	if (!stringa)
		$('#tavolo').val('');
	else
		$('#tavolo').val($('#tavolo').val() + stringa);
}

// Salva nel server il numero del tavolo
function salvatav() {
	if (idattuale == null)
		return;
	var tav = $('#tavolo').val();
	if (tav == '')
		tav = 'null';
	
	$.get("php/ajax.php", {a: "salvatavolo", id: idattuale, tavolo: tav}, function(res, stato) {
		if (stato == 'success') {
			if (res == '1') {
				mostratoast(true, '<i class="bi bi-save"></i>&emsp;Salvataggio riuscito!');
				ordini[idattuale].tavolo = tav;
				mostraTavolo();
			} else {
				mostratoast(false, "Salvataggio fallito: " + res);
			}
		} else {
			mostratoast(false, "Richiesta fallita: " + stato);
		}
	});
}

// Esegue un'evasione o un ripristino dell'ordine in esame.
// Tipo indica bar o cucina.
// Viene sempre salvato anche il numero del tavolo.
var lasttype = null;
var lastaction = null;
function evadi(type, action) {
	if (idattuale == null)
		return;
	lasttype = type;
	lastaction = action;
	var tav = (ordini[idattuale].esportazione ? "" : ($('#tavolo').val() == "" ? "null" : $('#tavolo').val()));
	$.get("php/ajax.php", {a: "evadi",
						   id: idattuale,
						   tipo: type,
						   tavolo: tav,
						   azione: action,
						   salvaora: document.getElementById('salvaoraevasione').checked},
	function(res, stato) {
		if (stato == 'success') {
			if (res == '1') {
				ordini[idattuale].tavolo = tav;
				getComande(idattuale);
			} else {
				mostratoast(false, (action == 'evaso' ? 'Evasione fallita: ' : 'Ripristino fallito: ') + res);
			}
		} else {
			mostratoast(false, "Richiesta fallita: " + stato);
		}
	});
}

function mostraTavolo() {
	if (idattuale == null)
		return;
	
	if (ordini[idattuale].esportazione) {
		$('#tastieratavolo').hide();
		$('#displaytavolo').hide();
		$('#titoloesportazione').show();
	} else if (ordini[idattuale].tavolo == "") {
		$('#tastieratavolo').show();
		$('#displaytavolo').hide();
		$('#titoloesportazione').hide();
	} else {
		$('#tastieratavolo').hide();
		$('#displaytavolo').show();
		$('#titoloesportazione').hide();
	}
	var tav = ordini[idattuale].tavolo;
	$('#tavolo').val(tav == 'null' ? '' : tav);
	$('#tavolosalvato').html(tav == 'null' ? 'Sconosciuto' : tav);
}