
var modcerca = new bootstrap.Modal(document.getElementById('modalcerca'));
var modalCerca = document.getElementById('modalcerca');
modalCerca.addEventListener('shown.bs.modal', function (event) {
	document.getElementById("inputcerca").focus();
});
var tipocerca = 0;
var trovati = [];

function cercaordine() {
	coloremenu('bg-info');
	$('#titolo').html('<h3 class="m-0"><button class="btn btn-info" onclick="preparalista();"><i class="bi bi-caret-left-fill"></i></button> Cerca un ordine');
	$('#corpo').html('<div class="btn-group-vertical w-100"><button class="btn btn-lg btn-outline-info" onclick="dialogcerca(1);"><i class="bi bi-123"></i> Per numero</button>\
					<button class="btn btn-lg btn-outline-info" onclick="dialogcerca(2);"><i class="bi bi-123"></i> Per ID <small>(numerazione interna)</small></button>\
					<button class="btn btn-outline-info btn-lg" onclick="dialogcerca(3);"><i class="bi bi-compass"></i> Per tavolo</button>\
					<button class="btn btn-outline-info btn-lg" onclick="dialogcerca(4);"><i class="bi bi-person"></i> Per nominativo</button></div>');
}

function dialogcerca(tipo) {
	tipocerca = tipo;
	$('#rescerca').html('');
	$('#inputcerca').val('');
	switch (tipo) {
		case 1:
			$('#desccerca').html('Inserisci il numero dell\'ordine:');
			$('#inputcerca').attr('type', 'number');
			break;
		case 2:
			$('#desccerca').html('Inserisci l\'ID dell\'ordine:');
			$('#inputcerca').attr('type', 'number');
			break;
		case 3:
			$('#desccerca').html('Inserisci il numero del tavolo:<br>(senza indicare SX, DX o CX)');
			$('#inputcerca').attr('type', 'number');
			break;
		case 4:
			$('#desccerca').html('Inserisci il nome del cliente:');
			$('#inputcerca').attr('type', 'text');
			break;
		default:
			break;
	}
	modcerca.show();
	trovati = [];
}

let oggetti = ['num', 'id', 'tav', 'nome'];
function cerca() {
	$.getJSON("ajax.php?a=cerca&" + oggetti[tipocerca - 1] + "=" + $('#inputcerca').val())
	.done(function(json) {
		try {
			if (tipocerca == 2) {
				if (json.length < 1) {
					$('#rescerca').html('<br>Ordine non trovato.');
				} else {
					trovati[0] = json[0];
					apriordine(0, 'trovati');
					modcerca.hide();
				}
			} else {
				$.each(json, function(i, res) {
					trovati.push(res);
				});
				rescerca();
				modcerca.hide();
			}
		} catch (err) {
			$('#rescerca').html('<span class="text-danger"><strong>Errore nell\'elaborazione della richiesta:</strong></span>' + err);
		}
	})
	.fail(function(jqxhr, textStatus, error) {
		$('#rescerca').html('<span class="text-danger"><strong>Errore durante la richiesta:</strong></span>' + jqxhr.responseText);
	});
}

function rescerca() {
	$('#titolo').html('<h3 class="m-0"><button class="btn btn-info" onclick="cercaordine();"><i class="bi bi-caret-left-fill"></i></button> Ordini ' + (tipocerca == 3 ? 'del tavolo ' : (tipocerca == 4 ? 'associati al nome ' : 'numerati ')) + $('#inputcerca').val());
	if (trovati.length == 0) {
		$('#corpo').html('Nessun ordine trovato.');
	} else {
		$('#corpo').html('');
		let delay = 0;
		for (let i = 0; i < trovati.length; i++) {
			$('#corpo').append('<button class="btn btn-secondary w-100 mb-3 ordinesala" style="animation-delay: ' + delay + 's;" onclick="apriordine(' + i + ', \'trovati\');"><div class="row"><div class="col-4 my-auto"><big>' + trovati[i].progressivo + '</big></div><div class="col my-auto">' + trovati[i].cliente + '<hr style="margin: 5px;">Ore ' + trovati[i].ora.substr(0, 5) + (trovati[i].tavolo != '' ? ' - Tavolo ' + trovati[i].tavolo : '') + '</div></div></button><br>');
			delay += 0.02;
		}
	}
}
