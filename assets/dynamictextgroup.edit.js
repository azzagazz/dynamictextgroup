/*
vim: set noexpandtab tabstop=4 shiftwidth=4 softtabstop=4
*/
(function ($, Symphony, exports, undefined) {

	function setup(element) {
		var fieldEditor;

		element.symphonyDuplicator({
			collapsible: false,
			destructable: true,
			orderable: false,
		});

		element.symphonyOrderable({
			items: '.field-holder',
			handles: '.draw-handle'
		});

		fieldEditor = new Symphony.DynamicTextGroupEditor(element, {
			onInstanceInit: function (instance) {
				var editor = this,
				handle = instance.data('settings').handle;
				instance.on('fieldremoved.dgt-options', function () {
					editor.changes.delfields.push(handle);
				});
			}
		});

		fieldEditor.element.on('constructstop.duplicator', function (event) {
			fieldEditor.updateInstaces();
			fieldEditor.notifyAdded.apply(fieldEditor, arguments);
		});

		fieldEditor.element.on('destructstart.duplicator', $.proxy(fieldEditor.deleteField, fieldEditor));

		fieldEditor.element.on('orderstop.orderable', function () {
			fieldEditor.updateSorting();
		});

		$('form').on('submit', $.proxy(fieldEditor.onSubmit, fieldEditor));
	}

	$(function () {

		$('form > fieldset > .frame').on('constructstop.duplicator', function (event) {
			var dtgFields = $(event.target).find('.frame.dynamictextgroup');
			if (dtgFields.length) {
				setup(dtgFields);
			}
		});

		$('.frame.dynamictextgroup').each(function () {
			setup($(this));
		});
	});
} (jQuery.noConflict(), this.Symphony, this));
