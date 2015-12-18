$(document).ready(function() {
	  
	$(".chinese").editable("save.php", {
		cancel: 'cancel',
		submit: 'modify',
		indicator: "save...",
		tooltip: 'click to edit...',
		width: "70px",
                height: "20px"
	});
	
	$(".english").editable("save.php", {
		cancel: 'cancel',
		submit: 'modify',
		indicator: "save...",
		tooltip: 'click to edit...',
		width: "70px",
                height: "20px"
	});
	
	$(".partofspeech").editable("save.php", {
		cancel: 'cancel',
		submit: 'modify',
		indicator: "save...",
		tooltip: 'click to edit...',
		width: "70px",
                height: "20px"
	});
	
	$(".word").editable("save.php", {
		type   : 'textarea',
		cancel: 'cancel',
		submit: 'modify',
		indicator: "save...",
		tooltip: 'click to edit...',
	});
	
	$(".explanation").editable("save.php", {
		type   : 'textarea',
		cancel: 'cancel',
		submit: 'modify',
		indicator: "save...",
		tooltip: 'click to edit...',
	});
	
	$(".questiontext").editable("save.php", {
		type   : 'textarea',
		cancel: 'cancel',
		submit: 'modify',
		indicator: "save...",
		tooltip: 'click to edit...',
	});
	
	$(".choice").editable("save.php", {
		type   : 'textarea',
		cancel: 'cancel',
		submit: 'modify',
		indicator: "save...",
		tooltip: 'click to edit...',
	});
	
	$(".answer").editable("save.php", {
		cancel: 'cancel',
		submit: 'modify',
		indicator: "save...",
		tooltip: 'click to edit...',
		width: "70px",
		height: "20px"
	});
	
	$( "#tabs" ).tabs();
});