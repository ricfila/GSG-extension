
<div class="modal fade" id="modalcasse">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Stampa rapporti</h5>
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
		out = '';
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
				let nometurno = giorni[data.getDay()].toLowerCase() + ' ' + data.getDate() + ' ' + (pranzo(data) ? 'pranzo' : 'cena');

				out += '<div class="row"><div class="col">';

				// Rendiconti di cassa
				out += '<h4 class="mb-3"><i class="bi bi-cash-coin"></i> Rendiconti di cassa</h4>'
				out += '<p><strong>Per il turno di ' + nometurno + ':</strong></p>';
				if (casse.length > 0) {
					casse.sort();
					for (var i = 0; i < casse.length; i++)
						out += '<button class="btn btn-primary mb-2" onclick="chiudiCassa(\'' + casse[i] + '\');"><i class="bi bi-file-earmark-arrow-down"></i> Chiusura ' + casse[i] + '</button><br>';
				
					out += '<button class="btn btn-outline-dark" style="margin-bottom: 10px;" onclick="chiudiCassa(true);"><i class="bi bi-piggy-bank"></i> Rendiconto del turno</button>';
				} else {
					out += 'Nessun incasso <i class="bi bi-currency-exchange"></i>';
				}

				out += '<hr><p><strong>Per l\'intera durata della sagra:</strong></p>';
				out += '<button class="btn btn-outline-dark" style="margin-bottom: 10px;" onclick="chiudiCassa(false);"><i class="bi bi-table"></i> Rendiconto completo</button>';
				out += '</div><div class="col">';

				// Statistiche di vendita
				out += '<h4 class="mb-3"><i class="bi bi-graph-up-arrow"></i> Statistiche di vendita</h4>';
				out += '<div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="statservizio" checked /><label for="statservizio">Includi statistiche sul servizio</label></div>';

				out += '<p><strong>Per il turno di ' + nometurno + ':</strong></p>';
				out += '<button class="btn btn-outline-success mb-2" onclick="reportarticoli(\'Articoli\', true);"><i class="bi bi-file-earmark-bar-graph"></i> Articoli venduti</button><br>';
				out += '<button class="btn btn-outline-warning" onclick="reportarticoli(\'Ingredienti\', true);"><i class="bi bi-file-earmark-ruled"></i> Ingredienti venduti</button>';
				
				out += '<hr><p><strong>Per l\'intera durata della sagra:</strong></p>';
				out += '<button class="btn btn-success mb-2" onclick="reportarticoli(\'Articoli\', false);"><i class="bi bi-file-earmark-bar-graph"></i> Articoli venduti</button><br>';
				out += '<button class="btn btn-warning" onclick="reportarticoli(\'Ingredienti\', false);"><i class="bi bi-file-earmark-ruled"></i> Ingredienti venduti</button>';
				out += '</div></div>';
				jsons = json;
				$(target).html(out);
			}
		} catch (err) {
			$(target).html('<strong class="text-danger">Errore durante la richiesta:</strong> ' + json);
		}
	})
	.fail(function(jqxhr, textStatus, error) {
		$(target).html('<strong class="text-danger">Richiesta fallita:</strong> ' + textStatus + '<br>' + jqxhr.responseText);
	});
}

function chiudiCassaModal() {
	preparaChiudiCassa('#modalcassebody');
	modcasse.show();
}

$('.nav-pills a[data-bs-target="#tabchiudicassa"]').on('show.bs.tab', function () {
	preparaChiudiCassa('#chiudicassabody');
});

function headerHtml(titolo) {
	return '<html><head><title>' + titolo + '</title><link href="../css/bootstrap-5.0.2/bootstrap.css" rel="stylesheet" /><link href="../css/bootstrap-5.0.2/bootstrap-icons.css" rel="stylesheet" /><style>@page {size: auto; margin: 20px;}</style><?php echo icona(); ?></head>';
}

function chiudiCassa(action) {
	modcasse.hide();
	var oggi = new Date();
	let out = headerHtml('Rendiconto ' + (action == false ? 'completo' : (action == true ? 'del turno' : action)));
	out += '<body style="height: 100%;"><center style="padding: 10px;">';
	
	if (action === false) { // Rendiconto completo
		out += '<h1>Rendiconto completo ' + oggi.getFullYear() + '</h1><br>';
		
		var intest = '<table class="table table-striped" style="font-size: 1.2em;"><thead><tr><th class="p-2">Cassa</th><th class="p-2">Pagamento</th><th class="p-2">Incasso</th></tr></thead><tbody>';
		
		var turno = null;
		var totale = 0;
		$.each(jsons, function(i, res) {
			var dt = new Date(res.data);
			var questoturno = giorni[dt.getDay()] + ' ' + dt.getDate() + ' ' + res.pranzo_cena;
			if (questoturno != turno) {
				if (turno != null)
					out += '<tr><td colspan="2" class="p-2"><strong>Totale</strong></td><td class="p-2"><strong>' + prezzo_cc(totale) + '</strong></td></tr>';
				out += (turno != null ? '</tbody></table><br>' : '') + '<h3><strong>' + questoturno + '</strong></h3>' + intest;
				turno = questoturno;
				totale = 0;
			}
			out += '<tr><td class="p-2">' + res.cassa + '</td><td class="p-2">' + res.tipo_pagamento + '</td><td class="p-2">' + prezzo_cc(res.importo_totale) + '</td></tr>';
			totale += res.importo_totale;
		});
		if (totale > 0)
			out += '<tr><td colspan="2" class="p-2"><strong>Totale</strong></td><td class="p-2"><strong>' + prezzo_cc(totale) + '</strong></td></tr>';
		out += '</tbody></table>';
		
		out += '</center></body></html>';
		apri_e_stampa(out);
	} else {
		$.getJSON("php/ajax.php?a=reportmodifiche&cassa=" + action + "&" + infoturno())
		.done(function(json) {
			if (action === true) { // Rendiconto del turno
				out += '<h1>Rendiconto del turno</h1><br>';
			} else { // Cassa singola
				out += '<h1>Rendiconto di cassa</h1><br>';
			}
			
			// Stampa informazioni sul turno
			var turnot = (pranzo(data) ? 'pranzo' : 'cena');
			out += '<h4><i class="bi bi-calendar-check"></i> ' + giorni[data.getDay()] + ' ' + data.getDate() + ' ' + turnot + '<br>';
			if (stessoTurno(data, oggi))
				out += '<i class="bi bi-clock"></i> stampato alle ore ' + (oggi.getHours() < 10 ? '0' : '') + oggi.getHours() + ':' + (oggi.getMinutes() < 10 ? '0' : '') + oggi.getMinutes() + '</h4><br>';
			else
				out += '<i class="bi bi-arrow-clockwise"></i> Stampa tardiva</h4><br>';
			
			// Preparazione per il rendiconto di cassa
			let casse2 = casse;
			if (action != true) {
				casse2 = [action];
			}
			
			var totali = {};
			for (var i = 0; i < casse2.length; i++) {
				out += '<div style="page-break-inside: avoid;">';
				out += '<h1 style="border: 5px solid black; border-radius: 10px; padding: 10px;"><strong>' + casse2[i] + '</strong></h1>';
				var importo = [];
				$.each(jsons, function(k, res) {
					if (res.cassa == casse2[i] && turnot == res.pranzo_cena && res.data == dataToString(data))
						importo.push({tipo_pagamento: res.tipo_pagamento, importo: res.importo_totale});
				});
				
				if (importo.length == 0) {
					out += '<span style="font-size: 2em;">Nessun incasso</span>';
				} else {
					let mod = [];
					mod['CONTANTI'] = {'str': '', tot: 0};
					mod['POS'] = {'str': '', tot: 0};
					if (action != true) {
						try {
							$.each(json, function(j, res) {
								let diff = parseFloat(res.differenza);
								if (res.tipo == "esterno" && res.agente != casse2[i]) {
									mod[res.tipo_pagamento].str += '<div class="row align-items-center" style="font-size: 1.5em;"><div class="col" style="text-align: left; padding-left: 40px;">' + (diff < 0 ? '<i class="bi bi-box-arrow-left"></i> reso' : '<i class="bi bi-box-arrow-in-right"></i> incasso') + ' da ' + res.agente + '<br><span style="font-size: 0.8em;">(' + res.righemodificate + ' rig' + (res.righemodificate == 1 ? 'a' : 'he') + ' mod. alle ' + res.ora.substring(0, 5) + ', ID ordine ' + res.id + ')</span></div><div class="col-auto no-pad" style="text-align: right;"><strong>' + (diff < 0 ? '+ ' : '- ') + prezzo_cc(Math.abs(diff)) + '</strong></div></div>';
									mod[res.tipo_pagamento].tot += diff;
								} else if (res.tipo == "agente") {
									diff = -1 * diff;
									mod[res.tipo_pagamento].str += '<div class="row align-items-center" style="font-size: 1.5em;"><div class="col" style="text-align: left; padding-left: 40px;">' + (diff > 0 ? '<i class="bi bi-box-arrow-left"></i> reso' : '<i class="bi bi-box-arrow-in-right"></i> incasso') + ' per ' + res.cassa + '<br><span style="font-size: 0.8em;">(' + res.righemodificate + ' rig' + (res.righemodificate == 1 ? 'a' : 'he') + ' mod. alle ' + res.ora.substring(0, 5) + ', ID ordine ' + res.id + ')</span></div><div class="col-auto no-pad" style="text-align: right;"><strong>' + (diff < 0 ? '+ ' : '- ') + prezzo_cc(Math.abs(diff)) + '</strong></div></div>';
									mod[res.tipo_pagamento].tot += diff;
								}
							});
						} catch (err) {
							out += '<strong>Errore durante l\'analisi del report delle modifiche:</strong> ' + json;
						}
					}
					for (var j = 0; j < importo.length; j++) {
						out += '<div class="row align-items-center" style="font-size: 2em;"><div class="col no-pad" style="text-align: left;">' + importo[j].tipo_pagamento + '</div><div class="col-auto no-pad" style="text-align: right;"><strong>' + prezzo_cc(importo[j].importo) + '</strong></div></div>';
						if (totali[importo[j].tipo_pagamento] == null)
							totali[importo[j].tipo_pagamento] = importo[j].importo;
						else
							totali[importo[j].tipo_pagamento] += importo[j].importo;
						out += mod[importo[j].tipo_pagamento].str;
						if (mod[importo[j].tipo_pagamento].tot != 0)
							out += '<div class="row align-items-center" style="font-size: 1.5em;"><div class="col" style="text-align: left; padding-left: 40px;">EFFETTIVI</div><div class="col-auto no-pad" style="text-align: right;"><strong>' + prezzo_cc(importo[j].importo - mod[importo[j].tipo_pagamento].tot) + '</strong></div></div>';
					}
				}
				out += '</div><br>';
			}
			if (casse2.length > 1 || Object.keys(totali).length > 1) {
				var complessivo = 0;
				out += '<div style="page-break-inside: avoid;">';
				out += '<hr style="border: 5px solid black; border-radius: 10px;">';
				for (var i = 0; i < Object.keys(totali).length; i++) {
					if (casse2.length > 1) {
						out += '<div class="row align-items-center" style="font-size: 2em;"><div class="col no-pad" style="text-align: left;">TOTALE ' + Object.keys(totali)[i] + '</div><div class="col-auto no-pad" style="text-align: right;"><strong>' + prezzo_cc(Object.values(totali)[i]) + '</strong></div></div>';
					}
					complessivo += Object.values(totali)[i];
				}
				if (Object.keys(totali).length > 1) {
					out += '<div class="row align-items-center" style="font-size: 2em;"><div class="col no-pad" style="text-align: left;">COMPLESSIVO</div><div class="col-auto no-pad" style="text-align: right;"><strong>' + prezzo_cc(complessivo) + '</strong></div></div>';
				}
				out += '</div>';
			}
			
			out += '</center></body></html>';
			apri_e_stampa(out);
		})
		.fail(function(jqxhr, textStatus, error) {
			mostratoast(false, '<strong>Richiesta fallita:</strong> ' + textStatus + '<br>' + error);
			console.log(jqxhr);
		});
	}
}

function prezzo_cc(num) {
	//return '&euro;&nbsp;' + ('' + num).replace(".", ",") + ((num - Math.trunc(num)) != 0 ? '0' : ',00');
	num = Math.abs(num);
	let x = Math.floor(num).toString();
	if (x.length > 3) {
		let z = x.split('');
		x = '';
		let j = 0;
		for (let i = z.length - 1; i >= 0; i--) {
			x = z[i] + x;
			if (++j == 3) {
				x = '.' + x;
				j = 0;
			}
		}
	}
	let y = (num + '').split(".");
	if (y.length > 1)
		y = y[1];
	else
		y = '00';
	if (y.length < 2)
		y = y + '0';
	return (num < 0 ? '- ' : '') + '&euro;&nbsp;' + x + ',' + y;
}

function reportarticoli(tipo, turno) {
	$.getJSON("php/ajax.php?a=reportarticoli&tipo=" + tipo + "&turno=" + (turno ? 'true' : 'false') + "&servizio=" + ($('#statservizio').is(':checked') ? 'true' : 'false') + "&" + infoturno())
	.done(function(json) {
		let turni = json.turni;
		turni.sort();
		let headturni = '';
		turni.forEach(function(t) {
			let d = new Date(t.substring(0, 10));
			headturni += '<th class="p-1 border-1">' + giorni[d.getDay()].substring(0, 3) + ' ' + d.getDate() + (t.substring(11) == 0 ? ' <i class="bi bi-sun-fill"></i>' : ' <i class="bi bi-moon-fill"></i>') + '</th>';
		});

		let out = headerHtml(tipo + ' venduti') + '<body>';
		out += '<h3><i class="bi bi-graph-up-arrow"></i> ' + tipo + ' venduti - ' + (turno ? giorni[data.getDay()] + ' ' + data.getDate() + (pranzo(data) ? ' pranzo' : ' cena') : turni[0].substring(0, 4)) + '</h3>';
		let headtable = '<table class="atable w-100 mb-3" style="font-size: 0.875rem;">';
		out += headtable;
		let tipologia = null;
		let articolo = null;
		let i = 0;
		let totale = 0;
		let descpagamenti = ['Contanti', 'POS', 'Incasso totale'];

		let vendite = (json.vendite).concat(json.servizio);
		$.each(vendite, function(j, res) {
			if (articolo != res.descrizione) {
				// Chiusura riga articolo precedente
				if (articolo != null) {
					while (i < turni.length) {
						out += '<td class="p-1 border-1"></td>';
						i++;
					}
					if (!turno) {
						out += '<td class="p-1 border-1"><strong>';
						if (descpagamenti.includes(articolo))
							out += prezzo_cc(parseFloat(totale));
						else
							out += (''+(Math.round(totale*100)/100)).replace(".", ",");
						out += '</strong></td>';
					}
					out += '</tr>';
				}

				if (tipologia != res.tipologia) {
					out += '</tbody>';
					if (res.tipologia == 'Servizio')
						out += '</table>' + headtable;
					out += '<tbody style="page-break-inside: avoid;"><tr class="border-2 border-dark"><th class="p-1 border-1"><h5 class="my-0">' + res.tipologia + '</h5></th>' + headturni + (!turno ? '<th class="p-1 border-1"><strong>Totale</strong></th>' : '') + '</tr>';
					tipologia = res.tipologia;
				}

				// Apertura riga nuovo articolo
				out += '<tr><td class="p-1 border-1">' + res.descrizione + '</td>';
				articolo = res.descrizione;
				i = 0;
				totale = 0;
			}
			while (turni[i] != (res.data + ',' + (res.turno == 'pranzo' ? 0 : 1)) && (i < turni.length)) {
				out += '<td class="p-1 border-1"></td>';
				i++;
			}
			out += '<td class="p-1 border-1">';
			if (descpagamenti.includes(res.descrizione))
				out += prezzo_cc(parseFloat(res.qta));
			else
				out += (''+parseFloat(res.qta)).replace(".", ",");
			out += '</td>';
			totale += parseFloat(res.qta);
			i++;
		});
		// Chiusura riga ultimo articolo
		while (i < turni.length) {
			out += '<td class="p-1 border-1"></td>';
			i++;
		}
		if (!turno) {
			out += '<td class="p-1 border-1"><strong>';
			if (descpagamenti.includes(articolo))
				out += prezzo_cc(parseFloat(totale));
			else
				out += (''+(Math.round(totale*100)/100)).replace(".", ",");
			out += '</strong></td></tr>';
		}

		out += '</table></body></html>';
		apri_e_stampa(out);
	})
	.fail(function(jqxhr, textStatus, error) {
		mostratoast(false, '<strong>Richiesta fallita:</strong> ' + textStatus + '<br>' + error);
		console.log(jqxhr);
	});
}

function apri_e_stampa(text) {
	let win = window.open('', '_blank');
	win.document.write(text);
	
	setTimeout(() => {
		win.print();
		win.close();
	}, 200);
	
}
</script>