<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap gen-blocks-admin">
    <h1><?php _e('Generate New Block', 'gen-blocks'); ?></h1>
    
    <div class="genb-layout">
        <!-- Left Column: Form and Preview -->
        <div class="genb-main-column">
            <div id="generation-area" class="card">
                <form id="block-generation-form" class="form-table">
                    <div class="form-field">
                        <label for="user-instructions" class="form-label"><?php _e('Instructions', 'gen-blocks'); ?></label>
                        <textarea id="user-instructions" name="user-instructions" class="large-text" rows="4" required
                            placeholder="<?php esc_attr_e('Describe what you want your block to do...', 'gen-blocks'); ?>"></textarea>
                        <p class="description"><?php _e('Provide detailed instructions for generating your block.', 'gen-blocks'); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="model-select" class="form-label"><?php _e('AI Model', 'gen-blocks'); ?></label>
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
                        <p class="description model-info">
                            <?php _e('Select the AI model to use. Each model has different capabilities and costs.', 'gen-blocks'); ?>
                        </p>
                    </div>

                    <div class="form-field">
                        <label class="form-label"><?php _e('Reference Image (Optional)', 'gen-blocks'); ?></label>
                        <div id="image-preview-container">
                            <button type="button" id="upload-btn" class="button">
                                <?php _e('Select Image', 'gen-blocks'); ?>
                            </button>
                            <div id="image-preview" class="hidden">
                                <img src="" alt="" style="max-width: 300px; margin: 10px 0;">
                                <button type="button" class="button remove-image">
                                    <?php _e('Remove Image', 'gen-blocks'); ?>
                                </button>
                            </div>
                        </div>
                        <p class="description"><?php _e('Optional: Upload a reference image to help guide the block generation.', 'gen-blocks'); ?></p>
                    </div>

                    <div class="form-field">
                        <label class="form-label"><?php _e('Or Generate from HTML/URL', 'gen-blocks'); ?></label>
                        <div class="html-input-wrapper">
                            <select id="input-type" class="regular-text">
                                <option value="html"><?php _e('HTML Code', 'gen-blocks'); ?></option>
                                <option value="url"><?php _e('Page URL', 'gen-blocks'); ?></option>
                            </select>
                            <div id="html-input">
                                <textarea id="html-code" class="large-text" rows="4" 
                                    placeholder="<?php esc_attr_e('Paste HTML code here...', 'gen-blocks'); ?>"></textarea>
                            </div>
                            <div id="url-input" class="hidden">
                                <input type="url" id="page-url" class="regular-text" 
                                    placeholder="<?php esc_attr_e('Enter page URL...', 'gen-blocks'); ?>">
                            </div>
                            <button type="button" id="generate-from-html" class="button">
                                <?php _e('Generate from Input', 'gen-blocks'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="form-field submit-wrapper">
                        <div class="button-with-loader">
                            <button type="submit" id="send-instructions" class="button button-primary">
                                <?php _e('Generate Block', 'gen-blocks'); ?>
                            </button>
                            <span class="spinner"></span>
                        </div>
                    </div>
                </form>
            </div>

            <div id="response-container" class="card hidden">
                <div class="inside">
                    <div class="preview-tabs">
                        <button type="button" class="tab-btn active" data-tab="preview">
                            <?php _e('Preview', 'gen-blocks'); ?>
                        </button>
                        <button type="button" class="tab-btn" data-tab="code">
                            <?php _e('Code', 'gen-blocks'); ?>
                        </button>
                    </div>

                    <div id="preview-tab" class="tab-content active">
                        <div class="preview-block"></div>
                    </div>

                    <div id="code-tab" class="tab-content">
                        <pre contenteditable="true"></pre>
                    </div>

                    <div class="preview-actions">
                        <button type="button" id="save-block" class="button button-primary">
                            <?php _e('Save Block', 'gen-blocks'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Chat -->
        <div id="chat-column" class="genb-chat-column hidden">
            <div class="card">
                <div class="chat-messages" id="chat-messages">
                    <!-- Messages will be inserted here -->
                </div>
                <div class="chat-input">
                    <form id="chat-form">
                        <textarea id="chat-message" 
                                placeholder="<?php esc_attr_e('Type your message to refine the block...', 'gen-blocks'); ?>"
                                rows="3"
                                class="large-text"></textarea>
                        <div class="button-with-loader">
                            <button type="submit" class="button button-primary">
                                <?php _e('Send Message', 'gen-blocks'); ?>
                            </button>
                            <span class="spinner"></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Save Block Modal -->
<div id="save-block-modal" class="genb-modal">
    <div class="genb-modal-content">
        <h3><?php _e('Save Your Block', 'gen-blocks'); ?></h3>
        <p><?php _e('Fill in the details for your custom block. A unique identifier will be generated automatically.', 'gen-blocks'); ?></p>
        
        <div class="form-field">
            <label for="block-title"><?php _e('Block Title', 'gen-blocks'); ?> <span class="required">*</span></label>
            <input type="text" id="block-title" placeholder="<?php esc_attr_e('e.g., Hero Section, Feature Card, etc.', 'gen-blocks'); ?>" required>
        </div>

        <div class="form-field">
            <label for="block-description"><?php _e('Description', 'gen-blocks'); ?></label>
            <textarea id="block-description" rows="3" placeholder="<?php esc_attr_e('Describe what your block does...', 'gen-blocks'); ?>"></textarea>
        </div>

        <div class="form-field">
            <label for="block-category"><?php _e('Category', 'gen-blocks'); ?></label>
            <select id="block-category">
                <option value="gen-blocks"><?php _e('Gen Blocks', 'gen-blocks'); ?></option>
                <option value="text"><?php _e('Text', 'gen-blocks'); ?></option>
                <option value="media"><?php _e('Media', 'gen-blocks'); ?></option>
                <option value="design"><?php _e('Design', 'gen-blocks'); ?></option>
                <option value="widgets"><?php _e('Widgets', 'gen-blocks'); ?></option>
                <option value="embed"><?php _e('Embed', 'gen-blocks'); ?></option>
                <option value="reusable"><?php _e('Reusable', 'gen-blocks'); ?></option>
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

        <div class="genb-modal-actions">
            <button type="button" class="button button-secondary" id="cancel-save"><?php _e('Cancel', 'gen-blocks'); ?></button>
            <button type="button" class="button button-primary" id="confirm-save"><?php _e('Save Block', 'gen-blocks'); ?></button>
        </div>
    </div>
</div>