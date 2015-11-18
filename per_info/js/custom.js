$(function(){
	$(".updatePwdBtn").click(function(){
		$(".updatePwd").removeClass("hidden").addClass("shown");
		$(this).hide();
	});
	$(".updateNickBtn").click(function(){
		$(".updateNick").removeClass("hidden").addClass("shown");
		$(this).parent().hide();
	});
	$(".chkNickBtn").click(function(){
		if(confirm("Sure about this?") == true){
			return true;
		}
		return false;
	});
});