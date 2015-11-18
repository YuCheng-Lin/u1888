$(function(){
	$('#dialogModal').on('shown.bs.modal', function (event) {
		var modal = $(this);
		var form  = $(this).find('form');
		modal.find('form input:first').focus();
		var validator = modal.find('form').validate({
			onkeyup: false,
			rules:{
				MemberAccount : {
					remote : "validate.php"
				}
			}
		});
	});
});