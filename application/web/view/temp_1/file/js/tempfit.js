$(document).ready(function(){
	/*装修*/
	
	/**
	*【定义拖曳方法】
	* 参数一：拖动舞台
	* 参数二：拖动元素
	* 参数三：拖动按钮
	* 参数四：拖动时透明度		
	**/
	function moveElement(moveStage,moveDiv,moveBtn,opacity){
		//定义鼠标距离左边及顶部的距离
		var mousex = 0,mousey = 0;
		//定义元素距离左边及顶部的距离
		var divLeft,divTop;
		//鼠标按下拖动按钮
		moveBtn.mousedown(function(e){
			var divOffset = moveDiv.offset();
			var stageOffset = moveStage.offset();
			divLeft = divOffset.left;
			divTop = divOffset.top;
			stageLeft = stageOffset.left;
			stageTop = stageOffset.top;		
			mousex = e.pageX;
			mousey = e.pageY;
			//绑定跟随鼠标移动事件
			$('body').bind('mousemove',moveDragElement);
		});
		//鼠标移动时的方法
		function moveDragElement(event){
			var left = divLeft - stageLeft + (event.pageX - mousex);
			var top = divTop - stageTop + (event.pageY - mousey);
			//样式变换
			moveDiv.css({
				'top' :  top + 'px',
				'left' : left + 'px',
				'position' : 'absolute',
				'opacity' : opacity
			});
			return false;
		}
		//鼠标抬起
		$(document).mouseup(function(){
			moveDiv.css('opacity',1);
			//解绑跟随鼠标移动事件
			$('body').unbind('mousemove');
		});
	}
	
	/**
	*【定义拉伸方法】
	* 参数一：拉伸元素
	* 参数二：拉伸按钮
	* 参数三：拉伸时透明度		
	**/
	function resizeElement(resizeDiv,resizeBtn,opacity){
		resizeBtn = resizeDiv.find(resizeBtn);
		//定义鼠标距离左边及顶部的距离
		var mousex = 0,mousey = 0;	
		var divWidth = 	resizeDiv.width();	
		var divHeight = resizeDiv.height();		
		//鼠标按下拖动按钮
		resizeBtn.mousedown(function(e){
			divWidth = 	resizeDiv.width();	
			divHeight = resizeDiv.height();			
			mousex = e.pageX;
			mousey = e.pageY;
			//绑定跟随鼠标移动事件
			$('body').bind('mousemove',resizeElementFun);
		});
		//鼠标移动时的方法
		function resizeElementFun(event){
			/*改变元素大小*/
			var width = divWidth + (event.pageX - mousex);
			var height = divHeight + (event.pageY - mousey);
			resizeDiv.css({
				'width' : width + 'px',
				'height' : height + 'px',
				'opacity' : opacity
			});
			/*改变拉伸框大小*/
			resizeDiv.find('.move').height(resizeDiv.outerHeight());			
			return false;
		}
		//鼠标抬起
		$(document).mouseup(function(){
			resizeDiv.css('opacity',1);
			//解绑跟随鼠标移动事件
			$('body').unbind('mousemove');
		});
	}
	
	/**
	*【定义拖曳克隆方法】
	* 参数一：克隆元素
	* 参数二：克隆按钮
	* 参数三：克隆舞台		
	**/
	function moveClone(){
		/*被克隆模块ID*/
		var site_id = $(".main").attr('site_id');/*站点ID*/
		var temp_id = $(".main").attr('temp_id');/*模板ID*/
		var page_channel = $(".pagechange").val();/*当前页面*/
		var block_id;	/*模块ID*/
		/*定义鼠标距离左边及顶部的距离*/
		var mousex = 0,mousey = 0,cloneStauts = 0;
		/*定义元素距离左边及顶部的距离和工具箱高度*/
		var objLeft,objTop,fitHeight,mainLeft,mailTop;
		$(".fitclone li").mousedown(function(e){		
			/*获取模块ID*/
			block_id = $(this).attr('id');
			/*拖曳元素清理和克隆*/
			$('.temporaryDom').remove();
			$(this).parent().append($(this).clone().addClass('temporaryDom'));
			/*设定计算位置所需要参数*/
			var mainOffset = $(".main").offset();
			mainLeft = mainOffset.left;
			mailTop = mainOffset.top;
			fitHeight = $(".fithead").outerHeight();/*工具栏占用高度*/
			mousex = e.pageX;/*鼠标-距离页面-左侧距离*/
			mousey = e.pageY;/*鼠标-距离页面-顶部距离*/
			objWidth = $(this).outerWidth();/*被拖曳块-宽度*/
			objHeight = $(this).outerHeight();/*被拖曳块-高度*/
			/*定位克隆体初始位置*/
			$('.temporaryDom').css({
				'position' : 'absolute',
				'left' : mousex - (objWidth/2) + 'px',
				'top' : mousey - (objHeight/2) - $(".fitnav").outerHeight() + 'px',
				'z-index' : 999999			
			});
			objLeft = parseFloat($('.temporaryDom').css('left'));
			objTop = parseFloat($('.temporaryDom').css('top'));
			$('body').bind('mousemove',moveCloneElement);
		})
		/*鼠标移动时的方法*/
		function moveCloneElement(event){
			var left = objLeft + (event.pageX - mousex);
			var top = objTop + (event.pageY - mousey);
			$('.temporaryDom').css({
				'top' :  top + 'px',
				'left' : left + 'px',
				'position' : 'absolute',
				'opacity' : 0.5
			});
			/*经过目标区域*/
			if(fitHeight > event.pageY){
				$('.temporaryDom').removeClass('temporaryDomHover');
			}else{
				$('.temporaryDom').addClass('temporaryDomHover');
			}
			/*创建授权*/
			cloneStauts = 1;		
		}
		/*鼠标抬起*/
		$(document).mouseup(function(event){
			if(cloneStauts == 1){
				/*经过目标区域*/
				if(fitHeight > event.pageY){
					alertBox('请拖曳至您的页面区域创建或编辑该模块..');
				}else{
					if(block_id > 0){
						var style = 'left:'+(event.pageX-mainLeft)+';top:'+(event.pageY-mailTop)+';';
						tempfitBlockClone(site_id,temp_id,page_channel,block_id,style);
					}
				}
				$('.temporaryDom').remove();
				/*解绑跟随鼠标移动事件*/
				$('body').unbind('mousemove');
				/*取消授权*/
				cloneStauts = 0;				
			}
		});
		return false;
	}
	moveClone();
		
	
	/*自适应页面高度*/
	var pageHeight = 0;/*定义页面高度*/
	/*查询模块占用最高*/
	$('.block').each(function(i){
		var objScrollTop = $(this).offset().top + $(this).outerHeight();
        if(objScrollTop > pageHeight){
			pageHeight = objScrollTop;
		}
    });
	/*获取屏幕高度*/
	if (window.innerHeight){
		var winH = window.innerHeight;
	}else if ((document.body) && (document.body.clientHeight)){
		var winH = document.body.clientHeight;	
	}
	/*内容高度低于屏幕高度时，页面高度使用屏幕高度*/
	if(pageHeight < winH){
		pageHeight = winH;
	}
	$('.main').css('minHeight',pageHeight);
	/*编辑模块*/
	$('.block').hover(function(){
		var temp_id = $(".main").attr('temp_id');/*模板ID*/
		var block_type = $(this).attr('type');
		var block_id = $(this).attr('id');
		var edit_html = '<div class="edit">';
		edit_html += '<a class="set" href="/index.php/web/website.cms/blocksave?id='+block_id+'&tid='+temp_id+'&sou=fit"><img src="/public/static/html/image/ico_white/cog.png"><span>样式</span></a>';
		edit_html += '<a class="compile" href="/index.php/web/website.cms/blockedit?id='+block_id+'&tid='+temp_id+'&sou=fit"><img src="/public/static/html/image/ico_white/pen_3.png"><span>内容</span></a>';
		edit_html += '<a class="save" href="javascript:;"><img src="/public/static/html/image/ico_white/polaroids.png"><span>保存</span></a>';
		edit_html += '<a class="del" href="javascript:;"><img src="/public/static/html/image/ico_white/circle_delete.png"><span>删除</span></a>';
		edit_html += '<span class="move" href="/medical.cms/blockedit?id='+block_id+'"><span class="line"></span><i class="toplef"></i><i class="topmid"></i><i class="toprig"></i><i class="midrig"></i><i class="botrig"></i><i class="botmid"></i><i class="botlef"></i><i class="midlef"></i></span>';
		edit_html += '</div>';
		$(this).append(edit_html);
		$(this).addClass('hovering');
		$(this).find('.move').height($(this).outerHeight());	
		/*模块删除*/
		$('.edit .del').click(function(){
			if(block_type != -1){
				alertBox('删除后不可恢复，确认删除该模块？','/medical.cms/blockdel?id='+block_id,'温馨提示：','true');
			}else{
				alertBox('此为系统频道模块，不允许删除。');
			}
		})		
		/*保存模块*/
		$('.edit .save').click(function(){
			var thisBlock = $(this).parents('.block');
			tempfitBlockSave(block_id,thisBlock.css('top'),thisBlock.css('left'),thisBlock.css('width'),thisBlock.css('height'));
		})
		//功能面板拖曳		
		moveElement($('.main'),$(this),$(this).find('.line'),0.5);
		//功能面板拉伸
		resizeElement($(this),$(this).find('i'),0.8);	
	},function(){
		$(this).find('.edit').remove();
		$(this).removeClass('hovering');
	});	
	
	/*切换频道页面*/
	$('.pagechange').change(function(){
		var temp_id = $(this).attr('temp_id');
		var page_id = $(this).val();
		var btype = $(this).attr('btype');
		var page_href = '?id='+temp_id+'&page_id='+page_id+'&btype='+btype;
		location = page_href;
	})

	/*手机装修*/
	$('.phone_box').parents('body').css('backgroundColor','#999');
});