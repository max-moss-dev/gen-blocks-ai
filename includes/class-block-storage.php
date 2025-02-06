<?php
/**
 * Block Storage Handler
 *
 * @category WordPress
 * @package  GENB
 */

namespace GENB;

/**
 * Class Block_Storage
 * 
 * @category WordPress
 * @package  GENB
 */
class Block_Storage
{
    /**
     * Initialize storage
     *
     * @return void
     */
    public function init()
    {
        \register_activation_hook(GENB_PLUGIN_FILE, [$this, 'createTable']);
        $this->createTable(); // Temporary call to create table immediately
    }

    /**
     * Create blocks table
     *
     * @return void
     */
    public function createTable()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gen_blocks';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            title varchar(255) NOT NULL,
            template longtext NOT NULL,
            description text DEFAULT NULL,
            category varchar(50) DEFAULT 'gen-blocks',
            icon varchar(50) DEFAULT 'block-default',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            modified_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Check if columns exist and add them if they don't
        $columns = $wpdb->get_col("DESC {$table}");
        
        if (!in_array('description', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN description text DEFAULT NULL AFTER template");
        }
        if (!in_array('category', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN category varchar(50) DEFAULT 'gen-blocks' AFTER description");
        }
        if (!in_array('icon', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN icon varchar(50) DEFAULT 'block-default' AFTER category");
        }
        
        // Remove content column if it exists (we'll store content in post meta)
        if (in_array('content', $columns)) {
            $wpdb->query("ALTER TABLE {$table} DROP COLUMN content");
        }
    }

    /**
     * Sanitize template content
     *
     * @param string $template Template content
     * @return string Sanitized template
     */
    public function sanitize_template($template) {
        if (empty($template)) {
            return '';
        }

        // Remove potential PHP code execution
        $template = preg_replace('/<\?(?:php|=)?|\?>/', '', $template);

        // Get WordPress allowed HTML tags
        $allowed_html = wp_kses_allowed_html('post');
        
        // Add style and script tag support
        $allowed_html['style'] = array(
            'type' => true,
            'id' => true,
            'class' => true,
        );
        
        // Add link tag support
        $allowed_html['link'] = array(
            'href' => true,
            'rel' => true,
            'type' => true,
            'id' => true,
            'class' => true,
            'media' => true,
            'crossorigin' => true,
            'integrity' => true,
            'referrerpolicy' => true,
            'sizes' => true,
            'title' => true,
            'disabled' => true,
            'as' => true,
        );
        
        // Add SVG support
        $allowed_html['svg'] = array(
            'xmlns' => true,
            'width' => true,
            'height' => true,
            'viewbox' => true,
            'version' => true,
            'class' => true,
            'aria-hidden' => true,
            'role' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true
        );
        
        $allowed_html['path'] = array(
            'd' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true
        );
        
        $allowed_html['circle'] = array(
            'cx' => true,
            'cy' => true,
            'r' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true
        );
        
        $allowed_html['rect'] = array(
            'x' => true,
            'y' => true,
            'width' => true,
            'height' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true
        );
        
        $allowed_html['script'] = array(
            'type' => true,
            'src' => true,
            'id' => true,
            'class' => true,
            'defer' => true,
            'async' => true,
            'zen-edit' => true,
            'zen-type' => true,
            'data-*' => true,
        );

        // Add canvas support
        $allowed_html['canvas'] = array(
            'id' => true,
            'class' => true,
            'width' => true, 
            'height' => true,
            'style' => true,
            'data-*' => true,
            'zen-edit' => true,
            'zen-type' => true
        );

        // Add zen attributes to all existing tags
        foreach ($allowed_html as $tag => $attributes) {
            $allowed_html[$tag]['zen-edit'] = true;
            $allowed_html[$tag]['zen-type'] = true;
            $allowed_html[$tag]['data-*'] = true;
        }

        // First pass: Extract style and script tags
        $special_tags = array();
        $template = preg_replace_callback('/<(style|script)\b[^>]*>.*?<\/\1>/is', function($matches) use (&$special_tags) {
            $placeholder = '<!--TAG-' . md5($matches[0]) . '-->';
            $special_tags[$placeholder] = $matches[0];
            return $placeholder;
        }, $template);

        // Second pass: Sanitize main content
        $template = wp_kses($template, $allowed_html);

        // Third pass: Restore style and script tags
        foreach ($special_tags as $placeholder => $tag) {
            $template = str_replace($placeholder, $tag, $template);
        }

        return $template;
    }

    /**
     * Save block to database
     *
     * @param string|array $name    Block name or block data array
     * @param string      $title   Block title
     * @param string      $template Block template
     * @return bool|int
     */
    public function saveBlock($name, $title = '', $template = '')
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gen_blocks';

        // Handle array input (new format)
        if (is_array($name)) {
            $blockData = $name;
            $data = [
                'name' => $blockData['name'],
                'title' => $blockData['title'],
                'template' => $this->sanitize_template($blockData['template']),
                'description' => $blockData['description'] ?? '',
                'category' => $blockData['category'] ?? 'gen-blocks',
                'icon' => $blockData['icon'] ?? 'block-default',
            ];
        } else {
            // Handle string input (old format)
            $template = wp_unslash($template);
            $data = [
                'name' => $name,
                'title' => $title,
                'template' => $this->sanitize_template($template),
            ];
        }

        return $wpdb->replace(
            $table,
            $data,
            array_fill(0, count($data), '%s')
        );
    }

    /**
     * Get block by name
     *
     * @param string $name Block name
     * @return object|null
     */
    public function getBlock($name)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gen_blocks';
        
        $block = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE name = %s",
                $name
            )
        );

        if ($block) {
            $block->template = wp_unslash($block->template);
        }

        return $block;
    }

    /**
     * Get a single block by name
     *
     * @param string $name Block name
     * @return object|null Block data or null if not found
     */
    public function getSingleBlock($name)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gen_blocks';

        $block = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE name = %s",
                $name
            )
        );

        if ($block) {
            $block->template = wp_unslash($block->template);
        }

        return $block;
    }

    /**
     * Get all blocks
     *
     * @return array
     */
    public function getBlocks()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gen_blocks';

        $blocks = $wpdb->get_results(
            "SELECT * FROM $table ORDER BY created_at DESC"
        );

        // Unescape content for each block
        foreach ($blocks as $block) {
            $block->template = wp_unslash($block->template);
        }

        return $blocks;
    }

    /**
     * Delete block
     *
     * @param string $name Block name
     * @return bool
     */
    public function deleteBlock($name)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gen_blocks';
        
        return $wpdb->delete(
            $table,
            ['name' => $name],
            ['%s']
        );
    }

    /**
     * Update block
     *
     * @param string $name      Block name
     * @param array  $blockData Block data
     * @return bool|int
     */
    public function updateBlock($name, $blockData)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gen_blocks';
        
        // Prepare data for update
        $data = [
            'title' => $blockData['title'],
            'template' => $this->sanitize_template($blockData['template']),
            'description' => $blockData['description'] ?? '',
            'category' => $blockData['category'] ?? 'gen-blocks',
            'icon' => $blockData['icon'] ?? 'block-default',
            'modified_at' => current_time('mysql')
        ];
        
        // Update the block
        return $wpdb->update(
            $table,
            $data,
            ['name' => $name],
            array_fill(0, count($data), '%s'),
            ['%s']
        );
    }
} 