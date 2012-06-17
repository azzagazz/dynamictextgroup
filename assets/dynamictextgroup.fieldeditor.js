/*
vim: set noexpandtab tabstop=4 shiftwidth=4 softtabstop=4
*/
(function ($, Symphony, exports, undefined) {
	Symphony.Language.add({
		'duplicate fieldnames: fieldnames must be unique': false
	});

	/**
	 * @module Symphony
	 * @class DynamicTextGroupEditor
	 * @constructor
	 * @event 'change.dtg-options'		 `change` events on instance- and options
	 * fields
	 * @event 'click.dtg-options'		 `click` event on toggle controls
	 * @event 'namechanged.dtg-options'   if a field gets renamed
	 * @event 'fieldremoved.dtg-options'  if a field gets removed
	 */
	var DynamicTextGroupEditor = (function () {
		/**
		 * Default Number validation String
		 *
		 * @property EXP_NUM
		 * @type {String}
		 * @protected
		 * @final
		 */
		var EXP_NUM = '/^-?(?:\\d+(?:\\.\\d+)?|\\.\\d+)$/i',

		/**
		 * Default MAIL validation String
		 *
		 * @property EXP_MAIL
		 * @type {String}
		 * @protected
		 * @final
		 */
		EXP_MAIL = '/^\\w(?:\\.?[\\w%+-]+)*@\\w(?:[\\w-]*\\.)+?[a-z]{2,}$/i',

		/**
		 * Default URI validation String
		 *
		 * @property EXP_URI
		 * @type {String}
		 * @protected
		 * @final
		 */
		EXP_URI = '/^[^\\s:\\/?#]+:(?:\\/{2,3})?[^\\s.\\/?#]+(?:\\.[^\\s.\\/?#]+)*(?:\\/[^\\s?#]*\\??[^\\s?#]*(#[^\\s#]*)?)?$/',

		stringValidation = {
			mail: EXP_MAIL,
			uri: EXP_URI,
			number: EXP_NUM
		};
		function _localeComp(a, b) {
			return a.localeCompare(b);
		}
		/**
		 * Takes an onedimensional array and searhes for duplicates.
		 * Duplicates indexes will be returned in an array (if any).
		 *
		 * @method _indexesOfDoublons()
		 * @static
		 * @param	{Array}		arr the input array
		 * @access	private
		 * @return	{Array}		Indexes of duplicate items
		 */
		function _indexesOfDoublons(arr) {
			var array = [],
			sort = [], indexes = [], l = arr.length, i = 0, j = 0;
			array.push.apply(array, arr);
			array.sort(_localeComp);

			for (; i < l; i++) {
				if (array[i + 1] === array[i]) {
					sort.push(array[i]);
				}
			}
			for (; j < l; j++) {
				if ($.inArray(arr[j], sort) >= 0) {
					indexes.push(j);
				}
			}
			return indexes;
		}

		/**
		 * Removes leading or trailing whitspace characters of a string
		 *
		 * @method _trim()
		 * @param	{String}	the string to be trimmed
		 * @access	private
		 * @return	{String}	the trimmed string
		 */
		function _trim(string) {
			string = string.replace(/(^\s|\s$)/gim, '');
			return string;
		}

		/**
		 * Basically "lowecases and replace whitespace with `-`" all field name
		 * declaration for the handle field value.
		 * This is just used for client side validation. The original Handles
		 * will be set on the server.
		 *
		 * @method _makeFieldHandle()
		 * @param	{String} string
		 * @access  private
		 * @return  {String} the lowecased and concatinated input string
		 */
		function _makeFieldHandle(string) {
			return (_trim(string).split(' ').join('-')).toLowerCase();
		}

		/**
		 * Method for creating an options field.
		 *
		 * @method _createOptionsWidget()
		 * @param  {Object}	parent jQuery Object: a parent node containing the
		 * template script
		 * @access private
		 * @return void
		 */
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

		/**
		 * Like `event.stopPropagation()` but can be bound directly as event
		 * handler
		 *
		 * @method _preventClick()
		 * @param event $event
		 * @access private
		 * @return void
		 */
		function _preventClick(event) {
			return event.stopPropagation();
		}

		/**
		 * Get the instances node of a event fired beneath that node
		 *
		 * @method _getInstanceFormEvent()
		 * @param {Object} event Event object
		 * @access private
		 * @return {Object} the instance node
		 */
		function _getInstanceFormEvent(event) {
			return $(event.target).parents('.instance:first');
		}

		/**
		 * Sets the dynamic values uf a selectbox field
		 *
		 * @method _setDynamicValues()
		 * @param {Object} event Event Object
		 * @access private
		 * @return void
		 */
		function _setDynamicValues(event) {
			this.setOptions(_getInstanceFormEvent(event), 'options.dynamic_values', parseInt(event.target.value, 10));
		}
		/**
		 * Sets the static values uf a selectbox field
		 *
		 * @method _setStaticValues()
		 * @param {Object} event Event Object
		 * @access private
		 * @return void
		 */
		function _setStaticValues(event) {
			this.setOptions(_getInstanceFormEvent(event), 'options.static_values', event.target.value);
		}
		/**
		 * Set selected, predefined validateion rules for a textfield
		 *
		 * @method _setValidationRule()
		 * @param {Object} event Event Object
		 * @access private
		 * @return void
		 */
		function _setValidationRule(event) {
			this.setOptions(_getInstanceFormEvent(event), 'options.validationRule', event.target.value);
		}

		/**
		 * Validates a fieldhandle. Returns an array of douplicates found (not
		 * he douplicates themselfs, but the indexes of their fieldinstance)
		 *
		 * @method _validateFieldName()
		 * @param instance $instance
		 * @param instances $instances
		 * @param handle $handle
		 * @access private
		 * @return {Array} Array of instance indexes
		 */
		function _validateFieldName(instance, instances, handle) {
			var opts = [],
			indexes, i, l, invalids = $([]);
			this.instances.each(function (index, element) {
				var el = $(this),
				dataHandle = el.data('settings').handle;
				opts.push(dataHandle);
			});

			indexes = _indexesOfDoublons(opts);
			if (indexes.length) {
				i = 0;
				l = indexes.length;
				for (; i < l; i++) {
					invalids.push(this.instances[indexes[i]]);
				}
			}
			return invalids;
		}

		/**
		 * Wraps a field with an errorframe or removes this errorframe
		 * depending on weather `clear` is set to `true` or `false`
		 *
		 * @method	_wrapInError()
		 * @param	{Object}	instance		the field to wrap or clear
		 * @param	{Mixed}		[message]		the error message or null
		 * @param	{Boolean}	[clear=false]   set this to true if you want to
		 * clear an error frame
		 * @access	private
		 * @return	void
		 */
		function _wrapInError(instance, message, clear) {
			message = clear ? null : message;
			clear = typeof message === 'string' ? false : true;
			if (!clear) {
				instance.not('.invalid').addClass('invalid').find('.content').append('<p class="message">' + Symphony.Language.get(message) + '</p>');
			} else {
				instance.removeClass('invalid').find('.message').remove();
			}
		}

		/**
		 * Sets the fieldname and its temporary handle.
		 * Will validate against existeing field names. No chances will be
		 * triggered if validation fails
		 *
		 * @method _setFieldName
		 * @param event $event
		 * @access private
		 * @return void
		 */
		function _setFieldName(event) {
			var instance = _getInstanceFormEvent(event),
			oldHandle = instance.data('settings').handle,
			handle = _makeFieldHandle(event.target.value),
			validHandle,
			label = _trim(event.target.value);

			this.setOptions(instance, 'label', label);
			this.setOptions(instance, 'handle', handle);

			validHandle = _validateFieldName.call(this, instance, this.instances, handle);

			_wrapInError(this.instances, null, true);

			if (validHandle.length) {
				_wrapInError(validHandle, Symphony.Language.get('duplicate fieldnames: fieldnames must be unique'));
			}

			if (!instance.hasClass('invalid')) {
				instance.trigger('namechanged.dtg-options', {
					type: 'namechanged',
					oldHandle: instance.data('settings').o_handle,
					handle: handle,
					label: label
				});
			}
		}
		/**
		 * Radio control validation for field instances that containe more
		 * than one `radio control` element
		 *
		 * @method _validateRadioGroupName
		 * @param event $event
		 * @access private
		 * @return void
		 */
		function _validateRadioGroupName(event) {
			var instance = _getInstanceFormEvent(event);
			if (!event.target.value) {
				_wrapInError(instance, 'You must provide a group name');
			} else {
				_wrapInError(instance, null, true);
				this.setOptions(instance, 'options.group_name', _trim(event.target.value));
			}
		}

		/**
		 *
		 * @deprecated
		 * @method _handleFieldChanges()
		 * @param	{String}	type		operation type rename|delete|add
		 * @param	{Object}	instance	jQuery Object: the field instance
		 * @param	{String}	[handle]	the existing field handle
		 * @param	{String}	[name]
		 * @param	{String}	[newhandle]	the new handle for a renaming
		 * operation
		 * @access	private
		 * @return	void
		 */
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

		/**
		 * Determines weather an existing field got renamed or retained its
		 * original name
		 *
		 * @method _extItemHandle()
		 * @param	{Object}	[target]
		 * @param	{Object}	[event]			event object
		 * @param	{Object}	data			data.settings object of a instance node
		 * @param	{Object}	data.handle
		 * @param	{Object}	data.label
		 * @param	{Object}	data.origHandle the handle value that was
		 * originaly registerd with the namechanged event
		 * @access	private
		 * @return	void
		 */
		function _extItemHandle(origHandle, target, event, data) {
			if (origHandle !== data.handle) {
				this.changes.renfields[origHandle] = data.label;
			} else {
				delete this.changes.renfields[origHandle];
			}
		}

		/**
		 * Is called, if a fieldinstance is added.
		 *
		 * @method _newItemHandle()
		 * @param	{Object}	[target]
		 * @param	{Object}	[event]			event object
		 * @param	{Object}	data			data.settings object of a instance node
		 * @param	{Object}	data.handle
		 * @param	{Object}	data.label
		 * @param	{Object}	data.oldHandle
		 * @access	private
		 * @return	void
		 */
		function _newItemHandle(target, event, data) {
			if (this.changes.addfields[data.oldHandle]) {
				delete this.changes.addfields[data.oldHandle];
			}
			this.changes.addfields[data.handle] = data.label;
		}

		/**
		 * Set eventdelegation of events defined in the events property
		 *
		 * @method delegateEvents
		 * @private
		 * @return void
		 */
		function _delegateEvents() {
			var key, evt, sel, opt, fn;
			for (key in this.events) {
				opt = key.split(' ');
				evt = opt[0].split(',').join(' ');
				sel = opt[1];
				fn = $.isFunction(this.events[key]) ? $.proxy(this.events[key], this) : $.proxy(this[this.events[key]], this);
				this.element.on(evt, sel, fn);
			}
		}

		function Editor(element, options) {
			var that = this;
			/**
			 * the instace settings for this object
			 *
			 * @property options
			 * @type {Object}
			 * @public
			 */
			this.options = $.extend({}, Editor.defaults, options);

			/**
			 * the container element
			 *
			 * @property element
			 * @type {Object}
			 * @public
			 */
			this.element = element;
			this
				.updateInstaces();
			_delegateEvents.call(this);

			/**
			 * the field schemas
			 *
			 * @property {Object} fieldsettings
			 * @type {Object}
			 * @public
			 */
			this.fieldsettings = $.parseJSON(element.find('input.schema').val());

			/**
			 * object for storing cahnges made on field instances
			 *
			 * @deprecated
			 * @property changeStore
			 * @type	 {Object}
			 * @public
			 */
			this.changeStore = {
				renfields: element.find('input.renfields'),
				delfields: element.find('input.delfields'),
				addfields: element.find('input.addfields')
			};

			/**
			 * object for storing cahnges made on field instances
			 *
			 * @property changes
			 * @type	 {Object}
			 * @public
			 */
			this.changes = {
				'renfields': {},
				'addfields': {},
				'delfields': []
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

		Editor.defaults = {
			//onInstanceInit: function () {}
		};

		Editor.prototype = {
			/**
			 * An object containing all eventdelegations of the container
			 * element
			 *
			 * @property events
			 * @type {Object}
			 * @public
			 */
			events: {
				'click.dtg-options .options': 'selectOptions',
				'click.dtg-options .tabgroup a': 'setValidationString',
				'click.dtg-options .instance': _preventClick,
				'change.dtg-options input[type=checkbox]': 'toggleCheckbox',
				'change.dtg-options,blur.dtg-options input[name=radio_group]': _validateRadioGroupName,
				'change.dtg-options,blur.dtg-options input[name=selectValues]': _setStaticValues,
				'change.dtg-options select[name=dynamicValues]': _setDynamicValues,
				'change.dtg-options,blur.dtg-options input[name=validationRule]': _setValidationRule,
				'change.dtg-options input[name=dynamictextgroup-item]': _setFieldName,
				'change.dtg-options select[name=type]': 'toggleFieldType'
			},

			toggleCheckbox: function (event) {
				this.setOptions(_getInstanceFormEvent(event), 'options.' + event.target.name, event.target.checked);
				return this;
			},
			/**
			 * set the default validation strings in the validation tabbar
			 *
			 * @param {Object} event click event object
			 * @chainable
			 * @public
			 *
			 */
			setValidationString: function (event) {
				event.preventDefault();
				var target = $(event.target),
				value = stringValidation[target.data('type')],
				field = target.parent().siblings().filter('input:[type=text]');
				field.val(value);
				field.trigger('change');
				return this;
			},

			/**
			 * Toggles the settings panel field type widget
			 * Needs an event obe
			 *
			 * @method	toggleFieldType()
			 * @param	{Object}	event				change event object
			 * @param	{Object}	event.target		the controlelement
			 * @param	{Object}	event.target.value	the controlelements
			 * value, representing the fieldtype (select, checkbox, radio,
			 * text, date)
			 * @chainable
			 * @public
			 *
			 */
			toggleFieldType: function (event) {
				event.stopPropagation();
				var type = event.target.value,
				instance = _getInstanceFormEvent(event);
				this.setOptions(instance, 'options.type', type);
				_createOptionsWidget.call(instance, this.element);
				return this;
			},

			/**
			 *  Sets a property on the data.settings data object of a field
			 *  instance.
			 *
			 * @method setOptions()
			 * @param	{Object}	jQuery	Object: the field instance
			 * @param	{String}	name	object path in dot notation
			 * @param	{String}	value   the property value
			 * @chainable
			 * @public
			 */
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
				return this;
			},

			/**
			 * Retains the correct sortorder property on a fieldinstance after
			 * a sorting operation
			 *
			 * @method updateSorting
			 * @chainable
			 * @public
			 */
			updateSorting: function () {
				var that = this;
				this.instances.each(function () {
					var instance = $(this),
					order = instance.index() + 1;
					that.setOptions($(this), 'options.sortorder', order);
				});
				return this;
			},

			/**
			 * Toggles the options view on a instance node
			 *
			 * @param	{Object} jQuery Object: element containing the options
			 * view container
			 * @method	toggleOptions
			 * @chainable
			 * @public
			 */
			toggleOptions: function (element) {
				var opc = element.find('.options-box');
				if (opc.hasClass('collapsed')) {
					opc.slideDown(250, function () {
						opc.addClass('expand');
					}).removeClass('collapsed');
				} else {
					opc.slideUp(250).addClass('collapsed');
				}
				return this;
			},

			/**
			 * @method deleteField
			 * @chainable
			 * @public
			 */
			deleteField: function (event) {
				var target = $(event.target);
				target.trigger('fieldremoved.dgt-options', target);
				return this;
			},

			/**
			 * @method notifyAdded
			 * @chainable
			 * @public
			 */
			notifyAdded: function (event) {
				var target = $(event.target),
				field = target.find('input[name=dynamictextgroup-item]'),
				handle = target.data('settings').handle;
				target
					.on('namechanged.dtg-options', $.proxy(_newItemHandle, this, target));
				return this;
			},

			/**
			 * @method selectOptions
			 * @chainable
			 * @public
			 */
			selectOptions: function (event) {
				event.stopPropagation();
				this.toggleOptions($(event.target).parent());
				return this;
			},

			/**
			 * @method updateInstaces
			 * @chainable
			 * @public
			 */
			updateInstaces: function () {
				this.instances = this.element.find('.field-holder.instance');
				return this;
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
				return this;
			}
		};
		return Editor;
	}());

	Symphony.DynamicTextGroupEditor = DynamicTextGroupEditor;
} (jQuery.noConflict(), this.Symphony, this));
