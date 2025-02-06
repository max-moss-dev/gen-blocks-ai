import { 
    useBlockProps, 
    MediaPlaceholder,
    RichText,
    MediaUpload,
    MediaUploadCheck,
    __experimentalLinkControl as LinkControl,
    InspectorControls
} from '@wordpress/block-editor';
import { Button, Modal, Popover, PanelBody } from '@wordpress/components';
import { createElement, useState, Fragment } from '@wordpress/element';

const { registerBlockType } = wp.blocks;

const blocks = window.genbBlocks.blocks || [];

blocks.forEach(block => {
    registerBlockType(`gen-blocks/${block.name}`, {
        title: block.metadata?.title || (block.name.charAt(0).toUpperCase() + block.name.slice(1)),
        description: block.metadata?.description || '',
        icon: block.metadata?.icon || 'block-default',
        category: block.metadata?.category || 'gen-blocks',
        attributes: block.attributes,
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();

            // Initialize template on first render
            if (!attributes._template && block.template) {
                setAttributes({ _template: block.template });
            }

            // Add inspector controls with edit link
            const inspectorControls = createElement(InspectorControls, {},
                createElement(PanelBody, { title: 'Block Settings' },
                    createElement(Button, {
                        variant: 'secondary',
                        href: `${window.genbBlocks.adminUrl}admin.php?page=gen-blocks-edit&block=${block.name}`,
                        target: '_blank',
                        icon: 'edit',
                        className: 'edit-block-button'
                    }, 'Edit Block Template')
                )
            );

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
                    onChange: (value) => {
                        setAttributes({ [name]: value.trim() });
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

                switch(elementType) {
                    case 'url': {
                        const [isURLPickerOpen, setIsURLPickerOpen] = useState(false);
                        
                        // Get original classes from template element
                        const templateClasses = templateEl?.className || '';
                        const combinedClasses = [templateClasses, commonProps.className, `zen-${name}`]
                            .filter(Boolean)
                            .join(' ');

                        // Get values from attributes
                        const textValue = attributes[`${name}_text`] || attributes[name] || '';
                        const urlValue = attributes[`${name}_url`] || '';
                        const targetBlank = attributes[`${name}_target`] === '_blank';
                        const rel = attributes[`${name}_rel`] || '';

                        return createElement('div', {
                            className: 'wp-block-gen-blocks-url-wrapper'
                        }, [
                            createElement('div', {
                                className: 'block-editor-rich-text__container'
                            }, [
                                createElement(RichText, {
                                    ...commonProps,
                                    key: 'text',
                                    tagName: 'a',
                                    className: `block-editor-rich-text__editable ${combinedClasses}`,
                                    value: textValue,
                                    onChange: (value) => {
                                        setAttributes({ 
                                            [`${name}_text`]: value,
                                            [name]: value // Keep main attribute in sync
                                        });
                                    },
                                    allowedFormats: ['core/bold', 'core/italic']
                                }),
                                createElement(Button, {
                                    key: 'url-button',
                                    icon: 'admin-links',
                                    className: 'block-editor-rich-text__link-button',
                                    onClick: () => setIsURLPickerOpen(true)
                                })
                            ]),
                            isURLPickerOpen && createElement(Popover, {
                                key: 'popover',
                                position: 'bottom center',
                                onClose: () => setIsURLPickerOpen(false),
                                focusOnMount: 'firstElement'
                            }, 
                                createElement('div', {
                                    style: { padding: '12px', minWidth: '360px' }
                                }, [
                                    createElement(LinkControl, {
                                        key: 'link-control',
                                        value: {
                                            url: urlValue,
                                            opensInNewTab: targetBlank
                                        },
                                        onChange: ({ url, opensInNewTab }) => {
                                            setAttributes({
                                                [`${name}_url`]: url || '',
                                                [`${name}_target`]: opensInNewTab ? '_blank' : '',
                                                [`${name}_rel`]: opensInNewTab ? 'noopener noreferrer' : ''
                                            });
                                        },
                                        settings: [
                                            {
                                                id: 'opensInNewTab',
                                                title: 'Open in new tab'
                                            }
                                        ]
                                    })
                                ])
                            )
                        ]);
                    }

                    case 'image': {
                        const [isModalOpen, setModalOpen] = useState(false);
                        const imageUrl = attributes[name];

                        if (!imageUrl) {
                            return createElement(MediaPlaceholder, {
                                key: name,
                                labels: { 
                                    title: 'Add ' + name,
                                    instructions: 'Upload or select image'
                                },
                                onSelect: (media) => {
                                    setAttributes({ [name]: media.url });
                                },
                                accept: "image/*",
                                allowedTypes: ['image']
                            });
                        }

                        const element = document.createElement('div');
                        element.innerHTML = block.template;
                        const imgEl = element.querySelector(`[zen-edit="${name}"]`);

                        return createElement(Fragment, { key: name }, [
                            createElement('img', {
                                key: 'image',
                                src: imageUrl,
                                className: imgEl?.className || '',
                                alt: imgEl?.getAttribute('alt'),
                                width: imgEl?.getAttribute('width'),
                                height: imgEl?.getAttribute('height'),
                                onClick: () => setModalOpen(true),
                                style: { cursor: 'pointer' }
                            }),
                            isModalOpen && createElement(Modal, {
                                title: 'Edit image',
                                onRequestClose: () => setModalOpen(false),
                                className: 'zen-image-modal'
                            }, [
                                createElement('div', {
                                    key: 'modal-content',
                                    style: {
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: '16px'
                                    }
                                }, [
                                    createElement('div', {
                                        key: 'preview',
                                        className: 'zen-image-preview',
                                        style: {
                                            maxWidth: '400px',
                                            margin: '0 auto'
                                        }
                                    }, 
                                        createElement('img', {
                                            src: imageUrl,
                                            alt: '',
                                            style: {
                                                width: '100%',
                                                height: 'auto',
                                                display: 'block',
                                                borderRadius: '4px'
                                            }
                                        })
                                    ),
                                    createElement('div', {
                                        key: 'buttons',
                                        style: {
                                            display: 'flex',
                                            gap: '8px',
                                            justifyContent: 'flex-end'
                                        }
                                    }, [
                                        createElement(MediaUploadCheck, { key: 'upload-check' },
                                            createElement(MediaUpload, {
                                                onSelect: (media) => {
                                                    setAttributes({ [name]: media.url });
                                                    setModalOpen(false);
                                                },
                                                allowedTypes: ['image'],
                                                render: ({ open }) => createElement(Button, {
                                                    onClick: open,
                                                    variant: 'secondary'
                                                }, 'Replace')
                                            })
                                        ),
                                        createElement(Button, {
                                            key: 'remove',
                                            variant: 'secondary',
                                            isDestructive: true,
                                            onClick: () => {
                                                setAttributes({ [name]: '' });
                                                setModalOpen(false);
                                            }
                                        }, 'Remove')
                                    ])
                                ])
                            ])
                        ]);
                    }

                    default:
                        // Get original classes from template element
                        const templateClasses = templateEl?.className || '';
                        const combinedClasses = [templateClasses, commonProps.className, `zen-${name}`]
                            .filter(Boolean)
                            .join(' ');

                        return createElement(RichText, {
                            ...commonProps,
                            tagName: templateEl?.tagName.toLowerCase() || 'span',
                            className: combinedClasses
                        });
                }
            };

            const tempContainer = document.createElement('div');
            tempContainer.innerHTML = attributes._template || block.template;

            const replaceEditableElements = (node) => {
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
                            props.dangerouslySetInnerHTML = { __html: child.innerHTML };
                        }

                        const element = createElement(
                            tagName,
                            props,
                            child.children.length > 0 ? replaceEditableElements(child) : undefined
                        );
                        elements.push(element);
                    }
                }
                
                return elements;
            };

            const children = replaceEditableElements(tempContainer);
            return createElement('div', blockProps, [
                inspectorControls,
                createElement('div', {}, children)
            ]);
        },

        save: function() {
            return null; // Use server-side rendering
        }
    });
});

console.log('GENB: Blocks data:', genbBlocks.blocks);