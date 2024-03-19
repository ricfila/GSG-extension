
$('.nav-pills a[data-bs-target="#tabmodificaordine"]').on('show.bs.tab', function () {
	$('#numordine').attr('placeholder', getCookie('id') == 1 ? 'ID' : 'Progressivo');
	$('#modificaordine').html('Seleziona un ordine.');
	$('#numordine').val('');
});

var totalevecchio = 0;
var totalenuovo = 0;
var cassaVecchia = "";
function apriordine() {
	$('#modificaordine').html('<div class="spinner-border"></div> Caricamento dell\'ordine...').show();
	var num = $('#numordine').val();
	$.getJSON("php/ajaxcasse.php?a=righecomanda&num=" + num + "&identificatocon=" + (getCookie('id') == 1 ? 'ID' : 'prog') + "&" + infoturno())
	.done(function(json) {
		var out = '';
		$('#modificaordine').html('');
		try {
			var tipologia = null;
			var totale = 0;
			$.each(json, function(i, res) {
				if (res.tipo == 'ordine') {
					out = '<div class="row">';
					out += '<div class="col-1 text-center"><small>Ordine</small><br><span class="lead" id="progressivo">' + res.progressivo + '</div>';
					out += '<div class="col"><small><span' + (!res.questoturno ? ' class="bg-danger text-white"' : '') + '>' + res.data + ', ' + res.ora.substring(0, 5) + '</span> - ';
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
					out += '</select></small><br>';
					out += '<span class="rigacomanda" style="cursor: pointer;" onclick="toggleCoperti();" id="desccoperti">' + (res.esportazione ? 'ASPORTO' : 'COPERTI') + '</span><span id="inputcoperti"' + (res.esportazione ? ' style="display: none;"' : '') + '>: <input class="form-control form-control-sm" id="valcoperti" type="number" min="0" style="display: inline; width: 70px;" value="' + res.coperti + '"></span>';
					out += '&emsp;Cliente: <input id="cliente" class="form-control form-control-sm" type="text" maxlength="254" style="display: inline; width: 150px;" value="' + res.cliente + '">';
					out += '&emsp;Tavolo: <input id="tavolo" class="form-control form-control-sm" type="text" min="0" style="display: inline; width: 70px;" value="' + res.tavolo + '">';
					out += '<span id="tagaddnoteordine"' + (res.note != '' ? ' class="d-none"' : '') + '>&emsp;<button class="btn btn-sm btn-light" onclick="$(\'#tagnoteordine\').removeClass(\'d-none\'); $(\'#tagaddnoteordine\').addClass(\'d-none\'); $(\'#noteordine\').addClass(\'riganote\');"><i class="bi bi-plus-lg"></i> Note</button></span>';
					out += '<span id="tagnoteordine"' + (res.note == '' ? ' class="d-none"' : '') + '><br>→&nbsp;<input class="form-control form-control-sm d-inline' + (res.note != '' ? ' riganote' : '') + '" type="text" id="noteordine" maxlength="254" style="width: 400px;" value="' + res.note + '" />&nbsp;<button class="btn btn-sm btn-light" onclick="$(\'#tagaddnoteordine\').removeClass(\'d-none\'); $(\'#tagnoteordine\').addClass(\'d-none\'); $(\'#noteordine\').removeClass(\'riganote\').addClass(\'toglinote\');;"><i class="bi bi-x-lg"></i></button></span></div></div>';
				} else {
					out = '';
					if (res.tipologia != tipologia) {
						tipologia = res.tipologia;
						out = '<div class="row mt-2"><div class="col-12" style="border-bottom: 2px solid #000;"><strong>' + tipologia + '</strong></div></div>';
					}
					out += '<div class="row d-flex align-items-center rigacomanda' + (res.note != '' ? ' riganote' : '') + '" id="' + res.id + '"><div class="col-2 p-0"><div class="row d-flex align-items-center">';
					out += '<div class="col" style="padding-right: 0px;"><button class="btn btn-sm btn-danger" onclick="cambiaqta(' + res.id + ', -1);"><i class="bi bi-dash-lg"></i></button></div>';
					out += '<div class="col text-center p-0"><span id="tagqtaoriginale' + res.id + '" class="d-none"><del id="originale' + res.id + '">' + res.quantita + '</del><br></span><strong id="quantita' + res.id + '">' + res.quantita + '</strong><span class="d-none" id="unitario' + res.id + '">' + res.prezzo_unitario + '</span><span class="d-none" id="poriginale' + res.id + '">' + (res.prezzo_unitario * res.quantita) + '</span></div>';
					out += '<div class="col" style="padding-left: 0px; text-align: right;"><button class="btn btn-sm btn-success" onclick="cambiaqta(' + res.id + ', 1);"><i class="bi bi-plus-lg"></i></button></div></div>';
					out += '</div><div class="col-8">' + res.descrizione;
					out += '<span id="tagaddnote' + res.id + '"' + (res.note != '' ? ' class="d-none"' : '') + '>&emsp;<button class="btn btn-sm btn-light" onclick="$(\'#tagnote' + res.id + '\').removeClass(\'d-none\'); $(\'#tagaddnote' + res.id + '\').addClass(\'d-none\'); $(\'#' + res.id + '\').addClass(\'riganote\');"><i class="bi bi-plus-lg"></i> Note</button></span>';
					out += '<span id="tagnote' + res.id + '"' + (res.note == '' ? ' class="d-none"' : '') + '><br>→&nbsp;<input class="form-control form-control-sm d-inline" type="text" id="note' + res.id + '" maxlength="254" style="width: 300px;" value="' + res.note + '" />&nbsp;<button class="btn btn-sm btn-light" onclick="$(\'#tagaddnote' + res.id + '\').removeClass(\'d-none\'); $(\'#tagnote' + res.id + '\').addClass(\'d-none\'); $(\'#' + res.id + '\').removeClass(\'riganote\').addClass(\'toglinote\');;"><i class="bi bi-x-lg"></i></button></span>' + '</div>';
					out += '<div class="col-2" style="text-align: right;" id="prezzo' + res.id + '">' + prezzo(res.prezzo_unitario * res.quantita) + '</div></div>';
					totale += res.prezzo_unitario * res.quantita;
				}
				$('#modificaordine').append(out);
			});
			if (json.length == 1) {
				$('#modificaordine').append('<br>L\'ordine richiesto non ha alcuna riga.');
			} else {
				out = '<div class="row mt-2"><div class="col-12" style="border-bottom: 2px solid #000;"></div></div>';
				out += '<div class="row d-flex align-items-center rigacomanda"><div class="col-2"></div><div class="col-8">TOTALE</div>';
				out += '<div class="col-2" style="text-align: right;" id="totalevecchio"><strong>' + prezzo(totale) + '</strong></div></div>';
				out += '<div class="row d-none align-items-center rigacomanda rigatotalenuovo"><div class="col-2"></div><div class="col-8">NUOVO TOTALE</div><div class="col-2" style="text-align: right;" id="totalenuovo"></div></div>';
				out += '<div class="row d-none align-items-center rigacomanda rigatotalenuovo"><div class="col-2"></div><div class="col-8" id="rigadiff"></div><div class="col-2" style="text-align: right;" id="diff"></div></div>';
				$('#modificaordine').append(out);
				totalevecchio = totale;
				totalenuovo = totale;
			}
			$('#modificaordine').append('<br><button class="btn btn-lg btn-success" onclick="$(\'#sicuro\').removeClass(\'d-none\');"><i class="bi bi-save"></i>&emsp;Salva</button>&emsp;<span id="sicuro" class="d-none">Sei sicuro?&nbsp;<button class="btn btn-success" onclick="salvaordine();">Salva</button>&nbsp;<button class="btn btn-danger" onclick="$(\'#sicuro\').addClass(\'d-none\');">Annulla</button></span><br><br>');
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
	})
	.always(function() {
		
	});
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

function cambiaqta(idriga, diff) {
	var qta = parseInt($('#quantita' + idriga).html());
	var qtanuova = qta + diff;
	if (qtanuova >= 0) {
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
		if (totalenuovo == totalevecchio) {
			$('.rigatotalenuovo').removeClass('d-flex');
			$('.rigatotalenuovo').addClass('d-none');
			$('#totalevecchio').html('<strong>' + prezzo(totalevecchio) + '</strong>');
		} else {
			$('.rigatotalenuovo').removeClass('d-none');
			$('.rigatotalenuovo').addClass('d-flex');
			$('#totalevecchio').html('<del>' + prezzo(totalevecchio) + '</del>');
			$('#totalenuovo').html('<strong>' + prezzo(totalenuovo) + '</strong>');
			$('#rigadiff').html((totalenuovo > totalevecchio ? '<span class="text-success">CHIEDERE' : '<span class="text-danger">PAGARE') + ' AL GENT. CLIENTE');
			$('#diff').html('<strong class="text-' + (totalenuovo > totalevecchio ? 'success' : 'danger') + '">' + prezzo(Math.abs(totalenuovo - totalevecchio)) + '</strong>');
		}
	}
}

function salvaordine() {
	var id = $('#idordine').html();
	var progressivo = $('#progressivo').html();
	var querystring = "php/ajaxcasse.php?a=salvaordine" +
		"&id=" + id +
		"&tavolo=" + $('#tavolo').val() +
		"&cliente=" + $('#cliente').val() +
		"&coperti=" + $('#valcoperti').val() +
		"&esportazione=" + ($('#desccoperti').html() == 'COPERTI' ? 'false' : 'true') +
		"&totale=" + totalenuovo +
		"&totalevecchio=" + totalevecchio +
		"&tipo_pagamento=" + $('#tipo_pagamento').val() +
		"&cassa=" + $('#cassa').val() +
		"&cassavecchia=" + cassaVecchia;
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