<?php
if (!defined('ABSPATH')) exit;

if (!isset($block)) {
    wp_die('Block not found');
}
?>
<div class="wrap gen-blocks-admin">
    <h1 class="wp-heading-inline"><?php printf(__('Edit Block: %s', 'gen-blocks'), esc_html($block->title)); ?></h1>
    <hr class="wp-header-end">
    
    <div class="genb-layout">
        <!-- Main Column: Preview and Code -->
        <div class="genb-main-column">
            <div id="response-container" class="card">
                <div class="inside">
                    <div class="preview-tabs">
                        <button type="button" class="tab-btn active" data-tab="preview">
                            <?php _e('Preview', 'gen-blocks'); ?>
                        </button>
                        <button type="button" class="tab-btn" data-tab="code">
                            <?php _e('Code', 'gen-blocks'); ?>
                        </button>
                        <button type="button" class="tab-btn" data-tab="settings">
                            <?php _e('Settings', 'gen-blocks'); ?>
                        </button>
                    </div>

                    <div id="settings-tab" class="tab-content">
                        <div class="block-settings-form">
                            <div class="form-field">
                                <label for="block-title"><?php _e('Block Title', 'gen-blocks'); ?></label>
                                <input type="text" id="block-title" class="regular-text" value="<?php echo esc_attr($block->title); ?>" required>
                            </div>
                            
                            <div class="form-field">
                                <label for="block-description"><?php _e('Description', 'gen-blocks'); ?></label>
                                <textarea id="block-description" class="large-text" rows="3"><?php echo esc_textarea($block->description); ?></textarea>
                            </div>
                            
                            <div class="form-field">
                                <label for="block-category"><?php _e('Category', 'gen-blocks'); ?></label>
                                <select id="block-category" class="regular-text">
                                    <option value="gen-blocks" <?php selected($block->category, 'gen-blocks'); ?>><?php _e('Gen Blocks', 'gen-blocks'); ?></option>
                                    <option value="text" <?php selected($block->category, 'text'); ?>><?php _e('Text', 'gen-blocks'); ?></option>
                                    <option value="media" <?php selected($block->category, 'media'); ?>><?php _e('Media', 'gen-blocks'); ?></option>
                                    <option value="design" <?php selected($block->category, 'design'); ?>><?php _e('Design', 'gen-blocks'); ?></option>
                                    <option value="widgets" <?php selected($block->category, 'widgets'); ?>><?php _e('Widgets', 'gen-blocks'); ?></option>
                                    <option value="theme" <?php selected($block->category, 'theme'); ?>><?php _e('Theme', 'gen-blocks'); ?></option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label for="block-icon"><?php _e('Icon', 'gen-blocks'); ?></label>
                                <div class="icon-picker-wrapper">
                                    <input type="text" id="block-icon" placeholder="<?php esc_attr_e('Select an icon...', 'gen-blocks'); ?>" readonly>
                                    <button type="button" class="button button-secondary icon-picker-button">
                                        <?php _e('Choose Icon', 'gen-blocks'); ?>
                                    </button>
                                    <div id="icon-picker-container" class="hidden"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="preview-tab" class="tab-content active" data-block-name="<?php echo esc_attr($block->name); ?>">
                        <div class="preview-block">
                            <?php 
                            // Extract HTML, styles and scripts from template
                            $template = $block->template;
                            
                            // Extract style and script tags
                            $html = $template;
                            $style = '';
                            $script = '';
                            
                            // Extract style
                            if (preg_match('/<style>(.*?)<\/style>/s', $template, $matches)) {
                                $style = $matches[1];
                                $html = preg_replace('/<style>.*?<\/style>/s', '', $html);
                            }
                            
                            // Extract script
                            if (preg_match('/<script>(.*?)<\/script>/s', $template, $matches)) {
                                $script = $matches[1];
                                $html = preg_replace('/<script>.*?<\/script>/s', '', $html);
                            }

                            // Get WordPress allowed HTML tags
                            $allowed_html = wp_kses_allowed_html('post');
                            
                            // Add zen attributes to all tags
                            foreach ($allowed_html as $tag => &$attributes) {
                                $attributes['zen-edit'] = true;
                                $attributes['zen-type'] = true;
                                $attributes['data-*'] = true;
                                $attributes['style'] = true;
                            }

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
                                'stroke-width' => true,
                                'style' => true,
                                'zen-edit' => true,
                                'zen-type' => true,
                                'data-*' => true,
                            );
                            
                            $allowed_html['path'] = array(
                                'd' => true,
                                'fill' => true,
                                'stroke' => true,
                                'stroke-width' => true,
                                'stroke-linecap' => true,
                                'stroke-linejoin' => true,
                                'style' => true,
                                'zen-edit' => true,
                                'zen-type' => true,
                                'data-*' => true,
                            );
                            
                            $allowed_html['circle'] = array(
                                'cx' => true,
                                'cy' => true,
                                'r' => true,
                                'fill' => true,
                                'stroke' => true,
                                'stroke-width' => true,
                                'style' => true,
                                'zen-edit' => true,
                                'zen-type' => true,
                                'data-*' => true,
                            );
                            
                            $allowed_html['rect'] = array(
                                'x' => true,
                                'y' => true,
                                'width' => true,
                                'height' => true,
                                'fill' => true,
                                'stroke' => true,
                                'stroke-width' => true,
                                'style' => true,
                                'zen-edit' => true,
                                'zen-type' => true,
                                'data-*' => true,
                            );
                            
                            $allowed_html['g'] = array(
                                'fill' => true,
                                'transform' => true,
                                'style' => true,
                                'zen-edit' => true,
                                'zen-type' => true,
                                'data-*' => true,
                            );
                            
                            $allowed_html['defs'] = array(
                                'zen-edit' => true,
                                'zen-type' => true,
                                'data-*' => true,
                            );

                            $allowed_html['clipPath'] = array(
                                'id' => true,
                                'zen-edit' => true,
                                'zen-type' => true,
                                'data-*' => true,
                            );

                            $allowed_html['mask'] = array(
                                'id' => true,
                                'zen-edit' => true,
                                'zen-type' => true,
                                'data-*' => true,
                            );
                            
                            // Output in correct order
                            echo wp_kses(trim($html), $allowed_html);
                            if ($style) {
                                echo '<style>' . wp_strip_all_tags($style) . '</style>';
                            }
                            if ($script) {
                                echo '<script>' . wp_strip_all_tags($script) . '</script>';
                            }
                            ?>
                        </div>
                    </div>

                    <div id="code-tab" class="tab-content">
                        <pre contenteditable="true"><?php echo esc_html($block->template); ?></pre>
                    </div>

                    <div class="preview-actions">
                        <button type="button" id="update-block" class="button button-primary">
                            <?php _e('Update Block', 'gen-blocks'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Chat -->
        <div id="chat-column" class="genb-chat-column">
            <div class="card">
                <div class="chat-messages" id="chat-messages">
                    <!-- Messages will be inserted here -->
                </div>
                <div class="chat-input">
                    <form id="chat-form">
                        <div class="chat-controls">
                            <div class="model-select-wrapper">
                                <select id="model-select" name="model" class="regular-text">
                                    <optgroup label="Claude Models">
                                        <?php foreach (\GENB\Config::CLAUDE_MODELS as $id => $model): ?>
                                            <option value="<?php echo esc_attr($id); ?>">
                                                <?php echo esc_html($model['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="OpenAI Models">
                                        <?php foreach (\GENB\Config::OPENAI_MODELS as $id => $model): ?>
                                            <option value="<?php echo esc_attr($id); ?>">
                                                <?php echo esc_html($model['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                            <textarea name="message" rows="3" placeholder="<?php esc_attr_e('Type your message here...', 'gen-blocks'); ?>"></textarea>
                            <button type="submit" class="button button-primary">
                                <?php _e('Send', 'gen-blocks'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Block Info -->
<div style="display: none;">
    <input type="hidden" id="block-name" value="<?php echo esc_attr($block->name); ?>">
    <input type="hidden" id="block-title" value="<?php echo esc_attr($block->title); ?>">
    <input type="hidden" id="block-description" value="<?php echo esc_attr($block->description); ?>">
    <input type="hidden" id="block-category" value="<?php echo esc_attr($block->category); ?>">
    <input type="hidden" id="block-icon" value="<?php echo esc_attr($block->icon); ?>">
</div>
