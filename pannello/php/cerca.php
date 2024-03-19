
<div class="modal fade" id="modalcerca">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Cerca per identificativo</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body" style="text-align: center;">
				<div class="input-group mb-3">
					<input class="form-control form-control-lg" type="number" placeholder="" id="numcerca" onkeyup="if (event.keyCode == 13) cerca();">
					<button class="btn btn-lg btn-success" onclick="cerca();"><i class="bi bi-search"></i> Cerca</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalcercatav">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Cerca per tavolo</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body" style="text-align: center;">
				<div class="input-group mb-3">
					<input class="form-control form-control-lg" type="number" placeholder="Numero del tavolo" id="numcercatav" onkeyup="if (event.keyCode == 13) cercatav();">
					<button class="btn btn-lg btn-success" onclick="cercatav();"><i class="bi bi-search"></i> Cerca</button>
				</div>
				<div id="restav"></div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalcercanome">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Cerca per nominativo</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body" style="text-align: center;">
				<div class="input-group mb-3">
					<input class="form-control form-control-lg" type="text" placeholder="Nominativo" id="nomecerca" onkeyup="if (event.keyCode == 13) cercanome();">
					<button class="btn btn-lg btn-success" onclick="cercanome();"><i class="bi bi-search"></i> Cerca</button>
				</div>
				<div id="resnome"></div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalcercarecenti">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Ultime comande evase</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body" id="recentibody">
			</div>
		</div>
	</div>
</div>

<script>
var modcerca = new bootstrap.Modal(document.getElementById('modalcerca'));
var modcercatav = new bootstrap.Modal(document.getElementById('modalcercatav'));
var modcercanome = new bootstrap.Modal(document.getElementById('modalcercanome'));
var modcercarecenti = new bootstrap.Modal(document.getElementById('modalcercarecenti'));

var modalCerca = document.getElementById('modalcerca');
modalCerca.addEventListener('show.bs.modal', function (event) {
	var numcerca = document.getElementById("numcerca");
	numcerca.value = '';
	numcerca.placeholder = (getCookie('id') == 1 ? 'ID dell\'ordine' : 'Progressivo dell\'ordine');
});
modalCerca.addEventListener('shown.bs.modal', function (event) {
	document.getElementById("numcerca").focus();
});

function cerca(id = null) {
	if (id == null) {
		var num = document.getElementById("numcerca").value;
		id = -1;
		if (getCookie('id') == 1) {
			id = num;
		} else {
			for (var i = 0; i < ordini.length; i++) {
				if (ordini[i] != null) {
					if (ordini[i].progressivo == num) {
						id = i;
						break;
					}
				}
			}
		}
	}
	if (id == -1 || ordini[id] == null) {
		mostratoast(false, '<i class="bi bi-emoji-frown"></i>&emsp;L\'ordine ' + num + ' non è stato trovato');
	} else {
		modcerca.hide();
		modcercatav.hide();
		modcercanome.hide();
		modcercarecenti.hide();
		if (getTarget(ordini[id]) == 'tab0') {
			$('#tabevadere').tab('show');
			tabmostrata = 0;
		} else {
			$('#tabevase').tab('show');
			tabmostrata = 1;
		}
		selezione(id);
	}
}

var modalCercaTav = document.getElementById('modalcercatav');
modalCercaTav.addEventListener('show.bs.modal', function (event) {
	document.getElementById("numcercatav").value = '';
	$('#restav').html('');
});
modalCercaTav.addEventListener('shown.bs.modal', function (event) {
	document.getElementById("numcercatav").focus();
});

function cercatav() {
	var tav = document.getElementById("numcercatav").value;
	$('#restav').html('<h5><strong>Ordini del tavolo ' + (tav == '' ? 'sconosciuto' : tav) + ':</strong></h5>(indicati con ' + (getCookie('id') == 1 ? 'ID' : 'progressivo') + ')<br><br>');
	
	let pattern = (tav == '' ? /null/ : /[0-9]/g);
	var j = 0;
	for (var i = 0; i < ordini.length; i++) {
		if (ordini[i] != null) {
			var tt = ordini[i].tavolo.match(pattern);
			if (tt != null) {
				if (tav == '' || tt.join('') == tav) {
					$('#restav').append('<button class="btn btn-lg btn-light" onclick="cerca(' + i + ');">' + (getCookie('id') == 1 ? i : ordini[i].progressivo) + ' - ' + ordini[i].cliente + '</button>');
					j++;
				}
			}
		}
	}
	
	if (j == 0)
		$('#restav').append('Nessuna occorrenza <i class="bi bi-emoji-expressionless"></i>');
}

var modalCercaNome = document.getElementById('modalcercanome');
modalCercaNome.addEventListener('show.bs.modal', function (event) {
	document.getElementById("nomecerca").value = '';
	$('#resnome').html('');
});
modalCercaNome.addEventListener('shown.bs.modal', function (event) {
	document.getElementById("nomecerca").focus();
});

var modalCercaRecenti = document.getElementById('modalcercarecenti');
modalCercaRecenti.addEventListener('show.bs.modal', function (event) {
	$('#recentibody').html('');
	$.getJSON("php/ajax.php?a=evasionirecenti&data=" + dataToString(data) + "&ora=" + (pranzo(data) ? "00:00" : "17:00") + "&data2=" + dataToString(new Date(data.getTime() + 86400000)))
	.done(function(json) {
		try {
			$('#recentibody').append('<p style="text-align: center;">Indicate con ' + (getCookie('id') == 1 ? 'ID' : 'progressivo') + '</p>');
			$.each(json, function(i, res) {
				$('#recentibody').append('<div class="row"><div class="col" style="text-align: right;"><button class="btn btn-light" style="margin-bottom: 10px;" onclick="cerca(' + res.id + ');">' + (getCookie('id') == 1 ? res.id : ordini[res.id].progressivo) + '&emsp;' + (res.tipo == 'bar' ? '<i class="bi bi-droplet-fill"></i> BAR' : '<i class="bi bi-flag-fill"></i> CUCINA') + '</button></div><div class="col">' + res.ora + '</div></div>');
			});
			if (json.length == 0) {
				$("#recentibody").html('Nessuna comanda evasa nel turno selezionato.');
			}
		} catch (err) {
			var msg = '<span class="text-danger"><strong>Errore durante la richiesta:</strong></span>' + json;
			$("#recentibody").html(msg);
			if (!($('#buttonupdatemonitor').hasClass('disabled')))
				updateModalMonitor();
		}
	})
	.fail(function(jqxhr, textStatus, error) {
		var msg = '<span class="text-danger"><strong>Errore durante la richiesta:</strong></span>' + jqxhr.responseText;
		$("#recentibody").html(msg);
		if (!($('#buttonupdatemonitor').hasClass('disabled')))
			updateModalMonitor();
	})
});

function cercanome() {
	var nome = document.getElementById("nomecerca").value;
	$('#resnome').html('<h5><strong>Ordini associati al nome <i>' + nome + '</i>:</strong></h5>(indicati con ' + (getCookie('id') == 1 ? 'ID' : 'progressivo') + ')<br><br>');
	
	var j = 0;
	for (var i = 0; i < ordini.length; i++) {
		if (ordini[i] != null) {
			if (ordini[i].cliente.toUpperCase().includes(nome.toUpperCase())) {
				$('#resnome').append('<button class="btn btn-lg btn-light" onclick="cerca(' + i + ');">' + (getCookie('id') == 1 ? i : ordini[i].progressivo) + ' - ' + ordini[i].cliente + '</button>');
				j++;
			}
		}
	}
	
	if (j == 0)
		$('#resnome').append('Nessuna occorrenza <i class="bi bi-emoji-expressionless"></i>');
}

</script>