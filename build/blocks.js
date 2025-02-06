/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!***********************!*\
  !*** ./src/blocks.js ***!
  \***********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);



const {
  registerBlockType
} = wp.blocks;
const blocks = window.genbBlocks.blocks || [];
blocks.forEach(block => {
  registerBlockType(`gen-blocks/${block.name}`, {
    title: block.metadata?.title || block.name.charAt(0).toUpperCase() + block.name.slice(1),
    description: block.metadata?.description || '',
    icon: block.metadata?.icon || 'block-default',
    category: block.metadata?.category || 'gen-blocks',
    attributes: block.attributes,
    edit: function (props) {
      const {
        attributes,
        setAttributes
      } = props;
      const blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.useBlockProps)();

      // Initialize template on first render
      if (!attributes._template && block.template) {
        setAttributes({
          _template: block.template
        });
      }

      // Add inspector controls with edit link
      const inspectorControls = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.InspectorControls, {}, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
        title: 'Block Settings'
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
        variant: 'secondary',
        href: `${window.genbBlocks.adminUrl}admin.php?page=gen-blocks-edit&block=${block.name}`,
        target: '_blank',
        icon: 'edit',
        className: 'edit-block-button'
      }, 'Edit Block Template')));
      const renderEditable = (name, config) => {
        if (!name || !config) {
          console.warn('Invalid config for editable field:', name, config);
          return null;
        }

        // Parse the template to get the element
        const parser = new DOMParser();
        const doc = parser.parseFromString(attributes._template, 'text/html');
        const templateEl = doc.querySelector(`[zen-edit="${name}"]`);
        const commonProps = {
          key: name,
          identifier: name,
          value: (attributes[name] || '').trim(),
          onChange: value => {
            setAttributes({
              [name]: value.trim()
            });
          },
          placeholder: config.default || `Enter ${name}...`,
          keepPlaceholderOnFocus: true
        };

        // Process zen-class if present
        const zenClass = templateEl?.getAttribute('zen-class');
        if (zenClass) {
          const processedClass = zenClass.replace(/\{\{(.+?)\}\}/g, (match, expr) => {
            if (expr.includes('?')) {
              const [condition, rest] = expr.split('?');
              const [trueVal, falseVal] = (rest || '').split(':');
              const conditionKey = (condition || '').trim();
              const trueValue = (trueVal || '').trim().replace(/['"]/g, '');
              const falseValue = (falseVal || '').trim().replace(/['"]/g, '');
              return attributes[conditionKey] === true ? trueValue : falseValue;
            }
            return attributes[expr.trim()] || '';
          });
          commonProps.className = `${commonProps.className} ${processedClass}`.trim();
        }

        // Get element type from template or config
        const elementType = templateEl?.getAttribute('zen-type') || config.type_info;
        switch (elementType) {
          case 'url':
            {
              const [isURLPickerOpen, setIsURLPickerOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);

              // Get original classes from template element
              const templateClasses = templateEl?.className || '';
              const combinedClasses = [templateClasses, commonProps.className, `zen-${name}`].filter(Boolean).join(' ');

              // Get values from attributes
              const textValue = attributes[`${name}_text`] || attributes[name] || '';
              const urlValue = attributes[`${name}_url`] || '';
              const targetBlank = attributes[`${name}_target`] === '_blank';
              const rel = attributes[`${name}_rel`] || '';
              return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)('div', {
                className: 'wp-block-gen-blocks-url-wrapper'
              }, [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)('div', {
                className: 'block-editor-rich-text__container'
              }, [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.RichText, {
                ...commonProps,
                key: 'text',
                tagName: 'a',
                className: `block-editor-rich-text__editable ${combinedClasses}`,
                value: textValue,
                onChange: value => {
                  setAttributes({
                    [`${name}_text`]: value,
                    [name]: value // Keep main attribute in sync
                  });
                },
                allowedFormats: ['core/bold', 'core/italic']
              }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
                key: 'url-button',
                icon: 'admin-links',
                className: 'block-editor-rich-text__link-button',
                onClick: () => setIsURLPickerOpen(true)
              })]), isURLPickerOpen && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Popover, {
                key: 'popover',
                position: 'bottom center',
                onClose: () => setIsURLPickerOpen(false),
                focusOnMount: 'firstElement'
              }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)('div', {
                style: {
                  padding: '12px',
                  minWidth: '360px'
                }
              }, [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.__experimentalLinkControl, {
                key: 'link-control',
                value: {
                  url: urlValue,
                  opensInNewTab: targetBlank
                },
                onChange: ({
                  url,
                  opensInNewTab
                }) => {
                  setAttributes({
                    [`${name}_url`]: url || '',
                    [`${name}_target`]: opensInNewTab ? '_blank' : '',
                    [`${name}_rel`]: opensInNewTab ? 'noopener noreferrer' : ''
                  });
                },
                settings: [{
                  id: 'opensInNewTab',
                  title: 'Open in new tab'
                }]
              })]))]);
            }
          case 'image':
            {
              const [isModalOpen, setModalOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
              const imageUrl = attributes[name];
              if (!imageUrl) {
                return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.MediaPlaceholder, {
                  key: name,
                  labels: {
                    title: 'Add ' + name,
                    instructions: 'Upload or select image'
                  },
                  onSelect: media => {
                    setAttributes({
                      [name]: media.url
                    });
                  },
                  accept: "image/*",
                  allowedTypes: ['image']
                });
              }
              const element = document.createElement('div');
              element.innerHTML = block.template;
              const imgEl = element.querySelector(`[zen-edit="${name}"]`);
              return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Fragment, {
                key: name
              }, [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)('img', {
                key: 'image',
                src: imageUrl,
                className: imgEl?.className || '',
                alt: imgEl?.getAttribute('alt'),
                width: imgEl?.getAttribute('width'),
                height: imgEl?.getAttribute('height'),
                onClick: () => setModalOpen(true),
                style: {
                  cursor: 'pointer'
                }
              }), isModalOpen && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Modal, {
                title: 'Edit image',
                onRequestClose: () => setModalOpen(false),
                className: 'zen-image-modal'
              }, [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)('div', {
                key: 'modal-content',
                style: {
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '16px'
                }
              }, [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)('div', {
                key: 'preview',
                className: 'zen-image-preview',
                style: {
                  maxWidth: '400px',
                  margin: '0 auto'
                }
              }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)('img', {
                src: imageUrl,
                alt: '',
                style: {
                  width: '100%',
                  height: 'auto',
                  display: 'block',
                  borderRadius: '4px'
                }
              })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)('div', {
                key: 'buttons',
                style: {
                  display: 'flex',
                  gap: '8px',
                  justifyContent: 'flex-end'
                }
              }, [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.MediaUploadCheck, {
                key: 'upload-check'
              }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.MediaUpload, {
                onSelect: media => {
                  setAttributes({
                    [name]: media.url
                  });
                  setModalOpen(false);
                },
                allowedTypes: ['image'],
                render: ({
                  open
                }) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
                  onClick: open,
                  variant: 'secondary'
                }, 'Replace')
              })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
                key: 'remove',
                variant: 'secondary',
                isDestructive: true,
                onClick: () => {
                  setAttributes({
                    [name]: ''
                  });
                  setModalOpen(false);
                }
              }, 'Remove')])])])]);
            }
          default:
            // Get original classes from template element
            const templateClasses = templateEl?.className || '';
            const combinedClasses = [templateClasses, commonProps.className, `zen-${name}`].filter(Boolean).join(' ');
            return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.RichText, {
              ...commonProps,
              tagName: templateEl?.tagName.toLowerCase() || 'span',
              className: combinedClasses
            });
        }
      };
      const tempContainer = document.createElement('div');
      tempContainer.innerHTML = attributes._template || block.template;
      const replaceEditableElements = node => {
        const elements = [];
        for (let child of node.children) {
          if (child.hasAttribute('zen-edit')) {
            const name = child.getAttribute('zen-edit');
            const config = block.attributes[name];
            if (!config) {
              console.warn(`No config found for editable field: ${name}`);
              continue;
            }
            const element = renderEditable(name, config);
            if (element) {
              elements.push(element);
            }
          } else {
            // Handle non-editable elements
            const tagName = child.tagName.toLowerCase();
            const props = {};

            // Copy attributes
            for (let attr of child.attributes) {
              let name = attr.name;
              let value = attr.value;

              // Convert class to className
              if (name === 'class') {
                name = 'className';
              }
              props[name] = value;
            }

            // Handle innerHTML for leaf nodes
            if (child.children.length === 0 && child.innerHTML) {
              props.dangerouslySetInnerHTML = {
                __html: child.innerHTML
              };
            }
            const element = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)(tagName, props, child.children.length > 0 ? replaceEditableElements(child) : undefined);
            elements.push(element);
          }
        }
        return elements;
      };
      const children = replaceEditableElements(tempContainer);
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)('div', blockProps, [inspectorControls, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createElement)('div', {}, children)]);
    },
    save: function () {
      return null; // Use server-side rendering
    }
  });
});
console.log('GENB: Blocks data:', genbBlocks.blocks);
})();

/******/ })()
;
//# sourceMappingURL=blocks.js.map