$(function(){
	$.validator.setDefaults({
		errorElement: "div",
		errorPlacement: function(error, element) {
			if($(element).parent(".input-group").length > 0){
				error.appendTo($(element).parent().parent());
			}else{
				error.appendTo($(element).parent());
			}
		},
		highlight: function(element) {
			if($(element).parent(".input-group").length > 0){
				$(element).parent().parent().parent().addClass("has-error").removeClass("has-success");
			}else{
				$(element).parent().parent().addClass("has-error").removeClass("has-success");
			}
		},
		unhighlight: function(element) {
			if($(element).parent(".input-group").length > 0){
				$(element).parent().parent().parent().removeClass("has-error").addClass("has-success");
			}else{
				$(element).parent().parent().removeClass("has-error").addClass("has-success");
			}
		},
		validClass: "alert alert-success",
		errorClass: "alert alert-danger",
		submitHandler: function(form) {
			//disabling submit button
			$(form).find("button[type=submit]").attr('disabled', true);
			$(".modal-footer").find(".formSubmit").attr('disabled', true);
			form.submit();
			return false;
		}
	});

	// dialogModal
	// 運用在init判斷開啟
	$("button.dialogModal, a.dialogModal").on('click', function(){
		var button = $(this);
		var title  = button.data('whatever');
		var target = button.data('target');
		var fn     = button.data('fn');
		var modal  = $(target);
		var id     = button.data('id');
		var public = button.data('public');
		var url    = public == "public" ? "/functionLib.php" : "functionLib.php";
		modal.find('.modal-title').text(title);
		$.ajax({
			url  :url,
			type :"POST",
			dataType : "json",
			data :{type : fn,id : id},
			success : function(json){
				if(json['systemErr']){
					eval(json['systemErr']);
					return false;
				}
				modal.find('.modal-body').html(json['result']);
				modal.modal("show");
				modal.find("form").append('<input type="hidden" value="'+fn+'" name="action" />');
				if(id){
					modal.find("form").append('<input type="hidden" value="'+id+'" name="id" />');
				}
				modal.find(".formSubmit").removeAttr("disabled").click(function(){
					modal.find("form").submit();
					return false;
				});
				if(button.data("modal-submit") == false){
					modal.find(".formSubmit").hide();
				}else{
					modal.find(".formSubmit").show();
				}
			}
		});
		return false;
	});

	//如需要另加function 可在此
	// $('#dialogModal').on('shown.bs.modal', function (event) {
		// var button = $(event.relatedTarget); // Button that triggered the modal
		// var title  = button.data('whatever'); // Extract info from data-* attributes
		// var fn     = button.data('fn');
		// var modal  = $(this);

		// modal.find('.modal-title').text("title");
	// });
	if($('#date_timepicker_start').length > 0 && $('#date_timepicker_end').length > 0){
		var start = $('#date_timepicker_start');
		var end   = $('#date_timepicker_end');
		start.datetimepicker({
			format:'Y-m-d',
			timepicker:false,
			minDate: start.data("mindate")?start.data("mindate"):false,
			maxDate: start.data("maxdate")?start.data("maxdate"):false,
			onShow:function( ct ){
				this.setOptions({
					maxDate:end.val()?end.val():false,
					formatDate:'Y-m-d'
				})
			}
		});
		end.datetimepicker({
			format:'Y-m-d',
			timepicker:false,
			minDate: end.data("mindate")?end.data("mindate"):false,
			maxDate: end.data("maxdate")?end.data("maxdate"):false,
			onShow:function( ct ){
				this.setOptions({
					minDate:start.val()?start.val():false,
					formatDate:'Y-m-d'
				})
			}
		});
		if($(".setdate").length > 0){
			$(".setdate").click(function(){
				start.val($(this).data("setdatefrom"));
				end.val($(this).data("setdateto"));
			});
		}
	}
});