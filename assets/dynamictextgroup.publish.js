(function ($, exports, undefined) {

	function _replaceMultiplyName(element) {
		var index = element.parents().filter('li').index();
		element[0].name = element[0].name.replace(/\[(\{\{multiple\}\}|\d)\]/i, '[' + index + ']');
	}

	function _initMultipleSelectControls(selector) {
		if (typeof selector === 'string') {
			selector = $(selector);
		}
		selector.each(function () {
			_replaceMultiplyName($(this));
		});
	}
	// Stage stuff
	$(document).ready(function () {


		_initMultipleSelectControls('.field-dynamictextgroup select[multiple]');

		$('.field-dynamictextgroup .frame').each(function () {
			var field = $(this);
			field.symphonyDuplicator({
				destructable: true,
				collapsible: false,
				constructable: true,
				orderable: false
				//orderable: field.hasClass('sortable')
			});

			field.on('constructstop.duplicator', function () {
				_initMultipleSelectControls($(this).find('select[multiple'));
			});
		});

		$('div.field-dynamictextgroup').each(function () {
			var manager = $(this),
			help = manager.find('label i'),
			stage = manager.find('div.stage'),
			selection = stage.find('ul.selection');

			manager.on('change', 'input[type=checkbox]', function (e) {
				var input = event.target;

				if (input.checked) {
					input.value = 'yes';
				} else {
					input.value = 'no';
				}
			}).on('change', 'input[type=radio]', function (e) {
				var input = event.target,
				field = $(input).siblings().filter('.dtg-radio'),
				name = field[0].className,
				fields = manager.find('.' + name.split(' ').join('.'));
				fields.val('no');
				field.val('yes');
			}).on('orderstop.orderable', function (e) {
				_replaceMultiplyName(manager.find('select[multiple'));
			});


			$('div.field-dynamictextgroup .sortable').symphonyOrderable({
				items: '.instance',
				handles: '.draw-handle'
			});
		});
	});
} (jQuery.noConflict(), this));
