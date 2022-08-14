(function (window) {
  if (window.welpodron || window.welpodron.core) {
    // CLEAVE BLOCK START
    var commonjsGlobal =
      typeof window !== 'undefined'
        ? window
        : typeof global !== 'undefined'
        ? global
        : typeof self !== 'undefined'
        ? self
        : {};

    var Util = {
      noop: function () {},

      strip: function (value, re) {
        return value.replace(re, '');
      },

      getPostDelimiter: function (value, delimiter, delimiters) {
        // single delimiter
        if (delimiters.length === 0) {
          return value.slice(-delimiter.length) === delimiter ? delimiter : '';
        }

        // multiple delimiters
        var matchedDelimiter = '';
        delimiters.forEach(function (current) {
          if (value.slice(-current.length) === current) {
            matchedDelimiter = current;
          }
        });

        return matchedDelimiter;
      },

      getDelimiterREByDelimiter: function (delimiter) {
        return new RegExp(
          delimiter.replace(/([.?*+^$[\]\\(){}|-])/g, '\\$1'),
          'g'
        );
      },

      getNextCursorPosition: function (
        prevPos,
        oldValue,
        newValue,
        delimiter,
        delimiters
      ) {
        // If cursor was at the end of value, just place it back.
        // Because new value could contain additional chars.
        if (oldValue.length === prevPos) {
          return newValue.length;
        }

        return (
          prevPos +
          this.getPositionOffset(
            prevPos,
            oldValue,
            newValue,
            delimiter,
            delimiters
          )
        );
      },

      getPositionOffset: function (
        prevPos,
        oldValue,
        newValue,
        delimiter,
        delimiters
      ) {
        var oldRawValue, newRawValue, lengthOffset;

        oldRawValue = this.stripDelimiters(
          oldValue.slice(0, prevPos),
          delimiter,
          delimiters
        );
        newRawValue = this.stripDelimiters(
          newValue.slice(0, prevPos),
          delimiter,
          delimiters
        );
        lengthOffset = oldRawValue.length - newRawValue.length;

        return lengthOffset !== 0 ? lengthOffset / Math.abs(lengthOffset) : 0;
      },

      stripDelimiters: function (value, delimiter, delimiters) {
        var owner = this;

        // single delimiter
        if (delimiters.length === 0) {
          var delimiterRE = delimiter
            ? owner.getDelimiterREByDelimiter(delimiter)
            : '';

          return value.replace(delimiterRE, '');
        }

        // multiple delimiters
        delimiters.forEach(function (current) {
          current.split('').forEach(function (letter) {
            value = value.replace(owner.getDelimiterREByDelimiter(letter), '');
          });
        });

        return value;
      },

      headStr: function (str, length) {
        return str.slice(0, length);
      },

      getMaxLength: function (blocks) {
        return blocks.reduce(function (previous, current) {
          return previous + current;
        }, 0);
      },

      // strip prefix
      // Before type  |   After type    |     Return value
      // PEFIX-...    |   PEFIX-...     |     ''
      // PREFIX-123   |   PEFIX-123     |     123
      // PREFIX-123   |   PREFIX-23     |     23
      // PREFIX-123   |   PREFIX-1234   |     1234
      getPrefixStrippedValue: function (
        value,
        prefix,
        prefixLength,
        prevResult,
        delimiter,
        delimiters,
        noImmediatePrefix,
        tailPrefix,
        signBeforePrefix
      ) {
        // No prefix
        if (prefixLength === 0) {
          return value;
        }

        // Value is prefix
        if (value === prefix && value !== '') {
          return '';
        }

        if (signBeforePrefix && value.slice(0, 1) == '-') {
          var prev =
            prevResult.slice(0, 1) == '-' ? prevResult.slice(1) : prevResult;
          return (
            '-' +
            this.getPrefixStrippedValue(
              value.slice(1),
              prefix,
              prefixLength,
              prev,
              delimiter,
              delimiters,
              noImmediatePrefix,
              tailPrefix,
              signBeforePrefix
            )
          );
        }

        // Pre result prefix string does not match pre-defined prefix
        if (prevResult.slice(0, prefixLength) !== prefix && !tailPrefix) {
          // Check if the first time user entered something
          if (noImmediatePrefix && !prevResult && value) return value;
          return '';
        } else if (prevResult.slice(-prefixLength) !== prefix && tailPrefix) {
          // Check if the first time user entered something
          if (noImmediatePrefix && !prevResult && value) return value;
          return '';
        }

        var prevValue = this.stripDelimiters(prevResult, delimiter, delimiters);

        // New value has issue, someone typed in between prefix letters
        // Revert to pre value
        if (value.slice(0, prefixLength) !== prefix && !tailPrefix) {
          return prevValue.slice(prefixLength);
        } else if (value.slice(-prefixLength) !== prefix && tailPrefix) {
          return prevValue.slice(0, -prefixLength - 1);
        }

        // No issue, strip prefix for new value
        return tailPrefix
          ? value.slice(0, -prefixLength)
          : value.slice(prefixLength);
      },

      getFirstDiffIndex: function (prev, current) {
        var index = 0;

        while (prev.charAt(index) === current.charAt(index)) {
          if (prev.charAt(index++) === '') {
            return -1;
          }
        }

        return index;
      },

      getFormattedValue: function (
        value,
        blocks,
        blocksLength,
        delimiter,
        delimiters,
        delimiterLazyShow
      ) {
        var result = '',
          multipleDelimiters = delimiters.length > 0,
          currentDelimiter = '';

        // no options, normal input
        if (blocksLength === 0) {
          return value;
        }

        blocks.forEach(function (length, index) {
          if (value.length > 0) {
            var sub = value.slice(0, length),
              rest = value.slice(length);

            if (multipleDelimiters) {
              currentDelimiter =
                delimiters[delimiterLazyShow ? index - 1 : index] ||
                currentDelimiter;
            } else {
              currentDelimiter = delimiter;
            }

            if (delimiterLazyShow) {
              if (index > 0) {
                result += currentDelimiter;
              }

              result += sub;
            } else {
              result += sub;

              if (sub.length === length && index < blocksLength - 1) {
                result += currentDelimiter;
              }
            }

            // update remaining string
            value = rest;
          }
        });

        return result;
      },

      // move cursor to the end
      // the first time user focuses on an input with prefix
      fixPrefixCursor: function (el, prefix, delimiter, delimiters) {
        if (!el) {
          return;
        }

        var val = el.value,
          appendix = delimiter || delimiters[0] || ' ';

        if (
          !el.setSelectionRange ||
          !prefix ||
          prefix.length + appendix.length <= val.length
        ) {
          return;
        }

        var len = val.length * 2;

        // set timeout to avoid blink
        setTimeout(function () {
          el.setSelectionRange(len, len);
        }, 1);
      },

      // Check if input field is fully selected
      checkFullSelection: function (value) {
        try {
          var selection =
            window.getSelection() || document.getSelection() || {};
          return selection.toString().length === value.length;
        } catch (ex) {
          // Ignore
        }

        return false;
      },

      setSelection: function (element, position, doc) {
        if (element !== this.getActiveElement(doc)) {
          return;
        }

        // cursor is already in the end
        if (element && element.value.length <= position) {
          return;
        }

        if (element.createTextRange) {
          var range = element.createTextRange();

          range.move('character', position);
          range.select();
        } else {
          try {
            element.setSelectionRange(position, position);
          } catch (e) {
            // eslint-disable-next-line
            console.warn('The input element type does not support selection');
          }
        }
      },

      getActiveElement: function (parent) {
        var activeElement = parent.activeElement;
        if (activeElement && activeElement.shadowRoot) {
          return this.getActiveElement(activeElement.shadowRoot);
        }
        return activeElement;
      },

      isAndroid: function () {
        return navigator && /android/i.test(navigator.userAgent);
      },

      // On Android chrome, the keyup and keydown events
      // always return key code 229 as a composition that
      // buffers the user’s keystrokes
      // see https://github.com/nosir/cleave.js/issues/147
      isAndroidBackspaceKeydown: function (lastInputValue, currentInputValue) {
        if (!this.isAndroid() || !lastInputValue || !currentInputValue) {
          return false;
        }

        return currentInputValue === lastInputValue.slice(0, -1);
      }
    };

    var Util_1 = Util;

    var DefaultProperties = {
      // Maybe change to object-assign
      // for now just keep it as simple
      assign: function (target, opts) {
        target = target || {};
        opts = opts || {};

        // others
        target.swapHiddenInput = !!opts.swapHiddenInput;

        target.numericOnly =
          target.creditCard || target.date || !!opts.numericOnly;

        target.uppercase = !!opts.uppercase;
        target.lowercase = !!opts.lowercase;

        target.prefix =
          target.creditCard || target.date ? '' : opts.prefix || '';
        target.noImmediatePrefix = !!opts.noImmediatePrefix;
        target.prefixLength = target.prefix.length;
        target.rawValueTrimPrefix = !!opts.rawValueTrimPrefix;
        target.copyDelimiter = !!opts.copyDelimiter;

        target.initValue =
          opts.initValue !== undefined && opts.initValue !== null
            ? opts.initValue.toString()
            : '';

        target.delimiter =
          opts.delimiter || opts.delimiter === ''
            ? opts.delimiter
            : opts.date
            ? '/'
            : opts.time
            ? ':'
            : opts.numeral
            ? ','
            : opts.phone
            ? ' '
            : ' ';
        target.delimiterLength = target.delimiter.length;
        target.delimiterLazyShow = !!opts.delimiterLazyShow;
        target.delimiters = opts.delimiters || [];

        target.blocks = opts.blocks || [];
        target.blocksLength = target.blocks.length;

        target.root =
          typeof commonjsGlobal === 'object' && commonjsGlobal
            ? commonjsGlobal
            : window;
        target.document = opts.document || target.root.document;

        target.maxLength = 0;

        target.backspace = false;
        target.result = '';

        target.onValueChanged = opts.onValueChanged || function () {};

        return target;
      }
    };

    var DefaultProperties_1 = DefaultProperties;

    var Cleave = function (element, opts) {
      var owner = this;
      var hasMultipleElements = false;

      if (typeof element === 'string') {
        owner.element = document.querySelector(element);
        hasMultipleElements = document.querySelectorAll(element).length > 1;
      } else {
        if (typeof element.length !== 'undefined' && element.length > 0) {
          owner.element = element[0];
          hasMultipleElements = element.length > 1;
        } else {
          owner.element = element;
        }
      }

      if (!owner.element) {
        throw new Error('[cleave.js] Please check the element');
      }

      if (hasMultipleElements) {
        try {
          // eslint-disable-next-line
          console.warn(
            '[cleave.js] Multiple input fields matched, cleave.js will only take the first one.'
          );
        } catch (e) {
          // Old IE
        }
      }

      opts.initValue = owner.element.value;

      owner.properties = Cleave.DefaultProperties.assign({}, opts);

      owner.init();
    };

    Cleave.prototype = {
      init: function () {
        var owner = this,
          pps = owner.properties;

        // no need to use this lib
        if (
          !pps.numeral &&
          !pps.phone &&
          !pps.creditCard &&
          !pps.time &&
          !pps.date &&
          pps.blocksLength === 0 &&
          !pps.prefix
        ) {
          owner.onInput(pps.initValue);

          return;
        }

        pps.maxLength = Cleave.Util.getMaxLength(pps.blocks);

        owner.isAndroid = Cleave.Util.isAndroid();
        owner.lastInputValue = '';
        owner.isBackward = '';

        owner.onChangeListener = owner.onChange.bind(owner);
        owner.onKeyDownListener = owner.onKeyDown.bind(owner);
        owner.onFocusListener = owner.onFocus.bind(owner);
        owner.onCutListener = owner.onCut.bind(owner);
        owner.onCopyListener = owner.onCopy.bind(owner);

        owner.initSwapHiddenInput();

        owner.element.addEventListener('input', owner.onChangeListener);
        owner.element.addEventListener('keydown', owner.onKeyDownListener);
        owner.element.addEventListener('focus', owner.onFocusListener);
        owner.element.addEventListener('cut', owner.onCutListener);
        owner.element.addEventListener('copy', owner.onCopyListener);

        // avoid touch input field if value is null
        // otherwise Firefox will add red box-shadow for <input required />
        if (pps.initValue || (pps.prefix && !pps.noImmediatePrefix)) {
          owner.onInput(pps.initValue);
        }
      },

      initSwapHiddenInput: function () {
        var owner = this,
          pps = owner.properties;
        if (!pps.swapHiddenInput) return;

        var inputFormatter = owner.element.cloneNode(true);
        owner.element.parentNode.insertBefore(inputFormatter, owner.element);

        owner.elementSwapHidden = owner.element;
        owner.elementSwapHidden.type = 'hidden';

        owner.element = inputFormatter;
        owner.element.id = '';
      },

      onKeyDown: function (event) {
        var owner = this,
          charCode = event.which || event.keyCode;

        owner.lastInputValue = owner.element.value;
        owner.isBackward = charCode === 8;
      },

      onChange: function (event) {
        var owner = this,
          pps = owner.properties,
          Util = Cleave.Util;

        owner.isBackward =
          owner.isBackward || event.inputType === 'deleteContentBackward';

        var postDelimiter = Util.getPostDelimiter(
          owner.lastInputValue,
          pps.delimiter,
          pps.delimiters
        );

        if (owner.isBackward && postDelimiter) {
          pps.postDelimiterBackspace = postDelimiter;
        } else {
          pps.postDelimiterBackspace = false;
        }

        this.onInput(this.element.value);
      },

      onFocus: function () {
        var owner = this,
          pps = owner.properties;
        owner.lastInputValue = owner.element.value;

        if (pps.prefix && pps.noImmediatePrefix && !owner.element.value) {
          this.onInput(pps.prefix);
        }

        Cleave.Util.fixPrefixCursor(
          owner.element,
          pps.prefix,
          pps.delimiter,
          pps.delimiters
        );
      },

      onCut: function (e) {
        if (!Cleave.Util.checkFullSelection(this.element.value)) return;
        this.copyClipboardData(e);
        this.onInput('');
      },

      onCopy: function (e) {
        if (!Cleave.Util.checkFullSelection(this.element.value)) return;
        this.copyClipboardData(e);
      },

      copyClipboardData: function (e) {
        var owner = this,
          pps = owner.properties,
          Util = Cleave.Util,
          inputValue = owner.element.value,
          textToCopy = '';

        if (!pps.copyDelimiter) {
          textToCopy = Util.stripDelimiters(
            inputValue,
            pps.delimiter,
            pps.delimiters
          );
        } else {
          textToCopy = inputValue;
        }

        try {
          if (e.clipboardData) {
            e.clipboardData.setData('Text', textToCopy);
          } else {
            window.clipboardData.setData('Text', textToCopy);
          }

          e.preventDefault();
        } catch (ex) {
          //  empty
        }
      },

      onInput: function (value) {
        var owner = this,
          pps = owner.properties,
          Util = Cleave.Util;

        // case 1: delete one more character "4"
        // 1234*| -> hit backspace -> 123|
        // case 2: last character is not delimiter which is:
        // 12|34* -> hit backspace -> 1|34*
        // note: no need to apply this for numeral mode
        var postDelimiterAfter = Util.getPostDelimiter(
          value,
          pps.delimiter,
          pps.delimiters
        );
        if (!pps.numeral && pps.postDelimiterBackspace && !postDelimiterAfter) {
          value = Util.headStr(
            value,
            value.length - pps.postDelimiterBackspace.length
          );
        }

        // numeral formatter
        if (pps.numeral) {
          // Do not show prefix when noImmediatePrefix is specified
          // This mostly because we need to show user the native input placeholder
          if (pps.prefix && pps.noImmediatePrefix && value.length === 0) {
            pps.result = '';
          } else {
            pps.result = pps.numeralFormatter.format(value);
          }
          owner.updateValueState();

          return;
        }

        // strip delimiters
        value = Util.stripDelimiters(value, pps.delimiter, pps.delimiters);

        // strip prefix
        value = Util.getPrefixStrippedValue(
          value,
          pps.prefix,
          pps.prefixLength,
          pps.result,
          pps.delimiter,
          pps.delimiters,
          pps.noImmediatePrefix,
          pps.tailPrefix,
          pps.signBeforePrefix
        );

        // strip non-numeric characters
        value = pps.numericOnly ? Util.strip(value, /[^\d]/g) : value;

        // convert case
        value = pps.uppercase ? value.toUpperCase() : value;
        value = pps.lowercase ? value.toLowerCase() : value;

        // prevent from showing prefix when no immediate option enabled with empty input value
        if (pps.prefix) {
          if (pps.tailPrefix) {
            value = value + pps.prefix;
          } else {
            value = pps.prefix + value;
          }

          // no blocks specified, no need to do formatting
          if (pps.blocksLength === 0) {
            pps.result = value;
            owner.updateValueState();

            return;
          }
        }

        // update credit card props
        if (pps.creditCard) {
          owner.updateCreditCardPropsByValue(value);
        }

        // strip over length characters
        value = Util.headStr(value, pps.maxLength);

        // apply blocks
        pps.result = Util.getFormattedValue(
          value,
          pps.blocks,
          pps.blocksLength,
          pps.delimiter,
          pps.delimiters,
          pps.delimiterLazyShow
        );

        owner.updateValueState();
      },

      updateValueState: function () {
        var owner = this,
          Util = Cleave.Util,
          pps = owner.properties;

        if (!owner.element) {
          return;
        }

        var endPos = owner.element.selectionEnd;
        var oldValue = owner.element.value;
        var newValue = pps.result;

        endPos = Util.getNextCursorPosition(
          endPos,
          oldValue,
          newValue,
          pps.delimiter,
          pps.delimiters
        );

        // fix Android browser type="text" input field
        // cursor not jumping issue
        if (owner.isAndroid) {
          window.setTimeout(function () {
            owner.element.value = newValue;
            Util.setSelection(owner.element, endPos, pps.document, false);
            owner.callOnValueChanged();
          }, 1);

          return;
        }

        owner.element.value = newValue;
        if (pps.swapHiddenInput)
          owner.elementSwapHidden.value = owner.getRawValue();

        Util.setSelection(owner.element, endPos, pps.document, false);
        owner.callOnValueChanged();
      },

      callOnValueChanged: function () {
        var owner = this,
          pps = owner.properties;

        pps.onValueChanged.call(owner, {
          target: {
            name: owner.element.name,
            value: pps.result,
            rawValue: owner.getRawValue()
          }
        });
      },

      setRawValue: function (value) {
        var owner = this,
          pps = owner.properties;

        value = value !== undefined && value !== null ? value.toString() : '';

        if (pps.numeral) {
          value = value.replace('.', pps.numeralDecimalMark);
        }

        pps.postDelimiterBackspace = false;

        owner.element.value = value;
        owner.onInput(value);
      },

      getRawValue: function () {
        var owner = this,
          pps = owner.properties,
          Util = Cleave.Util,
          rawValue = owner.element.value;

        if (pps.rawValueTrimPrefix) {
          rawValue = Util.getPrefixStrippedValue(
            rawValue,
            pps.prefix,
            pps.prefixLength,
            pps.result,
            pps.delimiter,
            pps.delimiters,
            pps.noImmediatePrefix,
            pps.tailPrefix,
            pps.signBeforePrefix
          );
        }

        if (pps.numeral) {
          rawValue = pps.numeralFormatter.getRawValue(rawValue);
        } else {
          rawValue = Util.stripDelimiters(
            rawValue,
            pps.delimiter,
            pps.delimiters
          );
        }

        return rawValue;
      },

      getFormattedValue: function () {
        return this.element.value;
      },

      destroy: function () {
        var owner = this;

        owner.element.removeEventListener('input', owner.onChangeListener);
        owner.element.removeEventListener('keydown', owner.onKeyDownListener);
        owner.element.removeEventListener('focus', owner.onFocusListener);
        owner.element.removeEventListener('cut', owner.onCutListener);
        owner.element.removeEventListener('copy', owner.onCopyListener);
      },

      toString: function () {
        return '[Cleave Object]';
      }
    };

    Cleave.Util = Util_1;
    Cleave.DefaultProperties = DefaultProperties_1;
    // CLEAVE BLOCK END

    window.welpodron.forms = {};

    window.welpodron.forms.factory = {};

    // TODO: Изменить логику, чтобы существовали только типы input, select, textarea

    window.welpodron.forms.factory.create = (element) => {
      switch (element.dataset.type) {
        case 'hidden':
          return new window.welpodron.forms.hidden(element);
        case 'text':
          return new window.welpodron.forms.text(element);
        case 'number':
          return new window.welpodron.forms.number(element);
        case 'email':
          return new window.welpodron.forms.email(element);
        case 'tel':
          return new window.welpodron.forms.tel(element);
        case 'textarea':
          return new window.welpodron.forms.textarea(element);
        case 'select':
          return new window.welpodron.forms.select(element);
        default:
          return new window.welpodron.forms.text(element);
      }
    };

    window.welpodron.forms.field = function (element) {
      this.element = element;
      this.name = this.element.dataset.name
        ? this.element.dataset.name.trim()
        : null;
      this.value = null;
    };

    window.welpodron.forms.inputable = function (element) {
      window.welpodron.forms.field.call(this, element);

      if (this.name) {
        this.control.name = this.name;
      }

      this.value = this.control.value;

      this.getErrors = () => {
        const errors = [];
        const validity = this.control.validity;

        if (validity.patternMismatch)
          errors.push(
            `Значение поля не удовлетворяет маске: ${
              this.control.title ? this.control.title : this.control.pattern
            }`
          );

        if (validity.rangeOverflow)
          errors.push(`Значение поля больше ${this.control.max}`);

        if (validity.rangeUnderflow)
          errors.push(`Значение поля меньше ${this.control.min}`);

        if (validity.stepMismatch)
          errors.push(
            `Значение поля не соответствует шагу: ${this.control.step}`
          );

        if (validity.tooLong) errors.push('Значение поля слишком длинное');

        if (validity.tooShort) errors.push('Значение поля слишком короткое');

        if (validity.typeMismatch)
          errors.push(
            `Значение поля не соответствует типу: ${this.control.type}`
          );

        if (validity.valueMissing)
          errors.push('Поле обязательно для заполнения');

        return errors;
      };

      this.handleChange = (evt) => {
        this.value = evt.currentTarget.value;

        const errors = this.getErrors();

        if (errors.length) {
          this.setValidity(errors.join('. \n'));
        } else {
          this.control.setCustomValidity('');
        }
      };

      this.control.removeEventListener('change', this.handleChange);
      this.control.addEventListener('change', this.handleChange);

      if (this.control.dataset.telMask !== undefined) {
        this.cleave = new Cleave(this.control, {
          delimiters: [' (', ') ', '-', '-'],
          blocks: [1, 3, 3, 2, 2],
          numericOnly: true
        });
      }
    };

    window.welpodron.forms.inputable.prototype = Object.create(
      window.welpodron.forms.field.prototype
    );

    // every proto method needs to be after proto def
    window.welpodron.forms.inputable.prototype.setValidity = function (msg) {
      this.control.setCustomValidity(msg);
      this.control.reportValidity();
    };

    window.welpodron.forms.inputable.prototype.reset = function () {
      this.control.value = '';
      this.value = this.control.value;
    };

    // TODO: Изменить наследуемость hiddeny на element

    window.welpodron.forms.hidden = function (element) {
      this.control = element.querySelector('input[type="hidden"]');
      window.welpodron.forms.inputable.call(this, element);

      this.reset = () => {
        return;
      };
    };

    window.welpodron.forms.hidden.prototype = Object.create(
      window.welpodron.forms.inputable.prototype
    );

    window.welpodron.forms.text = function (element) {
      this.control = element.querySelector('input[type="text"]');
      window.welpodron.forms.inputable.call(this, element);
    };

    window.welpodron.forms.text.prototype = Object.create(
      window.welpodron.forms.inputable.prototype
    );

    window.welpodron.forms.email = function (element) {
      this.control = element.querySelector('input[type="email"]');
      window.welpodron.forms.inputable.call(this, element);
    };

    window.welpodron.forms.email.prototype = Object.create(
      window.welpodron.forms.inputable.prototype
    );

    window.welpodron.forms.tel = function (element) {
      this.control = element.querySelector('input[type="tel"]');
      window.welpodron.forms.inputable.call(this, element);
    };

    window.welpodron.forms.tel.prototype = Object.create(
      window.welpodron.forms.inputable.prototype
    );

    window.welpodron.forms.textarea = function (element) {
      this.control = element.querySelector('textarea');
      window.welpodron.forms.inputable.call(this, element);
    };

    window.welpodron.forms.textarea.prototype = Object.create(
      window.welpodron.forms.inputable.prototype
    );

    window.welpodron.forms.number = function (element) {
      this.control = element.querySelector('input[type="number"]');
      window.welpodron.forms.inputable.call(this, element);
    };

    window.welpodron.forms.number.prototype = Object.create(
      window.welpodron.forms.inputable.prototype
    );

    // TODO: change
    window.welpodron.forms.select = function (element) {
      this.control = element.querySelector('select');
      window.welpodron.forms.inputable.call(this, element);
    };

    window.welpodron.forms.select.prototype = Object.create(
      window.welpodron.forms.inputable.prototype
    );

    window.welpodron.forms.fieldset = function (element) {
      this.element = element;
      this.name = this.element.dataset.name
        ? this.element.dataset.name.trim()
        : null;
      this.fields = [];
      this.fieldsets = [];
      this.data = {};

      [...this.element.querySelectorAll('[data-fieldset]')]
        .filter(
          (el) => el.parentElement.closest('[data-fieldset]') === this.element
        )
        .forEach((el) => {
          this.fieldsets.push(new window.welpodron.forms.fieldset(el));
        });

      this.element
        .querySelectorAll(
          `[data-name][data-type][data-field]:not(:scope [data-fieldset] *)`
        )
        .forEach((element) => {
          let instance = window.welpodron.forms.factory.create(element);

          if (instance) {
            this.fields.push(instance);
          }

          instance = null;
        });
    };

    window.welpodron.forms.fieldset.prototype.getFieldsFlat = function () {
      let temp = [...this.fields];

      this.fieldsets.forEach((fieldset) => {
        temp = [...temp, fieldset.getFieldsFlat()];
      });

      return temp;
    };

    window.welpodron.forms.fieldset.prototype.getData = function () {
      this.fields.forEach((field) => {
        if (field.name && field.value) {
          this.data[field.name] = field.value;
        }
      });

      this.fieldsets.forEach((fieldset) => {
        if (fieldset.name) {
          const data = fieldset.getData();

          if (Object.keys(data).length) {
            this.data[fieldset.name] = data;
          }
        } else {
          this.data = { ...this.data, ...fieldset.getData() };
        }
      });

      return this.data;
    };

    window.welpodron.forms.agreement = function (element, config = {}) {
      this.DEFAULT_ACTIONS_SELECTOR = 'data-agreement-action';
      this.DEFAULT_ACTIONS_ARGS_SELECTOR = 'data-agreement-action-args';
      this.DEFAULT_INDICATORS_SELECTOR = 'data-agreement-indicator';

      this.element = element;
      this.id = element.id;
      this.active = this.element.getAttribute('data-active') !== null;

      this.controls = document.querySelectorAll(
        `[${this.DEFAULT_ACTIONS_SELECTOR}][data-agreement-id="${this.element.id}"]`
      );

      this.indicators = document.querySelectorAll(
        `[${this.DEFAULT_INDICATORS_SELECTOR}][data-agreement-id="${this.element.id}"]`
      );

      this.indicators.forEach((indicator) => {
        if (indicator.matches('[type="checkbox"]')) {
          indicator.checked = this.active;
        }
      });

      this.handleClick = (evt) => {
        evt.preventDefault();

        const currentTarget = evt.currentTarget;
        const action = currentTarget.getAttribute(
          this.DEFAULT_ACTIONS_SELECTOR
        );

        if (action === null || !this[action]) {
          return;
        }

        const args = currentTarget.getAttribute(
          this.DEFAULT_ACTIONS_ARGS_SELECTOR
        );

        this[action](args);
      };

      this.controls.forEach((control) => {
        control.removeEventListener('click', this.handleClick);
        control.addEventListener('click', this.handleClick);
      });
    };

    window.welpodron.forms.agreement.prototype.accept = function () {
      if (this.active) {
        return;
      }

      this.indicators.forEach((indicator) => {
        if (indicator.matches('[type="checkbox"]')) {
          indicator.checked = true;
        }

        // TODO: change to set attribute to indicator
      });

      this.element.setAttribute('data-active', '');
      this.active = true;
    };

    window.welpodron.forms.agreement.prototype.decline = function () {
      if (!this.active) {
        return;
      }

      this.indicators.forEach((indicator) => {
        if (indicator.matches('[type="checkbox"]')) {
          indicator.checked = false;
        }

        // TODO: change to set attribute to indicator
      });

      this.element.removeAttribute('data-active', '');
      this.active = false;
    };

    window.welpodron.forms.agreement.prototype.toggle = function () {
      this.active ? this.decline() : this.accept();
    };

    window.welpodron.forms.agreement.prototype.listen = function () {
      this.indicators.forEach((indicator) => {
        if (indicator.matches('[type="checkbox"]')) {
          indicator.checked = this.active;
        }
      });
    };

    window.welpodron.forms.form = function (element, config = {}) {
      this.element = element;
      this.id = element.id;

      this.init();

      this.action =
        this.element.dataset.submit === 'false' ? null : this.element.action;

      this.before = config.before || (() => void 0);

      this.handleSubmit = (evt) => {
        evt.preventDefault();
        // See issue with nested modals and disabled etc
        // So the solution here will be to pass this button to modal AND FORCE active element there?

        this.activate(false);

        // before data parsing
        this.before(this);

        if (this.action) {
          this.send();
        } else {
          this.activate(true);
        }
      };

      this.element.removeEventListener('submit', this.handleSubmit);
      this.element.addEventListener('submit', this.handleSubmit);
    };

    window.welpodron.forms.form.prototype.activate = function (status) {
      [...this.element.elements].forEach((element) => {
        // TODO: Find better solution
        // SO Here we will disable everything except form submit to fix focus issue with nested modals and disabled fields
        element.disabled = status === false;
      });
    };

    window.welpodron.forms.form.prototype.send = function () {
      if (this.submitting) {
        return;
      }

      this.submitting = true;

      try {
        if (window.grecaptcha && this.element.dataset.cap) {
          grecaptcha.ready(() => {
            grecaptcha
              .execute(this.element.dataset.cap, {
                action: 'submit'
              })
              .then((token) => {
                const data = new FormData();

                this.fieldsets.forEach((fieldset) => {
                  for (const [key, value] of Object.entries(
                    fieldset.getData()
                  )) {
                    data.set(key, value);
                  }
                });

                if (this.id) {
                  data.set('formid', this.id);
                }

                data.set('token', token);

                const request = new welpodron.request(this.action)
                  .post({ body: data })
                  .then((d) => {
                    if (d.status !== 'success') {
                      const firstFoundError = d.errors[0];

                      if (firstFoundError.code === 'FIELD_VALIDATION_ERROR') {
                        const { customData } = firstFoundError;

                        if (customData.field && customData.message) {
                          const field = this.fields.find(
                            (field) => field.name === customData.field
                          );

                          if (field) {
                            this.activate(true);
                            field.setValidity(customData.message);
                          }
                        }
                      } else {
                        if (
                          window.welpodron.modal &&
                          window.welpodron.core.uuid &&
                          window.welpodron.templater &&
                          window.welpodron.templater.renderString
                        ) {
                          const modalId = window.welpodron.core.uuid('modal_');
                          window.welpodron.templater.renderString(
                            `<div data-force data-once id="${modalId}" data-modal role="dialog" aria-modal="true"> 
                            <div class="tw-bg-white tw-rounded-lg tw-max-w-md tw-mx-auto tw-overflow-y-auto tw-max-h-full"> 
                                <div class="tw-p-6 grid-center tw-gap-6 tw-text-center"> 
                                <svg xmlns="http://www.w3.org/2000/svg" width="70" height="70" viewBox="0 0 20 20" fill="#dc2626">
                                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                  <p class="tw-text-2xl tw-font-bold">Упс, что-то пошло не так</p> 
                                  <p>При попытке сохранить вашу заявку что-то пошло не так :c Свяжитесь с нами другим любым доступным способом</p> 
                                </div> 
                                <div class="tw-bg-gray-100 tw-p-6"> 
                                  <button data-action="hide" data-modal-id="${modalId}" class="tw-py-4 tw-px-12 tw-font-bold tw-mt-2 tw-bg-gray-200 tw-w-full tw-rounded-full" type="button">Закрыть окно</button> 
                                </div> 
                            </div>
                          </div>`,
                            document.body,
                            {
                              before: (fragment) => {
                                fragment
                                  .querySelectorAll('[data-modal]')
                                  .forEach((modal) => {
                                    window.welpodron.modalsList.push(
                                      new window.welpodron.modal({
                                        dom: modal
                                      })
                                    );
                                  });
                              }
                            }
                          );
                          // TODO: change to another reset
                          // this.element.reset();
                        }
                      }
                    } else {
                      if (
                        window.welpodron.modal &&
                        window.welpodron.core.uuid &&
                        window.welpodron.templater &&
                        window.welpodron.templater.renderString
                      ) {
                        const modalId = window.welpodron.core.uuid('modal_');
                        window.welpodron.templater.renderString(
                          `<div data-force data-once id="${modalId}" data-modal role="dialog" aria-modal="true">
                        <div class="tw-bg-white tw-rounded-lg tw-max-w-md tw-mx-auto tw-overflow-y-auto tw-max-h-full">
                            <div class="tw-p-6 grid-center tw-gap-6 tw-text-center">
                              <svg xmlns="http://www.w3.org/2000/svg" width="70" height="70" viewBox="0 0 20 20" fill="#22c55e">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                              </svg>
                              <p class="tw-text-2xl tw-font-bold">Заявка успешно принята</p>
                              <p>Спасибо, мы обязательно с вами свяжемся в ближайшее время!</p>
                            </div>
                            <div class="tw-bg-gray-100 tw-p-6">
                              <button data-action="hide" data-modal-id="${modalId}" class="tw-py-4 tw-px-12 tw-font-bold tw-mt-2 tw-bg-gray-200 tw-w-full tw-rounded-full" type="button">Закрыть окно</button>
                            </div>
                        </div>
                      </div>`,
                          document.body,
                          {
                            before: (fragment) => {
                              fragment
                                .querySelectorAll('[data-modal]')
                                .forEach((modal) => {
                                  window.welpodron.modalsList.push(
                                    new window.welpodron.modal({
                                      dom: modal
                                    })
                                  );
                                });
                            }
                          }
                        );
                      }
                      this.fields.forEach((field) => {
                        field.reset();
                      });
                      // this.element.reset();
                    }
                  })
                  .catch((err) => {
                    console.error(err);
                    // show modal
                    if (
                      window.welpodron.modal &&
                      window.welpodron.core.uuid &&
                      window.welpodron.templater &&
                      window.welpodron.templater.renderString
                    ) {
                      const modalId = window.welpodron.core.uuid('modal_');
                      window.welpodron.templater.renderString(
                        `<div data-force data-once id="${modalId}" data-modal role="dialog" aria-modal="true"> 
                        <div class="tw-bg-white tw-rounded-lg tw-max-w-md tw-mx-auto tw-overflow-y-auto tw-max-h-full"> 
                            <div class="tw-p-6 grid-center tw-gap-6 tw-text-center"> 
                            <svg xmlns="http://www.w3.org/2000/svg" width="70" height="70" viewBox="0 0 20 20" fill="#dc2626">
                              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                              <p class="tw-text-2xl tw-font-bold">Упс, что-то пошло не так</p> 
                              <p>При попытке сохранить вашу заявку что-то пошло не так :c Свяжитесь с нами другим любым доступным способом</p> 
                            </div> 
                            <div class="tw-bg-gray-100 tw-p-6"> 
                              <button data-action="hide" data-modal-id="${modalId}" class="tw-py-4 tw-px-12 tw-font-bold tw-mt-2 tw-bg-gray-200 tw-w-full tw-rounded-full" type="button">Закрыть окно</button> 
                            </div> 
                        </div>
                      </div>`,
                        document.body,
                        {
                          before: (fragment) => {
                            fragment
                              .querySelectorAll('[data-modal]')
                              .forEach((modal) => {
                                window.welpodron.modalsList.push(
                                  new window.welpodron.modal({
                                    dom: modal
                                  })
                                );
                              });
                          }
                        }
                      );
                    }
                  });
              });
          });
        }
      } catch (error) {
      } finally {
        this.activate(true);
        this.submitting = false;
      }
      // WARNING! FINAL POINT BEFORE SENDING SUBMIT WILL NOT TRIGGER HANDLESUBMIT!
      // this.element.submit();
    };

    window.welpodron.forms.form.prototype.getData = function () {
      this.data = [];

      this.fieldsets.forEach((fieldset) => {
        console.log(fieldset.getData());
        // this.data.push(data);
      });

      return this.data;
    };

    window.welpodron.forms.form.prototype.getArray = function () {
      const data = [];
      this.fieldsets.forEach((fieldset) => {
        data.push(fieldset.getData());
      });
      return data;
    };

    window.welpodron.forms.form.prototype.init = function () {
      this.fieldsets = [];

      [...this.element.querySelectorAll('[data-fieldset]')]
        .filter((el) => !el.parentElement.closest('[data-fieldset]'))
        .forEach((el) => {
          this.fieldsets.push(new window.welpodron.forms.fieldset(el));
        });

      this.fields = [];

      this.fieldsets.forEach((fieldset) => {
        this.fields = [...this.fields, ...fieldset.getFieldsFlat()];
      });

      const agreement = this.element.querySelector('[data-agreement]');

      if (agreement) {
        this.agreement = new window.welpodron.forms.agreement(agreement);
      }
    };

    window.welpodron.formsList = [];

    document.querySelectorAll('[data-form]').forEach((form) => {
      window.welpodron.formsList.push(new window.welpodron.forms.form(form));
    });
  }
})(window);
