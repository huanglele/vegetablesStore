function readytoadd(itemimage, alias, itemprice, skuid) {    var quantity = $("#tonysfarm_product" + skuid).val();    $("#cart_tip_img").html("<img src='" + itemimage + "'>");    $("#cart_tip_name").html(alias + "<br><span>+ " + quantity + " 加入成功</span>");    $("#cart_tip_price").html("¥" + itemprice);    if (!$(".pop_cart").is(":animated")) {        $(".pop_cart").animate({right: "56px", top: "260px"}, 220).show(function () {            setTimeout(function () {                $(".pop_cart").hide();            }, 2000);        });    }}//添加到购物车function addCart(itemId,num){    $.ajax({        'url':base_path + '/index/addCart',    })}