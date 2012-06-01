(function ($, exports, undefined) {

	function _replaceMultiplyName(element) {
		console.log(element);
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

	function _buttonLabel(select) {
		return select.find('optgroup').get(0).label;
	}
	// Stage stuff
	$(document).ready(function () {

		var instances = $('.field-dynamictextgroup li:visible select[multiple]');
		instances.multiselect({
			buttonText: _buttonLabel
		});
		_initMultipleSelectControls(instances);

		$('.field-dynamictextgroup .frame').each(function () {
			var field = $(this);
			field.symphonyDuplicator({
				destructable: true,
				collapsible: false,
				constructable: true,
				orderable: false
				//orderable: field.hasClass('sortable')
			});

			field.on('constructshow.duplicator', function (event, element) {
				var target = $(event.target).find('select:multiple');
				target.multiselect({
					buttonText: _buttonLabel
				});
				_initMultipleSelectControls(target);
			});
		});

		$('div.field-dynamictextgroup').each(function () {
			var manager = $(this),
			help = manager.find('label i'),
			stage = manager.find('div.stage'),
			selection = stage.find('ul.selection');

			manager.on('change', 'input[type=checkbox]', function (e) {
				var input = $(event.target),
				hidden = input.siblings().filter('input[type=hidden]');
				if (input[0].checked) {
					hidden[0].value = 'yes';
				} else {
					hidden[0].value = 'no';
				}
			}).on('change', 'input[type=radio]', function (e) {
				var input = event.target,
				field = $(input).siblings().filter('.dtg-radio'),
				name = field[0].className,
				fields = manager.find('.' + name.split(' ').join('.'));
				fields.val('no');
				field.val('yes');
			}).on('orderstop.orderable', function (e) {
				_replaceMultiplyName(manager.find('select:multiple'));
			});


			$('div.field-dynamictextgroup .sortable').symphonyOrderable({
				items: '.instance',
				handles: '.draw-handle'
			});
		});
	});
} (jQuery.noConflict(), this));
