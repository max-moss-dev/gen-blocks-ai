import './editor.scss';
import './admin.scss';

jQuery(document).ready(function($) {
    let selectedImage = null;
    let conversationHistory = [];
    let currentBlockName = '';
    
    const preview = $('#image-preview');
    const previewImg = preview.find('img');
    const uploadBtn = $('#upload-btn');
    const chatForm = $('#chat-form');
    const chatMessages = $('#chat-messages');
    const chatTextarea = chatForm.find('textarea');
    
    // Add info text about shortcuts under textarea
    $('<div class="shortcut-info">Press Enter to send, Shift+Enter for new line</div>')
        .insertAfter(chatTextarea)
        .css({
            'font-size': '12px',
            'color': '#666',
            'margin-top': '4px'
        });

    // Handle textarea key events
    chatTextarea.on('keydown', function(e) {
        if (e.key === 'Enter') {
            if (e.shiftKey) {
                // Shift+Enter: insert new line
                const start = this.selectionStart;
                const end = this.selectionEnd;
                const value = this.value;
                this.value = value.substring(0, start) + '\n' + value.substring(end);
                this.selectionStart = this.selectionEnd = start + 1;
                e.preventDefault();
            } else {
                // Enter: submit form
                e.preventDefault();
                chatForm.submit();
            }
        }
    });

    // Initialize icon picker
    initializeIconPicker();
    
    // Initialize media uploader
    const uploader = wp.media({
        title: 'Select Image',
        button: {
            text: 'Use this image'
        },
        multiple: false
    });

    // When an image is selected
    uploader.on('select', function() {
        const attachment = uploader.state().get('selection').first().toJSON();
        
        selectedImage = attachment.id;
        
        previewImg.attr('src', attachment.url);
        preview.removeClass('hidden');

        // Set instructions text if empty
        const instructionsField = $('#user-instructions');
        
        if (instructionsField.length && !instructionsField.val().trim()) {
            instructionsField.val('Generate block from this image');
        }
    });

    // Remove image when remove button is clicked
    $(document).on('click', '.remove-image', function(e) {
        e.preventDefault();
        selectedImage = null;
        preview.addClass('hidden');
        previewImg.attr('src', '');
    });

    // Open media uploader
    uploadBtn.on('click', function(e) {
        e.preventDefault();
        uploader.open();
    });

    // Handle tab switching
    $('.tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $(`#${tab}-tab`).addClass('active');
    });

    // Function to add a message to the chat
    function addMessage(content, role) {
        const message = $('<div>')
            .addClass('chat-message')
            .addClass(role)
            .text(content);
        
        $('#chat-messages').append(message);
        
        // Scroll to bottom
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
        
        // Add to conversation history
        conversationHistory.push({ role, content });
    }

    // Handle form submission
    $('#block-generation-form').on('submit', function(e) {
        e.preventDefault();
        
        const instructions = $('#user-instructions').val().trim();
        
        if (!instructions) {
            showError('Please enter instructions for the block');
            return;
        }
        
        currentBlockName = generateBlockName(); // Store generated name
        const sendBtn = $('#send-instructions');
        const spinner = sendBtn.siblings('.spinner');
        const chatColumn = $('#chat-column');
        const generationArea = $('#generation-area');
        const model = $('#model-select').val();
        
        sendBtn.prop('disabled', true);
        spinner.addClass('is-active');
        
        // Reset conversation history
        conversationHistory = [];
        chatMessages.empty();
        
        const formData = new FormData();
        formData.append('action', 'genb_analyze_image');
        formData.append('instructions', instructions);
        formData.append('block_name', currentBlockName);
        formData.append('nonce', genBlocksData.nonce);
        formData.append('model', model);
        
        // Handle image upload
        if (selectedImage) {
            // Get the image file from the media library
            const imageFile = new File(
                [new Blob([''], { type: 'image/jpeg' })],
                'image.jpg',
                { type: 'image/jpeg' }
            );
            formData.append('image', imageFile);
            formData.append('image_id', selectedImage);
        }

        $.ajax({
            url: genBlocksData.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('GENB: Response:', response);
                if (response.success) {
                    // Hide generation area and show response
                    generationArea.addClass('hidden');
                    $('#response-container').removeClass('hidden');
                    $('.genb-layout').addClass('has-response');
                    
                    // Update preview and code
                    const template = response.data.template;
                    const previewBlock = $('#preview-tab .preview-block');

                    // Clear existing content
                    previewBlock.empty();
                    previewBlock.find('style, script').remove();

                    // Extract style and script first
                    const styleMatch = template.match(/<style>([\s\S]*?)<\/style>/);
                    const scriptMatch = template.match(/<script>([\s\S]*?)<\/script>/);
                    
                    // Remove style and script tags to get clean HTML
                    let html = template
                        .replace(/<style>[\s\S]*?<\/style>/, '')
                        .replace(/<script>[\s\S]*?<\/script>/, '')
                        .trim();
                    
                    // console.log('Parts:', {
                    //     html: html,
                    //     style: styleMatch ? styleMatch[1] : 'none',
                    //     script: scriptMatch ? scriptMatch[1] : 'none'
                    // });

                    // Add the parts back in order
                    previewBlock.html(html);
                    
                    if (styleMatch && styleMatch[1]) {
                        previewBlock.append($('<style>').text(styleMatch[1]));
                    }
                    
                    if (scriptMatch && scriptMatch[1]) {
                        const script = document.createElement('script');
                        script.textContent = scriptMatch[1];
                        previewBlock[0].appendChild(script);
                    }

                    $('.genb-chat-column').removeClass('hidden');
                    $('#code-tab pre').text(template);

                    // Show chat column and add initial messages
                    chatColumn.removeClass('hidden');
                    addMessage(instructions, 'user');
                    addMessage('I\'ve generated the block based on your instructions. Let me know if you\'d like any changes!', 'assistant');
                    
                    showSuccess('Block generated successfully!');
                } else {
                    showError(response.data || 'Failed to generate block');
                }
            },
            error: function(xhr, status, error) {
                console.log('GENB: Error:', error);
                showError('Failed to generate block: ' + error);
            },
            complete: function() {
                sendBtn.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });

    // Handle chat form submission
    chatForm.on('submit', function(e) {
        e.preventDefault();

        const message = chatTextarea.val().trim();
        const model = $('#model-select').val();
        const sendBtn = $(this).find('button[type="submit"]');
        const spinner = sendBtn.siblings('.spinner');
        
        if (!message) return;
        
        // Clear textarea
        chatTextarea.val('');
        
        // Add user message to chat
        addMessage(message, 'user');
        
        // Show loading state
        sendBtn.prop('disabled', true);
        spinner.addClass('is-active');
        
        // Get current code from code tab
        const currentCode = $('#code-tab pre').text();
        
        // Get block name from data attribute or use a default
        const blockName = $('#preview-tab').data('block-name') || currentBlockName;
        
        $.ajax({
            url: genBlocksData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'genb_refine_block',
                message: message,
                model: model,
                current_code: currentCode,
                block_name: blockName,
                conversation_history: JSON.stringify(conversationHistory),
                nonce: genBlocksData.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update code preview
                    const template = response.data.template;
                    const previewBlock = $('#preview-tab .preview-block');
                    
                    // Clear existing content
                    previewBlock.empty();
                    previewBlock.find('style, script').remove();

                    // Extract style and script first
                    const styleMatch = template.match(/<style>([\s\S]*?)<\/style>/);
                    const scriptMatch = template.match(/<script>([\s\S]*?)<\/script>/);
                    
                    // Remove style and script tags to get clean HTML
                    let html = template
                        .replace(/<style>[\s\S]*?<\/style>/, '')
                        .replace(/<script>[\s\S]*?<\/script>/, '')
                        .trim();
                    
                    // console.log('Parts:', {
                    //     html: html,
                    //     style: styleMatch ? styleMatch[1] : 'none',
                    //     script: scriptMatch ? scriptMatch[1] : 'none'
                    // });

                    // Add the parts back in order
                    previewBlock.html(html);
                    
                    if (styleMatch && styleMatch[1]) {
                        previewBlock.append($('<style>').text(styleMatch[1]));
                    }
                    
                    if (scriptMatch && scriptMatch[1]) {
                        const script = document.createElement('script');
                        script.textContent = scriptMatch[1];
                        previewBlock[0].appendChild(script);
                    }

                    $('#code-tab pre').text(template);
                    $('#preview-tab').data('block-name', blockName);
                    
                    // Add assistant response to chat
                    addMessage(response.data.message, 'assistant');
                    if (response.data.template) {
                        updatePreview(response.data.template);
                    }
                } else {
                    showError(response.data || 'Failed to refine block');
                }
            },
            error: function(xhr, status, error) {
                showError('Failed to refine block: ' + error);
            },
            complete: function() {
                sendBtn.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });

    function updatePreview(template) {
        const previewBlock = $('.preview-block');
        
        // Create a temporary container
        const tempContainer = document.createElement('div');
        tempContainer.innerHTML = template;
        
        // Handle SVG elements
        const svgElements = tempContainer.getElementsByTagName('svg');
        Array.from(svgElements).forEach(svg => {
            // Fix SVG namespace
            if (!svg.hasAttribute('xmlns')) {
                svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            }
            
            // Fix SVG attributes
            Array.from(svg.attributes).forEach(attr => {
                if (attr.name === 'class' && attr.value.includes('[object SVGAnimatedString]')) {
                    svg.setAttribute('class', '');
                }
            });
            
            // Fix child elements
            const fixSVGElement = (el) => {
                Array.from(el.attributes).forEach(attr => {
                    if (attr.name === 'class' && attr.value.includes('[object SVGAnimatedString]')) {
                        el.setAttribute('class', '');
                    }
                });
                Array.from(el.children).forEach(fixSVGElement);
            };
            Array.from(svg.children).forEach(fixSVGElement);
        });
        
        // Update preview
        previewBlock.html(tempContainer.innerHTML);
    }

    // Generate a unique block name from title that follows WordPress naming conventions
    function generateBlockName() {
        // Start with 'block' to ensure it never starts with a number
        return 'gb' + Math.random().toString(36).substring(2, 6);
    }

    // Save block
    function saveBlock() {
        const blockTitle = $('#block-title').val();
        const blockDescription = $('#block-description').val();
        const blockCategory = $('#block-category').val();
        const blockIcon = $('#block-icon').val();
        const blockTemplate = $('#code-tab pre').text();

        if (!blockTitle || !blockTemplate) {
            showNotification('Please fill in all required fields', 'error');
            return;
        }

        // Use the stored block name instead of generating a new one
        const formData = new FormData();
        formData.append('action', 'genb_save_block');
        formData.append('nonce', genBlocksData.saveNonce);
        formData.append('name', currentBlockName);
        formData.append('title', blockTitle);
        formData.append('template', blockTemplate);
        formData.append('description', blockDescription);
        formData.append('category', blockCategory);
        formData.append('icon', blockIcon);

        $.ajax({
            url: genBlocksData.ajaxUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    closeModal();
                    // Redirect to edit block page
                    window.location.href = `${genBlocksData.ajaxUrl.replace('admin-ajax.php', '')}admin.php?page=gen-blocks-edit&block=${currentBlockName}&saved=1`;
                } else {
                    showNotification(response.data.message || 'Failed to save block', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                showNotification('An error occurred while saving the block', 'error');
            }
        });
    }

    // Handle save block button click
    $('#save-block').on('click', function() {
        // Show the modal
        const modal = $('#save-block-modal');
        modal.show();
        
        // Focus the input
        $('#block-title').focus();
        
    });

    // Handle confirm save button click
    $('#confirm-save').on('click', function() {
        saveBlock();
    });

    // Handle cancel button click
    $('#cancel-save').on('click', function() {
        closeModal();
    });

    function closeModal() {
        $('#save-block-modal').hide();
        // Clear form fields
        $('#block-title, #block-description, #block-icon').val('');
        // Reset icon picker
        $('#icon-picker-container').addClass('hidden');
        $('.icon-option').removeClass('selected');
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notice notice-${type} is-dismissible`;
        notification.innerHTML = `<p>${message}</p>`;
        
        const wrapper = document.querySelector('.wrap');
        if (wrapper) {
            wrapper.insertBefore(notification, wrapper.firstChild);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    }

    function showError(message) {
        const notice = $('<div class="notice notice-error is-dismissible"><p></p><button type="button" class="notice-dismiss"></button></div>')
            .find('p')
            .text(message)
            .end();
        
        // Add click handler for dismiss button
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(() => notice.remove());
        });

        // If we're in a modal, show the notice at the top of the modal content
        const activeModal = $('.genb-modal:visible .genb-modal-content');
        if (activeModal.length) {
            activeModal.prepend(notice);
            // Remove notice after 3 seconds
            setTimeout(() => notice.fadeOut(() => notice.remove()), 3000);
        } else {
            $('#response-container').prepend(notice);
        }
    }

    function showSuccess(message) {
        const notice = $('<div class="notice notice-success is-dismissible"><p></p><button type="button" class="notice-dismiss"></button></div>')
            .find('p')
            .text(message)
            .end();
            
        // Add click handler for dismiss button
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(() => notice.remove());
        });

        // If we're in a modal, show the notice at the top of the modal content
        const activeModal = $('.genb-modal:visible .genb-modal-content');
        if (activeModal.length) {
            activeModal.prepend(notice);
            // Remove notice after 3 seconds
            setTimeout(() => notice.fadeOut(() => notice.remove()), 3000);
        } else {
            $('#response-container').prepend(notice);
        }
    }

    // Handle update block button click
    $('#update-block').on('click', function() {
        const title = $('#block-title').val().trim();
        const description = $('#block-description').val().trim();
        const category = $('#block-category').val();
        const icon = $('#block-icon').val();
        const template = $('#code-tab pre').text();
        const blockName = $('#block-name').val();

        if (!title) {
            showError('Please enter a block title');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'genb_update_block');
        formData.append('nonce', genBlocksData.updateNonce);
        formData.append('name', blockName);
        formData.append('title', title);
        formData.append('description', description);
        formData.append('category', category);
        formData.append('icon', icon);
        formData.append('template', template);

        $.ajax({
            url: genBlocksData.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showSuccess('Block updated successfully!');
                } else {
                    showError(response.data?.message || 'Failed to update block');
                }
            },
            error: function(xhr, status, error) {
                showError('Failed to update block: ' + error);
            }
        });
    });

    function initializeIconPicker() {
        const iconPickerButton = $('.icon-picker-button');
        const iconPickerContainer = $('#icon-picker-container');
        const iconInput = $('#block-icon');
        
        // Only initialize once
        if (iconPickerContainer.data('initialized')) {
            return;
        }

        
        // Create icon picker UI if not already created
        if (iconPickerContainer.length && !iconPickerContainer.html().trim()) {
            const dashicons = [
                'admin-appearance', 'admin-comments', 'admin-home', 'admin-media', 'admin-page', 'admin-post',
                'admin-settings', 'admin-users', 'align-center', 'align-left', 'align-right', 'analytics',
                'archive', 'arrow-down', 'arrow-left', 'arrow-right', 'arrow-up', 'art', 'awards', 'backup',
                'block-default', 'button', 'calendar', 'camera', 'category', 'chart-area', 'chart-bar',
                'chart-line', 'chart-pie', 'clipboard', 'clock', 'cloud', 'columns'
            ];

            let iconsHtml = '';
            dashicons.forEach(icon => {
                iconsHtml += `
                    <div class="icon-option" data-icon="${icon}" title="${icon}">
                        <span class="dashicons dashicons-${icon}"></span>
                    </div>
                `;
            });
            iconPickerContainer.html(iconsHtml);
        }

        // Mark as initialized
        iconPickerContainer.data('initialized', true);

        // Toggle icon picker
        iconPickerButton.off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            iconPickerContainer.toggleClass('hidden');
        });

        // Handle icon selection
        iconPickerContainer.find('.icon-option').off('click').on('click', function() {
            const selectedIcon = $(this).data('icon');
            iconInput.val(selectedIcon);
            
            // Update selected state
            iconPickerContainer.find('.icon-option').removeClass('selected');
            $(this).addClass('selected');
            
            // Update preview
            const iconPreview = $('<span class="dashicons"></span>')
                .addClass('dashicons-' + selectedIcon)
                .css({
                    'position': 'absolute',
                    'left': '8px',
                    'top': '50%',
                    'transform': 'translateY(-50%)'
                });
            
            iconInput.prev('.dashicons').remove();
            iconInput.before(iconPreview);
            iconInput.css('padding-left', '30px');
            
            // Hide picker
            iconPickerContainer.addClass('hidden');
        });

        // Close icon picker when clicking outside
        $(document).off('click.iconPicker').on('click.iconPicker', function(e) {
            if (!$(e.target).closest('.icon-picker-wrapper').length) {
                iconPickerContainer.addClass('hidden');
            }
        });
    }

    $('#input-type').on('change', function() {
        const type = $(this).val();
        if (type === 'html') {
            $('#html-input').removeClass('hidden');
            $('#url-input').addClass('hidden');
        } else {
            $('#html-input').addClass('hidden');
            $('#url-input').removeClass('hidden');
        }
    });

    $('#generate-from-html').on('click', function() {
        const type = $('#input-type').val();
        const html = type === 'html' ? $('#html-code').val().trim() : '';
        const url = type === 'url' ? $('#page-url').val().trim() : '';
        const model = $('#model-select').val();
        
        if (!html && !url) {
            showError('Please provide HTML code or URL');
            return;
        }

        const button = $(this);
        const spinner = button.siblings('.spinner');
        
        button.prop('disabled', true);
        spinner.addClass('is-active');

        // Generate block name
        currentBlockName = generateBlockName();
        console.log('GENB: Current block name:', currentBlockName);

        $.ajax({
            url: genBlocksData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'genb_analyze_html',
                nonce: genBlocksData.nonce,
                html: html,
                url: url,
                block_name: currentBlockName,
                model: model
            },
            success: function(response) {
                console.log('GENB: Response:', response);
                if (response.success) {
                    // Hide generation area and show response
                    $('#generation-area').addClass('hidden');
                    $('#response-container').removeClass('hidden');
                    $('.genb-layout').addClass('has-response');
                    
                    // Update preview and code
                    updatePreview(response.data.template);
                    $('#code-tab pre').text(response.data.template);
                    
                    showSuccess('Block generated successfully!');
                } else {
                    showError(response.data || 'Failed to generate block');
                }
            },
            error: function(xhr, status, error) {
                showError('Failed to generate block: ' + error);
            },
            complete: function() {
                button.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });
});