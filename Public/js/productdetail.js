$(function() {
	

//			var h=$(".proTabBox").offset().top;
//			alert(h);

	   $(window).scroll(function() {
			var top = $(window).scrollTop();
			//alert(getPosition(JtabMod).top);
			var h=$(".proTabBox").offset().top;
			if (top > 699) {
				//alert(111);
				//$("#a").css({position:'fixed',top:'0'});
				$(".proTabBox").hide();
				$(".proTabBox2").show();
			} else {
				/*$("#J_tab_mod").removeAttr('style');*/
				//$("#a").css({position:'relative',top:'0'});
				$(".proTabBox").show();
				$(".proTabBox2").hide();	
			}
		});



    $(".proTabBox li,.proTabBox2 li").click(function() {
        var i = $(this).index();
		$(".proTabBox li").eq(i).addClass("on").siblings().removeClass("on");
		$(".proTabBox2 li").eq(i).addClass("on").siblings().removeClass("on");
        $(".productCont").eq(i).show().siblings(".productCont").hide();
    });




	
    $(".pro_specification li").click(function() {
        $(this).addClass("on").siblings().removeClass("on");
    });
	

	
	$("#store-selector .text").hover(function(){
		var p=$(this).parents("#store-selector");
		p.addClass("hover");
		$(".content",p).show();
		$(".close",p).show();
	});
	
	
	$("#store-selector .close").click(function(){
		// alert('111');
		var p=$(this).parents("#store-selector");
		p.removeClass("hover");
		$(".content",p).hide();
		$(".close",p).hide();
	});
	
/*
    $(".JD-stock .mt .tab li").click(function() {
        var i = $(this).index();
        $(this).addClass("curr").siblings().removeClass("curr");
        $("a",this).addClass("hover");
        $("a",$(this).siblings()).removeClass("hover");		
        $(".JD-stock .mc").eq(i).show().siblings(".mc").hide();
    });*/

    // $(".JD-stock .mt .tab li").mouseover(function() {

	
   $(".notice").click(function() {
        $(".noticeTip").show();
    });
    $(".noticeTip .tit a").click(function() {
        $(".noticeTip").hide();
    });
	
	

	
})

//�����Ʒ֪ͨemar
function viewProductNotifyEmar(){
	/*
	_adwq.push(['_setDataType', 'view']); 

	//�û�ID
	_adwq.push(['_setCustomer', '1234567']); 

	// �����������Ʒ����룬���ݶ����а�����������Ʒ������ÿ����Ʒ����һ�� 
	_adwq.push(['_setItem', 
	'09890', // 09890��һ�����ӣ���������Ʒ��� - ������ 
	'����', // ������һ�����ӣ���������Ʒ���� - ������ 
	'12.00', // 12.00��һ�����ӣ���������Ʒ�ּ� - ������ 
	'1', // 1��һ�����ӣ���������Ʒ���� - ������ 
	'A123', // A123��һ�����ӣ���������Ʒ������ - ������ 
	'�ҵ�', // �ҵ���һ�����ӣ���������Ʒ�������� - ������ 
	'10.00', // 10.00��һ�����ӣ���������Ʒԭ�� - ������ 
	'http://www.test.com/a.gif', // �������زĵ�ַ 
	'Y' // ��������Ʒ״̬��Y������Ч��N������Ч 
	]); 

	// �������ύ���룬�˶δ������������ϴ������ - ������ 
	_adwq.push([ '_trackTrans' ]); 
*/

}