<?php
/**
 * Block Registrar Handler
 *
 * @category WordPress
 * @package  GENB
 */

namespace GENB;

/**
 * Class Block_Registrar
 * 
 * @category WordPress
 * @package  GENB
 */
class Block_Registrar
{
    /**
     * Block storage instance
     *
     * @var Block_Storage
     */
    private $storage;

    /**
     * Template parser instance
     *
     * @var Template_Parser
     */
    private $template_parser;

    /**
     * Blocks data
     *
     * @var array
     */
    private $blocks = [];

    /**
     * Constructor
     *
     * @param Block_Storage   $storage        Block storage instance
     * @param Template_Parser $template_parser Template parser instance
     */
    public function __construct($storage, $template_parser)
    {
        $this->storage = $storage;
        $this->template_parser = $template_parser;
        $this->load_blocks();
    }

    /**
     * Load blocks from database
     *
     * @return void
     */
    private function load_blocks()
    {
        $stored_blocks = $this->storage->getBlocks();
        
        foreach ($stored_blocks as $block) {
            if (empty($block->template)) {
                continue;
            }

            // Parse template for attributes and metadata
            $template_data = $this->template_parser->parse_string($block->template);
            
            $this->blocks[] = (object)[
                'name' => $block->name,
                'template' => $block->template,
                'attributes' => $template_data['attributes'],
                'metadata' => [
                    'title' => $block->title,
                    'description' => $block->description,
                    'icon' => $block->icon,
                    'category' => $block->category
                ]
            ];
        }
    }

    /**
     * Initialize registrar
     *
     * @return void
     */
    public function init()
    {
        // Register blocks on WordPress init
        \add_action('init', [$this, 'register_blocks']);
        
        // Register block category
        \add_filter('block_categories_all', [$this, 'register_block_category']);
        
        // Enqueue block editor assets
        \add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
    }

    /**
     * Register block category
     *
     * @param array $categories Existing block categories
     * @return array Modified block categories
     */
    public function register_block_category($categories)
    {
        return array_merge($categories, [
            [
                'slug' => 'gen-blocks',
                'title' => 'Generated Blocks',
                'icon' => 'layout'
            ]
        ]);
    }

    /**
     * Enqueue block editor assets
     *
     * @return void
     */
    public function enqueue_editor_assets()
    {
        $asset_file = include(GENB_PLUGIN_DIR . 'build/blocks.asset.php');
        
        wp_enqueue_script(
            'gen-blocks',
            GENB_PLUGIN_URL . 'build/blocks.js',
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );

        // Get all blocks data
        $blocks_data = array_values(array_map(function($block) {
            return [
                'name' => $block->name,
                'template' => $block->template,
                'attributes' => $block->attributes,
                'metadata' => $block->metadata
            ];
        }, $this->get_blocks()));

        // Localize script with blocks data
        wp_localize_script('gen-blocks', 'genbBlocks', [
            'blocks' => $blocks_data,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url(),
            'restUrl' => get_rest_url(null, 'gen-blocks/v1'),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
        
        \wp_enqueue_style(
            'gen-blocks-editor',
            GENB_PLUGIN_URL . 'build/editor.css',
            ['wp-edit-blocks'],
            GENB_VERSION
        );
    }

    /**
     * Register all blocks
     *
     * @return void
     */
    public function register_blocks()
    {
        // Register default block first
        \register_block_type('gen-blocks/default', [
            'editor_script' => 'gen-blocks-default',
            'editor_style' => 'gen-blocks-editor'
        ]);

        // Register other blocks
        foreach ($this->blocks as $block) {
            $this->_registerBlock($block);
        }
    }

    private function _registerBlock($block)
    {
        \register_block_type('gen-blocks/' . $block->name, [
            'apiVersion' => 2,
            'attributes' => array_merge($block->attributes, [
                'align' => [
                    'type' => 'string',
                    'default' => ''
                ]
            ]),
            'supports' => array_merge([
                'html' => false,
                'anchor' => true,
                'customClassName' => true
            ], [
                'align' => ['wide', 'full']
            ]),
            'render_callback' => function($attributes) use ($block) {
                return $this->template_parser->render_string($block->template, $attributes);
            }
        ]);
    }

    /**
     * Get block by name
     *
     * @param string $name Block name
     * @return object|null Block data or null if not found
     */
    public function get_block($name)
    {
        foreach ($this->blocks as $block) {
            if ($block->name === $name) {
                return $block;
            }
        }
        return null;
    }

    /**
     * Get all blocks
     *
     * @return array
     */
    public function get_blocks()
    {
        return $this->blocks;
    }
} 