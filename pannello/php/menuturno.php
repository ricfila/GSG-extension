
<div class="modal fade" id="modalturno">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Cambia turno di lavoro</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col">
						<input class="form-control" type="date" id="data" onkeyup="if (event.keyCode == 13) { salvaTurno(); modturno.hide(); }" />
					</div>
					<div class="col lead">
						<div class="form-check">
							<input type="radio" class="form-check-input" name="oraturno" id="pranzo">
							<label class="form-check-label w-100" for="pranzo">Pranzo</label>
						</div>
						<div class="form-check">
							<input type="radio" class="form-check-input" name="oraturno" id="cena">
							<label class="form-check-label w-100" for="cena">Cena</label>
						</div>
					</div>
				</div><br>
				<div class="form-check" style="margin-left: 5px;">
					<input type="checkbox" class="form-check-input lead" id="impostacomunque">
					<label class="form-check-label" for="impostacomunque">Aggiorna la data di inizio servizio anche se il turno non è quello attuale (sconsigliato)</label>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" onclick="salvaTurno(true);" data-bs-dismiss="modal">Accedi al turno attuale</button>
				<button type="button" class="btn btn-success" onclick="salvaTurno();" data-bs-dismiss="modal">Accedi</button>
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
			</div>
		</div>
	</div>
</div>

<script>
const giorni = ["Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"];
var data = new Date(); // Data che definisce il turno attuale
inizializz();

function infoturno() {
	return "data=" + dataToString(data) + "&ora=" + (pranzo(data) ? "00:00" : "17:00") + "&data2=" + dataToString(new Date(data.getTime() + 86400000));
}

function inizializz() {
	$('#avvio').html('<h3><div class="spinner-border"></div> Connessione al database...</h3>');
	
	$.ajax({
		url: "php/ajax.php?a=getstart",
		success: function(res) {
			var timeshift = res.replace(" ", "T");
			var start = new Date(timeshift);
			
			if (stessoTurno(data, start)) {
				mostratoast(true, '<i class="bi bi-emoji-smile-fill"></i>&emsp;Bentornato nel turno di ' + giorni[data.getDay()].toLowerCase() + (pranzo(data) ? ' pranzo' : ' cena'), 2);
				impostaTurno(false);
			} else {
				$('#avvio').html('<h4><small><i class="bi bi-clock-history"></i> Ultima sessione intrapresa:</small><br><strong>' + giorni[start.getDay()] + ' ' + start.getDate() + (pranzo(start) ? ', turno del pranzo' : ', turno della cena') + '</strong></h4>');
				$('#avvio').append('<button class="btn btn-warning" onclick="data = new Date(\'' + timeshift + '\'); impostaTurno();"><i class="bi bi-caret-left-square-fill"></i> Ripristina</button><br><br>');
				$('#avvio').append('<button class="btn btn-lg btn-success" onclick="impostaTurno();"><small><i class="bi bi-play-circle-fill"></i> Avvia la nuova sessione</small><br><strong>' + giorni[data.getDay()] + ' ' + data.getDate() + ', turno del' + (pranzo(data) ? ' pranzo' : 'la cena') + '</strong></button>');
			}
		},
		error: function() { // Server non raggiungibile
			$('#avvio').html('<h3 class="text-danger"><i class="bi bi-exclamation-octagon-fill"></i> Database non raggiungibile</h3><br><button class="btn btn-light" onclick="inizializz();">Fai un nuovo tentativo</button>');
		},
		timeout: 2000
	});
}

function dataToString(d) {
	return d.getFullYear() + "-" + (("0" + (d.getMonth() + 1)).slice(-2)) + "-" + (("0" + d.getDate()).slice(-2));
}

// Informa se ci si trova nel turno del pranzo (true) o della cena (false)
function pranzo(d) {
	return d.getHours() < 17;
}

function stessoTurno(data1, data2) {
	return ((data1.getFullYear() == data2.getFullYear()) &&
			(data1.getMonth() == data2.getMonth()) &&
			(data1.getDate() == data2.getDate()) &&
			(pranzo(data1) == pranzo(data2)) );
}

// Stampa id e progressivo compatibilmente con la visualizzazione alternata
function numero(id, progressivo) {
	var usaid = getCookie('id');
	return '<span class="id"' + (usaid == 0 || usaid == "" ? ' style="display: none;"' : '') + '>' + id + '</span><span class="prog"' + (usaid == 1 ? ' style="display: none;"' : '') + '>' + progressivo + '</span>';
}

var modturno = new bootstrap.Modal(document.getElementById('modalturno'));
// Preparazione modal imposta turno
var modalTurno = document.getElementById('modalturno');
modalTurno.addEventListener('show.bs.modal', function (event) {
	document.getElementById("data").value = dataToString(data);
	document.getElementById("pranzo").checked = pranzo(data);
	document.getElementById("cena").checked = !pranzo(data);
	document.getElementById("impostacomunque").checked = false;
});

// Aggiorna la pagina in base al turno relativo alla variabile data
function impostaTurno(toast = true) {
	$('.nostart').removeClass('nostart');
	$('#colonnasx').show();
	var attuale = stessoTurno(data, new Date());
	
	document.getElementById("turno").innerHTML = (!attuale ? '<i class="bi bi-alarm"></i> ' : '<i class="bi bi-check2"></i> ') + giorni[data.getDay()] + " " + (data.getHours() < 17 ? "pranzo" : "cena");
	
	// Imposta la data di inizio servizio
	if (attuale || document.getElementById("impostacomunque").checked) {
		$.get("php/ajax.php", {a: "inizioturno", data: dataToString(data), ora: (pranzo(data) ? "00:00:00" : "17:00:00")}, function(res, stato) {
			if (stato == 'success') {
				if (res == '1') {
					if (toast) {
						document.getElementById('wxp').play();
						mostratoast(true, '<i class="bi bi-check-circle-fill"></i>&emsp;La sessione è stata avviata con successo!', 4);
					}
				} else {
					mostratoast(false, '<i class="bi bi-x-circle-fill"></i>&emsp;Avviamento fallito: ' + res);
				}
			} else {
				mostratoast(false, '<i class="bi bi-x-circle-fill"></i>&emsp;Richiesta fallita: ' + stato);
			}
		});
	}
	document.getElementById("impostacomunque").checked = false;
	
	accessoalturno();
}

// Preparazione tendina menu
$('.dropdown').on('show.bs.dropdown', function() {
	$(this).find('.dropdown-menu').first().stop(true, true).slideDown(100);
});
$('.dropdown').on('hide.bs.dropdown', function() {
	$(this).find('.dropdown-menu').first().stop(true, true).slideUp(50);
});

// Salva la visualizzazione di ID o progressivo
function toggleId() {
	if (getCookie('id') == 1) {
		setCookie('id', 0);
		$(".id").hide();
		$(".prog").show();
		$('.idprog').attr('placeholder', 'Progressivo');
		$("#iconid").html('<i class="bi bi-square"></i>');
	} else {
		setCookie('id', 1);
		$(".id").show();
		$(".prog").hide();
		$('.idprog').attr('placeholder', 'ID');
		$("#iconid").html('<i class="bi bi-check-square-fill"></i>');
	}
}

// Imposta la variabile data dai risultati della finestra di dialogo
function salvaTurno(attuale = false) {
	if (attuale) {
		data = new Date();
	} else {
		data = new Date(document.getElementById("data").value);
		data.setHours(document.getElementById("pranzo").checked ? 12 : 19);
	}
	impostaTurno();
}


// Libreria cookie
function setCookie(cname, cvalue) {
	const d = new Date();
	d.setTime(d.getTime() + (730 * 24 * 60 * 60 * 1000));
	let expires = "expires="+ d.toUTCString();
	document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
	let name = cname + "=";
	let decodedCookie = decodeURIComponent(document.cookie);
	let ca = decodedCookie.split(';');
	for (let i = 0; i < ca.length; i++) {
		let c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}

</script>