/* Makaazi Hero Slider */
(function () {
	'use strict';
	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function Slider(el) {
		if (el._mks) return;
		el._mks = 1;
		var cfg = {};
		try { cfg = JSON.parse(el.getAttribute('data-mks')) || {}; } catch (e) { cfg = {}; }
		var slides = Array.prototype.slice.call(el.querySelectorAll('.mks__slide'));
		var dots = Array.prototype.slice.call(el.querySelectorAll('[data-mks-to]'));
		var bar = el.querySelector('.mks__progress i');
		var pauseBtn = el.querySelector('[data-mks-pause]');
		var n = slides.length, cur = 0, timer = null, userPaused = false, hover = false;
		var autoplay = cfg.autoplay && !reduced;
		if (n < 2) return;
		if (reduced && pauseBtn) { el.classList.add('is-paused'); }

		function go(i, user) {
			if (!cfg.loop && (i < 0 || i >= n)) return;
			cur = (i + n) % n;
			el.style.setProperty('--cur', cur);
			slides.forEach(function (s, j) {
				var on = j === cur;
				s.classList.toggle('is-on', on);
				s.setAttribute('aria-hidden', on ? 'false' : 'true');
				Array.prototype.forEach.call(s.querySelectorAll('a'), function (a) { a.tabIndex = on ? 0 : -1; });
				var v = s.querySelector('video'); if (v) { if (on) { v.play().catch(function () {}); } else { v.pause(); } }
			});
			dots.forEach(function (d, j) { d.classList.toggle('is-on', j === cur); if (j === cur) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current'); });
			if (user) restart();
			else runBar();
		}
		function runBar() {
			if (!bar) return;
			bar.classList.remove('is-run'); void bar.offsetWidth;
			if (autoplay && !userPaused) bar.classList.add('is-run');
		}
		function stop() { clearTimeout(timer); timer = null; }
		function schedule() {
			stop();
			if (!autoplay || userPaused || hover || document.hidden) return;
			timer = setTimeout(function () { go(cur + 1); schedule(); }, cfg.delay || 6000);
		}
		function restart() { runBar(); schedule(); }

		el.addEventListener('click', function (e) {
			var b = e.target.closest('[data-mks-go]');
			if (b) { go(cur + parseInt(b.getAttribute('data-mks-go'), 10), true); return; }
			var d = e.target.closest('[data-mks-to]');
			if (d) { go(parseInt(d.getAttribute('data-mks-to'), 10), true); return; }
			if (e.target.closest('[data-mks-pause]')) {
				userPaused = !userPaused;
				el.classList.toggle('is-paused', userPaused);
				pauseBtn.setAttribute('aria-label', pauseBtn.getAttribute(userPaused ? 'data-label-play' : 'data-label-pause'));
				if (userPaused) stop(); else restart();
			}
		});
		if (cfg.pause) {
			el.addEventListener('mouseenter', function () { hover = true; stop(); if (bar) el.classList.add('is-paused'); });
			el.addEventListener('mouseleave', function () { hover = false; if (!userPaused) { el.classList.remove('is-paused'); restart(); } });
		}
		el.addEventListener('focusin', function () { stop(); });
		el.addEventListener('focusout', function () { if (!el.contains(document.activeElement)) schedule(); });
		document.addEventListener('visibilitychange', function () { if (document.hidden) stop(); else schedule(); });
		el.addEventListener('keydown', function (e) {
			if (e.target.closest('input, select, textarea')) return;
			if (e.key === 'ArrowRight') { go(cur + 1, true); }
			if (e.key === 'ArrowLeft') { go(cur - 1, true); }
		});
		var x0 = null, y0 = null;
		el.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; }, { passive: true });
		el.addEventListener('touchend', function (e) {
			if (x0 === null) return;
			var dx = e.changedTouches[0].clientX - x0, dy = e.changedTouches[0].clientY - y0;
			x0 = null;
			if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) go(cur + (dx < 0 ? 1 : -1), true);
		}, { passive: true });

		go(0);
		schedule();
	}

	function boot(root) { Array.prototype.forEach.call((root || document).querySelectorAll('[data-mks]'), Slider); }
	window.MakaaziSliderInit = boot;
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { boot(); }); else boot();
}());
