$(function(){
	$('#dialogModal').on('shown.bs.modal', function (event) {
		// var button = $(event.relatedTarget); // Button that triggered the modal
		// var title  = button.data('whatever'); // Extract info from data-* attributes
		var modal = $(this);
		var form  = $(this).find('form');

		modal.find('form input:first').focus();
		var fn = modal.find("form input[name=action]").val();

		//20150707-新增貨幣運算
		form.find("select[name=MemberCurrency]").on("change", function(){
			$(".CurrencyRate").text($(this).find("option[value="+$(this).val()+"]").data("rate"));
			form.find("#points").keyup();
		}).change();
		form.find("#points").on("keyup", function(){
			var point = $(this).val();
			var rate  = $(".CurrencyRate").text();
			var result = numMulti(point,rate);
			$(".calculate").text(rate+" * "+point+" = "+result);
		});

		// if(fn == 'addMember' || fn == 'addAgent'){
			var commissionText = $(".commission").text();
			var pointText      = $(".point").text();
			var validator = form.validate({
				onkeyup: false,
				rules:{
					admin_acc : {
						required : true,
						remote : {
							url:  "functionLib.php",
							type: "post",
							data: {
								type : "validate"
					        },
						}
					},
					admin_pwd : "required",
					admin_pwd2 : {
						required : true,
						equalTo: "#admin_pwd"
					},
					admin_name : {
						required : true,
						maxlength: 15
					},
					points : {
						required : true,
						number: true
					},
					commissionRate : {
						required : true,
						number: true,
						maxlength: 3
					},
					upAccount : {
						required: true,
						remote: {
					        url: "functionLib.php",
					        type: "post",
					        data: {
								upAccount: function(){
									return $("#upAccount").val();
								},
								type : "chkUpAccount"
					        },
					        success: function(json){
					        	var element = $("#upAccount").get(0);
					        	var previous = validator.previousValue( element );
					        	var valid = false;
					        	if(json['systemErr']){
					        		$(".commission").text(commissionText);
					        		$(".point").text(pointText);

					        		errors = {};
					        		message = json['systemErr'];
					        		errors[ element.name ] = previous.message = $.isFunction( message ) ? message( value ) : message;
					        		validator.invalid[ element.name ] = true;
					        		validator.showErrors( errors );
					        	}
					        	if(json['result'] || json['result'] == '0'){
					        		submitted = validator.formSubmitted;
					        		validator.prepareElement( element );
					        		validator.formSubmitted = submitted;
					        		validator.successList.push( element );
					        		delete validator.invalid[ element.name ];
					        		validator.showErrors();

					        		$(".commission").text(json['upAdminData'].commission+" %");
					        		$(".point").text(json['upAdminData'].point);
					        		valid = true;
					        	}
								previous.valid = valid;
								validator.stopRequest( element, valid );
					        },
					        error: function(){
					        	alert("System Error");
					        	return false;
					        }
					    }
					},

					// addMem
					MemberAccount : {
						required : true,
						remote : {
					        url: "functionLib.php",
					        type: "post",
					        data: {
					        	id   : form.find("input[name=id]").val(),
								type : "validate"
					        },
						}
					},
					MemberPassword : "required",
					MemberPassword2 : {
						required : true,
						equalTo: "#MemberPassword"
					},
					NickName : {
						required : true,
						maxlength: 15,
						remote : {
					        url: "functionLib.php",
					        type: "post",
					        data: {
					        	id   : form.find("input[name=id]").val(),
								type : "validate"
					        },
						}
					},
					points : {
						required : true,
						number: true
					},
					ReturnRate : {
						required : true,
						number: true,
						maxlength: 2
					},
					// 20150721-新增警報系統
					alarmAccount : {
						required: true,
						remote: {
					        url: "functionLib.php",
					        type: "post",
					        data: {
								type : "chkAlarmAccount"
					        },
					        success: function(json){
					        	var element = $("#alarmAccount").get(0);
					        	var previous = validator.previousValue( element );
					        	var valid = false;
					        	if(json['systemErr']){
					        		errors = {};
					        		message = json['systemErr'];
					        		errors[ element.name ] = previous.message = $.isFunction( message ) ? message( value ) : message;
					        		validator.invalid[ element.name ] = true;
					        		validator.showErrors( errors );
					        	}
					        	if(json['result'] || json['result'] == '0'){
					        		submitted = validator.formSubmitted;
					        		validator.prepareElement( element );
					        		validator.formSubmitted = submitted;
					        		validator.successList.push( element );
					        		delete validator.invalid[ element.name ];
					        		validator.showErrors();

					        		form.find("input[type=radio]").val([json["alarmType"]]);
					        		valid = true;
					        	}
								previous.valid = valid;
								validator.stopRequest( element, valid );
					        },
					        error: function(){
					        	alert("System Error");
					        	return false;
					        }
					    }
					},
				}
			});
		// }
		// if(fn == 'addMem'){
		// 	var validator = modal.find('form').validate({
		// 		rules:{
		// 			MemberAccount : "required",
		// 			MemberPassword : "required",
		// 			MemberPassword2 : {
		// 				required : true,
		// 				equalTo: "#MemberPassword"
		// 			},
		// 			NickName : {
		// 				required : true,
		// 				maxlength: 15
		// 			},
		// 			points : {
		// 				required : true,
		// 				number: true
		// 			},
		// 			ReturnRate : {
		// 				required : true,
		// 				number: true,
		// 				maxlength: 2
		// 			},
		// 			upAccount : {
		// 				required: true,
		// 				remote: {
		// 			        url: "functionLib.php",
		// 			        type: "post",
		// 			        data: {
		// 						upAccount: function(){
		// 							return $("#upAccount").val();
		// 						},
		// 						type : "chkUpAccount"
		// 			        },
		// 			        success: function(json){
		// 			        	var element = $("#upAccount").get(0);
		// 			        	var previous = validator.previousValue( element );
		// 			        	var valid = false;
		// 			        	if(json['systemErr']){
		// 			        		$(".point").text(commissionText);

		// 			        		errors = {};
		// 			        		message = json['systemErr'];
		// 			        		errors[ element.name ] = previous.message = $.isFunction( message ) ? message( value ) : message;
		// 			        		validator.invalid[ element.name ] = true;
		// 			        		validator.showErrors( errors );
		// 			        	}
		// 			        	if(json['result'] || json['result'] == '0'){
		// 			        		submitted = validator.formSubmitted;
		// 			        		validator.prepareElement( element );
		// 			        		validator.formSubmitted = submitted;
		// 			        		validator.successList.push( element );
		// 			        		delete validator.invalid[ element.name ];
		// 			        		validator.showErrors();

		// 			        		$(".point").text(json['upAdminData'].point);
		// 			        		valid = true;
		// 			        	}
		// 						previous.valid = valid;
		// 						validator.stopRequest( element, valid );
		// 			        },
		// 			        error: function(){
		// 			        	alert("System Error");
		// 			        	return false;
		// 			        }
		// 			    }
		// 			}
		// 		}
		// 	});
		// }
		// if(fn == 'plusAgentPoints' || fn == 'plusMemPoints' || fn == 'minusAgentPoints' || fn == 'minusMemPoints'){
		// 	modal.find('form').validate({
		// 		rules:{
		// 			points : {
		// 				required : true,
		// 				number: true
		// 			}
		// 		}
		// 	});
		// }
		if(fn == 'updAgentData'){
			modal.find('.updPwdBtn').click(function(){
				$(this).prev().show();
				$(this).parent().parent().next().show();
				$(this).remove();
				$( "#admin_pwd" ).rules( "add", {
					required : true
				});
				$( "#admin_pwd2" ).rules( "add", {
					required : function(){
						return $("#admin_pwd").val();
					},
					equalTo: "#admin_pwd"
				});
				$( "#admin_pwd" ).focus();
			});
			// modal.find('form').validate({
			// 	rules:{
			// 		admin_name : {
			// 			required : true,
			// 			maxlength: 15
			// 		},
			// 		commissionRate : {
			// 			required : true,
			// 			number: true,
			// 			maxlength: 3
			// 		}
			// 	}
			// });
		}
		if(fn == 'updMemData'){
			modal.find('.updPwdBtn').click(function(){
				$(this).prev().show();
				$(this).parent().parent().next().show();
				$(this).remove();
				$( "#MemberPassword" ).rules( "add", {
					required : true
				});
				$( "#MemberPassword2" ).rules( "add", {
					required : function(){
						return $("#MemberPassword").val();
					},
					equalTo: "#MemberPassword"
				});
				$( "#MemberPassword" ).focus();
			});
			// modal.find('form').validate({
			// 	rules:{
			// 		admin_name : {
			// 			required : true,
			// 			maxlength: 15
			// 		},
			// 		commissionRate : {
			// 			required : true,
			// 			number: true,
			// 			maxlength: 3
			// 		}
			// 	}
			// });
		}
	});

	//更新點數按鈕
	$(".refreshBtn").click(function(){
		var button = $(this);
		var fn     = button.data("fn");
		var id     = button.data("id");
		var span   = button.parent().find(">span");
		var points = span.text();
		var load   = '<img src="/img/loading-new-tweets.gif" alt="..." />';
		$.ajax({
			url: "functionLib.php",
			type: "POST",
			dataType: "json",
			data : {
				type : fn,
				id   : id
			},
			success : function(json){
				if(json['systemErr']){
					span.fadeOut(function(){
						span.text(points).fadeIn();
					});
					eval(json['systemErr']);
					return false;
				}
				if(json['result'] || json['result'] == '0'){
					span.fadeOut(function(){
						span.text(json['result']).fadeIn();	
					});
					return true;
				}
				span.text(points);
				return false;
			},
			beforeSend : function(e){
				span.fadeOut(function(){
					span.html(load).fadeIn();	
				});
			},
			error : function(e){
				span.fadeOut(function(){
					span.text(points).fadeIn();
				});
			}
		});
		return false;
	});

	//乘法運算-避免損失精度
	function numMulti(num1, num2) { 
		var baseNum = 0; 
		try { 
			baseNum += num1.toString().split(".")[1].length; 
		} catch (e) { 
		} 
		try { 
			baseNum += num2.toString().split(".")[1].length; 
		} catch (e) { 
		} 
		return Number(num1.toString().replace(".", "")) * Number(num2.toString().replace(".", "")) / Math.pow(10, baseNum); 
	}; 
});