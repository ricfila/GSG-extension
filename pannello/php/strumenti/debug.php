
<div class="modal fade" id="modalpostgresql">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Stringa di connessione al database PostgreSQL</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body">
				<div class="input-group mb-3">
					<input type="text" class="form-control" id="postgres">
					<button class="btn btn-outline-secondary" type="button" onclick="copiatesto();">Copia</button>
				</div>
			</div>
		</div>
	</div>
</div>


<div class="modal fade" id="modaldatabase">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Unifica database</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body">
				<?php
				$computer = array('Sagra01', 'Sagra02', 'Cassa1', 'Cassa2', 'Cassa3');
				
				echo '<h6><strong>Database coinvolti nella separazione</strong></h6>';
				foreach ($computer as $pc) {
					echo '<div class="form-check" style="margin-left: 5px;">';
					echo '<input type="checkbox" class="form-check-input" id="db' . $pc . '">';
					echo '<label class="form-check-label" for="db' . $pc . '">' . $pc . '</label>';
					echo '</div>';
				}
				
				echo '<br><h6><strong>Database di destinazione</strong></h6>';
				echo '<select class="form-select">';
				foreach ($computer as $pc) {
					echo '<option value="' . $pc . '">' . $pc . '</option>';
				}
				echo '</select>';
				?>
				<br>
				<button class="btn btn-success" onclick="unificaDb();">Procedi</button>
			</div>
		</div>
	</div>
</div>

<script>
var modpostgresql = new bootstrap.Modal(document.getElementById('modalpostgresql'));

// Preparazione modal postgres
function postgres() {
	$.get("php/ajax.php", {a: "postgres"}, function(res, stato) {
		if (stato == 'success') {
			document.getElementById("postgres").value = res;
			modpostgresql.show();
		} else {
			mostratoast(false, "Richiesta fallita: " + stato);
		}
	});
}

function copiatesto() {
	var copyText = document.getElementById("postgres");
	copyText.select();
	navigator.clipboard.writeText(copyText.value);
}

// Funzione per debug, DA NON USARE durante la sagra. Cambia la data di tutti gli ordini del database
function spostaOrdini() {
	conferma('Cambia data ordini', '<span class="text-danger">L\'azione che si sta osando intraprendere è <strong>distruttiva</strong> e predisposta solo per scopi di debug.</span><br><br>Sei sicuro di voler modificare la data di emissione di tutti gli ordini presenti nel database?', 'spostaOrdiniOk();');
}

function spostaOrdiniOk() {
	$.get("php/ajax.php", {a: "impostaordinioggi", data: dataToString(data)}, function(res, stato) {
		if (stato == 'success') {
			if (res == '1') {
				mostratoast(true, '<i class="bi bi-save"></i>&emsp;Salvataggio riuscito!');
				getComande();
			} else {
				mostratoast(false, "Salvataggio fallito: " + res);
			}
		} else {
			mostratoast(false, "Richiesta fallita: " + stato);
		}
	});
}

</script>