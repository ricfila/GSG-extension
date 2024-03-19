<!doctype html>
<html lang="it"><!-- Palmare - Versione 1.0 - Settembre 2023 -->
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta charset="utf-8" />
	<link href="../pannello/bootstrap-5.0.2-dist/css/bootstrap.css" rel="stylesheet" />
	<link href="../pannello/bootstrap-5.0.2-dist/css/bootstrap-icons.css" rel="stylesheet" />
	<script src="../pannello/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
	<script src="../pannello/js/jquery-3.6.0.min.js"></script>
	<link rel="stylesheet" href="../pannello/css/stile.css" />
	<link rel="stylesheet" href="stile_palmare.css" />
	<link rel="icon" type="image/png" href="../pannello/media/compass-fill.png" />
	<title>Palmare sagra</title>
</head>
<body style="height: 100vh;">
<?php
include '../pannello/connect.php';
$conn = pg_connect((filter_var($server, FILTER_VALIDATE_IP) ? "hostaddr" : "host") . "=$server port=$port dbname=$dbname user=$user password=$password connect_timeout=5") or die('Connessione al database non riuscita.');
if (pg_connection_status($conn) == PGSQL_CONNECTION_BAD) {
	echo 'Errore di connessione al database.';
}

if (isset($_GET['logout'])) {
	unset($_COOKIE['cameriere']);
	setcookie('cameriere', '', time() -1);
	header('Location: ' . $_SERVER['PHP_SELF']);
}
if (isset($_POST['submit']) && $_POST['pwd'] == $pwd_palmare) {
	echo $server;
	setcookie('cameriere', pg_escape_string($conn, $_POST['nome']), time() + 60 * 60 * 24 * 7);
	header('Location: ' . $_SERVER['PHP_SELF']);
}

if (isset($_COOKIE['cameriere'])) {
	setcookie('cameriere', $_COOKIE['cameriere'], time() + 60 * 60 * 24 * 7);
	?>
	<div class="container-lg h-100" style="padding-top: 80px;">
		<nav class="fixed-top navbar navbar-expand-lg navbar-dark bg-success" style="transition: 0.3s;">
			<div class="container-lg">
				<span class="navbar-brand">
					<a href="#" class="navbar-brand" onclick="preparalista();"><i class="bi bi-compass-fill"></i> Palmare sagra&emsp;</a><span id="attesa"></span> <!--i class="bi bi-<?php echo (str_ends_with($server, '1') ? 1 : 2); ?>-circle"></i-->
				</span>
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				
				<div class="collapse navbar-collapse" id="navbarColor01">
					<ul class="navbar-nav me-auto">
						<li class="nav-item">
							<a class="nav-link" href="index.php?logout=1"><strong class="lead"><i class="bi bi-person-fill"></i>&nbsp;<i><?php echo $_COOKIE['cameriere']; ?></i></strong>&emsp;<i class="bi bi-door-open-fill"></i> Disconnettiti</a>
						</li>
						<li class="nav-item lead">
							<a class="nav-link" href="#" onclick="ultimiassociati();"><i class="bi bi-clock-history"></i> Ultimi associati</a>
						</li>
						<li class="nav-item lead">
							<a class="nav-link" href="#" onclick="cercaordine();"><i class="bi bi-search"></i> Cerca un ordine</a>
						</li>
					</ul>
				</div>
			</div>
		</nav>
		
		<div id="titolo" style="transition: 0.3s;">
			<div class="alert alert-success" style="width: 100%; padding: 50px 15px;" onclick="$(this).remove(); $('#corpo').html(''); preparalista();">
				<h4 class="text-success">Bentornato/a, <strong><?php echo $_COOKIE['cameriere']; ?></strong></h4>
				Tocca qui per iniziare.
			</div>
		</div>
		<hr>
		<div id="corpo">
			<a class="btn btn-outline-danger" href="index.php?logout=1">Non sei tu?</a>
		</div>
		
		<div class="modal fade" id="dialog">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="dialogtitle"></h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
							<span aria-hidden="true"></span>
						</button>
					</div>
					<div class="modal-body" id="dialogbody"></div>
					<div class="modal-footer" id="dialogfooter"></div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="modalcerca">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Cerca</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
							<span aria-hidden="true"></span>
						</button>
					</div>
					<div class="modal-body">
						<span id="desccerca"></span><br>
						<div class="input-group mb-3 mt-3">
							<input class="form-control form-control-lg" type="number" id="inputcerca" onkeyup="if (event.keyCode == 13) cerca();">
							<button class="btn btn-lg btn-success" onclick="cerca();"><i class="bi bi-search"></i></button>
						</div>
						<div id="rescerca" style="text-align: center;"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<?php
	include '../pannello/php/toast.php';
	?>
	<script src="tavoli.js"></script>
	<script src="pagineblu.js"></script>
	<script src="cercapalmare.js"></script>
	<script>
	
	let modal = new bootstrap.Modal(document.getElementById('dialog'));
	function dialog(titolo, corpo, azione = null) {
		$('#dialogtitle').html(titolo);
		$('#dialogbody').html(corpo);
		if (azione != null)
			$('#dialogfooter').html('<button class="btn btn-danger" onclick="modal.hide();"><i class="bi bi-x-circle"></i>&emsp;Annulla</button>&nbsp;' + azione).show();
		else
			$('#dialogfooter').hide();
		modal.show();
	}
	
	function coloremenu(colore) {
		$('nav').removeClass('bg-warning').removeClass('bg-info').removeClass('bg-success').addClass(colore);
		$(".collapse").collapse('hide');
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
<?php
} else {
?>
	<div class="container" style="max-width: 500px;"><center>
		<br>
		<h3><i class="bi bi-compass-fill"></i> Palmare sagra</h3><br>
		<p>Questa è un'area riservata.<br>Per potervi accedere inserisci il tuo nome e la password:</p>
		<form method="post">
			<input type="text" class="form-control mb-2" placeholder="Nome" name="nome">
			<input type="password" class="form-control mb-2" placeholder="Password" name="pwd">
			<?php if (isset($_POST['submit']))
				echo '<span class="text-danger">La password è errata</span><br>';
			?>
			<br><input type="submit" class="btn btn-success" value="Accedi" name="submit">
		</form>
	</center></div>
<?php
}
?>
</body>
</html>
