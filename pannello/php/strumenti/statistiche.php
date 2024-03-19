
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="canvasstat" style="height: 100%;">
	<div class="offcanvas-header">
		<h5 class="modal-title">Statistiche sul servizio</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body" style="padding-top: 0px; padding-right: 0px; padding-bottom: 0px; overflow-x: hidden;" id="statbody">
	</div>
</div>

<script>
var canvasstat = new bootstrap.Offcanvas(document.getElementById('canvasstat'));

$('.nav-pills a[data-bs-target="#tabstatistiche"]').on('show.bs.tab', function () {
	caricastatistiche('#statistichebody');
});

function statistiche() {
	caricastatistiche('#statbody');
}

var xx;
function caricastatistiche(target) {
	$(target).html('<center><h3><div class="spinner-border"></div> Calcoli superdifficili...</h3></center>');
	if (target == '#statbody')
		canvasstat.show();
	$.get("php/ajax.php", {a: "statistiche", questoturno: dataToString(data) + (pranzo(data) ? 'pranzo' : 'cena')}, function(res, stato) {
		if (stato == 'success') {
			$(target).html(res);
		} else {
			$(target).html('<span class="text-danger">Richiesta fallita per un motivo sicuramente preoccupante: </span>' + stato);
		}
	});
}

</script>