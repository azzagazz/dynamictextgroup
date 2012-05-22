(function ($, Symphony, exports, undefined) {

	var EXP_NUM = '/^-?(?:\\d+(?:\\.\\d+)?|\\.\\d+)$/i',
	EXP_MAIL = '/^\\w(?:\\.?[\\w%+-]+)*@\\w(?:[\\w-]*\\.)+?[a-z]{2,}$/i',
	EXP_URI = '/^[^\\s:\\/?#]+:(?:\\/{2,3})?[^\\s.\\/?#]+(?:\\.[^\\s.\\/?#]+)*(?:\\/[^\\s?#]*\\??[^\\s?#]*(#[^\\s#]*)?)?$/',

	stringValidation = {
		mail: EXP_MAIL,
		uri: EXP_URI,
		number: EXP_NUM
	};

	function trim(string) {
		string = string.replace(/(^\s|\s$)/gim, '');
		return string;
	}

	function _makeFieldHandle(string) {
		return (trim(string).split(' ').join('-')).toLowerCase();
	}

	function _createOptionsWidget(parent) {
		var el = $(this),
		type = el.data('settings').options.type,
		template = parent.find('script.' + type).text(),
		container = el.find('.options-box-inner', parent.find('.tpl_options_' + type)),
		opt;
		container.html(template);
		opt = el.find('select:[name=type]').find('option:[name=' + type + ']');
		opt.attr('selected', true);
	}


	function _preventClick(event) {
		return event.stopPropagation();
	}

	function _getInstanceFormEvent(event) {
		return $(event.target).parents('.instance:first');
	}

	function _setDynamicValues(event) {
		this.setOptions(_getInstanceFormEvent(event), 'options.dynamic_values', parseInt(event.target.value, 10));
	}
	function _setStaticValues(event) {
		this.setOptions(_getInstanceFormEvent(event), 'options.static_values', event.target.value);
	}

	function _validateFieldName(instance, handle) {
		var opts = [];
		this.instances.each(function () {
			this !== instance[0] && opts.push($(this).data('settings').handle);
		});
		if ($.inArray(handle, opts) >= 0) {
			return false;
		}
		return handle;
	}

	function _wrapInError(instance, message, clear) {
		if (!clear) {
			instance.not('.invalid').addClass('invalid').append('<p class="message">' + Symphony.Language.get(message) + '</p>');
		} else {
			instance.removeClass('invalid').find('.message').remove();
		}
	}

	function _setFieldName(event) {
		var instance = _getInstanceFormEvent(event),
		oldHandle = instance.data('settings').handle,
		handle = _validateFieldName.call(this, instance, _makeFieldHandle(event.target.value)),
		label;

		if (handle) {
			label = trim(event.target.value);
			this.setOptions(instance, 'label', label);
			this.setOptions(instance, 'handle', handle);
			_wrapInError(instance, null, true);
			instance.trigger('namechanged.dtg-options', {type: 'namechanged', oldHandle: oldHandle, handle: handle, label: label});
		} else {
			_wrapInError(instance, 'Fieldname already exists');
		}
	}
	function _validateRadioGroupName(event) {
		var instance = _getInstanceFormEvent(event);
		if (!event.target.value) {
			_wrapInError(instance, 'You must provide a group name');
		} else {
			_wrapInError(instance, null, true);
			this.setOptions(instance, 'options.group_name', trim(event.target.value));
		}
	}

	function _handleFieldChanges(type, instance, handle, name, newhandle) {
		var data = instance.data('settings');
		switch (type) {
		case 'rename':
			this.changes.delfields[handle] = newhandle;
			break;
		case 'delete':
			this.changes.delfields.push(handle);
			break;
		case 'add':
			this.changes.addfields[data.handle] = data.label;
			break;
		default:
			return null;
		}
	}

	function _extItemHandle(origHandle, target, event, data) {
		this.changes.renfields[origHandle] = data.label;
	}

	function _newItemHandle(target, event, data) {
		if (this.changes.addfields[data.oldHandle]) {
			delete this.changes.addfields[data.oldHandle];
		}
		this.changes.addfields[data.handle] = data.label;
	}

	function DynamicTextGroupEditor(element, options) {
		var that = this;
		this.options = $.extend({}, DynamicTextGroupEditor.defaults, options);
		this.element = element;
		this.updateInstaces();
		this.delegateEvents();
		this.fieldsettings = $.parseJSON(element.find('input.schema').val());
		this.changeStore = {
			renfields: element.find('input.renfields'),
			delfields: element.find('input.delfields'),
			addfields: element.find('input.addfields')
		};
		this.changes = {
			'renfields':	{},
			'addfields':	{},
			'delfields':	[]
		};
		this.instances.each(function () {
			var instance = $(this),
			origHandle = instance.data('settings').handle;
			that.changes.renfields[origHandle] = '';
			instance.on('namechanged.dtg-options', $.proxy(_extItemHandle, that, origHandle, instance));
			if ($.isFunction(that.options.onInstanceInit)) {
				that.options.onInstanceInit.call(that, instance);
			}
		});
	}

	DynamicTextGroupEditor.defaults = {
		//onInstanceInit: function () {}
	};

	DynamicTextGroupEditor.prototype = {
		events: {
			'click.dtg-options .options': 'selectOptions',
			'click.dtg-options .tabgroup a': 'setValidationString',
			'click.dtg-options .instance': _preventClick,
			'change.dtg-options input[type=checkbox]': 'toggleCheckbox',
			'change.dtg-options,blur.dtg-options input[name=radio_group]': _validateRadioGroupName,
			'change.dtg-options select[name=dynamicValues]': _setDynamicValues,
			'change.dtg-options input[name=selectValues]': _setStaticValues,
			'change.dtg-options,blur.dtg-options input[name=dynamictextgroup-item]': _setFieldName,
			'change.dtg-options select[name=type]': 'toggleFieldType'
		},

		toggleCheckbox: function (event) {
			this.setOptions(_getInstanceFormEvent(event), 'options.' + event.target.name, event.target.checked);
		},
		setValidationString: function (event) {
			event.preventDefault();
			var target = $(event.target),
			value = stringValidation[target.data('type')],
			field = target.parent().siblings().filter('input:[type=text]');
			this.setOptions(_getInstanceFormEvent(event), 'options.validationRule', value);
			field.val(value);
		},

		toggleFieldType: function (event) {
			event.stopPropagation();
			var type = event.target.value,
			instance = _getInstanceFormEvent(event);
			this.setOptions(instance, 'options.type', type);
			_createOptionsWidget.call(instance, this.element);
		},

		setOptions: function (instance, name, value) {
			var data = instance.data('settings'),
			dd = data,
			objArr = name.split('.'),
			l,
			i,
			key = objArr.pop();
			l = objArr.length;
			i = 0;

			for (; i < l; i++) {
				dd = dd[objArr[i]];
			}

			dd[key] = value;
		},

		updateSorting: function () {
			var that = this;
			this.instances.each(function () {
				var instance = $(this),
				order = instance.index() + 1;
				that.setOptions($(this), 'options.sortorder', order);
			});
		},

		toggleOptions: function (element) {
			var opc = element.find('.options-box');
			if (opc.hasClass('collapsed')) {
				opc.slideDown(250, function () {
					opc.addClass('expand');
				}).removeClass('collapsed');
			} else {
				opc.slideUp(250).addClass('collapsed');
			}
		},

		deleteField: function (event) {
			var target = $(event.target);
			target.trigger('fieldremoved.dgt-options', target);
		},

		notifyAdded: function (event) {
			var target = $(event.target),
			field = target.find('input[name=dynamictextgroup-item]'),
			handle = target.data('settings').handle;
			target.on('namechanged.dtg-options', $.proxy(_newItemHandle, this, target));
		},

		selectOptions: function (event) {
			event.stopPropagation();
			this.toggleOptions($(event.target).parent());
		},

		delegateEvents: function () {
			var key, evt, sel, opt, fn;
			for (key in this.events) {
				opt = key.split(' ');
				evt = opt[0].split(',').join(' ');
				sel = opt[1];
				fn = $.isFunction(this.events[key]) ? $.proxy(this.events[key], this) : $.proxy(this[this.events[key]], this);
				this.element.on(evt, sel, fn);
			}
		},

		updateInstaces: function () {
			this.instances && this.instances.off('orderchange');
			this.instances = this.element.find('.field-holder.instance');
		},

		onSubmit: function (event) {
			//event.preventDefault();
			var settings, key;
			this.updateInstaces();
			settings = [];

			this.instances.each(function (i) {
				var instance = $(this);
				settings[i] = instance.data('settings');
			});

			for (key in this.changes) {
				if (this.changes.hasOwnProperty(key)) {
					this.changeStore[key].val(JSON.stringify(this.changes[key]));
				}
			}

			this.element.find('.schema').val(JSON.stringify(settings));
		}
	};

	$(function () {
		$('.frame.dynamictextgroup').each(function () {
			var element = $(this),
			fieldEditor;

			element.symphonyDuplicator({
				collapsible: false,
				destructable: true,
				orderable: false,
			});

			element.symphonyOrderable({
				items: '.field-holder',
				handles: '.draw-handle'
			});

			fieldEditor = new DynamicTextGroupEditor(element, {
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
		});
	});
} (jQuery.noConflict(), this.Symphony, this));
