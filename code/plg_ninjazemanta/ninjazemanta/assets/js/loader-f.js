(function () {
	function getReleaseId() {
		var rid = null;
		try {
			rid = window.ZemantaGetReleaseId();
		} catch (er) {
			rid = 't' + Math.floor(new Date().getTime() / 10000000);
		}
		return rid;
	}
	function now() {
		return new Date().getTime();
	}
	function load_file(file) {
		var i = 0, j = 0, obj = null, head = null, release = '';
		if (file.constructor === Array) { // array
			for (i = 0, j = file.length; i < j; i += 1) {
				arguments.callee.call(this, file[i]);
			}
			return;
		}
		head = arguments.callee.head = arguments.callee.head || document.getElementsByTagName("head")[0];
		release = arguments.callee.release = arguments.callee.release || '?rel=' + getReleaseId();
		file += release;
		if (file.indexOf('.js') >= 0) { //if filename is a external JavaScript file
			obj = document.createElement('script');
			obj.setAttribute("type", "text/javascript");
			obj.setAttribute("src", file);
		} else if (file.indexOf('.css') >= 0) { //if filename is an external CSS file
			obj = document.createElement("link");
			obj.setAttribute("rel", "stylesheet");
			obj.setAttribute("type", "text/css");
			obj.setAttribute("href", file);
		}
		if (head && obj) {
			head.appendChild(obj);
		}
	}
	
	var staticDomain = 'http://static.zemanta.com/',
		widget = document.createElement('fieldset'),
		insertionSpace = null,
		t0 = now();
	
	widget.setAttribute('id', 'zemanta-sidebar');
	widget.innerHTML = '<div id="zemanta-control" class="zemanta"></div><div id="zemanta-message" class="zemanta">Loading Zemanta...</div><div id="zemanta-filter" class="zemanta"></div><div id="zemanta-gallery" class="zemanta"></div><div id="zemanta-articles" class="zemanta"></div><div id="zemanta-preferences" class="zemanta"></div>';
	
	(function () {
		insertionSpace = document.getElementsByTagName('fieldset')[1];
		if (insertionSpace) {
			insertionSpace.parentNode.insertBefore(widget, insertionSpace);
			load_file([
				staticDomain + 'core/zemanta-widget.css',
				staticDomain + 'core/jquery.js',
				staticDomain + 'core/jquery.zemanta.js',
			]);
		} else if (now() - t0 < 5000) { // in 5s DOM should be available - should we check the ondomready/onload event?
			setTimeout(arguments.callee, 100);
		}
	})();
})();