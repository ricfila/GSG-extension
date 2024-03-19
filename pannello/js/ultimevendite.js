
$('.nav-pills a[data-bs-target="#tabultimevendite"]').on('show.bs.tab', function () {
	statristrette();
	ingredienti();
});

var minuti = getCookie('minuti');
range(minuti != "" ? minuti : 10);

function range(num) {
	$('#ingminuti').html(num);
	$('#rangeminuti').val(61 - num);
	$('.form-range').css('--percent', '-' + ((61 - num) / 60 * 100) + '%');
	setCookie('minuti', num);
}

function statristrette() {
	$('#statristrette').html('<div class="spinner-border"></div> Calcoli superdifficili...');
	$.ajax({
		url: "php/ajaxcasse.php?a=statisticheristrette&questoturno=" + dataToString(data) + (pranzo(data) ? 'pranzo' : 'cena') + "&" + infoturno(),
		success: function(res) {
			$('#statristrette').html(res);
		},
		error: function() { // Server non raggiungibile
			$('#statristrette').html('Richiesta fallita');
		},
		timeout: 2000
	});
}

function ingredienti() {
	$('#ingredienti').html('<div class="spinner-border"></div> Ricerca degli ingredienti...');
	$.getJSON("php/ajaxcasse.php?a=ingredienti&minuti=" + $('#ingminuti').html() + "&" + infoturno())
	.done(function(json) {
		var out = '';
		$('#ingredienti').html('');
		try {
			bar = cucina = '';
			$.each(json, function(i, res) {
				out = '<div class="row rigacomanda"><div class="col-4">' + res.descrizione + '</div>';
				out += '<div class="col-4"><strong>' + res.qta + '</strong> <i><small>(' + res.comande + ' comand' + (res.comande == 1 ? 'a' : 'e') + ')</small></i></div>';
				out += '<div class="col-4">' + (res.prezzo != 1 ? res.prezzo : '') + '</div></div>';
				if (res.copia == 'bar')
					bar += out;
				else
					cucina += out;
			});
			if (json.length == 0) {
				$('#ingredienti').html('Nessuna pietanza rimasta da servire.');
			} else {
				var head = '<div class="row"><div class="col-4">Descrizione</div><div class="col-4">Quantità</div><div class="col-4">Normalizzato</div></div><hr class="mt-0 mb-2">';
				$('#ingredienti').html('<div class="row">' + (cucina.length > 0 ? '<div class="col-6"><h5><i class="bi bi-flag"></i> Ingredienti</h5>' + head + cucina + '</div>' : '') + (bar.length > 0 ? '<div class="col-6"><h5><i class="bi bi-droplet"></i> Bevande</h5>' + head + bar + '</div></div>' : ''));
			}
		} catch (err) {
			$('#ingredienti').html('<span class="text-danger"><strong>Errore durante l\'analisi della risposta:</strong></span> ' + err + ' - ' + JSON.stringify(json));
			if (!($('#buttonupdatemonitor').hasClass('disabled')))
				updateModalMonitor();
		}
	})
	.fail(function(jqxhr, textStatus, error) {
		$('#ingredienti').html('<span class="text-danger"><strong>Errore durante la richiesta:</strong> </span>' + jqxhr.responseText);
		if (!($('#buttonupdatemonitor').hasClass('disabled')))
			updateModalMonitor();
	});
}