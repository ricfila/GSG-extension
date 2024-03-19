<?php
function azionibonifica() {
	$titoli = array(
		'<i class="bi bi-arrow-bar-right"></i>&emsp;Allinea le sequenze ai record attuali',
		'<i class="bi bi-eraser"></i>&emsp;Cancella giacenze residue',
		'<i class="bi bi-list-ol"></i>&emsp;Riordina articoli'
	);
	$codici = array(
		'SELECT fixsequences();',
		'BEGIN;<br>
		UPDATE articoli SET id_giacenza = null;<br>
		UPDATE ingredienti SET id_giacenza = null;<br>
		DELETE FROM giacenze;<br>
		COMMIT; / ROLLBACK;',
		'BEGIN;<br>
		SELECT articoli.id as id, tipologie.posizione as posizione<br>
		FROM articoli JOIN tipologie ON articoli.id_tipologia = tipologie.id<br>
		ORDER BY tipologie.posizione, articoli.descrizione;<br>
		<br>
		per ogni articolo {<br>
		&emsp;UPDATE articoli SET posizione = i;<br>
		}<br>
		COMMIT; / ROLLBACK;'
	);
	$id = array(
		'sequenze',
		'ripristinagiacenze',
		'ordinaarticoli'
	);
	
	$out = '<div class="accordion" id="accordion">';
	for ($i = 0; $i < count($titoli); $i++) {
		$out .= '<div class="accordion-item">
				<h2 class="accordion-header" id="h' . $id[$i] . '">
					<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#' . $id[$i] . '" aria-expanded="false" aria-controls="' . $id[$i] . '">' . $titoli[$i] . '</button>
				</h2>
				<div id="' . $id[$i] . '" class="accordion-collapse collapse" aria-labelledby="h' . $id[$i] . '" data-bs-parent="#accordion">
					<div class="accordion-body">
						<div class="row">
							<div class="col-7">
								<code lang="sql">' . $codici[$i] . '</code>
							</div>
							<div class="col-5">';
								if ($id[$i] == 'ordinaarticoli') {
									$out .= 'All\'interno della tipologia ordina per:<br>
										<div class="form-check">
											<input type="radio" class="form-check-input" name="pararticoli" id="posizione" value="posizione" checked>
											<label class="form-check-label w-100" for="posizione">Posizionamento preesistente</label>
										</div>
										<div class="form-check">
											<input type="radio" class="form-check-input" name="pararticoli" id="descrizione" value="descrizione">
											<label class="form-check-label w-100" for="descrizione">Descrizione</label>
										</div>
										<div class="form-check">
											<input type="radio" class="form-check-input" name="pararticoli" id="descrizionebreve" value="descrizionebreve">
											<label class="form-check-label w-100" for="descrizionebreve">Descrizione breve</label>
										</div><br>';
								}
							$out .= '<button class="btn btn-success mb-2" onclick="bonifica(\'' . $id[$i] . '\');"><i class="bi bi-play-circle-fill"></i> Esegui</button><br>
								<samp id="res' . $id[$i] . '"></samp>
							</div>
						</div>
					</div>
				</div>
			</div>';
	}
	$out .= '</div>';
	return $out;
}
?>

<div class="modal fade" id="modalbonifica">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Azioni di bonifica del database</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body">
				<?php if ($pagina == 'pannello') echo azionibonifica(); ?>
			</div>
		</div>
	</div>
</div>

<script>
function bonifica(azione) {
	$('#res' + azione).html('<div class="spinner-border spinner-border-sm"></div> Operazione in corso...');
	$.ajax({
		url: "php/ajaxcasse.php?a=" + azione + (azione == 'ordinaarticoli' ? '&order=' + $('input[name="pararticoli"]:checked').val() : ''),
		success: function(res) {
			$('#res' + azione).html(res == '1' ? '<span class="text-success">Operazione completata con successo</span>' : res);
		},
		error: function(err) { // Server non raggiungibile
			$('#res' + azione).html('<span class="text-danger">Richiesta fallita per un motivo sicuramente preoccupante:</span> ' + err);
		},
		timeout: 2000
	});
}

</script>