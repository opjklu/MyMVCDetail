/**
 * 
 */
var app = {
	set_goarea : function() {
		$('.float_block').css({
			top : 116,
			position : 'fixed'
		});
		var setright = function() {
			var rightnum = $(document).width() - 1680;
			// console.log(rightnum);
			if (rightnum > 0) {
				rightnum = rightnum / 2 + 30;
				$('.float_block').css({
					right : rightnum
				});
			} else {
				$('.float_block').css({
					right : 30
				});
			}
		}
		setright();
		$(window).resize(setright);
	},
	goarea : function() {
		var tmp = 0;
		$('li[data-toggle="goarea"]')
				.on(
						'click',
						function() {
							var $this = $(this);
							var area = $this.attr('data-area') || '#top';
							tmp = $(area).offset().top - 60 >= 0 ? $(area)
									.offset().top - 60 : 0;
							$("html,body").animate({
								scrollTop : tmp
							}, 300);
						});

		var areastr = '', areas = [], $goarea = $('li[data-toggle="goarea"]');
		if ($goarea.length) {
			$(document).scroll(
					function() {
						tmp = $goarea.offset().top - 60;
						/* 如果区域组还不存在，则获取区域组 */
						if (areastr == '') {
							$('li[data-toggle="goarea"]').each(
									function(i) {
										areastr += ','
												+ $goarea.eq(i).attr(
														'data-area');
									});
							areas = areastr.split(",");
						}
						/* 循环区域组，查看当前所属组。找到则跳出循环 */
						for (var i = areas.length - 1; i >= 1; i--) {
							$(document).scrollTop() < 56
									&& $('li[data-toggle="goarea"]')
											.removeClass('active');
							if ($(document).scrollTop() + 61 >= $(areas[i])
									.offset().top) {
								$('li[data-toggle="goarea"]').removeClass(
										'active');
								if (tmp < 9750) {
									$('li[data-area="' + areas[i] + '"]')
											.addClass('active');
								}
								break;
							}
						}
						;
						if (tmp > 9750) {
							$('li[data-area="#wap"]').addClass('active');
						}
					})
		}
	}
}