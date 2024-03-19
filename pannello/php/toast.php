
<div id="divtoast" class="toast-container bottom-0 end-0 p-3" style="z-index: 1100; position: absolute;"></div>

<div class="modal fade" id="modalconfirm">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="mctitolo"></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body" id="mcbody"></div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-bs-dismiss="modal">Annulla</button>
				<button type="button" class="btn btn-success" id="mcok" onclick="" data-bs-dismiss="modal">OK</button>
			</div>
		</div>
	</div>
</div>

<script>
var tok = 0;
var tno = 0;

function mostratoast(tipo, msg, tempo = false) {
	if (tipo) {
		$('#divtoast').append('<div class="toast bg-success text-white" id="tok' + (tok) + '" role="alert" style="border-radius: 10px; margin: 10px 0px 0px 0px;" data-bs-delay="' + (tempo == false ? '1500' : tempo * 1000) + '"><div class="d-flex">' +
		'<div class="toast-body">' + msg + '</div>' +
		'<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>');
		$("#tok" + tok).toast("show");
		tok++;
	} else {
		$('#divtoast').append('<div class="toast bg-danger text-white" id="tno' + (tno) + '" role="alert" style="border-radius: 10px; margin: 10px 0px 0px 0px;" data-bs-delay="' + (tempo == false ? '3000' : tempo * 1000) + '"><div class="d-flex">' +
		'<div class="toast-body">' + msg + '</div>' +
		'<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>');
		$("#tno" + tno).toast("show");
		tno++;
	}
}

var modconfirm = new bootstrap.Modal(document.getElementById('modalconfirm'));

function conferma(titolo, messaggio, azione) {
	$('#mctitolo').html(titolo);
	$('#mcbody').html(messaggio);
	$('#mcok').attr('onclick', azione);
	modconfirm.show();
}
</script>