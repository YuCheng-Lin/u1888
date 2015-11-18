;$(function(){
	//更動群組
	function chgAg(select_id, powerAry){
		
		//歸零
		$("#dialog_content .power-menu input").prop('disabled', false);
		$("#dialog_content .power-menu input").removeAttr('disabled');
		$("#dialog_content .power-menu input[type='radio'][value!='r'][value!='w']").prop('checked', true);
		$("#dialog_content .power-menu input[type='radio'][value!='r'][value!='w']").attr('checked', 'checked');
		
		$("#dialog_content .power-menu input[type='radio'][id='admin_power1w']").prop('checked', true);
		$("#dialog_content .power-menu input[type='radio'][id='admin_power1w']").attr('checked', 'checked');
		
		var defult_power = powerAry[select_id];
		
		for(i in defult_power){
			
//			//修正IE 6~7
//			if ( $.browser.msie && ($.browser.version == '7.0'|| $.browser.version == '6.0')){
//				var noCheckedW = '<input type="radio" class="noborder" id="admin_power'+ i + 'w" name="admin_power['+i+']" value="w" />';
//				$("#dialog_content .power-menu input[id='admin_power"+i+"w']").replaceWith(noCheckedW);
//				var noCheckedR = '<input type="radio" class="noborder" id="admin_power'+ i + 'r" name="admin_power['+i+']" value="r" />';
//				$("#dialog_content .power-menu input[id='admin_power"+i+"r']").replaceWith(noCheckedR);
//				var noCheckedNull = '<input type="radio" class="noborder" id="admin_power'+ i + '" name="admin_power['+i+']" value="" />';
//				$("#dialog_content .power-menu input[id='admin_power"+i+"']").replaceWith(noCheckedNull);
//				
//				//設定值
//				var nowNode = $("#dialog_content .power-menu input[id='admin_power"+ i + defult_power[i] +"']");
//				var nowNodeIEChecked = '<input type="radio" id="admin_power'+ i + defult_power[i] +'" name="admin_power['+i+']" checked="checked" value="'+ defult_power[i] +'" />';
//				nowNode.replaceWith(nowNodeIEChecked);
//				
//			}else{
				//設定值
				var nowNode = $("#dialog_content .power-menu input[id='admin_power"+ i + defult_power[i] +"']");
				nowNode.prop('checked', true);
				nowNode.attr('checked', "checked");
//			}
			
			
			var parentNode = $("#dialog_content .power-menu input[id='admin_power"+ i + defult_power[i] +"']").parentsUntil(".power-menu").has("ul");
			if(parentNode.length > 0){
				for(var j=0; j<parentNode.length; j++){
					var pow = $(parentNode[j]).children("label").children("input:checked").val();

					switch(pow){
						case 'w':
							$(parentNode[j]).children("ul").children("li").children("label").children("input").prop('disabled', false);
							$(parentNode[j]).children("ul").children("li").children("label").children("input").removeAttr('disabled');
						break;
						
						case 'r':
							$(parentNode[j]).children("ul").children("li").children("label").children("input[value='w']").prop('disabled', true);
							$(parentNode[j]).children("ul").children("li").children("label").children("input[value='w']").attr('disabled', 'disabled');
						break;
						
						default:
							$(parentNode[j]).children("ul").children("li").children("label").children("input").prop('disabled', true);
							$(parentNode[j]).children("ul").children("li").children("label").children("input").attr('disabled', 'disabled');
						break;
					}
				}
			}
			
		}
		
		//radio disabled
		var closeAry = $("#dialog_content .power-menu input[value!='r'][value!='w']:checked");
		if(closeAry.length > 0){
			for(var i=0; i<closeAry.length; i++){
				radioAct(closeAry[i]);
			}
		}
		
		
	}
	
	//更動radio
	function radioAct(Obj){	
		var thisVal = $(Obj).val();
		var parent_menu = $(Obj).parent().parent(); 
		
		//修正IE，不允許空值
		if(thisVal == ''){
			var obj = "ul input[value!='w'][value!='r']";
		}else{
			var obj = "ul input[value='"+ thisVal +"']";
		}
		
		$(parent_menu).find(obj).prop('checked', true);
		$(parent_menu).find(obj).attr('checked', 'checked');
		
		switch(thisVal){
			case 'w':
				$(parent_menu).find("ul input").prop("disabled", false);
				$(parent_menu).find("ul input").removeAttr("disabled");
			break;
			
			case 'r':
				$(parent_menu).find("ul input").prop("disabled", false);
				$(parent_menu).find("ul input").removeAttr("disabled");
				$(parent_menu).find("ul input[value='w']").prop("disabled", true);
				$(parent_menu).find("ul input[value='w']").attr("disabled", "disabled");
			break;
			
			default:
				$(parent_menu).find("ul input").prop("disabled", true);
				$(parent_menu).find("ul input").attr("disabled", "disabled");
			break;
		}
		
		//當子選單全關閉時，父層自動關閉
		if(thisVal == ''){
			var sub_menu_null = $(Obj).parent().parent().parent().find("input[type=radio][value!='r'][value!='w']");
			var close_parent = true;
			for(var i=0; i<sub_menu_null.length; i++){
				if($(sub_menu_null[i]).prop('checked') == false || $(sub_menu_null[i]).attr('checked') == ''){
					close_parent = false;
				}
			}
			if(close_parent){
				var tragetRadio = $(Obj).parent().parent().parent().parent().find("input[type=radio][value!='r'][value!='w']"); 
				$(tragetRadio).prop('checked', true);
				$(tragetRadio).attr('checked', "checked");
				$(tragetRadio).parent().parent().find("ul input").prop("disabled", true);
				$(tragetRadio).parent().parent().find("ul input").attr("disabled", "disabled");
			}
		}
		
	}
	
	
	
	//新增群組按鈕
	$(".createAdmin").click(function(){
		var o = $(this);
		$.ajax({
			url:"/functionLib.php",
			type:"POST",
			dataType: "json",
			data:{type	:  o.attr('fn')},
			success: function(json){
				if(json['systemErr']){
					eval(json['systemErr']);
					return false;
				}
				
				$("#dialog_content").html(json['result']);
				
				var powObj = $("#dialog_content a[pid]");
				
				for(i=0; i<powObj.length; i++){
					var pid = $(powObj[i]).attr('pid'); 
					
					var powerW = '<label for="admin_power'+ pid +'w"><input type="radio" value="w" class="noborder" id="admin_power'+ pid +'w" name="admin_power['+ pid +']">'+
							     '<img title="寫" alt="寫" src="/img/hammer_screwdriver.png"></label>';
					var powerR = '<label for="admin_power'+ pid +'r"><input type="radio" value="r" class="noborder" id="admin_power'+ pid +'r" name="admin_power['+ pid +']">'+
								 '<img title="讀" alt="讀" src="/img/view.png"></label>';
					var powerNull = '<label for="admin_power'+ pid +'"><input type="radio" checked="checked" value="" class="noborder" id="admin_power'+ pid +'" name="admin_power['+ pid +']">'+
									'<img title="無" alt="無" src="/img/cross.png"></label>';
					
					if(pid == '1'){
						$(powObj[i]).after('<label for="admin_power'+ pid +'w"><input type="radio" checked="checked" value="w" class="noborder" id="admin_power'+ pid +'w" name="admin_power['+ pid +']">'+
							     		   '<img title="寫" alt="寫" src="/img/hammer_screwdriver.png"></label>');
						continue;
					}
					
					switch(json['adminPowAry'][pid]){
						case 'w':
							var aftreHtml = powerW + powerR + powerNull; 
						break;
						
						case 'r':
							var aftreHtml = powerR + powerNull;
						break;
						
						default:
							var aftreHtml = '';
						break;
					}
					
					$(powObj[i]).after(aftreHtml);
				}
				
				
				//更動群組時
				$("#dialog_content .select_ag").change(function(){
					chgAg($(this).val(), json['agPowAry']);
				});
							
				//radio 動作
				$("#dialog_content .power-menu input[type='radio']").click(function(){
					radioAct($(this));
				});
				
				//表格變色
				tableRowColor();
				
				//表單檢驗
				$("#adminForm").validationEngine('attach', {promptPosition : "centerRight"});
				
				//初始化
				if($(".select_ag option").length > 0){
					var firstVal = $(".select_ag option").first().val();
					chgAg(firstVal, json['agPowAry']);
				}else{
					var closeAry = $("#dialog_content .power-menu input[value!='r'][value!='w'][checked]");
					if(closeAry.length > 0){
						for(var i=0; i<closeAry.length; i++){
							radioAct(closeAry[i]);
						}
					}
					var onlyRead = $("#dialog_content .power-menu input[value='r'][checked]");
					if(onlyRead.length > 0){
						for(var i=0; i<onlyRead.length; i++){
							radioAct(onlyRead[i]);
						}
					}
				}
				
				
				//dialog
				var dialogBtn = {
					"送出" : function(){
						$("#dialog_content #adminForm").submit();
					},
					"關閉" : function(){
						$(this).dialog("close");
					}
				};
				
				$("#dialog").dialog( "option", "buttons", dialogBtn );
				$("#dialog").dialog( "option", "title", "新增管理者群組" );
				$("#dialog").dialog( "option", "width", 620 );
				$("#dialog").dialog( "open" );
				
			}
		});
		
	});
	
	
	
	//帳號修改
	$(".editAdmin").click(function(){
		var agid = $(this).parent().parent().attr('agid');
		
		var o = $(this);
		$.ajax({
			url:"/functionLib.php",
			type:"POST",
			dataType: "json",
			data:{type	:  o.attr('fn'),
				  ag_id	:  agid},
			success: function(json){
				if(json['systemErr']){
					eval(json['systemErr']);
					return false;
				}
				
				$("#dialog_content").html(json['result']);
				
//				alert(json['adminPowAry']);
				
				var powObj = $("#dialog_content a[pid]");
				for(i=0; i<powObj.length; i++){
					var pid = $(powObj[i]).attr('pid'); 
					
					var powerW = '<label for="admin_power'+ pid +'w"><input type="radio" value="w" class="noborder" id="admin_power'+ pid +'w" name="admin_power['+ pid +']">'+
							     '<img title="寫" alt="寫" src="/img/hammer_screwdriver.png"></label>';
					var powerR = '<label for="admin_power'+ pid +'r"><input type="radio" value="r" class="noborder" id="admin_power'+ pid +'r" name="admin_power['+ pid +']">'+
								 '<img title="讀" alt="讀" src="/img/view.png"></label>';
					var powerNull = '<label for="admin_power'+ pid +'"><input type="radio" checked="checked" value="" class="noborder" id="admin_power'+ pid +'" name="admin_power['+ pid +']">'+
									'<img title="無" alt="無" src="/img/cross.png"></label>';
					
					if(pid == '1'){	//個人基本資料
						$(powObj[i]).after('<label for="admin_power'+ pid +'w"><input type="radio" checked="checked" value="w" class="noborder" id="admin_power'+ pid +'w" name="admin_power['+ pid +']">'+
							     		   '<img title="寫" alt="寫" src="/img/hammer_screwdriver.png"></label>');
						continue;
					}
					
					//目前操作人員權限
					switch(json['adminPowAry'][pid]){
						case 'w':
							var aftreHtml = powerW + powerR + powerNull; 
						break;
						
						case 'r':
							var aftreHtml = powerR + powerNull;
						break;
						
						default:
							var aftreHtml = '';
						break;
					}
					
					$(powObj[i]).after(aftreHtml);
				}
				
				//初始化
//				if($(".select_ag option").length > 0){
//					var firstVal = $(".select_ag option").first().val();
//					chgAg(firstVal, json['agPowAry']);
//				}
				
				
				$defAry = new Array();
				$defAry[0] = json['userPowAry'];
				chgAg(0, $defAry);
				
				
				//更動群組時
				$("#dialog_content .select_ag").change(function(){
					chgAg($(this).val(), json['agPowAry']);
				});
							
				//radio 動作
				$("#dialog_content .power-menu input[type='radio']").click(function(){
					radioAct($(this));
				});
				
				
				//表格變色
				tableRowColor();
				//表單檢驗
				$("#adminForm").validationEngine('attach', {promptPosition : "centerRight"});
				
				$(".button").button();
				$(".import_pwd").click(function(){
					$("#adminForm").validationEngine('detach');	//註銷表單檢驗
					$("#admin_pwd").prop("disabled", false);
					$("#admin_pwd").removeClass("disBtn");
					$("#admin_pwd").addClass("validate[required,custom[onlyLetterNumber],minSize[6],maxSize[16]]");
					$("#admin_pwd").focus();
					$("#adminForm").validationEngine('attach', {promptPosition : "centerRight"});
				});
				
				
				//dialog
				var dialogBtn = {
					"送出" : function(){
						$("#dialog_content #adminForm").submit();
					},
					"關閉" : function(){
						$(this).dialog("close");
					}
				};
				
				$("#dialog").dialog( "option", "buttons", dialogBtn );
				$("#dialog").dialog( "option", "title", "修改管理者群組" );
				$("#dialog").dialog( "option", "width", 620 );
				$("#dialog").dialog( "open" );
				
			}
		});		
	});
	
	//刪除帳號
	$(".delAdmin").click(function(){
		
		var agid = $(this).parent().parent().attr('agid');
		var o = $(this);
		$.ajax({
			url:"/functionLib.php",
			type:"POST",
			dataType: "json",
			data:{type	:  o.attr('fn'),
				  ag_id	:  agid},
			success: function(json){
				if(json['systemErr']){
					eval(json['systemErr']);
					return false;
				}
				
				$("#dialog_content").html(json['result']);
				
				//dialog
				var dialogBtn = {
					"送出" : function(){
						$("#dialog_content #adminForm").submit();
					},
					"關閉" : function(){
						$(this).dialog("close");
					}
				};
				
				$("#dialog").dialog( "option", "buttons", dialogBtn );
				$("#dialog").dialog( "option", "title", "刪除管理者群組" );
				$("#dialog").dialog( "option", "width", 300 );
				$("#dialog").dialog( "open" );
				
			}
		});	
	});
	
	
	//檢視帳號
	$(".viewAdmin").click(function(){
		
		var aid = $(this).parent().parent().attr('aid');
		
		var o = $(this);
		$.ajax({
			url:"/functionLib.php",
			type:"POST",
			dataType: "json",
			data:{
				type	:  o.attr('fn'),
				a_id	:  aid
			},
			success: function(json){
				
				if(json['systemErr']){
					eval(json['systemErr']);
					return false;
				}
				
				$("#dialog_content").html(json['result']);
				var powObj = $("#dialog_content a[pid]");
				for(i=0; i<powObj.length; i++){
					switch($(powObj[i]).attr('pow')){
						case 'w':
							$(powObj[i]).after('<img title="寫" alt="寫" src="/img/hammer_screwdriver.png">');
						break;
						
						case 'r':
							$(powObj[i]).after('<img title="讀" alt="讀" src="/img/view.png">');
						break;
					}
				}
				
				//表格變色
				tableRowColor();
				//dialog
				var dialogBtn = {
					"關閉" : function(){
						$(this).dialog("close");
					}
				};
				
				$("#dialog_content .pw").append('<img style="margin-left:10px;" src="/img/view.png" alt="檢視" title="檢視"/>');
				$("#dialog_content .pw").append('<img src="/img/hammer_screwdriver.png" alt="修改" title="修改"/>');
				$("#dialog_content .pr").append('<img style="margin-left:10px;" src="/img/view.png" alt="檢視" title="檢視"/>');
				
				$("#dialog").dialog( "option", "buttons", dialogBtn );
				$("#dialog").dialog( "option", "title", "檢視管理者帳號" );
				$("#dialog").dialog( "option", "width", 620 );
				$("#dialog").dialog( "open" );
			}
		});
	});
	
	
	
	
});

