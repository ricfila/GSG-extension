<!doctype html>
<html lang="it"><!-- Ausilio alle casse - Versione 1.0 - Settembre 2022 -->
<head>
	<?php include "css/bootstrap.php" ?>
	<title>Ausilio alle casse</title>
	<link rel="icon" type="image/png" href="media/heart-fill.png" />
</head>
<body style="height: 100vh;">
<?php
if (isset($_POST['pwd']) && $_POST['pwd'] == $pwd_ausilio) {
	setcookie('logincasse', '1', time() + 60 * 60 * 24 * 365);
	header('Location: ' . $_SERVER['PHP_SELF']);
}

if (isset($_COOKIE['logincasse'])) {
	setcookie('login', '1', time() + 60 * 60 * 24 * 365);
	?>
	<audio id="wxp" src="media/wxp.mp3" preload="auto"></audio>
	<audio id="sallarme" src="media/allarme.wav" preload="auto"></audio>
	<div class="container-lg h-100" style="padding-top: 67px; max-width: 100%;">
		<nav class="fixed-top navbar navbar-expand-lg navbar-dark bg-danger">
			<div class="container-lg">
				<span class="navbar-brand"><i class="bi bi-heart-fill"></i> Ausilio alle casse <i class="bi bi-<?php echo $lido; ?>-circle"></i></span>
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse" id="navbarColor01">
					<ul class="navbar-nav me-auto">
						<?php menuturno(); ?>
					</ul>
					<?php navdx(); ?>
				</div>
			</div>
		</nav>
	
		<div class="row h-100">
			<div class="col-3 h-100" id="colonnasx" style="display: none;">
				<div class="d-flex flex-column h-100">
					<div class="tab-content flex-grow-1" style="overflow-y: auto;">
						<ul id="navbarstat" class="nav nav-pills" style="padding: 10px 0px 10px 0px;">
							<li class="dropdown-header">Operazioni sugli ordini</li>
								<li class="nav-item w-100 ml-2" style="margin-left: 15px;"><a class="linkcasse nav-link active" data-bs-toggle="tab" data-bs-target="#tabordinirecenti" href="#"><i class="bi bi-clock-fill"></i> Ordini recenti</a></li>
								<li class="nav-item w-100" style="margin-left: 15px;"><a class="linkcasse nav-link" data-bs-toggle="tab" data-bs-target="#tabmodificaordine" href="#"><i class="bi bi-pencil-fill"></i> Modifica ordine</a></li>
							<li><hr class="dropdown-divider" /></li>
							<li class="dropdown-header">Resoconti</li>
								<li class="nav-item w-100" style="margin-left: 15px;"><a class="linkcasse nav-link" data-bs-toggle="tab" data-bs-target="#tabultimevendite" href="#"><i class="bi bi-cart-fill"></i> Ultime vendite</a></li>
								<li class="nav-item w-100" style="margin-left: 15px;"><a class="linkcasse nav-link" data-bs-toggle="tab" data-bs-target="#tabstatistiche" href="#"><i class="bi bi-bar-chart-fill"></i> Statistiche sul servizio</a></li>
								<li class="nav-item w-100" style="margin-left: 15px;"><a class="linkcasse nav-link" data-bs-toggle="tab" data-bs-target="#tabchiudicassa" href="#"><i class="bi bi-printer-fill"></i> Stampa rendiconto</a></li>
							<li class="dropdown-header">Stato del sistema</li>
								<li class="nav-item w-100" style="margin-left: 15px;"><a class="linkcasse nav-link" data-bs-toggle="tab" data-bs-target="#tabdatabase" href="#"><i class="bi bi-clipboard-check-fill"></i> Bonifica database</a></li>
								<li class="nav-item w-100" style="margin-left: 15px;"><a class="linkcasse nav-link" data-bs-toggle="tab" data-bs-target="#tabmonitoraggio" href="#"><i class="bi bi-display-fill"></i> Monitoraggio</a></li>
						</ul>
					</div>
				</div>
			</div>
			<div class="col h-100 tab-content p-0">
				<div id="tabordinirecenti" class="tab-pane fade show active flex-column d-flex h-100">
					<div id="start" class="tab-content flex-grow-1 colonnadx" style="overflow-y: auto;">
						<div style="text-align: center; width: 100%;" id="avvio"></div>
					</div>
				</div>
				<div id="tabmodificaordine" class="tab-pane fade flex-column">
					<div class="tab-content flex-grow-1 colonnadx" style="overflow-y: auto;">
						<div class="row">
							<div class="col-auto">
								<h4><i class="bi bi-pencil"></i> Modifica ordine</h4>
							</div>
							<div class="col-4">
								<div class="input-group">
									<input class="form-control idprog" id="numordine" placeholder="" type="number" min="0" onkeyup="if (event.keyCode == 13) apriordine();" />
									<button class="btn btn-success" onclick="apriordine();"><i class="bi bi-search"></i></button>
								</div>
							</div>
						</div><hr>
						<div id="modificaordine"></div>
					</div>
				</div>
				<div id="tabultimevendite" class="tab-pane fade flex-column">
					<div class="tab-content flex-grow-1 colonnadx" style="overflow-y: auto;">
						<h4><i class="bi bi-cart"></i> Ultime vendite</h4><hr>
						<div class="row">
							<div class="col-6">
								Cerca tra gli ordini non evasi degli ultimi <strong id="ingminuti"></strong> minuti
								<input type="range" id="rangeminuti" class="form-range" min="1" max="60" oninput="range(61 - $(this).val());"/>
								<button class="btn btn-success btn-sm" onclick="ingredienti();"><i class="bi bi-arrow-clockwise"></i> Ricarica vendite</button>
							</div>
							<div class="col-6">
								<strong>Tempi di servizio di questo turno</strong>&emsp;<button class="btn btn-light btn-sm" onclick="statristrette();"><i class="bi bi-arrow-clockwise"></i></button><br>
								<span id="statristrette"></span>
							</div>
						</div>
						<hr>
						<div id="ingredienti"></div>
					</div>
				</div>
	<?php
	include "php/toast.php";
	include "php/menuturno.php";
	include "php/strumenti/monitoraggio.php";
	include "php/strumenti/statistiche.php";
	include "php/strumenti/chiudicassa.php";
	include "php/strumenti/bonifica.php";
	?>
	<script src="js/modificaordine.js"></script>
	<script src="js/ordinirecenti.js"></script>
	<script src="js/ultimevendite.js"></script>
				<div id="tabstatistiche" class="tab-pane fade flex-column">
					<div class="tab-content flex-grow-1 colonnadx h-100"><div class="d-flex h-100 flex-column">
						<div class="row">
							<div class="col-auto"><h4><i class="bi bi-bar-chart"></i> Statistiche sul servizio</h4></div>
							<div class="col"><button class="btn btn-light" onclick="caricastatistiche('#statistichebody');"><i class="bi bi-arrow-clockwise"></i> Aggiorna</button></div>
						</div>
						<hr />
						<div id="statistichebody" class="d-flex" style="padding-top: 0px; padding-right: 0px; padding-bottom: 0px; overflow-x: hidden;"></div>
					</div></div>
				</div>
				<div id="tabchiudicassa" class="tab-pane fade flex-column">
					<div class="tab-content flex-grow-1 colonnadx" style="overflow-y: auto;">
						<h4><i class="bi bi-printer"></i> Stampa rendiconto</h4><hr>
						<div id="chiudicassabody"></div>
					</div>
				</div>
				<div id="tabdatabase" class="tab-pane fade flex-column">
					<div class="tab-content flex-grow-1 colonnadx" style="overflow-y: auto;">
						<h4><i class="bi bi-clipboard-check"></i> Azioni di bonifica del database</h4><hr>
						<?php echo azionibonifica(); ?><br>
					</div>
				</div>
				<div id="tabmonitoraggio" class="tab-pane fade flex-column">
					<div class="tab-content flex-grow-1 colonnadx" style="overflow-y: auto;">
						<h4><i class="bi bi-display"></i> Monitoraggio dei backup automatici</h4><hr>
						<div>
							<?php echo btnaggiorna() . '<br>' . monitorbody(); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<script>
	function accessoalturno() {
		var out = '<div class="row"><div class="col-auto"><h4><i class="bi bi-clock-history"></i> Ordini recenti</h4></div><div class="col"><button class="btn btn-light" onclick="ultimiordini();"><i class="bi bi-arrow-clockwise"></i> Aggiorna</button></div></div><hr>';
		out += '<small>Legenda:&emsp;<span class="badge rounded-pill bg-success">&emsp;</span>&nbsp;Servito in sala&emsp;<span class="badge rounded-pill bg-info">&emsp;</span>&nbsp;Asporto&emsp;<i class="bi bi-cart3"></i>&nbsp;Ordinato&emsp;<i class="bi bi-check-circle"></i>&nbsp;Evaso</small><br><br><div id="bodyhome"></div>';
		$('#start').html(out);
		apritab('#tabordinirecenti');
		ultimiordini();
	}
	
	$('.nav-link').on('shown.bs.tab', function () {
		$($(this).attr('data-bs-target')).addClass('d-flex');
		$($(this).attr('data-bs-target')).addClass('h-100');
	})
	.on('hidden.bs.tab', function() {
		$($(this).attr('data-bs-target')).removeClass('d-flex');
		$($(this).attr('data-bs-target')).removeClass('h-100');
	});
	
	function apritab(nome) {
		var tab = new bootstrap.Tab(document.querySelector('.nav-pills a[data-bs-target="' + nome + '"]'));
		tab.show();
	}
	</script>
<?php
} else {
?>
	<div class="container" style="max-width: 500px;"><center>
		<br>
		<h3><i class="bi bi-heart-fill"></i> Ausilio alle casse</h3>
		<p>Questa è un'area riservata.<br>Per potervi accedere inserisci la password:</p>
		<form method="post">
			<input type="password" class="form-control" placeholder="Password" name="pwd"><br>
			<input type="submit" class="btn btn-danger" value="Accedi">
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
