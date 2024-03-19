<!doctype html>
<html lang="it"><!-- Pannello evasione comande - Versione 1.5.2 - Ottobre 2023 -->
<head>
	<?php include "css/bootstrap.php" ?>
	<title>Pannello evasione comande</title>
	<?php echo icona(); ?>
</head>
<body style="height: 100vh;">
<?php
if (isset($_POST['pwd']) && $_POST['pwd'] == $pwd_pannello) {
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
		<nav class="fixed-top navbar navbar-expand-lg navbar-dark bg-primary">
			<div class="container-lg">
				<span class="navbar-brand" id="brand"><i class="bi bi-star-fill"></i> Pannello evasione comande <i class="bi bi-<?php echo $lido; ?>-circle"></i></span>
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse" id="navbarColor01">
					<ul class="navbar-nav me-auto">
						<?php menuturno(); ?>
						
						<li class="nav-item dropdown nostart">
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><i class="bi bi-search"></i> Cerca</a>
							<ul class="dropdown-menu">
								<li><a class="dropdown-item nostart" href="#" data-bs-toggle="modal" data-bs-target="#modalcerca"><i class="bi bi-123"></i> Per identificativo</a></li>
								<li><a class="dropdown-item nostart" href="#" data-bs-toggle="modal" data-bs-target="#modalcercatav"><i class="bi bi-compass"></i> Per tavolo</a></li>
								<li><a class="dropdown-item nostart" href="#" data-bs-toggle="modal" data-bs-target="#modalcercanome"><i class="bi bi-person"></i> Per nominativo</a></li>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item nostart" href="#" data-bs-toggle="modal" data-bs-target="#modalcercarecenti"><i class="bi bi-clock-history"></i> Evasioni recenti</a></li>
							</ul>
						</li>
						
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><i class="bi bi-tools"></i> Strumenti</a>
							<ul class="dropdown-menu">
								<li><a class="dropdown-item nostart" href="#" onclick="statistiche();"><i class="bi bi-bar-chart"></i> Statistiche</a></li>
								<li><a class="dropdown-item nostart" href="#" onclick="chiudiCassaModal();"><i class="bi bi-printer"></i> Stampa rendiconto</a></li>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalbonifica"><i class="bi bi-clipboard-check"></i> Bonifica database</a></li>
								<!--li><a class="dropdown-item" href="#" data-bs-toggle="offcanvas" data-bs-target="#modalmonitor"><i class="bi bi-display"></i> Monitoraggio</a></li-->
								<li class="">
									<a class="dropdown-item nostart" href="#"><div class="row"><div class="col"><i class="bi bi-bug"></i> Debug</div><div class="col-3" style="text-align: right;"><i class="bi bi-caret-right-fill"></i>&emsp;</div></div></a>
									<ul class="dropdown-menu dropdown-submenu">
										<!--li><a class="dropdown-item" href="#" onclick="postgres();"><i class="bi bi-link-45deg"></i> Stringa di connessione a PostgreSQL</a></li-->
										<li><a class="dropdown-item nostart" id="linkjson" href="#" target="_blank"><i class="bi bi-box-arrow-up-right"></i> JSON degli ordini di questo turno</a></li>
										<!--li><a class="dropdown-item" id="linkpgadmin" href="<?php echo 'http://' . $server . '/pgadmin4/browser/'; ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> pgAdmin</a></li-->
										<li><a class="dropdown-item" href="#" onclick="spostaOrdini();"><i class="bi bi-arrow-90deg-right"></i> Sposta tutti gli ordini al giorno odierno</a></li>
									</ul>
								</li>
								<!--li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modaldatabase"><i class="bi bi-exclude"></i> Unifica database</a></li-->
							</ul>
						</li>
					</ul>
					<?php navdx(); ?>
				</div>
			</div>
		</nav>
	
		<div class="row h-100">
			<div class="col-3 h-100" id="colonnasx" style="display: none;">
				<div class="d-flex flex-column h-100">
					<ul class="nav nav-tabs">
						<li class="nav-item w-50">
							<a class="nav-link active" data-bs-toggle="tab" href="#tab0" id="tabevadere" astyle="padding-left: 10px; padding-right: 10px;" title=""><i class="bi bi-cart3"></i> Ordinate</a>
						</li>
						<li class="nav-item w-50">
							<a class="nav-link" data-bs-toggle="tab" href="#tab1" id="tabevase" astyle="padding-left: 10px; padding-right: 10px;" title=""><i class="bi bi-check2-circle"></i> Evase</a>
						</li>
					</ul>
					<div class="tab-content flex-grow-1" style="overflow-y: auto;">
						<div class="tab-pane fade active show" id="tab0"></div>
						<div class="tab-pane fade" id="tab1"></div>
					</div>
				</div>
			</div>
			<div class="col">
				<div id="start" class="colonnadx">
					<div style="text-align: center; width: 100%;" id="avvio"></div>
				</div>
				<div id="normal" class="h-100 colonnadx" style="display: none;">
					<h1>Ordine <span id="num"></span>&emsp;<small><small id="nomecliente"></small></small></h1>
					<hr>
					<div class="row align-items-top">
						<div class="col-4" style="margin-left: 10px;">
							<div id="tastieratavolo">
								<div class="row"><div class="col" style="padding: 2px;">
									<div class="input-group mb-3">
										<input type="text" class="form-control form-control-lg text-center" id="tavolo" style="padding: 5px; font-size: 1.5em; margin: 0px;" placeholder="Tavolo">
										<button class="btn btn-danger btn-lg" onclick="tav(false);"><i class="bi bi-backspace"></i></button>
									</div>
								</div></div>
								<div class="row">
									<div class="col" style="padding: 2px;">
										<button class="btn btn-light btn-lg w-100 numt" onclick="tav('7');">7</button><br>
										<button class="btn btn-light btn-lg w-100 numt" onclick="tav('4');">4</button><br>
										<button class="btn btn-light btn-lg w-100 numt" onclick="tav('1');">1</button><br>
										<button class="btn btn-light btn-lg w-100 numt" onclick="tav(' SX');">SX</button>
									</div>
									<div class="col" style="padding: 2px;">
										<button class="btn btn-light btn-lg w-100 numt" onclick="tav('8');">8</button><br>
										<button class="btn btn-light btn-lg w-100 numt" onclick="tav('5');">5</button><br>
										<button class="btn btn-light btn-lg w-100 numt" onclick="tav('2');">2</button><br>
										<button class="btn btn-light btn-lg w-100 numt" onclick="tav('0');">0</button>
									</div>
									<div class="col" style="padding: 2px;">
										<button class="btn btn-light btn-lg w-100 numt" onclick="tav('9');">9</button><br>
										<button class="btn btn-light btn-lg w-100 numt" onclick="tav('6');">6</button><br>
										<button class="btn btn-light btn-lg w-100 numt" onclick="tav('3');">3</button><br>
										<button class="btn btn-light btn-lg w-100 numt" onclick="tav(' DX');">DX</button>
									</div>
								</div>
								<div class="row"><div class="col" style="padding: 2px;">
									<button class="btn btn-success btn-lg w-100" onclick="salvatav()"><i class="bi bi-save"></i>&emsp;Salva</button>
								</div></div>
							</div>
							<div id="displaytavolo">
								<h4>Tavolo</h4>
								<h2 id="tavolosalvato"></h2><br>
								<button class="btn btn-success btn-sm" onclick="$('#displaytavolo').hide(); $('#tastieratavolo').show();"><i class="bi bi-pencil-fill"></i> Modifica</button>
							</div>							
							<div id="titoloesportazione">
								<h4>Per esportazione</h4>
							</div>
						</div>
						<div class="col-7" style="padding-left: 50px;">
							<h4><span id="iconabar"></span> Comanda del bar:</h4>
								<div id="comandabar" style="height: 120px;"></div>
							<h4><span id="iconacucina"></span> Comanda della cucina:</h4>
								<div id="comandacucina" style="height: 120px;"></div>
							<div class="form-check" style="margin-left: 5px;">
								<input type="checkbox" class="form-check-input lead" id="salvaoraevasione">
								<label class="form-check-label" for="salvaoraevasione">Salva l'ora dell'evasione</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<?php
	include "php/menuturno.php";
	include "php/cerca.php";
	//include "php/strumenti/monitoraggio.php";
	include "php/strumenti/statistiche.php";
	include "php/strumenti/chiudicassa.php";
	include "php/strumenti/debug.php";
	include "php/strumenti/bonifica.php";
	include "php/toast.php";
	?>
	<script src="js/comande.js"></script>
	<script>
	function accessoalturno() {
		$('#start').html('<h4><i class="bi bi-arrow-down-left"></i> Seleziona un ordine</h4>');
		document.getElementById('linkjson').href = "php/ajax.php?a=comande&" + infoturno();
		getComande();
	}
	
	</script>
<?php
} else {
?>
	<div class="container" style="max-width: 500px;"><center>
		<br>
		<h3><i class="bi bi-star-fill"></i> Pannello evasione comande</h3>
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
