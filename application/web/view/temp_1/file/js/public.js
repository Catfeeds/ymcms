$(document).ready(function(){
	/*菜单 start*/
	/*是否存在当前菜单*/
	if($('#nav .on').length > 0){	
	}else{
		$('#nav li').eq(0).addClass('on');
	}
	$('#nav').find('.on').parents('li').addClass('on');
	$('#nav li ul').hide();
	$('#nav .on ul').show();
	$('#nav li').click(function(){
		if($(this).find('ul').is(':visible')){
			$(this).find('ul').slideUp();
		}else{
			$(this).find('ul').slideDown();
		}
	})
	/*菜单 end*/
	/*响应式布局*/
	function winbox_width(){  
	  var win_width = $(window).width();
	  var win_Height = window.screen.height;/*$(window).height();*/
	  var docH = $(document).height();
	  /*主体布局适配*/
	  $('body').css({'minHeight':win_Height - 200});
	  $("#header .lef").css({'width':240});
	  $("#header .rig").css({'width':win_width - $("#header .lef").outerWidth()});
	   $("#header .lef").css({'width':'240px'});
	  $("#content").css({
		  'minHeight':win_Height - $("#header").outerHeight() - 220,
		  'width':win_width - $("#nav").outerWidth() - 40
	  });
	  $("#nav").css({
		  'minHeight':docH - $("#header").outerHeight() - 200,
	  	  'width':240
	  });
	  /*屏宽适配*/
	  if(win_width < 600){
	  	$("body").addClass("wap").removeClass("pad");
	  }else if(win_width >= 600 && win_width <= 1000){
		$("body").addClass("pad").removeClass("wap");
	  }else{
		$("body").removeClass("wap,pad");
	  };
	};
	winbox_width();	
	winbox_width();	
	$(window).resize(function(){
		winbox_width();	
	});		
	$(window).scroll(function() {
  		winbox_width();
	});
});