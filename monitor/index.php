<!doctype html>
<html lang="it"><!-- Palmare - Versione 1.2 - Ottobre 2024 -->
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta charset="utf-8" />
	<link href="../css/bootstrap-5.0.2/bootstrap.css" rel="stylesheet" />
	<link href="../css/bootstrap-5.0.2/bootstrap-icons.css" rel="stylesheet" />
	<script src="../js/bootstrap-5.0.2/bootstrap.bundle.min.js"></script>
	<script src="../js/jquery-3.6.0.min.js"></script>
	<link rel="stylesheet" href="../css/stile.css" />
	<link rel="icon" type="image/png" href="../pannello/media/display-fill.png" />
	<title>Monitor cucina</title>
	<?php
	include '../connect.php';
	$conn = pg_connect((filter_var($server, FILTER_VALIDATE_IP) ? "hostaddr" : "host") . "=$server port=$port dbname=$dbname user=$user password=$password connect_timeout=5") or die('Connessione al database non riuscita.');
	if (pg_connection_status($conn) == PGSQL_CONNECTION_BAD) {
		echo 'Errore di connessione al database.';
	}
	?>
</head>
<body aclass="bg-warning">
	<div class="container-lg h-100 pt-4">
		<?php
		if (!isset($_GET['s'])) {
			?>
			<h1><i class="bi bi-display-fill"></i> Monitor cucina</h1>
			<h5>Seleziona il reparto degli ingredienti che vuoi controllare</h5>

			<div class="row mt-4">
				<?php
				$res = pg_query($conn, "SELECT * FROM dati_ingredienti JOIN ingredienti on dati_ingredienti.id_ingrediente = ingredienti.id WHERE dati_ingredienti.monitora ORDER BY dati_ingredienti.settore, ingredienti.descrizione;");
				$settore = null;
				$out = '';
				$lista = '';
				while ($row = pg_fetch_assoc($res)) {
					if ($row['settore'] != $settore) {
						if ($settore != null)
							$out .= '</div></div></a></div>';
						$out .= '<div class="col-sm-4 col-md-3">';
						$out .= '<a class="text-dark" href="index.php?s=' . $row['settore'] . '" style="text-decoration: none;">';
						$out .= '<div class="card mb-4 border-warning border-4"><h3 class="card-header bg-warning">' . $row['settore'] . '</h3><div class="card-body p-2">';
						$settore = $row['settore'];
						$i = 0;
					}
					$out .= ($i == 0 ? '' : ', ') . $row['descrizionebreve'];
					$i++;
				}
				echo $out;
				?>
			</div>
			<?php
		} else {
			$settore = pg_escape_string($conn, $_GET['s']);
			?>
			
			<div class="row">
				<div class="col-12 col-sm-auto">
					<h1><i class="bi bi-display-fill"></i> <?php echo $settore; ?></h1>
				</div>
				<div class="col-6 my-auto">
					<a class="btn btn-sm btn-outline-dark" href="index.php"><i class="bi bi-caret-left-fill"></i> Cambia reparto</a>
				</div>
				<div class="col-6 col-sm-auto my-auto text-end">
					<button class="btn btn-success" onclick="aggiorna();"><i class="bi bi-arrow-clockwise"></i> Aggiorna</button>
				</div>
			
			<p id="err"></p>
			<div id="corpo" class="mt-4"></div>

			<script>
				function aggiorna() {
					$.getJSON("ajax.php?a=ingredienti&settore=<?php echo $settore; ?>")
					.done(function(json) {
						$('#err').html('');
						$('#corpo').html('');
						try {
							$.each(json, function(i, res) {
								
							});
						} catch (err) {
							$('#err').html('<span class="text-danger"><strong>Errore nell\'elaborazione della richiesta:</strong></span>' + json);
						}
					})
					.fail(function(jqxhr, textStatus, error) {
						$('#err').html('<span class="text-danger"><strong>Errore durante la richiesta:</strong></span>' + jqxhr.responseText);
					})
				}

			</script>
			<?php
		}
		?>
	</div>
	
	<script>
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
</body>
</html>
