<!-- https://bootswatch.com/zephyr/ -->

<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta charset="utf-8" />
<!--link href="custom.css" rel="stylesheet" /-->
<link href="../css/bootstrap-5.0.2/bootstrap.css" rel="stylesheet" />
<link href="../css/bootstrap-5.0.2/bootstrap-icons.css" rel="stylesheet" />
<script src="../js/bootstrap-5.0.2/bootstrap.bundle.min.js"></script>
<script src="../js/jquery-3.6.0.min.js"></script>
<script src="../js/chart.js"></script>
<!-- Info compilazione bootstrap:
Il file sorgente da modificare per sovrascrivere le variabili è "bootstrap-5.0.2/scss/bootstrap.scss"
Compilare lanciando lo script "compila.bat", che sovrascrive il file "bootstrap-5.0.2/dist/css/bootstrap.css" già importato in questa pagina.
sass.bat e la cartella src servono al compilatore, non vanno rimossi o spostati. -->
<link rel="stylesheet" href="../css/stile.css" />

<?php
require '../connect.php';
$pagina = (str_ends_with($_SERVER['PHP_SELF'], 'index.php') ? 'pannello' : (str_ends_with($_SERVER['PHP_SELF'], 'casse.php') ? 'ausilio' : 'associazioni'));
$lido = str_ends_with($server, '1') ? 1 : 2;

// MODIFICARE QUESTI IP SE DIVERSI DAI PROPRI:
$ipserver1 = '192.168.1.201';
$ipserver2 = '192.168.1.202';

function menuturno() {
	global $pagina, $lido;
	echo '<li class="nav-item">
		<a class="nav-link" href="#" id="turno" data-bs-toggle="modal" data-bs-target="#modalturno"><i class="bi bi-alarm"></i> Seleziona il turno</a>
	</li>';
}

function navdx() {
	global $server, $pagina, $lido, $ipserver1, $ipserver2;
	echo '<ul class="navbar-nav">
		<li class="nav-item dropdown">
			<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><i class="bi bi-link-45deg"></i> Collegamenti</a>
			<ul class="dropdown-menu">
				<li><a class="dropdown-item" href="index.php"><i class="bi bi-star' . ($pagina == 'pannello' ? '-fill' : '') . '"></i> Pannello evasione comande</a></li>
				<li><a class="dropdown-item" href="casse.php"><i class="bi bi-heart' . ($pagina == 'ausilio' ? '-fill' : '') . '"></i> Ausilio alle casse</a></li>
				<li><a class="dropdown-item" href="http://' . $server . '/monitor/" target="_blank"><i class="bi bi-display"></i> Monitor cucina</a></li>
				<li><a class="dropdown-item" href="associazioni.php"><i class="bi bi-geo-alt' . ($pagina == 'associazioni' ? '-fill' : '') . '"></i> Associazioni</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item" href="http://' . ($lido == 1 ? $ipserver2 : $ipserver1) . '/pannello/' . ($pagina == 'pannello' ? '' : 'casse.php') . '"><i class="bi bi-' . ($lido == 1 ? 2 : 1) . '-circle"></i> ' . ($pagina == 'pannello' ? 'Pannello' : 'Ausilio') . ' su Sagra0' . ($lido == 1 ? 2 : 1) . '</a></li>
			</ul>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="#" onclick="toggleId();"><span id="iconid">' . (isset($_COOKIE['id']) && $_COOKIE['id'] == '1' ? '<i class="bi bi-check-square-fill"></i>' : '<i class="bi bi-square"></i>') . '</span> ID</a>
		</li>
	</ul>';
}

function icona() {
	global $pagina;
	return '<link rel="icon" type="image/png" href="media/' . ($pagina == 'pannello' ? 'star' : ($pagina == 'ausilio' ? 'heart' : 'geo-alt')) . '-fill.png" />';
}

?>