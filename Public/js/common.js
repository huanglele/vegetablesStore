$(function() {	$(".nav li").each(function(i) {		$(this).hover(function() {				$(".subNav", $(this)).show();				var $subNav = $(".subNav", $(this))			},			function() {				$(".subNav", $(this)).hide()			})	});	$(".security_tab li").hover(function() {		var i = $(this).index();		$(this).addClass("on").siblings().removeClass("on");		$(".security_cont").eq(i).show().siblings(".security_cont").hide()	});	$(".menuList li .menuName").click(function() {		var parent = $(this).parents("li");		parent.addClass("curr").siblings("li").removeClass("curr");		$($(".menuName"), parent).removeClass("on");		$(this).addClass("on")	});	$(".siderbar-wx").hover(function() {			$(".thick_wx").show()		},		function() {			$(".thick_wx").hide()		});	$(".siderbar-search").hover(function() {			$(".thick_search").show()		},		function() {			$(".thick_search").hide()		});	$(".addNumBox .add").click(function() {		var $parent = $(this).parent(".addNumBox");		var $num = window.Number($(".inputBorder", $parent).val());		$(".inputBorder", $parent).val($num + 1)	});	$(".addNumBox .minus").click(function() {		var $parent = $(this).parent(".addNumBox");		var $num = window.Number($(".inputBorder", $parent).val());		if ($num > 2) {			$(".inputBorder", $parent).val($num - 1)		} else {			$(".inputBorder", $parent).val(1)		}	});	$(".siteSwitch").hover(function() {			$(".siteSwitch dd").show()		},		function() {			$(".siteSwitch dd").hide()		})})