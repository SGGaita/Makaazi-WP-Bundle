/* Makaazi admin: tabs, gallery, location pin, attributes, featured star, copy buttons. */
(function ($) {
	'use strict';
	var A = window.MAKAAZI_ADMIN || { i18n: {} };

	/* Tabs */
	$(document).on('click', '[data-mk-tabs] .mk-tabs__tab', function () {
		var $t = $(this), box = $t.closest('[data-mk-tabs]'), k = $t.data('tab');
		box.find('.mk-tabs__tab').removeClass('is-on').attr('aria-selected', 'false');
		$t.addClass('is-on').attr('aria-selected', 'true');
		box.find('.mk-pane').attr('hidden', true).removeClass('is-on');
		box.find('.mk-pane[data-pane="' + k + '"]').removeAttr('hidden').addClass('is-on');
		if (k === 'location') initPin();
		try { sessionStorage.setItem('mk_tab', k); } catch (e) { /* ignore */ }
	});
	$(function () {
		var k = null; try { k = sessionStorage.getItem('mk_tab'); } catch (e) { /* ignore */ }
		if (k) $('[data-mk-tabs] .mk-tabs__tab[data-tab="' + k + '"]').trigger('click');
	});

	/* Price preview + rent default period */
	function fmt(n) { return Number(n).toLocaleString('en-US'); }
	function previewPrice() {
		var p = $('[data-mk-price]').val(), per = $('[data-mk-period]').val(), por = $('input[name="mk[price_on_request]"]').is(':checked');
		var suffix = { total: '', month: ' /month', year: ' /year', night: ' /night' }[per] || '';
		$('[data-mk-price-preview]').text(por || !p ? 'Price on request' : 'KES ' + fmt(p) + suffix);
	}
	$(document).on('input change', '[data-mk-price], [data-mk-period], input[name="mk[price_on_request]"]', previewPrice);
	$(document).on('change', '#taxonomy-property_status input, #property_statusdiv input', function () {
		var label = $.trim($(this).parent().text()).toLowerCase();
		if (this.checked && label.indexOf('rent') > -1 && $('[data-mk-period]').val() === 'total') { $('[data-mk-period]').val('month'); previewPrice(); }
	});

	/* Gallery */
	function syncGallery() {
		var ids = $('[data-mk-gallery] li').map(function () { return $(this).data('id'); }).get();
		$('[data-mk-gallery-input]').val(ids.join(','));
		$('[data-mk-gallery] li').each(function (i) { $(this).find('.mk-gal__n').text(i === 0 ? A.i18n.cover : i + 1); });
	}
	$(function () {
		if ($.fn.sortable) $('[data-mk-gallery]').sortable({ update: syncGallery, tolerance: 'pointer' });
	});
	$(document).on('click', '[data-mk-gallery] .mk-gal__x', function () { $(this).closest('li').remove(); syncGallery(); });
	var frame;
	$(document).on('click', '[data-mk-gallery-add]', function (e) {
		e.preventDefault();
		if (!frame) {
			frame = wp.media({ title: A.i18n.choose, button: { text: A.i18n.use }, library: { type: 'image' }, multiple: 'add' });
			frame.on('select', function () {
				var have = $('[data-mk-gallery-input]').val().split(',');
				frame.state().get('selection').each(function (att) {
					var a = att.toJSON();
					if (have.indexOf(String(a.id)) > -1) return;
					var src = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
					$('[data-mk-gallery]').append('<li data-id="' + a.id + '"><img src="' + src + '" alt=""><span class="mk-gal__n"></span><button type="button" class="mk-gal__x" aria-label="' + A.i18n.remove + '">&times;</button></li>');
				});
				syncGallery();
			});
		}
		frame.open();
	});

	/* Location image (terms) */
	$(document).on('click', '[data-mk-image-pick] [data-pick]', function (e) {
		e.preventDefault();
		var box = $(this).closest('[data-mk-image-pick]');
		var f = wp.media({ title: A.i18n.choose, multiple: false, library: { type: 'image' } });
		f.on('select', function () {
			var a = f.state().get('selection').first().toJSON();
			box.find('input').val(a.id);
			box.find('.mk-term-image__preview').html('<img src="' + (a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url) + '" alt="" width="80">');
		});
		f.open();
	});
	$(document).on('click', '[data-mk-image-pick] [data-clear]', function (e) {
		e.preventDefault();
		var box = $(this).closest('[data-mk-image-pick]');
		box.find('input').val(''); box.find('.mk-term-image__preview').empty();
	});

	/* Location pin */
	var pinMap, pin;
	function setLatLng(ll) {
		$('[data-mk-lat]').val(ll.lat.toFixed(6));
		$('[data-mk-lng]').val(ll.lng.toFixed(6));
	}
	function initPin() {
		var el = document.querySelector('[data-mk-pin]');
		if (!el || !window.L) return;
		if (pinMap) { setTimeout(function () { pinMap.invalidateSize(); }, 50); return; }
		var lat = parseFloat($('[data-mk-lat]').val()), lng = parseFloat($('[data-mk-lng]').val());
		var has = !isNaN(lat) && !isNaN(lng);
		var c = has ? [lat, lng] : (A.center && A.center.length === 2 ? A.center : [-1.2864, 36.8172]);
		pinMap = L.map(el).setView(c, has ? 15 : 12);
		L.tileLayer(A.tiles, { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(pinMap);
		pin = L.marker(c, { draggable: true }).addTo(pinMap);
		if (!has) pin.setOpacity(0.5);
		pin.on('dragend', function () { pin.setOpacity(1); setLatLng(pin.getLatLng()); });
		pinMap.on('click', function (e) { pin.setLatLng(e.latlng).setOpacity(1); setLatLng(e.latlng); });
		$('[data-mk-lat], [data-mk-lng]').on('change', function () {
			var a = parseFloat($('[data-mk-lat]').val()), b = parseFloat($('[data-mk-lng]').val());
			if (!isNaN(a) && !isNaN(b)) { pin.setLatLng([a, b]).setOpacity(1); pinMap.setView([a, b]); }
		});
		setTimeout(function () { pinMap.invalidateSize(); }, 50);
	}
	$(document).on('click', '[data-mk-geo-go]', function () {
		var q = $('[data-mk-geo]').val(); if (!q || !pinMap) return;
		var btn = $(this).prop('disabled', true);
		fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=ke&q=' + encodeURIComponent(q))
			.then(function (r) { return r.json(); })
			.then(function (d) {
				btn.prop('disabled', false);
				if (!d || !d[0]) { window.alert(A.i18n.notfound); return; }
				var ll = L.latLng(parseFloat(d[0].lat), parseFloat(d[0].lon));
				pin.setLatLng(ll).setOpacity(1); pinMap.setView(ll, 16); setLatLng(ll);
			})
			.catch(function () { btn.prop('disabled', false); window.alert(A.i18n.notfound); });
	});
	$(document).on('keydown', '[data-mk-geo]', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('[data-mk-geo-go]').trigger('click'); } });

	/* Attributes */
	$(document).on('click', '[data-mk-attr-add]', function () {
		var row = $('[data-mk-attrs] .mk-attr').first().clone();
		row.find('input').val('');
		$('[data-mk-attrs]').append(row);
		row.find('input').first().trigger('focus');
	});
	$(document).on('click', '.mk-attr__x', function () {
		var rows = $('[data-mk-attrs] .mk-attr');
		if (rows.length > 1) $(this).closest('.mk-attr').remove(); else rows.find('input').val('');
	});

	/* Featured star on the list screen */
	$(document).on('click', '.mk-star', function () {
		var b = $(this);
		$.post(A.ajax, { action: 'makaazi_feature', id: b.data('id'), nonce: A.nonce }, function (r) {
			if (!r || !r.success) return;
			var on = !!r.data.featured;
			b.toggleClass('is-on', on).attr('aria-pressed', on ? 'true' : 'false');
			b.find('.dashicons').toggleClass('dashicons-star-filled', on).toggleClass('dashicons-star-empty', !on);
		});
	});

	/* Reject with a reason (pending properties) */
	$(document).on('click', 'a.mk-reject', function (e) {
		e.preventDefault();
		var reason = window.prompt($(this).data('prompt'), '');
		if (reason === null) return;
		window.location.href = this.href + '&reason=' + encodeURIComponent(reason);
	});

	/* Copy buttons */
	$(document).on('click', '[data-mk-copy]', function () {
		var b = $(this), t = b.data('mk-copy'), label = b.text();
		var done = function () { b.text(A.i18n.copied); setTimeout(function () { b.text(label); }, 1200); };
		if (navigator.clipboard) navigator.clipboard.writeText(t).then(done, function () { window.prompt('', t); });
		else window.prompt('', t);
	});
}(jQuery));
