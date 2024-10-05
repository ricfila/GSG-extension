
<div class="modal fade" id="modalingredienti">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Modifica ingrediente</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-3 text-end my-auto">Descrizione</div>
					<div class="col"><input type="text" class="form-control" id="inputdescrizione" maxlength="255"></div>
				</div>
				<div class="row">
					<div class="col-3 text-end my-auto">Descrizione breve</div>
					<div class="col"><input type="text" class="form-control" id="inputdescrizionebreve" maxlength="15"></div>
				</div>
				<div class="row">
					<div class="col-3 text-end my-auto">Divisore</div>
					<div class="col"><input type="number" class="form-control" id="inputdivisore" min="1"></div>
				</div>
				<div class="row">
					<div class="col-3 text-end my-auto">Settore</div>
					<div class="col"><input type="text" class="form-control" id="inputsettore"></div>
				</div>
				<div class="row">
					<div class="col-3 text-end my-auto">Monitora</div>
					<div class="col"><input class="form-check-input" type="checkbox" value="" id="inputmonitora"></div>
				</div>
				<div class="text-end">
					<button class="btn btn-success" onclick="salvainfoing();"><i class="bi bi-save2"></i> Salva informazioni</button>
				</div>
				<span id="errinfoing"></span>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalgiacenze">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Modifica scorta di <span id="descinggiacenza"></span></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-3 text-end mb-3">Scorta precedente</div>
					<div class="col"><span id="scortaprecedente"></span></div>
				</div>
				<div class="row">
					<div class="col-3 text-end mb-3">Giacenza attuale</div>
					<div class="col"><strong id="giacenzaattuale"></strong></div>
				</div>
				<div class="row">
					<div class="col-3 text-end my-auto">Nuova scorta</div>
					<div class="col"><input type="number" class="form-control" id="inputgiacenza" onkeyup="if (event.keyCode == 13) salvagiacenza($('#inputgiacenza').val());"><small>Inserire la quantità reale, non moltiplicata per il divisore</small></div>
				</div>
				<div class="text-end">
					<button class="btn btn-danger" onclick="salvagiacenza(0);"><i class="bi bi-dash-lg"></i> Scorta terminata</button>&nbsp;<button class="btn btn-primary" onclick="salvagiacenza(null);"><i class="bi bi-infinity"></i> Scorta infinita</button>&nbsp;<button class="btn btn-success" onclick="salvagiacenza($('#inputgiacenza').val());"><i class="bi bi-save2"></i> Salva nuova scorta</button>
				</div>
				<span id="errgiacenza"></span>
			</div>
		</div>
	</div>
</div>

<script>
var modingredienti = new bootstrap.Modal(document.getElementById('modalingredienti'));
var modgiacenze = new bootstrap.Modal(document.getElementById('modalgiacenze'));
var ingredienti = null;
var editing = null;
var lastorder = '';
var lasttype = '';

$('.nav-pills a[data-bs-target="#tabingredienti"]').on('show.bs.tab', function () {
	tabellaIngredienti();
});

function tabellaIngredienti(order = "ingredienti.id", type = "ASC") {
	lastorder = order;
	lasttype = type;
	$.getJSON("php/ajaxcasse.php?a=ingredienti&orderby=" + order + " " + type)
	.done(function(json) {
		try {
			let out = '<table class="table" border="1">';
			out += '<thead><tr>\
				<th class="p-2">#</th>\
				<th class="p-2" onclick="tabellaIngredienti(\'ingredienti.id\', \'' + (order == "ingredienti.id" && type == "ASC" ? "DESC" : "ASC") + '\');">ID' + (order == "ingredienti.id" ? ' <i class="bi bi-caret-' + (type == "ASC" ? 'down' : 'up') + '-fill"></i>' : '') + '</th>\
				<th class="p-2" onclick="tabellaIngredienti(\'ingredienti.descrizione\', \'' + (order == "ingredienti.descrizione" && type == "ASC" ? "DESC" : "ASC") + '\');">Descrizione' + (order == "ingredienti.descrizione" ? ' <i class="bi bi-caret-' + (type == "ASC" ? 'down' : 'up') + '-fill"></i>' : '') + '</th>\
				<th class="p-2" onclick="tabellaIngredienti(\'ingredienti.descrizionebreve\', \'' + (order == "ingredienti.descrizionebreve" && type == "ASC" ? "DESC" : "ASC") + '\');">Descrizione breve' + (order == "ingredienti.descrizionebreve" ? ' <i class="bi bi-caret-' + (type == "ASC" ? 'down' : 'up') + '-fill"></i>' : '') + '</th>\
				<th class="p-2" onclick="tabellaIngredienti(\'dati_ingredienti.divisore\', \'' + (order == "dati_ingredienti.divisore" && type == "ASC" ? "DESC" : "ASC") + '\');">Divisore' + (order == "dati_ingredienti.divisore" ? ' <i class="bi bi-caret-' + (type == "ASC" ? 'down' : 'up') + '-fill"></i>' : '') + '</th>\
				<th class="p-2" onclick="tabellaIngredienti(\'dati_ingredienti.settore\', \'' + (order == "dati_ingredienti.settore" && type == "ASC" ? "DESC" : "ASC") + '\');">Settore' + (order == "dati_ingredienti.settore" ? ' <i class="bi bi-caret-' + (type == "ASC" ? 'down' : 'up') + '-fill"></i>' : '') + '</th>\
				<th class="p-2" onclick="tabellaIngredienti(\'dati_ingredienti.monitora\', \'' + (order == "dati_ingredienti.monitora" && type == "ASC" ? "DESC" : "ASC") + '\');"><i class="bi bi-display-fill"></i>' + (order == "dati_ingredienti.monitora" ? ' <i class="bi bi-caret-' + (type == "ASC" ? 'down' : 'up') + '-fill"></i>' : '') + '</th>\
				<th class="p-2" onclick="tabellaIngredienti(\'giacenze.scorta_iniziale\', \'' + (order == "giacenze.scorta_iniziale" && type == "ASC" ? "DESC" : "ASC") + '\');">Giacenza' + (order == "giacenze.scorta_iniziale" ? ' <i class="bi bi-caret-' + (type == "ASC" ? 'down' : 'up') + '-fill"></i>' : '') + '</th>\
				<th class="p-2"><i class="bi bi-pencil-fill"></i></th></thead>';
			out += '<tbody>';
			$.each(json, function(i, res) {
				out += '<tr class="rigacomanda" id="ing' + res.id + '">';
				out += '<td class="p-1 border-1">' + (i + 1) + '</td>';
				out += '<td class="p-1 border-1">' + res.id + '</td>';
				out += '<td class="p-1 border-1">' + res.descrizione + '</td>';
				out += '<td class="p-1 border-1">' + res.descrizionebreve + '</td>';
				out += '<td class="p-1 border-1">' + res.divisore + '</td>';
				out += '<td class="p-1 border-1">' + res.settore + '</td>';
				out += '<td class="p-1 border-1">' + (res.monitora == 't' ? '<i class="bi bi-check-square-fill"></i>' : (res.monitora == 'f' ? '<i class="bi bi-square"></i>' : res.monitora)) + '</td>';
				out += '<td class="p-0 border-1"><button class="btn btn-sm btn-' + (res.giacenza == null ? 'primary' : (res.giacenza == 0 ? 'danger' : 'warning')) + ' w-100" onclick="modificagiacenza(' + i + ');">' + (res.giacenza == null ? '<i class="bi bi-infinity"></i>' : (res.giacenza / res.divisore)) + '</button></td>';
				out += '<td class="p-0 border-1"><button class="btn btn-sm btn-success w-100" onclick="modificaing(' + i + ');">Modifica</button></td>';
				out += '</tr>';
			});
			out += '</tbody></table>';
			$('#ingredientibody').html(out);
			ingredienti = json;
		} catch (err) {
			$('#ingredientibody').html('<span class="text-danger"><strong>Errore nell\'elaborazione della richiesta:</strong></span>' + json);
		}
		filtraingredienti();
	})
	.fail(function(jqxhr, textStatus, error) {
		$('#ingredientibody').html('<span class="text-danger"><strong>Errore durante la richiesta:</strong></span>' + jqxhr.responseText);
		
	});
}

function filtraingredienti() {
	testo = $('#filtraingredienti').val().toLowerCase();
	ingredienti.forEach(ing => {
		if (ing.descrizione.toLowerCase().search(testo) >= 0 || ing.descrizionebreve.toLowerCase().search(testo) >= 0) {
			$('#ing' + ing.id).show();
		} else {
			$('#ing' + ing.id).hide();
		}
	});
}

function modificagiacenza(i) {
	editing = i;
	$('#scortaprecedente').html(i == null || ingredienti[i].giacenza == null ? '<i class="bi bi-infinity"></i>' : (ingredienti[i].giacenza / ingredienti[i].divisore));
	$('#giacenzaattuale').html('<i class="bi bi-infinity"></i>');
	$('#inputgiacenza').val('');
	$('#errgiacenza').html('');
	if (i != null && ingredienti[i].giacenza != null) {
		$.get("php/ajaxcasse.php",
			{"a" : "qtavendute",
			"id" : ingredienti[i].id
		})
		.done(function(data) {
			$('#giacenzaattuale').html((ingredienti[i].giacenza - parseInt(data)) / ingredienti[i].divisore);
		});
	}
	modgiacenze.show();
}

function modificaing(i) {
	editing = i;
	$('#inputdescrizione').val(i == null ? '' : ingredienti[i].descrizione);
	$('#inputdescrizionebreve').val(i == null ? '' : ingredienti[i].descrizionebreve);
	$('#inputsettore').val(i == null ? '' : ingredienti[i].settore);
	$('#inputdivisore').val(i == null ? 1 : ingredienti[i].divisore);
	$('#inputmonitora').prop('checked', (i == null ? true : ingredienti[i].monitora == 't'));
	$('#errinfoing').html('');
	modingredienti.show();
}

function salvainfoing() {
	$.get("php/ajaxcasse.php",
		{"a" : "salvainfoing",
		"id" : editing == null ? null : ingredienti[editing].id,
		"descrizione" : $('#inputdescrizione').val(),
		"descrizionebreve" : $('#inputdescrizionebreve').val(),
		"settore" : $('#inputsettore').val(),
		"divisore" : $('#inputdivisore').val(),
		"monitora" : $('#inputmonitora').is(":checked")
	})
	.done(function(data) {
		if (data == '1') {
			tabellaIngredienti(lastorder, lasttype);
			editing = null;
			modingredienti.hide();
		} else {
			$('#errinfoing').html(data);
		}
	})
	.fail(function(data) {
		$('#errinfoing').html(data);
	});
}

function salvagiacenza(qta) {
	if (editing != null) {
		$.get("php/ajaxcasse.php",
			{"a" : "salvagiacenza",
			"id" : ingredienti[editing].id,
			"giacenza" : qta == null ? '' : qta})
		.done(function(data) {
			if (data == '1') {
				tabellaIngredienti(lastorder, lasttype);
				editing = null;
				modgiacenze.hide();
			} else {
				$('#errgiacenza').html(data);
			}
		})
		.fail(function(data) {
			$('#errgiacenza').html(data);
		});
	} else {
		$('#errgiacenza').html('Errore di operazione.');
	}
}
	

</script>