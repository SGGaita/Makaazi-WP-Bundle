/* Makaazi Hero Slider admin */
(function ($) {
	'use strict';
	var A = window.MKS_ADMIN || {};
	var seq = Date.now();
	var $list = $('[data-mks-slides]');

	function refreshTitles() {
		$list.find('[data-mks-slide]').each(function (i) {
			var t = $(this).find('input[name$="[title]"]').val();
			$(this).find('.mks-slide__title').text(t || (A.slide + ' ' + (i + 1)));
		});
	}
	if ($.fn.sortable) $list.sortable({ handle: '.mks-slide__handle', tolerance: 'pointer', update: refreshTitles });

	$(document).on('click', '[data-mks-add]', function () {
		var html = $('#mks-slide-tpl').html().replace(/__i__/g, 'n' + (seq++));
		var $s = $(html).addClass('is-open');
		$list.append($s); refreshTitles();
		$s.find('input[name$="[title]"]').trigger('focus');
	});
	$(document).on('click', '[data-mks-toggle]', function () { $(this).closest('[data-mks-slide]').toggleClass('is-open'); });
	$(document).on('click', '[data-mks-del]', function () {
		var $s = $(this).closest('[data-mks-slide]');
		if ($s.hasClass('is-confirm')) { $s.remove(); refreshTitles(); return; }
		$s.addClass('is-confirm'); $(this).text(A.confirm || 'Click again to delete');
	});
	$(document).on('click', '[data-mks-dup]', function () {
		var $s = $(this).closest('[data-mks-slide]'), key = 'n' + (seq++);
		var $c = $s.clone();
		$c.find('[name]').each(function () { this.name = this.name.replace(/mks_slides\[[^\]]+\]/, 'mks_slides[' + key + ']'); });
		$s.find('select').each(function (i) { $c.find('select').eq(i).val($(this).val()); });
		$c.insertAfter($s).addClass('is-open'); refreshTitles();
	});
	$(document).on('input', '[data-mks-slide] input[name$="[title]"]', refreshTitles);

	$(document).on('click', '[data-mks-img] [data-pick]', function (e) {
		e.preventDefault();
		var box = $(this).closest('[data-mks-img]');
		var f = wp.media({ title: A.choose, multiple: false, library: { type: 'image' } });
		f.on('select', function () {
			var a = f.state().get('selection').first().toJSON();
			box.find('input').val(a.id);
			box.find('.mks-img__preview').html('<img src="' + (a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url) + '" alt="">');
		});
		f.open();
	});
	$(document).on('click', '[data-mks-img] [data-clear]', function (e) {
		e.preventDefault();
		var box = $(this).closest('[data-mks-img]');
		box.find('input').val('0'); box.find('.mks-img__preview').empty();
	});

	function syncFields() {
		var v = $('[data-mks-fields] li').filter(function () { return $(this).find('input').is(':checked'); }).map(function () { return $(this).data('k'); }).get();
		$('[data-mks-fields-input]').val(v.join(','));
	}
	if ($.fn.sortable) $('[data-mks-fields]').sortable({ update: syncFields });
	$(document).on('change', '[data-mks-fields] input', syncFields);

	$(document).on('click', '[data-mks-copy]', function () {
		var b = $(this), t = b.data('mks-copy'), label = b.text();
		if (navigator.clipboard) navigator.clipboard.writeText(t).then(function () { b.text(A.copied); setTimeout(function () { b.text(label); }, 1200); });
	});
}(jQuery));
