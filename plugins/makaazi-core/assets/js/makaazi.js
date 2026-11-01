/* Makaazi Core front-end. No dependencies (Leaflet only on map pages). */
(function () {
	'use strict';
	var CFG = window.MAKAAZI || { rest: '/wp-json/makaazi/v1', i18n: {} };
	var T = CFG.i18n || {};
	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function $(sel, root) { return (root || document).querySelector(sel); }
	function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
	function on(el, ev, fn, opt) { if (el) el.addEventListener(ev, fn, opt || false); }
	function restUrl(path, params) {
		var base = CFG.rest, q = params ? new URLSearchParams(params).toString() : '';
		if (base.indexOf('rest_route=') > -1) return base + path + (q ? '&' + q : '');
		return base.replace(/\/$/, '') + path + (q ? '?' + q : '');
	}
	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	var toastTimer;
	function toast(msg, action, fn) {
		var t = $('.mk-toast');
		if (!t) { t = document.createElement('div'); t.className = 'mk-toast'; t.setAttribute('role', 'status'); document.body.appendChild(t); }
		t.textContent = msg; t.hidden = false;
		if (action) {
			var b = document.createElement(typeof fn === 'string' ? 'a' : 'button');
			b.className = 'mk-toast__act'; b.textContent = action;
			if (typeof fn === 'string') b.href = fn; else { b.type = 'button'; b.addEventListener('click', function () { t.hidden = true; fn(); }); }
			t.appendChild(b);
		}
		clearTimeout(toastTimer); toastTimer = setTimeout(function () { t.hidden = true; }, action ? 5000 : 2800);
	}
	function updated() { try { document.dispatchEvent(new CustomEvent('mk:updated')); } catch (e) { /* old browsers */ } }

	/* ---------- Photo strips (cards + mobile gallery) ---------- */
	function initStrips(root) {
		$$('[data-mk-strip]', root).forEach(function (strip) {
			if (strip._mk) return; strip._mk = 1;
			var box = strip.parentElement;
			var update = function () {
				var i = Math.round(strip.scrollLeft / Math.max(1, strip.clientWidth));
				$$('.mk-strip__dots i', box).forEach(function (d, j) { d.classList.toggle('is-on', j === i); });
				var c = $('.mk-gstrip__count', box); if (c) c.textContent = (i + 1) + ' / ' + strip.children.length;
				$$('.mk-strip__slide', strip).forEach(function (s, j) { if (s.tagName === 'A') s.setAttribute('aria-hidden', j === i ? 'false' : 'true'); });
			};
			on(strip, 'scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
		});
	}
	on(document, 'click', function (e) {
		var b = e.target.closest('[data-mk-strip-nav]');
		if (!b) return;
		e.preventDefault();
		var strip = $('[data-mk-strip]', b.closest('.mk-card__media'));
		var n = strip.children.length, w = strip.clientWidth;
		var i = Math.round(strip.scrollLeft / w) + parseInt(b.getAttribute('data-mk-strip-nav'), 10);
		i = (i + n) % n;
		strip.scrollTo({ left: i * w, behavior: reduced ? 'auto' : 'smooth' });
	});

	/* ---------- Carousels ---------- */
	on(document, 'click', function (e) {
		var b = e.target.closest('[data-mk-car]');
		if (!b) return;
		var track = $('.mk-carousel__track', b.closest('[data-mk-carousel]'));
		var dir = parseInt(b.getAttribute('data-mk-car'), 10);
		var max = track.scrollWidth - track.clientWidth;
		var target = track.scrollLeft + dir * track.clientWidth * 0.9;
		if (dir > 0 && track.scrollLeft >= max - 4) target = 0;
		else if (dir < 0 && track.scrollLeft <= 4) target = max;
		track.scrollTo({ left: target, behavior: reduced ? 'auto' : 'smooth' });
	});

	/* ---------- Show more ---------- */
	on(document, 'click', function (e) {
		var b = e.target.closest('[data-mk-more-toggle]');
		if (!b) return;
		var box = b.closest('[data-mk-more]');
		var open = box.classList.toggle('is-open');
		if (!b._label) b._label = b.textContent;
		b.textContent = open ? b.getAttribute('data-less') : b._label;
		b.setAttribute('aria-expanded', open ? 'true' : 'false');
	});

	/* ---------- Unit toggle ---------- */
	function fmtArea(sqm, unit) {
		var v = unit === 'sqft' ? Math.round(sqm * 10.7639) : Math.round(sqm);
		return v.toLocaleString('en-US') + (unit === 'sqft' ? ' ft²' : ' m²');
	}
	function applyUnit(unit) {
		CFG.unit = unit;
		$$('.mk-area[data-sqm]').forEach(function (el) { el.textContent = fmtArea(parseFloat(el.getAttribute('data-sqm')), unit); });
		$$('.mk-unit [data-u]').forEach(function (s) { s.classList.toggle('is-on', s.getAttribute('data-u') === unit); });
	}
	on(document, 'click', function (e) {
		var b = e.target.closest('[data-mk-unit-toggle]');
		if (!b) return;
		var unit = CFG.unit === 'sqft' ? 'sqm' : 'sqft';
		document.cookie = 'mk_unit=' + unit + ';path=/;max-age=31536000;SameSite=Lax';
		try { localStorage.setItem('mk_unit', unit); } catch (err) { /* ignore */ }
		applyUnit(unit);
	});

	/* ---------- Search form: Buy/Rent swaps price brackets ---------- */
	function syncSearch(form) {
		var st = (form.querySelector('input[name=status]:checked') || form.querySelector('input[name=status]') || {}).value || 'sale';
		$$('[data-mk-price]', form).forEach(function (box) {
			var off = box.getAttribute('data-mk-price') !== st;
			box.hidden = off;
			$$('select', box).forEach(function (s) { s.disabled = off; if (off) s.value = ''; });
		});
	}
	$$('[data-mk-search]').forEach(syncSearch);
	on(document, 'change', function (e) {
		var f = e.target.closest('[data-mk-search]');
		if (f && e.target.name === 'status') syncSearch(f);
	});
	on(document, 'submit', function (e) {
		var f = e.target.closest('[data-mk-search]');
		if (!f) return;
		// Drop empty fields so the results URL stays short and shareable.
		$$('select, input', f).forEach(function (el) { if (!el.value && el.name) el.disabled = true; });
		setTimeout(function () { $$('select, input', f).forEach(function (el) { el.disabled = false; }); syncSearch(f); }, 0);
	});

	/* ---------- Listing (filters, chips, sort, views, pagination) ---------- */
	function serialize(form, extra) {
		var p = {};
		$$('input, select', form).forEach(function (el) {
			if (!el.name || el.disabled) return;
			if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
			if (el.value === '') return;
			var k = el.name.replace(/\[\]$/, '');
			p[k] = p[k] ? p[k] + ',' + el.value : el.value;
		});
		Object.keys(extra || {}).forEach(function (k) { if (extra[k] === '' || extra[k] == null) delete p[k]; else p[k] = extra[k]; });
		return p;
	}
	function initListing(box) {
		if (box._mk) return; box._mk = 1;
		var cfg = box.getAttribute('data-config'), base = box.getAttribute('data-base');
		var timer, ctrl;
		function inner() { return $('.mk-listing__inner', box); }
		function load(params, opts) {
			opts = opts || {};
			var res = $('.mk-results', box); if (res) res.setAttribute('aria-busy', 'true');
			var drawerOpen = !!$('.mk-filters-wrap.is-open', box);
			var active = document.activeElement && box.contains(document.activeElement) ? document.activeElement.id : '';
			var q = Object.assign({}, params, { cfg: cfg, base: base });
			if (ctrl) ctrl.abort();
			ctrl = window.AbortController ? new AbortController() : null;
			fetch(restUrl('/listing', q), { credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined })
				.then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
				.then(function (d) {
					var tmp = document.createElement('div'); tmp.innerHTML = d.html;
					var fresh = tmp.firstElementChild;
					inner().replaceWith(fresh);
					var qs = fresh.getAttribute('data-query');
					var url = base + (qs ? (base.indexOf('?') > -1 ? '&' : '?') + qs : '');
					if (!opts.pop) history.pushState({ mk: 1 }, '', url);
					if (drawerOpen) openDrawer(true);
					if (active && document.getElementById(active)) document.getElementById(active).focus({ preventScroll: true });
					initStrips(box); initMaps(box); applyUnit(CFG.unit); updated();
					if (opts.top) box.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
				})
				.catch(function (err) {
					if (err && err.name === 'AbortError') return;
					var r = $('.mk-results', box); if (r) r.setAttribute('aria-busy', 'false');
					toast(T.error || 'Error');
				});
		}
		function fromForm(extra) {
			var form = $('[data-mk-filters]', box);
			var sort = $('[data-mk-sort]', box);
			var p = form ? serialize(form) : {};
			if (sort && sort.value) p.sort = sort.value;
			var view = new URLSearchParams(location.search).get('view');
			if (view && !p.view) p.view = view;
			return Object.assign(p, extra || {});
		}
		on(box, 'change', function (e) {
			if (!e.target.closest('[data-mk-filters]') && !e.target.matches('[data-mk-sort]')) return;
			var form = $('[data-mk-filters]', box);
			if (e.target.name === 'status' && form) $$('select[name=min_price], select[name=max_price]', form).forEach(function (s) { s.value = ''; });
			clearTimeout(timer);
			timer = setTimeout(function () { load(fromForm()); }, 150);
		});
		on(box, 'input', function (e) {
			if (e.target.name !== 'q') return;
			clearTimeout(timer);
			timer = setTimeout(function () { load(fromForm()); }, 350);
		});
		on(box, 'submit', function (e) { if (e.target.matches('[data-mk-filters]')) { e.preventDefault(); load(fromForm()); } });
		on(box, 'click', function (e) {
			var a = e.target.closest('a[data-mk-link]');
			if (!a || e.metaKey || e.ctrlKey || e.shiftKey) return;
			e.preventDefault();
			var u = new URL(a.href, location.href), p = {};
			u.searchParams.forEach(function (v, k) { p[k] = v; });
			load(p, { top: a.hasAttribute('data-mk-top') });
		});
		on(window, 'popstate', function () {
			var p = {}; new URLSearchParams(location.search).forEach(function (v, k) { p[k] = v; });
			load(p, { pop: true });
		});
		// Drawer (phones)
		function openDrawer(keep) {
			var w = $('.mk-filters-wrap', box); if (!w) return;
			w.classList.add('is-open'); document.body.classList.add('mk-lock');
			if (!keep) { var c = $('[data-mk-drawer-close]', w); if (c) c.focus(); }
		}
		function closeDrawer() {
			var w = $('.mk-filters-wrap', box); if (!w) return;
			w.classList.remove('is-open'); document.body.classList.remove('mk-lock');
			var b = $('[data-mk-drawer-open]', box); if (b) b.focus();
		}
		on(box, 'click', function (e) {
			if (e.target.closest('[data-mk-drawer-open]')) openDrawer();
			if (e.target.closest('[data-mk-drawer-close]')) closeDrawer();
		});
		on(document, 'keydown', function (e) { if (e.key === 'Escape' && $('.mk-filters-wrap.is-open', box)) closeDrawer(); });
	}

	/* ---------- Load more ---------- */
	on(document, 'click', function (e) {
		var b = e.target.closest('[data-mk-loadmore]');
		if (!b) return;
		var page = parseInt(b.getAttribute('data-page'), 10) + 1;
		var block = b.closest('.mk-properties');
		var list = $('.mk-grid, .mk-list, .mk-carousel__track', block);
		b.disabled = true; b.textContent = T.loading || '…';
		fetch(restUrl('/cards', { atts: b.getAttribute('data-atts'), page: page }), { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (d) {
				var tmp = document.createElement('div'); tmp.innerHTML = d.html;
				var first = tmp.firstElementChild;
				while (tmp.firstChild) list.appendChild(tmp.firstChild);
				initStrips(list); applyUnit(CFG.unit); updated();
				b.setAttribute('data-page', page); b.disabled = false; b.textContent = T.loadmore || 'Load more';
				if (!d.more) b.parentElement.remove();
				if (first) { var a = $('.mk-card__title a', first); if (a) a.focus({ preventScroll: true }); }
			})
			.catch(function () { b.disabled = false; b.textContent = T.loadmore || 'Load more'; toast(T.error); });
	});

	/* ---------- Lightbox ---------- */
	var LB = null;
	function buildLB() {
		var el = document.createElement('div');
		el.className = 'mk-lb'; el.hidden = true;
		el.setAttribute('role', 'dialog'); el.setAttribute('aria-modal', 'true'); el.setAttribute('aria-label', T.viewer || 'Photo viewer');
		el.innerHTML = '<div class="mk-lb__top"><span class="mk-lb__count" aria-live="polite"></span><span class="sp"></span><span class="mk-lb__title"></span><span class="sp"></span>' +
			'<button type="button" data-lb="zoom" aria-label="' + esc(T.zoom) + '"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4M8 11h6M11 8v6"/></svg></button>' +
			'<button type="button" data-lb="close" aria-label="' + esc(T.close) + '"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>' +
			'<div class="mk-lb__stage"><button type="button" class="mk-lb__nav mk-lb__nav--prev" data-lb="prev" aria-label="' + esc(T.prev) + '"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 6l-6 6 6 6"/></svg></button>' +
			'<img class="mk-lb__img" alt="" data-lb="zoom"><button type="button" class="mk-lb__nav mk-lb__nav--next" data-lb="next" aria-label="' + esc(T.next) + '"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 6l6 6-6 6"/></svg></button></div>' +
			'<div class="mk-lb__cap"></div><div class="mk-lb__thumbs"></div>';
		document.body.appendChild(el);
		var x0 = null;
		on($('.mk-lb__stage', el), 'pointerdown', function (e) { x0 = e.clientX; });
		on($('.mk-lb__stage', el), 'pointerup', function (e) { if (x0 === null) return; var dx = e.clientX - x0; x0 = null; if (Math.abs(dx) > 40) step(dx < 0 ? 1 : -1); });
		on(el, 'click', function (e) {
			var b = e.target.closest('[data-lb]');
			if (!b) { if (e.target === $('.mk-lb__stage', el)) close(); return; }
			var a = b.getAttribute('data-lb');
			if (a === 'close') close(); else if (a === 'prev') step(-1); else if (a === 'next') step(1); else if (a === 'zoom') $('.mk-lb__img', el).classList.toggle('is-zoom');
			else if (a === 'go') show(parseInt(b.getAttribute('data-i'), 10));
		});
		on(el, 'keydown', function (e) {
			if (e.key === 'Escape') { e.preventDefault(); close(); }
			else if (e.key === 'ArrowRight') { e.preventDefault(); step(1); }
			else if (e.key === 'ArrowLeft') { e.preventDefault(); step(-1); }
			else if (e.key === 'Tab') { // keep focus inside
				var f = $$('button', el).filter(function (b) { return b.offsetParent !== null; });
				var i = f.indexOf(document.activeElement);
				if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); }
				else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
			}
		});
		return el;
	}
	var state = { items: [], i: 0, trigger: null };
	function show(i) {
		var n = state.items.length; state.i = (i + n) % n;
		var it = state.items[state.i], img = $('.mk-lb__img', LB);
		img.classList.remove('is-zoom'); img.src = it.src; img.alt = it.alt || '';
		$('.mk-lb__count', LB).textContent = (state.i + 1) + ' / ' + n;
		$('.mk-lb__cap', LB).textContent = (it.caption || '') + (it.caption ? ' · ' : '') + (window.innerWidth > 700 ? (T.browse || '') : '');
		$$('.mk-lb__thumbs button', LB).forEach(function (b, j) { b.classList.toggle('is-on', j === state.i); if (j === state.i) b.scrollIntoView({ block: 'nearest', inline: 'nearest' }); });
		[1, -1].forEach(function (d) { var p = state.items[(state.i + d + n) % n]; if (p) { var im = new Image(); im.src = p.src; } });
	}
	function step(d) { show(state.i + d); }
	function open(items, i, title, trigger) {
		if (!LB) LB = buildLB();
		state = { items: items, i: i, trigger: trigger };
		$('.mk-lb__title', LB).textContent = title || '';
		$('.mk-lb__thumbs', LB).innerHTML = items.map(function (it, j) { return '<button type="button" data-lb="go" data-i="' + j + '" aria-label="' + (j + 1) + '"><img src="' + esc(it.thumb || it.src) + '" alt="" loading="lazy"></button>'; }).join('');
		LB.hidden = false; document.body.classList.add('mk-lock');
		show(i);
		$('[data-lb=close]', LB).focus();
	}
	function close() {
		LB.hidden = true; document.body.classList.remove('mk-lock');
		if (state.trigger) state.trigger.focus();
	}
	on(document, 'click', function (e) {
		var b = e.target.closest('[data-mk-open]');
		if (!b) return;
		var g = b.closest('[data-mk-gallery]');
		if (!g) return;
		e.preventDefault();
		var items = []; try { items = JSON.parse(g.getAttribute('data-mk-gallery')); } catch (err) { return; }
		if (items.length) open(items, parseInt(b.getAttribute('data-mk-open'), 10) || 0, g.getAttribute('data-title'), b);
	});

	/* ---------- Share ---------- */
	on(document, 'click', function (e) {
		var b = e.target.closest('[data-mk-share]');
		if (!b) return;
		var url = b.getAttribute('data-url'), title = b.getAttribute('data-title');
		if (navigator.share) { navigator.share({ title: title, url: url }).catch(function () {}); return; }
		if (navigator.clipboard) navigator.clipboard.writeText(url).then(function () { toast(T.copied); }, function () { window.prompt('', url); });
	});

	/* ---------- Enquiry ---------- */
	on(document, 'submit', function (e) {
		var f = e.target.closest('[data-mk-enquiry]');
		if (!f) return;
		e.preventDefault();
		var status = $('.mk-enquiry__status', f), btn = $('button[type=submit]', f);
		var data = {}; $$('input, textarea', f).forEach(function (el) { if (el.name) data[el.name] = el.value; });
		status.className = 'mk-enquiry__status'; status.textContent = T.sending || '…'; btn.disabled = true;
		fetch(restUrl('/enquiry'), { method: 'POST', credentials: 'omit', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
			.then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
			.then(function (res) {
				btn.disabled = false;
				if (res.ok) { status.className = 'mk-enquiry__status is-ok'; status.textContent = res.d.message; $$('input[type=text], input[type=tel], input[type=email]', f).forEach(function (el) { if (el.name !== 'website') el.value = ''; }); }
				else { status.className = 'mk-enquiry__status is-error'; status.textContent = (res.d && res.d.message) || T.error; }
			})
			.catch(function () { btn.disabled = false; status.className = 'mk-enquiry__status is-error'; status.textContent = T.error; });
	});

	/* ---------- Maps (Leaflet) ---------- */
	function tileLayer(map) {
		window.L.tileLayer(CFG.tiles || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: CFG.attrib || '' }).addTo(map);
	}
	function primary() { return getComputedStyle(document.documentElement).getPropertyValue('--wp--preset--color--primary').trim() || '#13223F'; }
	function initSingleMap(el) {
		var lat = parseFloat(el.getAttribute('data-lat')), lng = parseFloat(el.getAttribute('data-lng')), r = parseInt(el.getAttribute('data-radius'), 10) || 500;
		var map = window.L.map(el, { scrollWheelZoom: false });
		tileLayer(map);
		var c = primary();
		if (el.getAttribute('data-exact') === '1') { window.L.marker([lat, lng]).addTo(map); map.setView([lat, lng], 15); }
		else { map.fitBounds(window.L.latLng(lat, lng).toBounds(r * 2.4)); window.L.circle([lat, lng], { radius: r, color: c, weight: 1.5, dashArray: '4 3', fillColor: c, fillOpacity: 0.16 }).addTo(map); }
	}
	function initResultsMap(el) {
		var pts = []; try { pts = JSON.parse(el.getAttribute('data-mk-map-results')); } catch (err) { return; }
		var map = window.L.map(el, { scrollWheelZoom: false });
		tileLayer(map);
		var layer = window.L.layerGroup().addTo(map);
		function popup(p) { return '<div class="mk-popup">' + (p.img ? '<img src="' + esc(p.img) + '" alt="">' : '') + '<a href="' + esc(p.url) + '">' + esc(p.title) + '</a><span>' + esc(p.price) + '</span></div>'; }
		function draw() { // simple pixel-grid clustering
			layer.clearLayers();
			var groups = {}, size = 60;
			pts.forEach(function (p) {
				var pt = map.latLngToLayerPoint([p.lat, p.lng]), k = Math.floor(pt.x / size) + ':' + Math.floor(pt.y / size);
				(groups[k] = groups[k] || []).push(p);
			});
			Object.keys(groups).forEach(function (k) {
				var g = groups[k];
				var lat = g.reduce(function (s, p) { return s + p.lat; }, 0) / g.length, lng = g.reduce(function (s, p) { return s + p.lng; }, 0) / g.length;
				var sz = 28 + Math.min(20, g.length * 3);
				var m = window.L.marker([lat, lng], { icon: window.L.divIcon({ className: '', html: '<div class="mk-cluster" style="width:' + sz + 'px;height:' + sz + 'px">' + g.length + '</div>', iconSize: [sz, sz] }), keyboard: true, title: g.length + '' });
				if (g.length === 1) m.bindPopup(popup(g[0]));
				else m.on('click', function () { map.fitBounds(window.L.latLngBounds(g.map(function (p) { return [p.lat, p.lng]; })).pad(0.5), { maxZoom: 16 }); });
				m.addTo(layer);
			});
		}
		if (pts.length) map.fitBounds(window.L.latLngBounds(pts.map(function (p) { return [p.lat, p.lng]; })).pad(0.15), { maxZoom: 14 });
		else map.setView([-1.2864, 36.8172], 11);
		map.on('zoomend', draw); draw();
	}
	function initMaps(root) {
		if (!window.L) return;
		var els = $$('[data-mk-map], [data-mk-map-results]', root).filter(function (el) { return !el._mk; });
		var go = function (el) { el._mk = 1; if (el.hasAttribute('data-mk-map')) initSingleMap(el); else initResultsMap(el); };
		if (!('IntersectionObserver' in window)) { els.forEach(go); return; }
		var io = new IntersectionObserver(function (entries) { entries.forEach(function (en) { if (en.isIntersecting) { io.unobserve(en.target); go(en.target); } }); }, { rootMargin: '200px' });
		els.forEach(function (el) { io.observe(el); });
	}


	/* ---------- Favourites & compare ---------- */
	var SL = (function () {
		var KEY = { favs: 'mk_favs', compare: 'mk_compare', user: 'mk_sl_user' };
		var MAX = { favs: 200, compare: CFG.cmpMax || 4 };
		var mini = {};
		var st = { favs: read('favs'), compare: read('compare') };
		function read(k) {
			try { var v = JSON.parse(localStorage.getItem(KEY[k]) || '[]'); return Array.isArray(v) ? v.map(Number).filter(Boolean).slice(0, MAX[k]) : []; } catch (e) { return []; }
		}
		function write() {
			try { localStorage.setItem(KEY.favs, JSON.stringify(st.favs)); localStorage.setItem(KEY.compare, JSON.stringify(st.compare)); } catch (e) { /* storage blocked */ }
		}
		function push() {
			if (!CFG.user) return;
			fetch(restUrl('/me/saved'), { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce }, body: JSON.stringify(st) }).catch(function () {});
		}
		function union(a, b, m) { var o = a.slice(); b.forEach(function (x) { if (o.indexOf(x) < 0) o.push(x); }); return o.slice(0, m); }
		function sync() {
			if (!CFG.user || !window.fetch) return Promise.resolve();
			return fetch(restUrl('/me/saved'), { credentials: 'same-origin', headers: { 'X-WP-Nonce': CFG.nonce } })
				.then(function (r) { return r.ok ? r.json() : null; })
				.then(function (d) {
					if (!d) return;
					var last = null; try { last = localStorage.getItem(KEY.user); } catch (e) { /* ignore */ }
					if (String(last) !== String(CFG.user)) {
						// First visit on this device as this user: merge what was saved as a guest.
						st.favs = union(d.favs || [], st.favs, MAX.favs);
						st.compare = union(d.compare || [], st.compare, MAX.compare);
						try { localStorage.setItem(KEY.user, String(CFG.user)); } catch (e) { /* ignore */ }
						write(); push();
					} else {
						st.favs = d.favs || []; st.compare = d.compare || []; write();
					}
				}).catch(function () {});
		}
		function has(k, id) { return st[k].indexOf(+id) > -1; }
		function set(k, list) { st[k] = list.slice(0, MAX[k]); write(); push(); render(); }
		function toggle(k, id) {
			id = +id; var i = st[k].indexOf(id);
			if (i > -1) st[k].splice(i, 1);
			else { if (st[k].length >= MAX[k]) { toast(T.cmpFull); return null; } st[k].push(id); }
			write(); push(); render();
			return i < 0;
		}
		function getMini(ids) {
			var need = ids.filter(function (id) { return !mini[id]; });
			if (!need.length) return Promise.resolve();
			return fetch(restUrl('/saved/mini', { ids: need.join(',') }), { credentials: 'omit' })
				.then(function (r) { return r.json(); })
				.then(function (list) { (list || []).forEach(function (m) { mini[m.id] = m; }); })
				.catch(function () {});
		}
		var onComparePage = false;
		function bar() {
			var b = $('.mk-cbar');
			var show = st.compare.length && !onComparePage;
			document.body.classList.toggle('mk-cbar-on', !!show);
			if (!show) { if (b) b.hidden = true; return; }
			if (!b) {
				b = document.createElement('div'); b.className = 'mk-cbar'; b.setAttribute('role', 'region'); b.setAttribute('aria-label', T.cmpBar || 'Compare');
				document.body.appendChild(b);
			}
			b.hidden = false;
			document.body.classList.toggle('mk-has-mbar', !!$('.mk-mbar'));
			getMini(st.compare).then(function () {
				var n = st.compare.length, ok = n > 1;
				var thumbs = st.compare.map(function (id) {
					var m = mini[id] || {};
					return '<span class="mk-cbar__item"><img src="' + esc(m.thumb || '') + '" alt="" title="' + esc(m.title || '') + '"><button type="button" data-mk-cmp="' + id + '" aria-label="' + esc((T.remove || 'Remove') + ' ' + (m.title || '')) + '">&times;</button></span>';
				}).join('');
				for (var i = n; i < MAX.compare; i++) thumbs += '<span class="mk-cbar__slot" aria-hidden="true"></span>';
				b.innerHTML = '<div class="mk-cbar__in"><strong class="mk-cbar__label">' + esc(T.cmpBar) + ' <span>' + n + '/' + MAX.compare + '</span></strong><div class="mk-cbar__items">' + thumbs + '</div><span class="mk-cbar__sp"></span>' +
					'<button type="button" class="mk-cbar__clear" data-mk-clear="compare">' + esc(T.clear) + '</button>' +
					(CFG.cmpUrl ? (ok ? '<a class="mk-btn mk-btn--gold mk-btn--sm" href="' + esc(CFG.cmpUrl) + '">' + esc(T.cmpNow) + '</a>' : '<span class="mk-cbar__hint">' + esc(T.cmpNeed) + '</span>') : '') + '</div>';
			});
		}
		function render() {
			$$('[data-mk-fav]').forEach(function (b) {
				var on = has('favs', b.getAttribute('data-mk-fav'));
				b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
				var lab = b.querySelector('[data-on]'); if (lab) lab.textContent = lab.getAttribute(on ? 'data-on' : 'data-off');
			});
			$$('[data-mk-cmp]').forEach(function (b) {
				var on = has('compare', b.getAttribute('data-mk-cmp'));
				b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
			$$('[data-mk-fav-count]').forEach(function (el) { el.textContent = st.favs.length; el.hidden = !st.favs.length; });
			bar();
		}
		function shareUrl(k) {
			var base = (k === 'favs' ? CFG.favUrl : CFG.cmpUrl) || (location.origin + location.pathname);
			return base + (base.indexOf('?') > -1 ? '&' : '?') + 'ids=' + st[k].join(',');
		}
		/* Favourites page */
		function favPage(el) {
			var shared = el.getAttribute('data-shared'), body = $('[data-mk-body]', el), empty = $('[data-mk-empty]', el), count = $('[data-mk-count]', el);
			var showCount = function (n) { count.textContent = n ? '(' + n + ')' : ''; };
			if (shared) { showCount(shared.split(',').length); return; }
			var acts = $$('[data-mk-share-list], [data-mk-clear]', el);
			function draw() {
				var ids = st.favs;
				showCount(ids.length);
				acts.forEach(function (a) { a.hidden = !ids.length; });
				if (!ids.length) { body.innerHTML = ''; empty.hidden = false; return; }
				empty.hidden = true;
				fetch(restUrl('/saved/cards', { ids: ids.join(','), columns: el.getAttribute('data-columns') || 3 }), { credentials: 'omit' })
					.then(function (r) { return r.json(); })
					.then(function (d) {
						body.innerHTML = d.html || '';
						if (d.ids && d.ids.length !== ids.length) { st.favs = st.favs.filter(function (id) { return d.ids.indexOf(id) > -1; }); write(); push(); showCount(st.favs.length); }
						initStrips(body); applyUnit(CFG.unit); render();
					}).catch(function () { toast(T.error); });
			}
			el._draw = draw; draw();
		}
		/* Compare page */
		function cmpPage(el) {
			onComparePage = true;
			var shared = el.getAttribute('data-shared'), body = $('[data-mk-body]', el), empty = $('[data-mk-empty]', el), count = $('[data-mk-count]', el);
			var acts = $$('[data-mk-share-list], [data-mk-clear]', el);
			function draw() {
				var ids = shared ? shared.split(',').map(Number) : st.compare;
				count.textContent = ids.length ? '(' + ids.length + ')' : '';
				acts.forEach(function (a) { a.hidden = !ids.length; });
				if (ids.length < 2) {
					body.innerHTML = ''; empty.hidden = false;
					var h = $('[data-mk-empty-title]', empty); h.textContent = h.getAttribute(ids.length ? 'data-one' : 'data-none');
					return;
				}
				empty.hidden = true;
				if (shared && body.querySelector('table')) return;
				fetch(restUrl('/saved/compare', { ids: ids.join(',') }), { credentials: 'omit' })
					.then(function (r) { return r.json(); })
					.then(function (d) { body.innerHTML = d.html || ''; applyUnit(CFG.unit); })
					.catch(function () { toast(T.error); });
			}
			el._draw = draw; draw();
		}
		on(document, 'click', function (e) {
			var b;
			if ((b = e.target.closest('[data-mk-fav]'))) {
				e.preventDefault();
				var id = +b.getAttribute('data-mk-fav'), added = toggle('favs', id);
				if (added === null) return;
				var page = $('[data-mk-favs-page]:not([data-shared])');
				if (page && !added) { var card = b.closest('.mk-card'); if (card && page.contains(card)) { card.remove(); page._draw(); } }
				if (added) toast(T.saved, CFG.favUrl && !page ? T.view : null, CFG.favUrl);
				else toast(T.unsaved, T.undo || 'Undo', function () { toggle('favs', id); if (page) page._draw(); });
				return;
			}
			if ((b = e.target.closest('[data-mk-cmp], [data-mk-cmp-remove]'))) {
				e.preventDefault();
				var cid = +(b.getAttribute('data-mk-cmp') || b.getAttribute('data-mk-cmp-remove'));
				if (b.hasAttribute('data-mk-cmp-remove') && !has('compare', cid)) { var cp = $('[data-mk-compare-page]'); if (cp) { cp.removeAttribute('data-shared'); } }
				var res = toggle('compare', cid);
				if (res && st.compare.length === 1) toast(T.cmpAdded);
				var cpage = $('[data-mk-compare-page]'); if (cpage) cpage._draw();
				return;
			}
			if ((b = e.target.closest('[data-mk-clear]'))) {
				var k = b.getAttribute('data-mk-clear'), prev = st[k].slice();
				set(k, []);
				$$('[data-mk-favs-page], [data-mk-compare-page]').forEach(function (p) { if (p._draw) p._draw(); });
				toast(T.cleared || T.clear, T.undo || 'Undo', function () { set(k, prev); $$('[data-mk-favs-page], [data-mk-compare-page]').forEach(function (p) { if (p._draw) p._draw(); }); });
				return;
			}
			if ((b = e.target.closest('[data-mk-share-list]'))) {
				var url = shareUrl(b.getAttribute('data-mk-share-list'));
				if (navigator.share) { navigator.share({ url: url }).catch(function () {}); return; }
				if (navigator.clipboard) navigator.clipboard.writeText(url).then(function () { toast(T.shareList); }, function () { window.prompt('', url); });
				else window.prompt('', url);
				return;
			}
			if ((b = e.target.closest('[data-mk-save-all]'))) {
				var ids = b.closest('[data-shared]').getAttribute('data-shared').split(',').map(Number);
				set('favs', union(st.favs, ids, MAX.favs));
				toast(T.savedAll, CFG.favUrl ? T.view : null, CFG.favUrl);
				return;
			}
			if ((b = e.target.closest('[data-mk-use-compare]'))) {
				set('compare', b.closest('[data-shared]').getAttribute('data-shared').split(',').map(Number));
				if (CFG.cmpUrl) location.href = CFG.cmpUrl;
			}
		});
		on(document, 'change', function (e) {
			if (e.target.matches('[data-mk-diff]')) {
				var p = e.target.closest('[data-mk-compare-page]'); if (p) p.classList.toggle('is-diff-only', e.target.checked);
			}
		});
		on(window, 'storage', function (e) {
			if (e.key === KEY.favs || e.key === KEY.compare) { st.favs = read('favs'); st.compare = read('compare'); render(); }
		});
		on(document, 'mk:updated', render);
		function boot() {
			$$('[data-mk-compare-page]').forEach(function (el) { onComparePage = true; });
			render();
			sync().then(function () {
				render();
				$$('[data-mk-favs-page]').forEach(favPage);
				$$('[data-mk-compare-page]').forEach(cmpPage);
			});
		}
		return { boot: boot, state: st };
	}());

	/* ---------- Boot ---------- */
	function boot() {
		initStrips(document);
		$$('[data-mk-listing]').forEach(initListing);
		initMaps(document);
		var saved = null; try { saved = localStorage.getItem('mk_unit'); } catch (err) { /* ignore */ }
		if (saved && saved !== CFG.unit) applyUnit(saved);
		SL.boot();
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
}());
