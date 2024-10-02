<!doctype html>
<html lang="it"><!-- Pannello associazioni - Versione 1.1 - Ottobre 2023 -->
<head>
	<?php include "php/bootstrap.php" ?>
	<title>Associazioni</title>
	<?php echo icona(); ?>
</head>
<body style="height: 100vh;">
<?php
if (isset($_POST['pwd']) && $_POST['pwd'] == $pwd_associazioni) {
	setcookie('login', '1', time() + 60 * 60 * 24 * 365);
	header('Location: ' . $_SERVER['PHP_SELF']);
}

if (isset($_COOKIE['login'])) {
	setcookie('login', '1', time() + 60 * 60 * 24 * 365);
	?>
	<audio id="wxp" src="media/wxp.mp3" preload="auto"></audio>
	<audio id="sevadi" src="media/evadi.wav" preload="auto"></audio>
	<audio id="sripristina" src="media/ripristina.wav" preload="auto"></audio>
	<audio id="sallarme" src="media/allarme.wav" preload="auto"></audio>
	<div class="container-lg h-100" style="padding-top: 67px;">
		<nav class="fixed-top navbar navbar-expand-lg navbar-dark bg-success">
			<div class="container-lg">
				<span class="navbar-brand"><i class="bi bi-geo-alt-fill"></i> Associazioni <i class="bi bi-<?php echo $lido; ?>-circle"></i></span>
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse" id="navbarColor01">
					<ul class="navbar-nav me-auto">
						<?php menuturno(); ?>
						<li class="nav-item">
							<a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#modalpresi"><i class="bi bi-send"></i> Presi in carico</a>
						</li>
					</ul>
					<?php navdx(); ?>
				</div>
			</div>
		</nav>
		
		<div id="start" class="colonnadx">
			<div style="text-align: center; width: 100%;" id="avvio"></div>
		</div>
		
		<div class="h-100">
			<div class="d-flex flex-column h-100">
				<div id="head" class="d-none" style="padding: 20px 0px; aposition: fixed;">
					<div class="row">
						<div class="col-auto">
							<button class="btn btn-success" onclick="lista();"><i class="bi bi-arrow-clockwise"></i> Aggiorna</button>
						</div>
						<div class="col-auto">
							Ordina per 
							<div class="btn-group" role="group" id="order" onchange="lista();">
								<input type="radio" class="btn-check" name="btnradio" id="btnradio1" value="ora" autocomplete="off" checked="">
								<label class="btn btn-sm btn-outline-primary" for="btnradio1"><i class="bi bi-clock"></i> Ora di associazione</label>
								<input type="radio" class="btn-check" name="btnradio" id="btnradio2" value="prog" autocomplete="off">
								<label class="btn btn-sm btn-outline-primary" for="btnradio2"><i class="bi bi-123"></i> Progressivo</label>
							</div>
						</div>
						<div class="col">
							<div class="form-check" onchange="lista();">
								<input class="form-check-input" type="checkbox" value="" id="cresc" checked="">
								<label class="form-check-label" for="cresc">Crescente</label>
							</div>
						</div>
						<div class="col" style="text-align: right;">
							Ultimo ordine inserito: <span id="last"></span>
						</div>
					</div>
				</div>
				<div id="lista" class="d-none flex-grow-1" style="overflow-y: auto;"></div>
			</div>
		</div>
	</div>
	
	<div class="modal  fade" id="modalordine">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title" id="titleordine">Ordine</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
						<span aria-hidden="true"></span>
					</button>
				</div>
				<div class="modal-body" id="bodyordine"></div>
				<div class="modal-footer">
					<button class="btn btn-lg btn-outline-danger" data-bs-dismiss="modal">Annulla</button>
					<button class="btn btn-lg btn-success" onclick="conferma();">Conferma</button>
				</div>
			</div>
		</div>
	</div>
	
	<div class="modal fade" id="modalpresi">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Ordini presi in carico</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
						<span aria-hidden="true"></span>
					</button>
				</div>
				<div class="modal-body" id="bodypresi"></div>
			</div>
		</div>
	</div>
	
	<?php
	include "php/menuturno.php";
	include "php/toast.php";
	?>
	<script>
	var ordini = [];
	var fatti = [];
	var actual = null;
	var modalOrdine = document.getElementById('modalordine');
	var modordine = new bootstrap.Modal(document.getElementById('modalordine'));
	var modpresi = new bootstrap.Modal(document.getElementById('modalpresi'));
	
	window.addEventListener("beforeunload", function (event) {
		if (fatti.length > 0) {
			event.preventDefault();
			return event.returnValue = "Sicuro di voler uscire? Perderai i dati degli ordini presi in carico di recente.";
		} else
			return true;
	}, { capture: true });

	function accessoalturno() {
		$('#start').remove();
		$('#head').removeClass('d-none');
		$('#lista').removeClass('d-none');
		lista();
	}
	
	function lista() {
		$('#lista').html('');
		$.getJSON("php/ajaxassociazioni.php?a=associazionirecenti&order=" + (document.getElementById('btnradio1').checked ? "ora" : "prog") + "&desc=" + (document.getElementById('cresc').checked ? "0" : "1") + "&" + infoturno())
		.done(function(json) {
			try {
				let j = 0;
				$.each(json, function(i, res) {
					ordini[res.id] = res;
					$('#lista').append('<a class="dropdown-item ordineass" id="riga' + res.id + '" style="animation-delay: ' + j + 's;" onclick="select(' + res.id + ');"><div class="row">\
						<div class="col">Ordine <strong>' + numero(res.id, res.progressivo) + '</strong> di <strong>' + res.cliente + '</strong></div>\
						<div class="col-1 text-center">' + icone(res) + '</div>\
						<div class="col-4">' + (res.esportazione ? 'per asporto' : 'al tavolo <strong>' + res.tavolo + '</strong>') + '</div>\
						<div class="col-4 d-none d-lg-block">' + (res.esportazione ? '' : 'associato ' + (res.cameriere == '' || res.cameriere == null ? 'in cassa' : 'da ' + res.cameriere) + ' alle ' + res.ora.substring(0, 5)) + '</div>\
					</div></a>');
					j += 0.05;
				});
				if (j == 0)
					$('#lista').html('Nessuna associazione recente.');
			} catch (err) {
				$('#lista').html('<span class="text-danger"><strong>Errore nell\'elaborazione della richiesta:</strong></span>' + json);
			}
		})
		.fail(function(jqxhr, textStatus, error) {
			$('#lista').html('<span class="text-danger"><strong>Errore durante la richiesta:</strong></span>' + jqxhr.responseText);
		});
		$.getJSON("php/ajaxassociazioni.php?a=ultimo&" + infoturno())
		.done(function(json) {
			try {
				$('#last').html('<strong class="text-success">' + numero(json.id, json.progressivo) + '</strong>&nbsp;' + icone(json) + '<br>alle ore ' + json.ora.substring(0, 5));
			} catch (err) {
				$('#last').html('-');
			}
		})
		.fail(function(jqxhr, textStatus, error) {
			$('#last').html('-');
		});
	}
	
	function select(id) {
		actual = id;
		$('.dropdown-item').removeClass('active');
		$('#riga' + id).addClass('active');
		$('#titleordine').html('Ordine <strong class="text-success">' + ordini[id].progressivo + '&emsp;' + icone(ordini[id], true) + '</strong>');
		$('#bodyordine').html('<h5>di <strong>' + ordini[id].cliente + '</strong>&emsp;(ID: ' + id + ')</h5><br>');
		$('#bodyordine').append(ordini[id].esportazione ? '<h1>per asporto</h1>' : '<h1>al tavolo <strong style="color: #43de62;">' + ordini[id].tavolo + '</strong></h1>');
		$('#bodyordine').append(ordini[id].esportazione ? '' : '<br><h5>associato ' + (ordini[id].cameriere == '' || ordini[id].cameriere == null ? 'in cassa' : 'da ' + ordini[id].cameriere) + ' alle ' + ordini[id].ora.substring(0, 5) + '</h5>');
		modordine.show();
	}
	
	modalOrdine.addEventListener('hide.bs.modal', function (event) {
		$('#riga' + actual).removeClass('active');
	});
	
	function conferma() {
		$.ajax({
			url: "php/ajaxassociazioni.php?a=lavorazione&id=" + actual + "&esportazione=" + (ordini[actual].esportazione ? 'true' : 'false'),
			success: function(res) {
				if (res == '1') {
					//$('#riga' + actual).remove();
					fatti.push(ordini[actual]);
					mostratoast(true, 'L\'ordine ' + numero(actual, ordini[actual].progressivo) + ' è in lavorazione');
					lista();
				} else {
					mostratoast(false, 'Errore nel salvataggio dei dati: ' + res);
				}
				modordine.hide();
			},
			error: function(xhr, status, error) { // Server non raggiungibile
				mostratoast(false, 'Errore nell\'invio dei dati: ' + error);
				modordine.hide();
			},
			timeout: 2000
		});
	}
	
	var modalPresi = document.getElementById('modalpresi');
	modalPresi.addEventListener('show.bs.modal', function (event) {
		$('#bodypresi').html('');
		for (let i = fatti.length - 1; i >= 0; i--) {
			$('#bodypresi').append('Ordine <strong>' + numero(fatti[i].id, fatti[i].progressivo) + '</strong>' + (fatti[i].esportazione ? ' per asporto' : ' al tavolo <strong>' + fatti[i].tavolo + '</strong>') + '&emsp;<button class="btn btn-sm btn-outline-danger" onclick="recupera(' + i + ');">Recupera</button><br>');
		}
		if (fatti.length == 0)
			$('#bodypresi').html('Nessun ordine è stato ancora preso in carico.');
	});
	
	function recupera(index) {
		$.ajax({
			url: "php/ajaxassociazioni.php?a=recupera&id=" + fatti[index].id + "&esportazione=" + (fatti[index].esportazione ? 'true' : 'false'),
			success: function(res) {
				if (res == '1') {
					mostratoast(true, 'L\'ordine ' + numero(fatti[index].id, fatti[index].progressivo) + ' non è più in lavorazione');
					var fatti2 = [];
					for (let i = 0; i < fatti.length; i++) {
						if (fatti[i].id != fatti[index].id)
							fatti2.push(fatti[i]);
					}
					fatti = fatti2;
					lista();
				} else {
					mostratoast(false, 'Errore nel salvataggio dei dati: ' + res);
				}
				modpresi.hide();
			},
			error: function(xhr, status, error) { // Server non raggiungibile
				mostratoast(false, 'Errore nell\'invio dei dati: ' + error);
				modpresi.hide();
			},
			timeout: 2000
		});
	}
	
	function icone(obj, fill = false) {
		return (obj.esportazione ? '<i class="bi bi-bag' + (fill ? '-fill" style="color: #8240b8;>' : '') + '"></i>' : (obj.copia_bar ? '<i class="bi bi-droplet' + (fill ? '-fill" style="color: #6ec0ff;' : '') + '"></i>' : '&emsp;') + (obj.copia_cucina ? '<i class="bi bi-flag' + (fill ? '-fill" style="color: #f5953b;' : '') + '"></i>' : '&emsp;'));
	}
	</script>
<?php
} else {
?>
	<div class="container" style="max-width: 500px;"><center>
		<br>
		<h3><i class="bi bi-geo-alt-fill"></i> Associazioni</h3>
		<p>Questa è un'area riservata.<br>Per potervi accedere inserisci la password:</p>
		<form method="post">
			<input type="password" class="form-control" placeholder="Password" name="pwd"><br>
			<input type="submit" class="btn btn-success" value="Accedi">
		</form>
		<?php if (isset($_POST['pwd']))
			echo '<span class="text-danger">La password è errata</span>';
		?>
	</center></div>
<?php
}
?>
</body>
</html>
