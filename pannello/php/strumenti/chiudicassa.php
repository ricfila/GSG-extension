
<div class="modal fade" id="modalcasse">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Chiusura cassa</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body" id="modalcassebody" style="text-align: center;">
			</div>
		</div>
	</div>
</div>

<script>
var modcasse = new bootstrap.Modal(document.getElementById('modalcasse'));
var jsons;
var casse = [];

function preparaChiudiCassa(target) {
	var out = '';
	$.getJSON("php/ajax.php?a=chiudicassa")
	.done(function(json) {
		out = '<i class="bi bi-info-circle-fill"></i> Per avviare la stampa, fare clic nella scheda che si apre.<br><br>';
		casse = [];
		try {
			if (json.length == 0) {
				$(target).html('Nessun totale da stampare.');
			} else {
				$.each(json, function(i, res) {
					var dt = new Date(res.data + 'T' + (res.pranzo_cena == 'pranzo' ? '00:00:00' : '17:00:00'));
					if (!casse.includes(res.cassa) && stessoTurno(data, dt))
						casse.push(res.cassa);
				});
				
				out += '<div class="row"><div class="col">';
				out += '<strong>Per il turno di ' + giorni[data.getDay()].toLowerCase() + ' ' + data.getDate() + ' ' + (pranzo(data) ? 'pranzo' : 'cena') + ':</strong><br><br>';
				if (casse.length > 0) {
					casse.sort();
					for (var i = 0; i < casse.length; i++)
						out += '<button class="btn btn-lg btn-primary" style="margin-bottom: 10px;" onclick="chiudiCassa(\'' + casse[i] + '\');"><i class="bi bi-file-earmark-arrow-down"></i> Chiusura ' + casse[i] + '</button><br>';
				
					out += '<button class="btn btn-lg btn-dark" style="margin-bottom: 10px;" onclick="chiudiCassa(true);"><i class="bi bi-piggy-bank"></i> Rendiconto del turno</button>';
				} else {
					out += 'Nessun incasso <i class="bi bi-currency-exchange"></i>';
				}
				
				out += '</div><div class="col">';
				out += '<strong>Per l\'intera durata della sagra:</strong><br><br>';
				out += '<button class="btn btn-lg btn-dark" style="margin-bottom: 10px;" onclick="chiudiCassa(false);"><i class="bi bi-table"></i> Rendiconto completo</button>';
				out += '</div></div>';
				jsons = json;
				$(target).html(out);
			}
		} catch (err) {
			$(target).html('<strong class="text-danger">Errore durante la richiesta:</strong> ' + json);
		}
	})
	.fail(function(jqxhr, textStatus, error) {
		$(target).html('<strong class="text-danger">Richiesta fallita:</strong> ' + textStatus);
	});
}

function chiudiCassaModal() {
	preparaChiudiCassa('#modalcassebody');
	modcasse.show();
}

$('.nav-pills a[data-bs-target="#tabchiudicassa"]').on('show.bs.tab', function () {
	preparaChiudiCassa('#chiudicassabody');
});

function chiudiCassa(action) {
	modcasse.hide();
	var oggi = new Date();
	my_window = window.open('', '_blank');
	my_window.document.write('<html><head><title>Rendiconto ' + (action == false ? 'completo' : (action == true ? 'del turno' : action)) + '</title><link href="bootstrap-5.0.2-dist/css/bootstrap.css" rel="stylesheet" /><link href="bootstrap-5.0.2-dist/css/bootstrap-icons.css" rel="stylesheet" /><!--script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script--><style>@page {size: auto; margin: 0;}</style><?php echo icona(); ?></head>');
	my_window.document.write('<body style="height: 100%;" onclick="print(); window.close();"><center style="padding: 30px;">');
	
	if (action === false) { // Rendiconto completo
		my_window.document.write('<h1>Rendiconto completo ' + oggi.getFullYear() + '</h1><br>');
		
		var intest = '<table class="table table-striped" style="font-size: 1.2em;"><thead><tr><th class="p-2">Cassa</th><th class="p-2">Pagamento</th><th class="p-2">Incasso</th></tr></thead><tbody>';
		
		var turno = null;
		var totale = 0;
		$.each(jsons, function(i, res) {
			var dt = new Date(res.data);
			var questoturno = giorni[dt.getDay()] + ' ' + dt.getDate() + ' ' + res.pranzo_cena;
			if (questoturno != turno) {
				if (turno != null)
					my_window.document.write('<tr><td colspan="2" class="p-2"><strong>Totale</strong></td><td class="p-2"><strong>&euro; ' + (''+ totale).replace(".", ",") + ((totale - Math.trunc(totale)) != 0 ? '0' : ',00') + '</strong></td></tr>');
				my_window.document.write((turno != null ? '</tbody></table><br>' : '') + '<h3><strong>' + questoturno + '</strong></h3>' + intest);
				turno = questoturno;
				totale = 0;
			}
			my_window.document.write('<tr><td class="p-2">' + res.cassa + '</td><td class="p-2">' + res.tipo_pagamento + '</td><td class="p-2">&euro; ' + (''+ res.importo_totale).replace(".", ",") + ((res.importo_totale - Math.trunc(res.importo_totale)) != 0 ? '0' : ',00') + '</td></tr>');
			totale += res.importo_totale;
		});
		if (totale > 0)
			my_window.document.write('<tr><td colspan="2" class="p-2"><strong>Totale</strong></td><td class="p-2"><strong>&euro; ' + (''+ totale).replace(".", ",") + ((totale - Math.trunc(totale)) != 0 ? '0' : ',00') + '</strong></td></tr>');
		my_window.document.write('</tbody></table>');
	} else {
		if (action === true) { // Rendiconto del turno
			my_window.document.write('<h1>Rendiconto del turno</h1><br>');
		} else { // Cassa singola
			my_window.document.write('<h1>Rendiconto di cassa</h1><br>');
		}
		
		// Stampa informazioni sul turno
		var turnot = (pranzo(data) ? 'pranzo' : 'cena');
		my_window.document.write('<h4><i class="bi bi-calendar-check"></i> ' + giorni[data.getDay()] + ' ' + data.getDate() + ' ' + turnot + '<br>');
		if (stessoTurno(data, oggi))
			my_window.document.write('<i class="bi bi-clock"></i> stampato alle ore ' + (oggi.getHours() < 10 ? '0' : '') + oggi.getHours() + ':' + (oggi.getMinutes() < 10 ? '0' : '') + oggi.getMinutes() + '</h4><br>');
		else
			my_window.document.write('<i class="bi bi-arrow-clockwise"></i> Stampa tardiva</h4><br>');
		
		if (action != true)
			casse = [action];
		
		var totali = {};
		for (var i = 0; i < casse.length; i++) {
			my_window.document.write('<h1 style="border: 5px solid black; border-radius: 10px; padding: 10px;"><strong>' + casse[i] + '</strong></h1>');
			var importo = [];
			$.each(jsons, function(k, res) {
				if (res.cassa == casse[i] && turnot == res.pranzo_cena && res.data == dataToString(data))
					importo.push({tipo_pagamento: res.tipo_pagamento, importo: res.importo_totale});
			});
			
			if (importo.length == 0) {
				my_window.document.write('<span style="font-size: 2em;">Nessun incasso</span>');
			} else {
				for (var j = 0; j < importo.length; j++) {
					my_window.document.write('<div class="row align-items-center" style="font-size: 2em;"><div class="col no-pad" style="text-align: left;">' + importo[j].tipo_pagamento + '</div><div class="col-auto no-pad" style="text-align: right;"><strong>&euro; ' + ('' + importo[j].importo).replace(".", ",") + ((importo[j].importo - Math.trunc(importo[j].importo)) != 0 ? '0' : ',00') + '</strong></div></div>');
					if (totali[importo[j].tipo_pagamento] == null)
						totali[importo[j].tipo_pagamento] = importo[j].importo;
					else
						totali[importo[j].tipo_pagamento] += importo[j].importo;
				}
			}
			my_window.document.write('<br>');
		}
		if (casse.length > 1 || Object.keys(totali).length > 1) {
			var complessivo = 0;
			my_window.document.write('<hr style="border: 5px solid black; border-radius: 10px;">');
			for (var i = 0; i < Object.keys(totali).length; i++) {
				if (casse.length > 1) {
					my_window.document.write('<div class="row align-items-center" style="font-size: 2em;"><div class="col no-pad" style="text-align: left;">TOTALE ' + Object.keys(totali)[i] + '</div><div class="col-auto no-pad" style="text-align: right;"><strong>&euro; ' + ('' + Object.values(totali)[i]).replace(".", ",") + ((Object.values(totali)[i] - Math.trunc(Object.values(totali)[i])) != 0 ? '0' : ',00') + '</strong></div></div>');
				}
				complessivo += Object.values(totali)[i];
			}
			if (Object.keys(totali).length > 1) {
				my_window.document.write('<div class="row align-items-center" style="font-size: 2em;"><div class="col no-pad" style="text-align: left;">COMPLESSIVO</div><div class="col-auto no-pad" style="text-align: right;"><strong>&euro; ' + ('' + complessivo).replace(".", ",") + ((complessivo - Math.trunc(complessivo)) != 0 ? '0' : ',00') + '</strong></div></div>');
			}
		}
	}
	my_window.document.write('</center></body></html>');
}
</script>