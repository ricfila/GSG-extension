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
	<link rel="stylesheet" href="stile_monitor.css" />
	<link rel="icon" type="image/png" href="../pannello/media/display-fill.png" />
	<title>Monitor cucina</title>
	<?php
	include '../connect.php';
	$conn = pg_connect((filter_var($server, FILTER_VALIDATE_IP) ? "hostaddr" : "host") . "=$server port=$port dbname=$dbname user=$user password=$password connect_timeout=5") or die('Connessione al database non riuscita.');
	if (pg_connection_status($conn) == PGSQL_CONNECTION_BAD) {
		echo 'Errore di connessione al database.';
	}
	if (isset($_GET['s']))
		$settore = pg_escape_string($conn, $_GET['s']);
	?>
</head>
<body>
	<div class="container-lg h-100" style="padding-top: 80px;">
		<nav class="fixed-top navbar navbar-expand-lg navbar-dark bg-warning">
			<div class="container-lg">
				<span class="navbar-brand">
					<a href="index.php" class="navbar-brand"><i class="bi bi-display-fill"></i> <?php echo (isset($settore) ? $settore : 'Monitor cucina'); ?></a>
				</span>
				
				<?php if (isset($settore)) { ?>
					<ul class="navbar-nav text-end">
						<li class="nav-item">
							<a class="nav-link" href="#" onclick="aggiorna();"><i class="bi bi-arrow-clockwise"></i> Aggiorna</a>
						</li>
					</ul>
				<?php } ?>
			</div>
		</nav>

		<?php
		if (!isset($settore)) {
			?>
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
			?>
			<p id="err" aclass="mb-0">&nbsp;</p>
			<div id="corpo"></div>

			<script>
				function aggiorna() {
					let pre = $('#corpo').html();
					$('#err').html('<div class="spinner-border spinner-border-sm"></div> Caricamento in corso...');

					$.getJSON("ajax.php?a=ingredienti&settore=<?php echo $settore; ?>")
					.done(function(json) {
						$('#err').html('&nbsp;');
						try {
							let out = '<div class="row">';
							$.each(json, function(i, res) {
								out += '<div class="col-12 col-md-6 mb-4">';
								out += '<div class="row"><div class="col-auto">';
								out += '<h4>' + res.descrizione + ':</h4>';
								out += '</div><div class="col-auto">';
								out += '<div class="bg-' + (res.qta_attiva == 0 ? "dark" : (res.qta_attiva < 10 ? "success" : "danger")) + ' text-center py-1 px-0 rounded-3 rigaing" style="animation-delay: ' + (i * 0.05) + 's;"><h4 class="text-light m-0">' + res.qta_attiva + '</h4></div>';
								out += '</div></div>';
								out += '<p>Ordinati: ' + (parseInt(res.qta_attiva) + parseInt(res.qta_evasa)) + ' - Evasi: ' + res.qta_evasa + '</p>';
								out += '</div>';
							});
							out += '</div>';
							$('#corpo').html(out);
						} catch (err) {
							$('#err').html('<span class="text-danger"><strong>Errore nell\'elaborazione della richiesta:</strong></span>' + json);
						}
					})
					.fail(function(jqxhr, textStatus, error) {
						$('#err').html('<span class="text-danger"><strong>Errore durante la richiesta:</strong></span>' + jqxhr.responseText);
						$('#corpo').html(pre);
					})
				}
				aggiorna();
				setInterval(aggiorna, 30*1000);
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
