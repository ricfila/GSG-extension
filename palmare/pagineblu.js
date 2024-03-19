
function ultimiassociati() {
	inviabili = false;
	coloremenu('bg-info');
	$('#titolo').html('<h3 class="m-0"><button class="btn btn-info" onclick="preparalista();"><i class="bi bi-caret-left-fill"></i></button> Ultimi associati');
	$('#corpo').html('');
	let sorting = [];
	$.getJSON("ajax.php?a=ultimi")
	.done(function(json) {
		$('#corpo').html('');
		try {
			$.each(json, function(i, res) {
				fatti[res.id] = res;
				sorting.push(res.id);
			});
		} catch (err) {
			$('#corpo').html('<span class="text-danger"><strong>Errore nell\'elaborazione della richiesta:</strong></span>' + json);
		}
	})
	.fail(function(jqxhr, textStatus, error) {
		$('#corpo').html('<span class="text-danger"><strong>Errore durante la richiesta:</strong></span>' + jqxhr.responseText);
	})
	.always(function() {
		let out = fatti.concat(salvati);
		if (out.length > 1)
			out.sort(function(a, b) {
				if (a == null || b == null)
					return 0;
				return b.ora - a.ora});
		let delay = 0;
		for (let i = salvati.length - 1; i >= 0; i--) {
			$('#corpo').append('<button class="btn btn-secondary w-100 mb-3 ordinesala" style="animation-delay: ' + delay + 's;" onclick="apriordine(' + salvati[i].id + ', \'' + (salvati[i].cameriere == null ? 'salvati' : 'fatti') + '\');"><div class="row"><div class="col-4"><big>' + salvati[i].progressivo + '</big></div><div class="col my-auto">' + salvati[i].cliente + '</div></div></button><br>');
			delay += 0.02;
		}
		for (let i = 0; i < sorting.length; i++) {
			$('#corpo').append('<button class="btn btn-secondary w-100 mb-3 ordinesala" style="animation-delay: ' + delay + 's;" onclick="apriordine(' + sorting[i] + ', \'' + (fatti[sorting[i]].cameriere == null ? 'salvati' : 'fatti') + '\');"><div class="row"><div class="col-4"><big>' + fatti[sorting[i]].progressivo + '</big></div><div class="col my-auto">' + fatti[sorting[i]].cliente + '</div></div></button><br>');
			delay += 0.02;
		}
		if (delay == 0)
			$('#corpo').append('Nessun ordine associato recentemente.');
		aggiornastato();
	});
}

function apriordine(id, array = false) {
	inviabili = false;
	// Pescaggio dell'ordine da mostrare
	let ordine = null;
	if (array == 'salvati') {
		for (let i = 0; i < salvati.length; i++) {
			if (salvati[i].id == id) {
				ordine = salvati[i];
				break;
			}
		}
	} else if (array == 'fatti') {
		ordine = fatti[id];
	} else {
		ordine = trovati[id];
	}
	
	testataordine(ordine, 'info', (array == 'trovati' ? (tipocerca > 2 ? 'rescerca();' : 'cercaordine();') : 'ultimiassociati();'));
	let out = '<h4 class="mb-0">Cliente: <strong>' + ordine.cliente + '</strong></h4>';
	out += (ordine.coperti != null ? '&emsp;<strong>' + ordine.coperti + '</strong> copert' + (ordine.coperti == 1 ? 'o' : 'i') : '');
	if (ordine.esportazione == true) {
		out += '<h4 class="mt-2">Ordine per ASPORTO</h4>';
	} else {
		let notavolo = ordine.tavolo == null || ordine.tavolo == '' || ordine.tavolo == 'null';
		out += '<h4 class="mt-2 mb-0">Tavolo: <strong>' + (notavolo ? '<small class="text-body-secondary"><i>non associato</i></small>' : ordine.tavolo) + '</strong>' + (!notavolo && ordine.stato == 0 ? '&emsp;<button class="btn btn-sm btn-outline-danger" onclick="dialogRipristina(' + id + ', \'' + array + '\');">Dissocia</button>' : '') + '</h4>';
		out += (ordine.associazione != null && ordine.associazione != 'null' ? '&emsp;Associato da <strong>' + ordine.cameriere + '</strong> alle ' + ordine.associazione.substr(0, 5) : '')
	}
	out += '<br><br>';
	let statocomanda;
	if (ordine.esportazione) {
		if (ordine.stato == 0)
			statocomanda = '<i class="bi bi-hourglass-split"></i> In attesa';
		else
			statocomanda = '<i class="bi bi-clipboard2-pulse"></i> In lavorazione';
	} else {
		if (ordine.associazione == null || ordine.associazione == 'null')
			statocomanda = '<i class="bi bi-hourglass-split"></i> In attesa di associazione al tavolo';
		else {
			if (ordine.stato == 0)
				statocomanda = '<i class="bi bi-pencil"></i> Trascrizione tavolo in corso';
			else
				statocomanda = '<i class="bi bi-clipboard2-pulse"></i> In lavorazione';
		}
	}
	if (ordine.copia_bar && !ordine.esportazione) {
		out += '<h4 class="mb-0 text-info">Comanda bevande:</h4>&emsp;' + (ordine.stato_bar == 'ordinato' ? statocomanda : '<i class="bi bi-star-fill"></i> Evasa' + (ordine.stato_bar != '' ? ' alle ' + ordine.stato_bar.substr(0, 5) : ''));
	}
	if (ordine.copia_cucina) {
		out += '<h4 class="mt-2 mb-0" style="color: #eb8e38;">Comanda cucina:</h4>&emsp;' + (ordine.stato_cucina == 'ordinato' ? statocomanda : '<i class="bi bi-star-fill"></i> Evasa' + (ordine.stato_cucina != '' ? ' alle ' + ordine.stato_cucina.substr(0, 5) : ''));
	}
	$('#corpo')
	.css('opacity', 0)
	.html(out)
	.animate({opacity: 1});
}

function dialogRipristina(id, array) {
	dialog('Dissocia tavolo', 'Sei sicuro di voler annullare l\'associazione al tavolo di questo ordine?<br><br><span id="msgdrip"></span>', '<button class="btn btn-success" onclick="ripristina(' + id + ', \'' + array + '\');"><i class="bi bi-check-circle-fill"></i> Conferma</button>');
}

function ripristina(id, array) {
	let ordine;
	$('#msgdrip').html('<span class="text-success">Elaborazione in corso...</span>');
	if (array == 'salvati') {
		let salvati2;
		for (let i = 0; i < salvati.length; i++) {
			if (salvati[0].id == id) {
				ordine = salvati.shift();
			} else {
				salvati2.push(salvati.shift());
			}
		}
		salvati = salvati2;
		ordini[id] = ordine;
		ordini[id].tavolo = null;
		modal.hide();
		ultimiassociati();
	} else {
		ordine = fatti[id];
		fatti[id] = null;
		$.ajax({
			url: "ajax.php?a=dissocia&id=" + id,
			success: function(res) {
				if (res == '1') {
					ordini[id] = ordine;
					ordini[id].tavolo = null;
					modal.hide();
					ultimiassociati();
				} else {
					$('#msgdrip').html('<span class="text-danger">' + res + '</span>');
				}
			},
			error: function(xhr, status, error) { // Server non raggiungibile
				$('#msgdrip').html('<span class="text-danger">Errore nell\'invio dei dati: ' + error + '</span>');
			},
			timeout: 2000
		});
	}
}
