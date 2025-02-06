<?php

namespace GENB;

use DOMDocument;
use DOMElement;

class Template_Parser {
    private $template_html = '';
    private $block_metadata = array();

    private function minify_html($html) {
        // Remove comments
        $html = preg_replace('/<!--(.|\s)*?-->/', '', $html);
        
        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);
        
        // Remove whitespace around template attributes
        $html = preg_replace('/\s+(zen-[a-z-]+)=/', ' $1=', $html);
        
        // Collapse multiple spaces to single space
        $html = preg_replace('/\s+/', ' ', $html);
        
        return trim($html);
    }

    public function parse_string($template_html) {
        // Minify template before parsing
        $this->template_html = $this->minify_html($template_html);
        
        // Extract metadata if present in PHP comments
        if (preg_match('/\/\*\s*zen\s*=\s*(\{.*?\})\s*\*\//s', $this->template_html, $matches)) {
            $this->block_metadata = json_decode($matches[1], true) ?: [];
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        
        // Preserve SVG namespace
        if (strpos($this->template_html, '<svg') !== false) {
            $this->template_html = '<?xml version="1.0" encoding="UTF-8"?>' . $this->template_html;
        }
        
        $dom->loadHTML($this->template_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE);
        libxml_clear_errors();
        
        $attributes = array();
        $elements = $dom->getElementsByTagName('*');

        // Add controls from metadata to attributes first
        if (!empty($this->block_metadata['controls'])) {
            foreach ($this->block_metadata['controls'] as $name => $config) {
                if ($config['type'] === 'boolean') {
                    $attributes[$name] = [
                        'type' => 'boolean',
                        'default' => isset($config['default']) ? 
                            filter_var($config['default'], FILTER_VALIDATE_BOOLEAN) : false,
                        'type_info' => 'control'
                    ];
                } else {
                    $attributes[$name] = [
                        'type' => $config['type'],
                        'default' => $config['default'] ?? '',
                        'type_info' => 'control'
                    ];
                }
            }
        }

        // Process zen-edit elements
        foreach ($elements as $element) {
            if ($element instanceof DOMElement && $element->hasAttribute('zen-edit')) {
                $field_name = $element->getAttribute('zen-edit');
                // Skip if already defined in controls
                if (isset($attributes[$field_name])) {
                    continue;
                }
                
                $field_type = $element->getAttribute('zen-type') ?: 
                    ($element->tagName === 'img' ? 'image' : 'text');

                $default_content = $this->get_element_content($element, $field_type);
                
                $attribute_config = $this->get_attribute_config(
                    $field_type,
                    $default_content,
                    $field_name,
                    $element
                );

                // Merge attribute config into attributes array
                $attributes = array_merge($attributes, $attribute_config);
            }
        }

        $attributes['_template'] = [
            'type' => 'string',
            'default' => $this->template_html,
            'type_info' => 'template'
        ];

        return [
            'attributes' => $attributes,
            'metadata' => $this->block_metadata
        ];
    }

    public function render_string($template_html, $attributes) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($this->minify_html($attributes['_template'] ?? $template_html), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Update editable elements with their values
        $xpath = new \DOMXPath($dom);
        foreach ($attributes as $name => $value) {
            if ($name === '_template' || empty($value)) continue;
            
            // Skip URL suffix attributes as they're handled with the main attribute
            if (preg_match('/_(?:text|url|target|rel)$/', $name)) continue;

            // Find element with zen-edit attribute
            $elements = $xpath->query("//*[@zen-edit='$name']");
            if ($elements && $elements->length > 0) {
                $element = $elements->item(0);
                
                if ($element->tagName === 'img') {
                    $element->setAttribute('src', trim($value));
                } elseif ($element->getAttribute('zen-type') === 'url') {
                    // Set href from the URL attribute
                    $url = $attributes["{$name}_url"] ?? '';
                    if ($url) {
                        $element->setAttribute('href', trim($url));
                    }
                    
                    // Set target and rel if specified
                    $target = $attributes["{$name}_target"] ?? '';
                    if ($target) {
                        $element->setAttribute('target', trim($target));
                    } else {
                        $element->removeAttribute('target');
                    }

                    $rel = $attributes["{$name}_rel"] ?? '';
                    if ($rel) {
                        $element->setAttribute('rel', trim($rel));
                    } else {
                        $element->removeAttribute('rel');
                    }
                    
                    // Set text content from the text attribute
                    $text = $attributes["{$name}_text"] ?? $value;
                    if ($text) {
                        // Create a temporary document for the HTML content
                        $temp = new DOMDocument();
                        libxml_use_internal_errors(true);
                        $temp->loadHTML('<div>' . trim($text) . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                        libxml_clear_errors();
                        
                        // Import and replace content
                        $imported = $dom->importNode($temp->documentElement, true);
                        while ($element->firstChild) {
                            $element->removeChild($element->firstChild);
                        }
                        while ($imported->firstChild) {
                            $element->appendChild($dom->importNode($imported->firstChild, true));
                        }
                    }
                } else {
                    // Trim the value while preserving HTML
                    $value = trim($value);
                    
                    // Create a temporary document for the HTML content
                    $temp = new DOMDocument();
                    libxml_use_internal_errors(true);
                    $temp->loadHTML('<div>' . trim($value) . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    libxml_clear_errors();
                    
                    // Import and replace content
                    $imported = $dom->importNode($temp->documentElement, true);
                    while ($element->firstChild) {
                        $element->removeChild($element->firstChild);
                    }
                    while ($imported->firstChild) {
                        $element->appendChild($dom->importNode($imported->firstChild, true));
                    }
                }

                // Remove zen- attributes as they're not needed in frontend
                $element->removeAttribute('zen-edit');
                $element->removeAttribute('zen-type');
            }
        }

        // Remove any remaining zen- attributes from all elements
        $all_elements = $xpath->query('//*[@*[starts-with(name(), "zen-")]]');
        foreach ($all_elements as $element) {
            foreach ($element->attributes as $attribute) {
                if (strpos($attribute->nodeName, 'zen-') === 0) {
                    $element->removeAttribute($attribute->nodeName);
                }
            }
        }

        // Get the rendered HTML
        $html = '';
        foreach ($dom->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        $align_class = !empty($attributes['align']) ? ' align' . $attributes['align'] : '';
        $block_name = !empty($attributes['blockName']) ? str_replace('gen-blocks/', '', $attributes['blockName']) : '';
        $block_class = $block_name ? ' wp-block-gen-blocks-' . str_replace('_', '-', $block_name) : '';

        return sprintf(
            '<div class="%s%s">%s</div>',
            trim($block_class),
            $align_class,
            $html
        );
    }

    private function get_element_content($element, $field_type) {
        if ($field_type === 'image') {
            return $element->getAttribute('src') ?: '';
        } elseif ($field_type === 'url') {
            // For URL type, prefer text content
            return $element->textContent ?: '';
        }
        return $element->textContent ?: '';
    }

    private function get_attribute_config($field_type, $default_content, $field_name, $element) {
        if ($field_type === 'url') {
            return [
                $field_name => [
                    'type' => 'string',
                    'default' => $default_content,
                    'type_info' => 'url'  // Keep original type for main attribute
                ],
                "{$field_name}_text" => [
                    'type' => 'string',
                    'default' => $default_content,
                    'type_info' => 'url'
                ],
                "{$field_name}_url" => [
                    'type' => 'string',
                    'default' => $element->getAttribute('href') ?: '',
                    'type_info' => 'url'
                ],
                "{$field_name}_target" => [
                    'type' => 'string',
                    'default' => $element->getAttribute('target') ?: '',
                    'type_info' => 'url'
                ],
                "{$field_name}_rel" => [
                    'type' => 'string',
                    'default' => $element->getAttribute('rel') ?: '',
                    'type_info' => 'url'
                ]
            ];
        }
        
        return [
            $field_name => [
                'type' => 'string',
                'default' => $default_content,
                'type_info' => $field_type
            ]
        ];
    }
}
