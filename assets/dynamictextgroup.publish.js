(function ($, exports, undefined) {

	// Stage stuff
	$(document).ready(function () {
		$('.field-dynamictextgroup .frame').each(function () {
			var field = $(this);
			field.symphonyDuplicator({
				destructable: true,
				collapsible: false,
				constructable: true,
				orderable: field.hasClass('sortable')
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
			});
		});
	});
} (jQuery.noConflict(), this));
