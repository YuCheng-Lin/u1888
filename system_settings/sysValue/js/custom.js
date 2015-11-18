$(function(){
	$('#dialogModal').on('shown.bs.modal', function (event) {
		var modal = $(this);
		var form  = modal.find('form');

		modal.find('form input:first').focus();
		var validator = form.validate();
	});

	$(".sysBtn").on("click", function(){
		var button = $(this);
		var fn     = button.data("fn");
		var msg    = fn == "recovery" ? "確認恢復額度？" : "確定執行？";
		if(!confirm(msg)){
			return false;
		}
		$.ajax({
			url: "functionLib.php",
			type: "POST",
			dataType: "json",
			data : {
				type : fn
			},
			success : function(json){
				button.removeClass("disabled");
				if(json['systemErr']){
					eval(json['systemErr']);
					return false;
				}
				if(json['result']){
					alert(json['resultMsg']);
				}
				return false;
			},
			beforeSend : function(e){
				button.addClass("disabled");
			},
			error : function(e){
				button.removeClass("disabled");
			}
		});
		return false;
	});

	$(document).on("click", ".calBtn", function(){
		var button  = $(this);
		var fn      = button.data("fn");
		var msg     = "確定計算？";
		var btnText = button.text();
		var select  = button.parents("#dataForm").find("#select").val();

		if(!confirm(msg)){
			return false;
		}
		$.ajax({
			url: "functionLib.php",
			type: "POST",
			dataType: "json",
			data : {
				type   : fn,
				select : select
			},
			success : function(json){
				button.removeClass("disabled").text(btnText);
				if(json['systemErr']){
					eval(json['systemErr']);
					return false;
				}
				if(json['result']){
					eval(json['resultMsg']);
				}
				return false;
			},
			beforeSend : function(e){
				button.addClass("disabled").text("計算中...");
			},
			error : function(e){
				button.removeClass("disabled").text(btnText);
			}
		});
		return false;
	});
});