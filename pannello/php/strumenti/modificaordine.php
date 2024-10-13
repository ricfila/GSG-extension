
<div class="modal fade" id="modalarticoli">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Aggiungi articoli</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body" id="modalarticolibody"></div>
		</div>
	</div>
</div>

<script>
$('.nav-pills a[data-bs-target="#tabmodificaordine"]').on('show.bs.tab', function () {
	$('#numordine').attr('placeholder', getCookie('id') == 1 ? 'ID' : 'Progressivo');
	$('#modificaordine').html('Seleziona un ordine.');
	$('#numordine').val('');
});
var modarticoli = new bootstrap.Modal(document.getElementById('modalarticoli'));

var totalevecchio = 0;
var totalenuovo = 0;
var cassaVecchia = "";
var omaggio = false;
var omaggio2 = false;
var sconti = [];
var scontiapp = [];
function apriordine() {
	$('#modificaordine').html('<div class="spinner-border"></div> Caricamento dell\'ordine...').show();
	var num = $('#numordine').val();
	$.getJSON("php/ajaxcasse.php?a=righecomanda&num=" + num + "&identificatocon=" + (getCookie('id') == 1 ? 'ID' : 'prog') + "&" + infoturno())
	.done(function(json) {
		var out = '';
		$('#modificaordine').html('');
		scontiapp = [];
		try {
			var tipologia = null;
			var totale = 0;
			$.each(json, function(i, res) {
				if (res.tipo == 'ordine') {
					out = '<div class="row">';
					out += '<div class="col-1 text-center"><small>Ordine</small><br><span class="lead" id="progressivo">' + res.progressivo + '</div>';
					out += '<div class="col">';
					
					// Prima riga
					out += '<div class="row"><div class="col-auto">';
					out += '<small><span' + (!res.questoturno ? ' class="bg-danger text-white"' : '') + '>' + res.data + ', ' + res.ora.substring(0, 5) + '</span> - ';
					var casse = ['Cassa1', 'Cassa2', 'Cassa3'];
					out += '<select id="cassa" class="form-select form-select-sm d-inline" style="width: 100px; padding-right: 2rem;">';
					for (var i = 0; i < casse.length; i++) {
						out += '<option value="' + casse[i] + '"' + (casse[i] == res.cassa ? ' selected' : '') + '>' + casse[i] + '</option>';
					}					
					out += '</select>';
					cassaVecchia = res.cassa;
					out += ' - ID <span id="idordine">' + res.id + '</span> - ';
					out += 'Pagamento: <select id="tipo_pagamento" class="form-select form-select-sm d-inline" style="width: 150px;"><option value="CONTANTI"' + (res.tipo_pagamento == 'CONTANTI' ? ' selected' : '') + '>CONTANTI</option>';
					for (var i = 0; i < res.pagamenti.length; i++) {
						out += '<option value="' + res.pagamenti[i] + '"' + (res.tipo_pagamento == res.pagamenti[i] ? ' selected' : '') + '>' + res.pagamenti[i] + '</option>';
					}
					out += '</select></small></div>';
					out += '<div class="col my-auto"><small><div class="form-check" onchange="menu_omaggio();"><input class="form-check-input" type="checkbox" value="" id="omaggio"' + (res.menu_omaggio ? ' checked=""' : '') + '><label class="form-check-label" for="omaggio">Omaggio</label></div></small></div></div>';
					omaggio = omaggio2 = res.menu_omaggio;
					
					// Seconda riga
					out += '<div class="row"><div class="col">';
					out += '<span class="rigacomanda" style="cursor: pointer;" onclick="toggleCoperti();" id="desccoperti">' + (res.esportazione ? 'ASPORTO' : 'COPERTI') + '</span><span id="inputcoperti"' + (res.esportazione ? ' style="display: none;"' : '') + '>: <input class="form-control form-control-sm" id="valcoperti" type="number" min="0" style="display: inline; width: 70px;" value="' + res.coperti + '"></span>';
					out += '&emsp;Cliente: <input id="cliente" class="form-control form-control-sm" type="text" maxlength="254" style="display: inline; width: 150px;" value="' + res.cliente + '">';
					out += '&emsp;Tavolo: <input id="tavolo" class="form-control form-control-sm" type="text" min="0" style="display: inline; width: 70px;" value="' + res.tavolo + '">';
					out += '<span id="tagaddnoteordine"' + (res.note != '' ? ' class="d-none"' : '') + '>&emsp;<button class="btn btn-sm btn-light" onclick="$(\'#tagnoteordine\').removeClass(\'d-none\'); $(\'#tagaddnoteordine\').addClass(\'d-none\'); $(\'#noteordine\').addClass(\'riganote\');"><i class="bi bi-plus-lg"></i> Note</button></span>';
					out += '<span id="tagnoteordine"' + (res.note == '' ? ' class="d-none"' : '') + '><br>→&nbsp;<input class="form-control form-control-sm d-inline' + (res.note != '' ? ' riganote' : '') + '" type="text" id="noteordine" maxlength="254" style="width: 400px;" value="' + res.note + '" />&nbsp;<button class="btn btn-sm btn-light" onclick="$(\'#tagaddnoteordine\').removeClass(\'d-none\'); $(\'#tagnoteordine\').addClass(\'d-none\'); $(\'#noteordine\').removeClass(\'riganote\').addClass(\'toglinote\');;"><i class="bi bi-x-lg"></i></button></span></div></div>';
					out += '</div></div>';
					
					sconti = res.sconti;
				} else {
					out = '';
					if (res.tipo == 'riga_articolo') {
						if (res.tipologia != tipologia) {
							tipologia = res.tipologia;
							out = '<div class="row mt-2"><div class="col-12" style="border-bottom: 2px solid #000;"><strong>' + tipologia + '</strong></div></div>';
						}
						out += riga_articolo(res.id, res.descrizione, res.quantita, res.prezzo_unitario, res.note);
						totale += res.prezzo_unitario * res.quantita;
					} else {
						out += rigaSconto(res);
						scontiapp.push(res);
						let val = parseFloat(res.valore);
						scontiapp[scontiapp.length - 1].valore = val;
						totale -= val;
					}
				}
				$('#modificaordine').append(out);
			});
			if (json.length == 1) {
				$('#modificaordine').append('<br>L\'ordine richiesto non ha alcuna riga.');
			} else {
				// Aggiunta di righe
				out = '<div class="row mt-2 d-none" id="headaltrerighe"><div class="col-12" style="border-bottom: 2px solid #000;"><strong>Nuovi articoli</strong></div></div>';
				out += '<div id="altrerighe"></div>';
				out += '<br><button class="btn btn-primary" onclick="modaggiungiArticolo();"><i class="bi bi-plus-lg"></i> Aggiungi articolo</button><br>';

				// Aggiunta di sconti
				out += '<div id="altrisconti"></div>';
				for (let i = 0; i < sconti.length; i++) {
					out += '<br><button class="btn btn-secondary" onclick="aggiungiSconto(' + i + ');"><i class="bi bi-plus-lg"></i> ' + sconti[i].descrizione + '</button><br>';
				}
				out += '<div class="row mt-2"><div class="col-12" style="border-bottom: 2px solid #000;"></div></div>';
				out += '<div class="row d-flex align-items-center rigacomanda"><div class="col-2"></div><div class="col-8">TOTALE</div>';
				out += '<div class="col-2" style="text-align: right;" id="totalevecchio"><strong>' + prezzo(omaggio ? 0 : totale) + '</strong></div></div>';
				out += '<div class="row d-none align-items-center rigacomanda rigatotalenuovo"><div class="col-2"></div><div class="col-8">NUOVO TOTALE</div><div class="col-2" style="text-align: right;" id="totalenuovo"></div></div>';
				out += '<div class="row d-none align-items-center rigacomanda rigatotalenuovo"><div class="col-2"></div><div class="col-8" id="rigadiff"></div><div class="col-2" style="text-align: right;" id="diff"></div></div>';
				$('#modificaordine').append(out);
				totalevecchio = totale;
				totalenuovo = totale;
			}
			$('#modificaordine').append('<br><button class="btn btn-lg btn-success" onclick="$(\'#sicuro\').removeClass(\'d-none\');"><i class="bi bi-save"></i>&emsp;SALVA</button>&emsp;<span id="sicuro" class="d-none">Sei sicuro?&nbsp;<button class="btn btn-success" onclick="salvaordine();">Salva</button>&nbsp;<button class="btn btn-danger" onclick="$(\'#sicuro\').addClass(\'d-none\');">Annulla</button></span><br><br>');
		} catch (err) {
			$('#modificaordine').html('<span class="text-danger"><strong>Errore durante l\'analisi della risposta:</strong></span> ' + json);
			if (!($('#buttonupdatemonitor').hasClass('disabled')))
				updateModalMonitor();
		}
	})
	.fail(function(jqxhr, textStatus, error) {
		$('#modificaordine').html('<span class="text-danger"><strong>Errore durante la richiesta:</strong> </span>' + jqxhr.responseText);
		if (!($('#buttonupdatemonitor').hasClass('disabled')))
			updateModalMonitor();
	});
}

function riga_articolo(id, descrizione, quantita, prezzo_unitario, note) {
	let out = '';
	out += '<div class="row d-flex align-items-center rigacomanda' + (note != '' ? ' riganote' : '') + '" id="' + id + '"><div class="col-2 p-0"><div class="row d-flex align-items-center">';

	// Decremento
	out += '<div class="col" style="padding-right: 0px;"><button class="btn btn-sm btn-danger" onclick="cambiaqta(' + id + ', -1);"><i class="bi bi-dash-lg"></i></button></div>';

	// Quantità
	out += '<div class="col text-center p-0">';
	out += '<span id="tagqtaoriginale' + id + '" class="d-none"><del id="originale' + id + '">' + quantita + '</del><br></span>';
	out += '<strong id="quantita' + id + '">' + quantita + '</strong>';
	out += '<span class="d-none" id="unitario' + id + '">' + prezzo_unitario + '</span><span class="d-none" id="poriginale' + id + '">' + (prezzo_unitario * quantita) + '</span></div>';

	// Aumento
	out += '<div class="col" style="padding-left: 0px; text-align: right;"><button class="btn btn-sm btn-success" onclick="cambiaqta(' + id + ', 1);"><i class="bi bi-plus-lg"></i></button></div></div>';

	out += '</div><div class="col-8">' + descrizione;
	out += '<span id="tagaddnote' + id + '"' + (note != '' ? ' class="d-none"' : '') + '>&emsp;<button class="btn btn-sm btn-light" onclick="$(\'#tagnote' + id + '\').removeClass(\'d-none\'); $(\'#tagaddnote' + id + '\').addClass(\'d-none\'); $(\'#' + id + '\').addClass(\'riganote\');"><i class="bi bi-plus-lg"></i> Note</button></span>';
	out += '<span id="tagnote' + id + '"' + (note == '' ? ' class="d-none"' : '') + '><br>→&nbsp;<input class="form-control form-control-sm d-inline" type="text" id="note' + id + '" maxlength="254" style="width: 300px;" value="' + note + '" />&nbsp;<button class="btn btn-sm btn-light" onclick="$(\'#tagaddnote' + id + '\').removeClass(\'d-none\'); $(\'#tagnote' + id + '\').addClass(\'d-none\'); $(\'#' + id + '\').removeClass(\'riganote\').addClass(\'toglinote\');;"><i class="bi bi-x-lg"></i></button></span>' + '</div>';
	out += '<div class="col-2" style="text-align: right;" id="prezzo' + id + '">' + prezzo(prezzo_unitario * quantita) + '</div></div>';
	return out;
}

function prezzo(p) {
	return '&euro;&nbsp;' + ('' + p).replace(".", ",") + ((p - Math.trunc(p)) != 0 ? '0' : ',00');
}

function toggleCoperti() {
	if ($('#desccoperti').html() == 'COPERTI') {
		$('#desccoperti').html('ASPORTO');
		$('#inputcoperti').hide();
	} else {
		$('#desccoperti').html('COPERTI');
		$('#inputcoperti').show();
	}
}

function rigaSconto(res) {
	let out = '<div class="row rigacomanda" id="sconto' + res.id + '">';
	out += '<div class="col-2" style="text-align: center;"><button class="btn btn-danger" onclick="togliSconto(\'' + res.id + '\');"><i class="bi bi-x-lg"></i></button></div>';
	out += '<div class="col-8">' + res.descrizione + '</div>';
	out += '<div class="col-2" style="text-align: right;">- ' + prezzo(res.valore) + '</div>';
	out += '</div>';
	return out;
}

function menu_omaggio() {
	omaggio2 = document.getElementById('omaggio').checked;
	aggiornatotale();
}

function cambiaqta(idriga, diff) {
	var qta = parseInt($('#quantita' + idriga).html());
	var qtanuova = qta + diff;
	if (qtanuova < 0)
		return;
	
	var qtaoriginale = parseInt($('#originale' + idriga).html());
	var prezzounitario = parseFloat($('#unitario' + idriga).html());
	var prezzonuovo = prezzounitario * qtanuova;
	var prezzooriginale = parseFloat($('#poriginale' + idriga).html());
	$('#quantita' + idriga).html(qtanuova);
	totalenuovo += prezzounitario * diff;
	if (qtanuova == qtaoriginale) {
		$('#tagqtaoriginale' + idriga).addClass('d-none');
		$('#' + idriga).removeClass('rigadec');
		$('#' + idriga).removeClass('rigainc');
		$('#' + idriga).addClass('rigacomanda');
		$('#prezzo' + idriga).html(prezzo(prezzonuovo));
	} else {
		$('#tagqtaoriginale' + idriga).removeClass('d-none');
		$('#' + idriga).removeClass('rigacomanda');
		$('#' + idriga).addClass(qtanuova > qtaoriginale ? 'rigainc' : 'rigadec');
		$('#prezzo' + idriga).html('<del>' + prezzo(prezzooriginale) + '</del><br><strong>' + prezzo(prezzonuovo) + '</strong>');
	}
	aggiornatotale();
}

function togliSconto(id) {
	$('#sconto' + id).remove();
	for (let i = 0; i < scontiapp.length; i++) {
		if (scontiapp[i].id == id) {
			totalenuovo += scontiapp[i].valore;
			scontiapp[i].del = true;
		}
	}
	aggiornatotale();
}

var scontik = 0;
function aggiungiSconto(id) {
	let nuovo = {"tipo": "riga_sconto", "id": "-" + scontik++, "idbase": sconti[id].id, "valore": parseFloat(sconti[id].valore), "descrizione": sconti[id].descrizione, "nuovo": true};
	scontiapp.push(nuovo);
	$('#altrisconti').append(rigaSconto(nuovo));
	totalenuovo -= parseFloat(nuovo.valore);
	aggiornatotale();
}

var lista_articoli = null;
function modaggiungiArticolo() {
	$.getJSON("php/ajaxcasse.php?a=articoli")
	.done(function(json) {
		let out = '<p><strong class="text-danger">Attenzione!</strong> Controllare manualmente se ci sono problemi di giacenza dei singoli articoli.</p>';
		let tipologia = null;
		lista_articoli = json;
		$.each(json, function(i, res) {
			if (res.desc_tipologia != tipologia) {
				out += '<h5>' + res.desc_tipologia + '</h5><hr class="mt-0" />';
				tipologia = res.desc_tipologia;
			}
			out += '<button class="btn p-3 me-2 mb-2" style="background-color: #' + convertiColore(res.sfondo) + '; border: 1px solid black;" onclick="aggiungiArticolo(' + i + ');">' + res.descrizionebreve + '</button>';
		});
		$('#modalarticolibody').html(out);
		modarticoli.show();
	})
	.fail(function(jqxhr, textStatus, error) {
		$('#modificaordine').html('<span class="text-danger"><strong>Errore durante la richiesta:</strong> </span>' + jqxhr.responseText);
		if (!($('#buttonupdatemonitor').hasClass('disabled')))
			updateModalMonitor();
	});
}

function convertiColore(str) {
	//return (parseInt(str).toString(16)).padStart(6, '0');
    let hexColor = parseInt(str).toString(16);
    while (hexColor.length < 6) {
        hexColor = '0' + hexColor;
    }
    return hexColor;
}

function aggiungiArticolo(index) {
	modarticoli.hide();
	let id_riga = (-1) * lista_articoli[index].id;
	let out = riga_articolo(id_riga, lista_articoli[index].descrizione, 0, lista_articoli[index].prezzo, '');
	$('#headaltrerighe').removeClass('d-none');
	$('#altrerighe').append(out);
	cambiaqta(id_riga, +1);
}

function aggiornatotale() {
	let tvecchio = omaggio ? 0 : totalevecchio;
	let tnuovo = omaggio2 ? 0 : totalenuovo;
	if (totalenuovo == totalevecchio && omaggio == omaggio2) { // Se si è tornati al punto di partenza
		$('.rigatotalenuovo').removeClass('d-flex');
		$('.rigatotalenuovo').addClass('d-none');
		$('#totalevecchio').html('<strong>' + prezzo(tvecchio) + '</strong>');
	} else {
		$('.rigatotalenuovo').removeClass('d-none');
		$('.rigatotalenuovo').addClass('d-flex');
		$('#totalevecchio').html('<del>' + prezzo(tvecchio) + '</del>');
		$('#totalenuovo').html('<strong>' + prezzo(tnuovo) + '</strong>');
		$('#rigadiff').html((tnuovo > tvecchio ? '<span class="text-success">CHIEDERE' : '<span class="text-danger">PAGARE') + ' AL GENT. CLIENTE');
		$('#diff').html('<strong class="text-' + (tnuovo > tvecchio ? 'success' : 'danger') + '">' + prezzo(Math.abs(tnuovo - tvecchio)) + '</strong>');
	}
}

function salvaordine() {
	let tvecchio = omaggio ? 0 : totalevecchio;
	let tnuovo = omaggio2 ? 0 : totalenuovo;
	let id = $('#idordine').html();
	let progressivo = $('#progressivo').html();
	let querystring = "php/ajaxcasse.php?a=salvaordine" +
		"&id=" + id +
		"&tavolo=" + $('#tavolo').val() +
		"&cliente=" + $('#cliente').val() +
		"&coperti=" + $('#valcoperti').val() +
		"&esportazione=" + ($('#desccoperti').html() == 'COPERTI' ? 'false' : 'true') +
		"&totale=" + tnuovo +
		"&totalevecchio=" + tvecchio +
		"&tipo_pagamento=" + $('#tipo_pagamento').val() +
		"&cassa=" + $('#cassa').val() +
		"&cassavecchia=" + cassaVecchia +
		"&menu_omaggio=" + (omaggio2 ? 'true' : 'false');
	for (let i = 0; i < scontiapp.length; i++) {
		if (scontiapp[i].nuovo && scontiapp[i].del == null) {
			querystring += "&addsconti[]=" + scontiapp[i].idbase;
		}
		if (scontiapp[i].nuovo != null && scontiapp[i].del) {
			querystring += "&delsconti[]=" + scontiapp[i].id;
		}
	}
	$('.rigainc, .rigadec').each(function() {
		querystring += '&righe[' + $(this).attr('id') + ']=' + $('#quantita' + $(this).attr('id')).html();
	});
	$('.riganote, .toglinote').each(function() {
		if ($(this).attr('id') == 'noteordine') {
			querystring += '&righenote[noteordine]=' + ($(this).hasClass('toglinote') ? '' : $(this).val());
		} else {
			querystring += '&righenote[' + $(this).attr('id') + ']=' + ($(this).hasClass('toglinote') ? '' : $('#note' + $(this).attr('id')).val());
		}
	});
	$.ajax({
		url: querystring,
		success: function(res) {
			if (res == '1') {
				mostratoast(true, '<i class="bi bi-save"></i>&emsp;Salvataggio riuscito!');
				aprimodifica(id, progressivo);
			} else {
				mostratoast(false, "Salvataggio fallito: " + res);
			}
		},
		error: function() { // Server non raggiungibile
			mostratoast(false, "Richiesta fallita.");
		},
		timeout: 3000
	});
}

function aprimodifica(id, progressivo) {
	var tab = new bootstrap.Tab(document.querySelector('.nav-pills a[data-bs-target="#tabmodificaordine"]'));
	tab.show();
	$('#numordine').val(getCookie('id') == 1 ? id : progressivo);
	apriordine();
}
</script>