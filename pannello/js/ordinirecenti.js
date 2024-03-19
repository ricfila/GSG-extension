
$('.nav-pills a[data-bs-target="#tabordinirecenti"]').on('show.bs.tab', function () {
	ultimiordini();
	cassaAperta = null;
});

function ultimiordini() {
	$('#bodyhome').html('<div class="spinner-border"></div> Caricamento degli ordini...');
	$.getJSON("php/ajaxcasse.php?a=ultimiordini&" + infoturno())
	.done(function(json) {
		var out = '';
		$('#bodyhome').html('');
		try {
			out = '<div class="row">';
			$.each(json, function(i, res) {
				out += '<div class="col colcassa" id="' + res.cassa + '"><div class="row"><div class="col"><h5><strong>' + res.cassa + '</strong></h5></div><div class="col-auto"><button class="btn btn-outline-info btn-sm" id="btnexpand' + res.cassa + '" onclick="toggleCassa(\'' + res.cassa + '\');"><i class="bi bi-arrows-angle-expand"></i></button></div></div>';
				out += '<small><ul><li>' + res.totale + ' ordini emessi</li><li>' + (res.totale - res.evasi) + ' ancora da evadere</li></ul></small><hr>';
				$.each(res.ordini, function(i, res2) {
					out += '<div class="row rigaordine p-2 m-0" style="border-radius: 5px;"><div class="col-4 p-0"><button class="btn btn-' + (res2.esportazione ? 'info' : 'success') + ' btn-sm h-100 w-100 p-0" onclick="aprimodifica(' + res2.id + ', ' + res2.progressivo + ');">' + (res2.evaso ? '<i class="bi bi-check-circle-fill"></i>' : '<i class="bi bi-cart3"></i>') + '&nbsp;<strong class="lead">' + numero(res2.id, res2.progressivo) + '</strong></button></div>';
					out += '<div class="col">ore ' + res2.ora.substr(0, 5) + ' - <i>' + res2.cliente + '</i></div></div>';
				});
				out += '</div>';
			});
			out += '</div>';
			if (json.length == 0) {
				$('#bodyhome').html('Nessun ordine trovato per il turno corrente.');
			} else {
				$('#bodyhome').html(out);
			}
		} catch (err) {
			$('#bodyhome').html('<span class="text-danger"><strong>Errore durante l\'analisi della risposta:</strong></span> ' + err + ' - ' + JSON.stringify(json));
			if (!($('#buttonupdatemonitor').hasClass('disabled')))
				updateModalMonitor();
		}
	})
	.fail(function(jqxhr, textStatus, error) {
		$('#bodyhome').html('<span class="text-danger"><strong>Errore durante la richiesta:</strong> </span>' + jqxhr.responseText);
		if (!($('#buttonupdatemonitor').hasClass('disabled')))
			updateModalMonitor();
	});
}

cassaAperta = null;
function toggleCassa(cassa) {
	if (cassaAperta == null) {
		$('.colcassa').each(function() {
			if ($(this).attr('id') != cassa)
				$(this).addClass('d-none');
		});
		cassaAperta = cassa;
		$('#btnexpand' + cassa).html('<i class="bi bi-arrows-angle-contract"></i>');
	} else {
		$('.colcassa').each(function() {
			$(this).removeClass('d-none');
		});
		$('#btnexpand' + cassa).html('<i class="bi bi-arrows-angle-expand"></i>');
		cassaAperta = null;
	}
}