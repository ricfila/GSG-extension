var ordini = [];
var ordinic = [];
var fatti = [];
var id = null;
var t;
var msg = "";

function aggiornastato() {
	//$('#attesa').html(ordinic.length > 0 ? '<i class="bi bi-upload"></i>' : '');
	$('#errore').html(msg != '' ? '<i class="bi bi-exclamation-triangle-fill text-danger"></i>' : '');
}

function preparalista() {
	coloremenu('bg-success');
	$('#titolo').html('<h3><button class="btn btn-success" onclick="lista();"><i class="bi bi-arrow-clockwise text-lead"></i></button>&nbsp;Ordini da raccogliere</h3>');
	lista();
}

function lista() {
	$('#corpo').html('');//<div class="spinner-border spinner-border-sm"></div>&nbsp;Caricamento in corso...');
	$.getJSON("ajax.php?a=lista")
	.done(function(json) {
		$('#corpo').html('');
		try {
			ordini = [];
			//Ricostruzione degli ordini già salvati a partire dai cookie
			ordiniCookie();
			//Preparazione della lista con gli ordini non ancora associati
			$.each(json, function(i, res) {
				if (ordinic[res.id] == null)
					ordini[res.id] = res;
			});
		} catch (err) {
			$('#corpo').html('<span class="text-danger"><strong>Errore nell\'elaborazione della richiesta:</strong></span>' + json);
		}
	})
	.fail(function(jqxhr, textStatus, error) {
		$('#corpo').html('<span class="text-danger"><strong>Errore durante la richiesta:</strong></span>' + jqxhr.responseText);
	})
	.always(function() {
		let delay = 0;
		for (let i = 0; i < ordini.length; i++) {
			if (ordini[i] != null) {
				$('#corpo').append('<button class="btn btn-secondary w-100 mb-3 ordinesala" style="animation-delay: ' + delay + 's;" onclick="ordine(' + i + ');"><div class="row"><div class="col-4"><big>' + ordini[i].progressivo + '</big></div><div class="col my-auto">' + ordini[i].cliente + '</div></div></button><br>');
				delay += 0.02;
			}
		}
		if (delay == 0)
			$('#corpo').append('Nessun ordine da raccogliere.');
		aggiornastato();
	});
}

function ordine(num) {
	coloremenu('bg-warning');
	id = num;
	testataordine(ordini[id], 'warning');
	$('#corpo').html('<div style="padding: 0px 15px; overflow-x: hidden;"><div style="animation: tastieraTav 0.4s; animation-fill-mode: forwards;">\
		<h4>Cliente: <strong>' + ordini[id].cliente + '</strong></h4>\
		<div id="tastiera">' + tastiera + '</div>\
	</div></div>');
}

function salvatav() {
	t = $('#tavolo').val();
	if (t.length > 0) {
		$('#tastiera')
		//.css('opacity', 0)
		.html('<h4 style="letter-spacing: 10px;" id="riep">Tavolo: <big><strong class="text-success">' + t + '</strong></big></h4><br>\
			<div class="row" id="tasticonferma"><div class="col" style="padding: 2px;">\
				<button class="btn btn-danger btn-lg w-100 mb-2" onclick="annulla();"><i class="bi bi-x-circle"></i>&emsp;Annulla</button>\
				<button class="btn btn-success btn-lg w-100" onclick="conferma();"><i class="bi bi-check-circle-fill"></i>&emsp;Conferma</button>\
			</div></div>')
		//.animate({opacity: 1});
		$('#riep').animate({letterSpacing: "0px"});
	}
}

function annulla() {
	$('#tastiera')
	.css('opacity', 0)
	.html(tastiera)
	.animate({opacity: 1});
}

function conferma() {
	// 0_id_tavolo_progressivo_ora_cliente
	setCookie('action' + Date.now(), '0_' + id + '_' + t + '_' + ordini[id].progressivo + '_' + ordini[id].ora + '_' + ordini[id].cliente);
	ordini[id] = null;
	preparalista();
}

function invio() {
	aggiornastato();
	$.ajax({
		url: "ajax.php?a=invio",
		success: function(res) {
			msg = '';
			if (res != '1') {
				msg = 'Errore nel salvataggio dei dati: ' + res;
				dialog('Errore', msg);
			}
			aggiornastato();
		},
		error: function(xhr, status, error) { // Server non raggiungibile
			msg = 'Errore nell\'invio dei dati: ' + error;
		},
		timeout: 2000
	});
}
setInterval(invio, 2000);

function ordiniCookie() {
	ordinic = [];
	let sorting = [];
	let cookies = document.cookie.split("; ");
	let singolo, singolo2;
	cookies.sort();
	for (let i = 0; i < cookies.length; i++) {
		singolo = cookies[i].split("=");
		if (singolo[0].startsWith("action")) {
			singolo2 = singolo.split("_");
			if (singolo2[0] == '0') {
				ordinic[singolo2[1]] = {"id": singolo2[1],
									"progressivo": singolo2[3],
									"tavolo": singolo2[2],
									"ora": singolo2[4],
									"cliente": singolo2[5]};
				sorting.push(singolo2[1]);
			} else
				ordinic[singolo2[1]] = null;
		}
	}
	return sorting;
}

function mostraerrore() {
	if (msg != '') {
		dialog('Errore', msg);
	}
}

function testataordine(ord, stile, azione = "preparalista();") {
	$('#titolo').html('<div class="row"><div class="col-auto"><h3 class="m-0"><button class="btn btn-' + stile + '" onclick="' + azione + '"><i class="bi bi-caret-left-fill"></i></button>&nbsp;Ordine <strong>' + ord.progressivo + '</strong></h3></div><div class="col" style="text-align: right;"><small>ID ' + ord.id + ' - ' + ord.ora.substr(0, 5) + (ord.data != null ? '<br>' + ord.data : '') + '</small></div></div>');
}

let tastiera = '<div class="row"><div class="col" style="padding: 2px;">\
				<div class="input-group mb-3">\
					<input type="text" class="form-control form-control text-center" id="tavolo" style="padding: 5px; font-size: 1.5em; margin: 0px;" placeholder="Tavolo">\
					<button class="btn btn-danger btn-lg" onclick="tav(false);"><i class="bi bi-backspace"></i></button>\
				</div>\
			</div></div>\
			<div class="row">\
				<div class="col" style="padding: 0px 2px;">\
					<button class="btn btn-outline-dark btn-lg w-100 numt" astyle="animation-delay: 0.1s;" onclick="tav(\'1\');">1</button><br>\
					<button class="btn btn-outline-dark btn-lg w-100 numt" astyle="animation-delay: 0.1s;" onclick="tav(\'4\');">4</button><br>\
					<button class="btn btn-outline-dark btn-lg w-100 numt" astyle="animation-delay: 0.1s;" onclick="tav(\'7\');">7</button><br>\
				</div>\
				<div class="col" style="padding: 0px 2px;">\
					<button class="btn btn-outline-dark btn-lg w-100 numt" astyle="animation-delay: 0.2s;" onclick="tav(\'2\');">2</button><br>\
					<button class="btn btn-outline-dark btn-lg w-100 numt" astyle="animation-delay: 0.2s;" onclick="tav(\'5\');">5</button><br>\
					<button class="btn btn-outline-dark btn-lg w-100 numt" astyle="animation-delay: 0.2s;" onclick="tav(\'8\');">8</button><br>\
				</div>\
				<div class="col" style="padding: 0px 2px;">\
					<button class="btn btn-outline-dark btn-lg w-100 numt" astyle="animation-delay: 0.3s;" onclick="tav(\'3\');">3</button><br>\
					<button class="btn btn-outline-dark btn-lg w-100 numt" astyle="animation-delay: 0.3s;" onclick="tav(\'6\');">6</button><br>\
					<button class="btn btn-outline-dark btn-lg w-100 numt" astyle="animation-delay: 0.3s;" onclick="tav(\'9\');">9</button><br>\
				</div>\
			</div>\
			<div class="row">\
				<div class="col"></div>\
				<div class="col-4" style="padding: 0px 2px;">\
					<button class="btn btn-outline-dark btn-lg w-100 numt btnlater disabled" onclick="tav(\'0\');">0</button>\
				</div>\
				<div class="col"></div>\
			</div>\
			<div class="row mb-3">\
				<div class="col" style="padding: 2px;">\
					<button class="btn btn-outline-primary btn-lg w-100 numt btnlater disabled" onclick="tav(\' SX\');">SX</button>\
				</div>\
				<div class="col" style="padding: 2px;">\
					<button class="btn btn-outline-primary btn-lg w-100 numt btnlater disabled" onclick="tav(\' CX\');">CX</button>\
				</div>\
				<div class="col" style="padding: 2px;">\
					<button class="btn btn-outline-primary btn-lg w-100 numt btnlater disabled" onclick="tav(\' DX\');">DX</button>\
				</div>\
			</div>\
			<div class="row"><div class="col" style="padding: 2px;">\
				<button class="btn btn-success btn-lg w-100 btnlater disabled" onclick="salvatav();"><i class="bi bi-check-circle-fill"></i>&emsp;Ok</button>\
			</div></div>';

function tav(stringa) {
	if (!stringa) {
		$('#tavolo').val('');
		$('.btnlater').each(function() {$(this).addClass('disabled');});
	} else {
		$('#tavolo').val($('#tavolo').val() + stringa);
		$('.btnlater').each(function() {$(this).removeClass('disabled');});
	}
}
