(function () {
	var $ = null;
	function setPlatform($, p) {
		return $.zextend(p, {
			widget_version: 3,
			platform: {
				dnd_supported: true,
				links_initialize: function () {
					$('#editor-xtd-buttons').before('<div id="zemanta-links" class="zemanta postbox"><ul id="zemanta-links-div-ul"><li class="zemanta-title">&laquo; Links</li></ul><p class="zem-clear">&nbsp;</p></div>');
				},
				tags_initialize: function () {
					$('#metakeywords').before('<div id="zemanta-tags" class="zemanta"><div id="zemanta-tags-div"><ul id="zemanta-tags-div-ul"><li class="zemanta-title">&laquo; Tags</li></ul><p class="zem-clear">&nbsp;</p></div></div>');
				},
				get_editor: function () {
					var elm = null, win = null, editor = {element: null, property: null, type: null, win: null};
					try {
						if ($('#mce_editor_0').get(0)) {
						elm = $('#mce_editor_0').get(0);
						} else if ($('#text_ifr').get(0)){
						elm = $('#text_ifr').get(0);
						} else if ($('#text___Frame').get(0)) {
						elm = $('#text___Frame').get(0);
						}
						if (elm && elm.contentWindow) {
							win = elm.contentWindow;
							elm = null;
						} else {
							elm = $('#text').get(0);
						}
						editor = win && {element: win.document.body, property: 'innerHTML', type: 'RTE', win: win} ||
							elm && {element: elm, property: 'value', type: elm.tagName.toLowerCase(), win: null} ||
							editor;
					} catch (er) {
						$.zemanta.log(er);
					}
					return editor;
				}
			}
		});
	}
	function waitForLoad() {var done = false, t0 = null;
		if (typeof $.zemanta === "undefined") {
			$('#zemanta-message').html('Waiting...');
			setTimeout(arguments.callee, 100);
			return;
		}
		t0 = arguments.callee.t0 = arguments.callee.t0 || new Date().getTime();
		$('#zemanta-message').html('Initializing...');
		try {
			done = $.zemanta.initialize(setPlatform($, {
				interface_type: "joomla",
				tags_target_id: "metakeywords",
           		gallery_width: 4
			}));
		} catch (er) {
			done = true;
		}
		if (!done) {
			if (new Date().getTime() - t0 < 15000) {
				setTimeout(arguments.callee, 100);
			} else {
				$('#zemanta-message').html('Gave up on finding editor. ').append($('<a href="#">Retry</a>').click(arguments.callee));
			}
		}
	}
	try {
		$ = window.zQuery;
		if (!$) {
			throw "No zQuery!";
		}
		if ($('#zemanta-message').html() === 'Loading...') {
			$('#zemanta-message').html('Preparing...');
		}
		waitForLoad();
	} catch (er) {
		window.setTimeout(arguments.callee, 100);
		return;
	}
})();

