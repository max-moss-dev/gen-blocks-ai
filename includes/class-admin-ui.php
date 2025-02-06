<?php
/**
 * @category WordPress
 * @package  GENB
 */

namespace GENB;

/**
 * Class Admin_UI
 */
use GENB\Config;

class Admin_UI
{
    /**
     * Block storage instance
     *
     * @var Block_Storage
     */
    private $_storage;

    /**
     * Constructor
     *
     * @param Block_Storage $storage Block storage instance
     */
    public function __construct(Block_Storage $storage)
    {
        $this->_storage = $storage;
    }

    /**
     * Initialize the admin UI
     *
     * @return void
     */
    public function init()
    {
        \add_action('admin_menu', [$this, 'addMenuPages']);
        \add_action('admin_init', [$this, 'registerSettings']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        \add_action('wp_ajax_genb_analyze_image', [$this, 'handleImageAnalysis']);
        \add_action('wp_ajax_genb_refine_block', [$this, 'handleBlockRefinement']);
        \add_action('wp_ajax_genb_save_block', [$this, 'ajaxSaveBlock']);
        \add_action('wp_ajax_genb_update_block', [$this, 'ajaxUpdateBlock']);
        \add_action('wp_ajax_genb_delete_block', [$this, 'handleBlockDeletion']);
        \add_action('wp_ajax_genb_get_block', [$this, 'handleGetBlock']);
        \add_action('wp_ajax_genb_analyze_html', [$this, 'handleHtmlAnalysis']);
        
        // Add settings link to plugins page
        $plugin_basename = plugin_basename(GENB_PLUGIN_FILE);
        \add_filter("plugin_action_links_{$plugin_basename}", [$this, 'addPluginActionLinks']);
    }

    /**
     * Add menu pages
     *
     * @return void
     */
    public function addMenuPages()
    {
        \add_menu_page(
            'Gen Blocks',
            'Gen Blocks',
            'manage_options',
            'gen-blocks',
            [$this, 'renderManagePage'],
            'dashicons-layout'
        );

        \add_submenu_page(
            'gen-blocks',
            'Add New Block',
            'Add New Block',
            'manage_options',
            'gen-blocks-generate',
            [$this, 'renderAdminPage']
        );

        \add_submenu_page(
            '/admin.php?page=gen-blocks-edit',
            'Edit Block',
            'Edit Block',
            'manage_options',
            'gen-blocks-edit',
            [$this, 'renderEditPage']
        );

        \add_submenu_page(
            'gen-blocks',
            'Settings',
            'Settings',
            'manage_options',
            'gen-blocks-settings',
            [$this, 'renderSettingsPage']
        );
    }

    /**
     * Render admin page
     *
     * @return void
     */
    public function renderAdminPage()
    {
        include GENB_PLUGIN_DIR . 'templates/admin-page.php';
    }

    /**
     * Render edit page
     *
     * @return void
     */
    public function renderEditPage()
    {
        if (!isset($_GET['block'])) {
            wp_die('Block not specified');
        }

        $block_name = sanitize_text_field($_GET['block']);
        $block = $this->_storage->getBlock($block_name);

        if (!$block) {
            wp_die('Block not found');
        }

        include GENB_PLUGIN_DIR . 'templates/edit-block.php';
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public function renderSettingsPage()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('genb_settings');
                do_settings_sections('genb-settings');
                submit_button(__('Save Settings', 'gen-blocks'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render manage page
     *
     * @return void
     */
    public function renderManagePage()
    {
        include GENB_PLUGIN_DIR . 'templates/manage-blocks.php';
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook The current admin page.
     * @return void
     */
    public function enqueueAdminAssets($hook)
    {
        // Only load our assets on our plugin's pages
        if (!in_array($hook, [
            'toplevel_page_gen-blocks', 
            'gen-blocks_page_gen-blocks-generate', 
            'gen-blocks_page_gen-blocks-edit', 
            'gen-blocks_page_gen-blocks-settings',
            'admin_page_gen-blocks-edit'
            ])) {
            return;
        }

        \wp_enqueue_media();

        // Register and enqueue admin script
        \wp_enqueue_script(
            'genb-admin',
            GENB_PLUGIN_URL . 'build/admin.js',
            ['jquery', 'wp-components', 'wp-i18n'],
            GENB_VERSION,
            true
        );

        // Register and enqueue admin styles
        \wp_enqueue_style(
            'genb-admin',
            GENB_PLUGIN_URL . 'build/admin.css',
            ['dashicons'],
            GENB_VERSION
        );

        // Localize script with necessary data
        \wp_localize_script(
            'genb-admin',
            'genBlocksData',
            [
                'ajaxUrl' => \admin_url('admin-ajax.php'),
                'nonce' => \wp_create_nonce('genb_analyze_image'),
                'saveNonce' => \wp_create_nonce('gen-blocks-save-block'),
                'updateNonce' => \wp_create_nonce('genb_update_block'),
                'manageUrl' => \admin_url('admin.php?page=gen-blocks'),
                'strings' => [
                    'chooseIcon' => __('Choose Icon', 'gen-blocks'),
                    'selectIcon' => __('Select an icon', 'gen-blocks')
                ]
            ]
        );
    }

    /**
     * Handle image analysis
     *
     * @return void
     */
    public function handleImageAnalysis()
    {
        \check_ajax_referer('genb_analyze_image', 'nonce');
        
        $instructions = isset($_POST['instructions']) ? \sanitize_text_field($_POST['instructions']) : '';
        $blockName = isset($_POST['block_name']) ? \sanitize_text_field($_POST['block_name']) : '';
        $imageId = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
        
        if (empty($instructions)) {
            error_log('GENB: Missing instructions');
            \wp_send_json_error('Please provide instructions for the block');
            return;
        }

        if (empty($blockName)) {
            error_log('GENB: Missing block name');
            \wp_send_json_error('Please provide a block name');
            return;
        }

        // Handle image data
        $imageData = '';
        if ($imageId) {
            $imagePath = \get_attached_file($imageId);
            if ($imagePath && file_exists($imagePath)) {
                $imageData = \base64_encode(\file_get_contents($imagePath));
            } else {
                error_log('GENB: Failed to load image from path: ' . $imagePath);
            }
        }

        // Add block name to instructions
        $instructionsWithClass = "Create a block with class name 'wp-block-genb-{$blockName}'. " . $instructions;
        
        $response = $this->_callClaudeApi($imageData, $instructionsWithClass);

        // error_log('GENB: Response: ' . json_encode($response));
        
        if (\is_wp_error($response)) {
            error_log('GENB: API Error: ' . $response->get_error_message());
            \wp_send_json_error($response->get_error_message());
            return;
        }

        // Now use the block name in template generation
        $template = $this->_generateBlockTemplate($response, $blockName);

        \wp_send_json_success([
            'template' => $template,
            'preview' => $template,
            'raw' => $response
        ]);
    }

    /**
     * Handle block refinement request
     *
     * @return void
     */
    public function handleBlockRefinement()
    {
        \check_ajax_referer('genb_analyze_image', 'nonce');
        
        $message = isset($_POST['message']) ? \sanitize_text_field($_POST['message']) : '';
        $currentCode = isset($_POST['current_code']) ? $_POST['current_code'] : '';
        $imageId = isset($_POST['image_id']) ? (int) $_POST['image_id'] : 0;
        $blockName = isset($_POST['block_name']) ? \sanitize_title($_POST['block_name']) : '';
        $history = isset($_POST['conversation_history']) ? json_decode(stripslashes($_POST['conversation_history']), true) : [];
        
        if (!$message || !$currentCode || !$blockName) {
            \wp_send_json_error('Missing required fields');
        }

        // If we have an image, get its data
        $imageData = '';
        if ($imageId) {
            $imagePath = \get_attached_file($imageId);
            if ($imagePath) {
                $imageData = \base64_encode(\file_get_contents($imagePath));
            }
        }

        // Prepare the context for the API
        $context = "Current block name: $blockName\n\n";
        $context .= "Current code:\n$currentCode\n\n";
        if ($imageData) {
            $context .= "Reference image is attached.\n\n";
        }
        $context .= "Conversation history:\n";
        foreach ($history as $entry) {
            $context .= "{$entry['role']}: {$entry['content']}\n";
        }
        $context .= "\nUser's new request: $message";

        // Call the API with the full context
        $response = $this->_callClaudeApi($imageData, $context);
        
        if (\is_wp_error($response)) {
            \wp_send_json_error($response->get_error_message());
        }

        // error_log('GENB: Response: ' . $response);

        // Generate new template
        $template = $this->_generateBlockTemplate($response, $blockName);

        // error_log('GENB: Generated template 3: ' . $template);

        \wp_send_json_success([
            'template' => $template,
            'message' => 'I\'ve updated the block based on your request. Let me know if you\'d like any other changes!'
        ]);
    }

    /**
     * Handle block deletion
     *
     * @return void
     */
    public function handleBlockDeletion()
    {
        \check_ajax_referer('genb_manage_blocks', 'nonce');
        
        $blockName = isset($_POST['block_name']) ? \sanitize_title($_POST['block_name']) : '';
        if (!$blockName) {
            \wp_send_json_error('Block name is required');
        }

        if ($this->_storage->deleteBlock($blockName)) {
            \wp_send_json_success(['message' => 'Block deleted successfully']);
        } else {
            \wp_send_json_error('Failed to delete block');
        }
    }

    /**
     * Handle AJAX request to save block
     */
    public function ajaxSaveBlock()
    {
        // Verify nonce
        if (!check_ajax_referer('gen-blocks-save-block', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }

        // Get and validate required data
        $blockData = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'template' => $this->sanitize_block_template($_POST['template'] ?? ''),
            'description' => sanitize_text_field($_POST['description'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? 'gen-blocks'),
            'icon' => sanitize_text_field($_POST['icon'] ?? 'block-default'),
        ];

        // Validate required fields
        if (empty($blockData['name']) || empty($blockData['title']) || empty($blockData['template'])) {
            wp_send_json_error(['message' => 'Required fields are missing']);
            return;
        }

        // Save block
        $result = $this->_storage->saveBlock($blockData);

        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to save block']);
            return;
        }

        wp_send_json_success([
            'message' => 'Block saved successfully',
            'blockId' => $result
        ]);
    }

    /**
     * Handle AJAX request to update block
     */
    public function ajaxUpdateBlock()
    {
        // Verify nonce
        if (!check_ajax_referer('genb_update_block', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }

        // Get and validate required data
        $blockData = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'template' => $this->sanitize_block_template($_POST['template'] ?? ''),
            'description' => sanitize_text_field($_POST['description'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? 'gen-blocks'),
            'icon' => sanitize_text_field($_POST['icon'] ?? 'block-default'),
        ];

        // Validate required fields
        if (empty($blockData['name']) || empty($blockData['title']) || empty($blockData['template'])) {
            wp_send_json_error(['message' => 'Required fields are missing']);
            return;
        }

        // Update block
        $result = $this->_storage->updateBlock($blockData['name'], $blockData);

        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to update block']);
            return;
        }

        wp_send_json_success([
            'message' => 'Block updated successfully',
            'blockData' => $blockData
        ]);
    }

    /**
     * Sanitize block template while preserving style tags and SVG elements
     *
     * @param string $template The template to sanitize
     * @return string Sanitized template
     */
    public function sanitize_block_template($template)
    {
        // Use Block_Storage's sanitization
        return $this->_storage->sanitize_template($template);
    }

    /**
     * Handle get block request
     *
     * @return void
     */
    public function handleGetBlock()
    {
        if (!current_user_can('manage_options')) {
            \wp_send_json_error('Insufficient permissions');
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'genb_analyze_image')) {
            \wp_send_json_error('Invalid nonce');
            return;
        }

        $blockName = sanitize_text_field($_POST['block_name']);
        $block = $this->_storage->getSingleBlock($blockName);
        
        if (!$block) {
            \wp_send_json_error('Block not found');
            return;
        }

        \wp_send_json_success($block);
    }

    /**
     * Call Claude API
     *
     * @param string $imageData Base64 encoded image data.
     * @param string $userPrompt   User prompt for the API.
     * 
     * @return string|\WP_Error
     */
    private function _callClaudeApi($imageData = '', $userPrompt = '')
    {
        $options = get_option('genb_settings', []);
        $apiKey = $options['api_key'] ?? '';
        
        if (empty($apiKey)) {
            return new \WP_Error(
                'missing_api_key', 'Claude API key is not configured'
            );
        }

        $messageContent = [];
        
        // Add text content
        $messageContent[] = [
            'type' => 'text',
            'text' => Prompts::get_block_generation_prompt($imageData, $userPrompt) . "\n\n" . $userPrompt
        ];

        // Add image if provided
        if (!empty($imageData)) {
            $messageContent[] = [
                'type'   => 'image',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => 'image/jpeg',
                    'data'       => $imageData
                ]
            ];
        }

        $model = sanitize_text_field($_POST['model']);
        
        // Check if it's an OpenAI model
        if (isset(Config::OPENAI_MODELS[$model])) {
            return $this->_callOpenAiApi($messageContent, $model);
        }

        $response = \wp_remote_post(
            Config::CLAUDE_API['endpoint'],
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'x-api-key'     => $apiKey,
                    'anthropic-version' => Config::CLAUDE_API['version']
                ],
                'timeout' => Config::CLAUDE_API['timeout'],
                'body'    => \wp_json_encode([
                    'model'      => $model,
                    'max_tokens' => Config::CLAUDE_API['max_tokens'],
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => $messageContent
                        ]
                    ]
                ])
            ]
        );

        if (\is_wp_error($response)) {
            return new \WP_Error(
                'api_error',
                'API request failed: ' . $response->get_error_message()
            );
        }

        $code = \wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = \json_decode(\wp_remote_retrieve_body($response), true);
            $message = $body['error']['message'] ?? 'Unknown API error';
            return new \WP_Error('api_error', "Claude API error ($code): $message");
        }

        $body = \json_decode(\wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            $message = $body['error']['message'] ?? 'Unknown API error';
            return new \WP_Error('api_error', "Claude API error: $message");
        }
        
        $text = $body['content'][0]['text'] ?? '';

        // Clean up response
        $text = \preg_replace('/^```.*?\n|```$/m', '', $text);
        $text = \trim($text);

        // Validate response format
        if (!\preg_match('/<div class="[^"]+">.*<\/div>\s*<style>.*<\/style>/s', $text)) {
            return new \WP_Error('invalid_response', 'Invalid response format from Claude API');
        }

        return $text;
    }

    /**
     * Call OpenAI API
     */
    private function _callOpenAiApi($messageContent, $model) {
        $options = get_option('genb_settings', []);
        $apiKey = $options['openai_key'] ?? '';
        
        if (empty($apiKey)) {
            return new \WP_Error(
                'missing_api_key', 'OpenAI API key is not configured'
            );
        }

        // Format messages for OpenAI
        $messages = [];
        foreach ($messageContent as $content) {
            if ($content['type'] === 'text') {
                $messages[] = [
                    'role' => 'user',
                    'content' => $content['text']
                ];
            } elseif ($content['type'] === 'image') {
                $messages[] = [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/jpeg;base64,' . $content['source']['data']
                            ]
                        ]
                    ]
                ];
            }
        }

        $response = \wp_remote_post(
            Config::OPENAI_API['endpoint'],
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey
                ],
                'timeout' => Config::OPENAI_API['timeout'],
                'body'    => \wp_json_encode([
                    'model'       => $model,
                    'messages'    => $messages,
                    'max_completion_tokens'  => Config::OPENAI_API['max_tokens']
                ])
            ]
        );

        if (\is_wp_error($response)) {
            return new \WP_Error(
                'api_error',
                'API request failed: ' . $response->get_error_message()
            );
        }

        $code = \wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = \json_decode(\wp_remote_retrieve_body($response), true);
            $message = $body['error']['message'] ?? 'Unknown API error';
            return new \WP_Error('api_error', "OpenAI API error ($code): $message");
        }

        $body = \json_decode(\wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            $message = $body['error']['message'] ?? 'Unknown API error';
            return new \WP_Error('api_error', "OpenAI API error: $message");
        }
        
        $text = $body['choices'][0]['message']['content'] ?? '';

        // Clean up response
        $text = \preg_replace('/^```.*?\n|```$/m', '', $text);
        $text = \trim($text);

        // Validate response format
        if (!\preg_match('/<div class="[^"]+">.*<\/div>\s*<style>.*<\/style>/s', $text)) {
            return new \WP_Error('invalid_response', 'Invalid response format from OpenAI API');
        }

        return $text;
    }

    /**
     * Generate block template
     *
     * @param string $response   Response content.
     * @param string $blockName  Block name.
     *
     * @return string
     */
    private function _generateBlockTemplate($response, $blockName)
    {
        if (empty($response)) {
            return '';
        }

        // Extract the code blocks from the response
        if (preg_match('/<div[^>]*>(.*?)<\/div>\s*<style[^>]*>(.*?)<\/style>(?:\s*<script[^>]*>(.*?)<\/script>)?/s', $response, $matches)) {
            $html = $matches[1];
            $css = $matches[2];
            $js = isset($matches[3]) ? $matches[3] : '';
            
            // error_log('GENB: Matched parts - HTML: ' . substr($html, 0, 10000) . '..., CSS: ' . substr($css, 0, 1000) . '..., JS: ' . substr($js, 0, 100) . '...');
        } else {
            error_log('GENB: No match found in response');
            return $response;

        }

        // Clean up the extracted code
        $html = trim($html);
        $css = trim($css);
        $js = trim($js);

        // Build the final template
        $template = "<div class=\"wp-block-genb-{$blockName}\">\n";
        $template .= $html . "\n";
        $template .= "</div>\n";
        if (!empty($css)) {
            $template .= "<style>\n" . $css . "\n</style>\n";
        }
        if (!empty($js)) {
            $template .= "<script>\n" . $js . "\n</script>";
        }

        return $template;
    }

    /**
     * Register settings
     *
     * @return void
     */
    public function registerSettings()
    {
        register_setting(
            'genb_settings', // Option group
            'genb_settings', // Option name
            [
                'type' => 'array',
                'description' => 'Generate Blocks plugin settings',
                'sanitize_callback' => [$this, 'sanitizeSettings'],
                'default' => []
            ]
        );

        add_settings_section(
            'genb_main_section',
            __('API Settings', 'gen-blocks'),
            [$this, 'renderSettingsSection'],
            'genb-settings'
        );

        add_settings_field(
            'genb_api_key',
            __('Claude API Key', 'gen-blocks'),
            [$this, 'renderApiKeyField'],
            'genb-settings',
            'genb_main_section'
        );

        add_settings_field(
            'genb_openai_key',
            __('OpenAI API Key', 'gen-blocks'),
            [$this, 'renderOpenAiApiKeyField'],
            'genb-settings',
            'genb_main_section'
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $input The value being saved.
     * @return array Sanitized value
     */
    public function sanitizeSettings($input)
    {
        $sanitized = [];
        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }
        if (isset($input['openai_key'])) {
            $sanitized['openai_key'] = sanitize_text_field($input['openai_key']);
        }
        return $sanitized;
    }

    /**
     * Render settings section
     *
     * @return void
     */
    public function renderSettingsSection()
    {
        echo '<p>' . __('Configure your API settings below.', 'gen-blocks') . '</p>';
    }

    /**
     * Render API key field
     *
     * @return void
     */
    public function renderApiKeyField()
    {
        $options = get_option('genb_settings', []);
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        ?>
        <input type="password" 
               id="genb_api_key" 
               name="genb_settings[api_key]" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text"
               autocomplete="off">
        <p class="description">
            <?php _e('Enter your API key here. You can find this in <a target="_blank" rel="noopener noreferrer" href="https://console.anthropic.com/settings/keys" target="_blank">your account settings</a>.', 'gen-blocks'); ?>
        </p>
        <?php
    }

    /**
     * Render OpenAI API key field
     *
     * @return void
     */
    public function renderOpenAiApiKeyField()
    {
        $options = get_option('genb_settings', []);
        $openai_key = isset($options['openai_key']) ? $options['openai_key'] : '';
        ?>
        <input type="password" 
               id="genb_openai_key" 
               name="genb_settings[openai_key]" 
               value="<?php echo esc_attr($openai_key); ?>" 
               class="regular-text"
               autocomplete="off">
        <p class="description">
            <?php _e('Enter your OpenAI API key here. You can find this in <a target="_blank" rel="noopener noreferrer" href="https://beta.openai.com/account/api-keys" target="_blank">your account settings</a>.', 'gen-blocks'); ?>
        </p>
        <?php
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing plugin action links
     * @return array Modified plugin action links
     */
    public function addPluginActionLinks($links)
    {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            \admin_url('admin.php?page=gen-blocks'),
            \__('Manage blocks', 'gen-blocks')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Handle HTML analysis
     *
     * @return void
     */
    public function handleHtmlAnalysis()
    {
        \check_ajax_referer('genb_analyze_image', 'nonce');
        
        $html = isset($_POST['html']) ? stripslashes($_POST['html']) : '';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $blockName = isset($_POST['block_name']) ? \sanitize_text_field($_POST['block_name']) : '';
        
        if (empty($blockName)) {
            \wp_send_json_error('Block name is required');
            return;
        }

        if (empty($html) && empty($url)) {
            \wp_send_json_error('Please provide HTML or URL');
            return;
        }

        // If URL provided, fetch HTML
        if (!empty($url)) {
            $response = \wp_remote_get($url);
            if (\is_wp_error($response)) {
                \wp_send_json_error('Failed to fetch URL: ' . $response->get_error_message());
                return;
            }
            $html = \wp_remote_retrieve_body($response);
        }

        // Process HTML with our HTML processor
        $template = $this->processHtml($html, $blockName);

        \wp_send_json_success([
            'template' => $template,
            'preview' => $template
        ]);
    }

    /**
     * Process HTML and add zen attributes
     *
     * @param string $html      HTML content
     * @param string $blockName Block name
     * @param string $baseUrl   Base URL for relative paths
     * @return string
     */
    private function processHtml($html, $blockName)
    {
        // Extract base URL from provided URL or use site URL
        $baseUrl = '';
        if (!empty($_POST['url'])) {
            $url = $_POST['url'];
            error_log('GENB: Post url: ' . $url);
            $baseUrl = preg_replace('/\/[^\/]*$/', '', $url); // Remove last path segment
            error_log('GENB: Base URL: ' . $baseUrl);
        } else {
            $baseUrl = get_site_url();
        }

        error_log('GENB: Base URL: ' . $baseUrl);

        // Load HTML into DOMDocument
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true); // Suppress HTML5 errors
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        // Create XPath object
        $xpath = new \DOMXPath($dom);
        
        // Process text elements (h1-h6, p, span, div with only text)
        $textNodes = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6|//p|//span|//div[not(*)]');
        foreach ($textNodes as $i => $node) {
            if ($node->textContent && trim($node->textContent) !== '') {
                $elementType = strtolower($node->nodeName);
                $node->setAttribute('zen-edit', "{$elementType}_text_{$i}");
            }
        }
        
        // Process images and fix relative paths
        $images = $xpath->query('//img');
        foreach ($images as $i => $img) {
            $img->setAttribute('zen-edit', "image_{$i}");
            $img->setAttribute('zen-type', 'image');
            
            // Fix relative src
            if ($img->hasAttribute('src')) {
                $src = $img->getAttribute('src');
                if (strpos($src, 'http') !== 0 && strpos($src, '//') !== 0) {
                    $src = ltrim($src, '/');
                    $img->setAttribute('src', $baseUrl . '/' . $src);
                }
            }
            
            // Fix relative srcset
            if ($img->hasAttribute('srcset')) {
                $srcset = $img->getAttribute('srcset');
                $parts = explode(',', $srcset);
                foreach ($parts as &$part) {
                    $part = trim($part);
                    if (strpos($part, 'http') !== 0 && strpos($part, '//') !== 0) {
                        list($url, $size) = preg_split('/\s+/', $part);
                        $url = ltrim($url, '/');
                        $part = $baseUrl . '/' . $url . ' ' . $size;
                    }
                }
                $img->setAttribute('srcset', implode(', ', $parts));
            }
        }
        
        // Process links and buttons, fix relative hrefs
        $clickables = $xpath->query('//a|//button');
        foreach ($clickables as $i => $element) {
            $elementType = strtolower($element->nodeName);
            $element->setAttribute('zen-edit', "{$elementType}_{$i}");
            $element->setAttribute('zen-type', 'url');
            
            // Fix relative href for links
            if ($element->nodeName === 'a' && $element->hasAttribute('href')) {
                $href = $element->getAttribute('href');
                if (strpos($href, 'http') !== 0 && strpos($href, '//') !== 0 && strpos($href, '#') !== 0 && strpos($href, 'mailto:') !== 0) {
                    $href = ltrim($href, '/');
                    $element->setAttribute('href', $baseUrl . '/' . $href);
                }
            }
        }
        
        // Fix relative paths in link tags
        $links = $xpath->query('//link');
        foreach ($links as $link) {
            if ($link->hasAttribute('href')) {
                $href = $link->getAttribute('href');
                if (strpos($href, 'http') !== 0 && strpos($href, '//') !== 0) {
                    $href = ltrim($href, '/');
                    $link->setAttribute('href', $baseUrl . '/' . $href);
                }
            }
        }
        
        // Fix relative paths in script tags
        $scripts = $xpath->query('//script');
        foreach ($scripts as $script) {
            if ($script->hasAttribute('src')) {
                $src = $script->getAttribute('src');
                if (strpos($src, 'http') !== 0 && strpos($src, '//') !== 0) {
                    $src = ltrim($src, '/');
                    $script->setAttribute('src', $baseUrl . '/' . $src);
                }
            }
        }
        
        // Wrap content in block div if not already wrapped
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $wrapper = $dom->createElement('div');
            $wrapper->setAttribute('class', "wp-block-genb-{$blockName}");
            
            // Move all body children to wrapper
            while ($body->firstChild) {
                $wrapper->appendChild($body->firstChild);
            }
            $body->appendChild($wrapper);
        }
        
        // Get the processed HTML
        $processedHtml = $dom->saveHTML();
        
        // Clean up the output
        $processedHtml = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $processedHtml);
        $processedHtml = trim($processedHtml);
        
        return $processedHtml;
    }
} 