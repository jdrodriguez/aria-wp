/**
 * Aria Admin JavaScript
 * 
 * @package Aria
 * @since 1.0.0
 */

(function($) {
    'use strict';

    console.log('Aria admin.js loaded!');
    console.log('jQuery available:', typeof $);
    console.log('ariaAdmin object:', typeof ariaAdmin !== 'undefined' ? ariaAdmin : 'undefined');

    /**
     * Aria Admin Controller
     */
    class AriaAdmin {
        constructor() {
            this.init();
            this.bindEvents();
        }

        init() {
            // Initialize color pickers
            if ($.fn.wpColorPicker) {
                $('.aria-color-picker').wpColorPicker();
            }

            // Initialize tooltips
            this.initTooltips();

            // Initialize charts if on dashboard
            if ($('#aria-conversation-chart').length) {
                this.initDashboardCharts();
            }

            // Initialize personality preview
            if ($('.aria-personality-form').length) {
                this.initPersonalityPreview();
            }

            // Initialize design preview
            if ($('.aria-design-form').length) {
                this.initDesignPreview();
            }
        }

        bindEvents() {
            // Dashboard events
            $(document).on('click', '.aria-stat-card', this.handleStatCardClick.bind(this));

            // Knowledge base events
            $(document).on('click', '#add-custom-response', this.addCustomResponse.bind(this));
            $(document).on('click', '.aria-remove-response', this.removeCustomResponse.bind(this));
            $(document).on('click', '.aria-import-trigger', this.handleImport.bind(this));

            // Personality events
            $(document).on('change', 'input[name="business_type"]', this.updateRecommendedTone.bind(this));
            $(document).on('change', 'input[name="tone_setting"], input[name="personality_traits[]"]', this.updatePersonalityPreview.bind(this));
            $(document).on('input', '#greeting_message', this.updateGreetingPreview.bind(this));

            // Design events
            $(document).on('change', 'input[name="button_icon"]', this.handleIconChange.bind(this));
            $(document).on('change', '#show_avatar', this.toggleAvatarOptions.bind(this));
            $(document).on('change', 'input[name="avatar_style"]', this.handleAvatarChange.bind(this));
            $(document).on('click', '#upload_icon_button', this.openMediaUploader.bind(this, 'icon'));
            $(document).on('click', '#upload_avatar_button', this.openMediaUploader.bind(this, 'avatar'));
            $(document).on('click', '.aria-device-btn', this.switchDevicePreview.bind(this));

            // AI Config events
            $(document).on('change', '#ai_provider', this.switchAIProvider.bind(this));
            $(document).on('click', '#toggle-api-key', this.toggleApiKey.bind(this));
            $(document).on('click', '#test-api-connection', this.testApiConnection.bind(this));
            $(document).on('click', '#test-saved-api', this.testSavedApiKey.bind(this));
            $(document).on('input', '#openai_temperature', this.updateTemperatureValue.bind(this));

            // Conversation events
            $(document).on('change', '#cb-select-all', this.toggleAllCheckboxes.bind(this));
            // Conversation actions handled by inline JS on conversation view page

            // Settings events
            $(document).on('change', '.aria-schedule-table input[type="checkbox"]', this.toggleScheduleInputs.bind(this));
            $(document).on('change', 'input[name="show_on_pages[]"][value="all"]', this.handleShowOnAllPages.bind(this));
            $(document).on('click', '#deactivate-license', this.deactivateLicense.bind(this));

            // Vector system events
            $(document).on('click', '.aria-retry-failed', this.retryFailedProcessing.bind(this));

            // General form submission
            $(document).on('submit', '.aria-settings-form', this.handleFormSubmit.bind(this));
        }

        /**
         * Initialize tooltips
         */
        initTooltips() {
            $('.aria-tooltip').each(function() {
                $(this).tooltip({
                    position: {
                        my: 'center bottom-10',
                        at: 'center top',
                        using: function(position, feedback) {
                            $(this).css(position);
                            $('<div>')
                                .addClass('aria-tooltip-arrow')
                                .addClass(feedback.vertical)
                                .addClass(feedback.horizontal)
                                .appendTo(this);
                        }
                    }
                });
            });
        }

        /**
         * Initialize dashboard charts
         */
        initDashboardCharts() {
            const ctx = document.getElementById('aria-conversation-chart');
            if (!ctx) return;

            // Sample data - in production, this would come from the server
            const chartData = {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Conversations',
                    data: [12, 19, 15, 25, 22, 30, 28],
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.4
                }]
            };

            new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        /**
         * Initialize personality preview
         */
        initPersonalityPreview() {
            this.personalityPreviewTimer = null;
            this.updatePersonalityPreview();
        }

        /**
         * Initialize design preview
         */
        initDesignPreview() {
            this.designPreviewTimer = null;
            this.updateDesignPreview();
        }

        /**
         * Handle stat card clicks
         */
        handleStatCardClick(e) {
            const $card = $(e.currentTarget);
            const statType = $card.data('stat-type');
            
            // Navigate to relevant page based on stat type
            switch(statType) {
                case 'conversations':
                    window.location.href = ariaAdmin.adminUrl + 'admin.php?page=aria-conversations';
                    break;
                case 'knowledge':
                    window.location.href = ariaAdmin.adminUrl + 'admin.php?page=aria-knowledge';
                    break;
                case 'license':
                    window.location.href = ariaAdmin.adminUrl + 'admin.php?page=aria-settings&tab=license';
                    break;
            }
        }

        /**
         * Add custom response row
         */
        addCustomResponse(e) {
            e.preventDefault();
            const $container = $('#custom-responses-container');
            const $newRow = $('.aria-custom-response-row:first').clone();
            
            $newRow.find('input, textarea').val('');
            $container.append($newRow);
        }

        /**
         * Remove custom response row
         */
        removeCustomResponse(e) {
            e.preventDefault();
            const $row = $(e.currentTarget).closest('.aria-custom-response-row');
            
            if ($('.aria-custom-response-row').length > 1) {
                $row.fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                $row.find('input, textarea').val('');
            }
        }

        /**
         * Handle import triggers
         */
        handleImport(e) {
            e.preventDefault();
            const importType = $(e.currentTarget).data('type');
            
            // In production, this would open a modal or redirect to import page
            alert(`Import feature for ${importType} coming soon!`);
        }

        /**
         * Update recommended tone based on business type
         */
        updateRecommendedTone(e) {
            const businessType = $(e.target).val();
            // Update UI to highlight recommended tone
            $('.aria-tone-option').removeClass('recommended');
            $(`.aria-tone-option[data-business-type="${businessType}"]`).addClass('recommended');
        }

        /**
         * Update personality preview
         */
        updatePersonalityPreview() {
            clearTimeout(this.personalityPreviewTimer);
            
            this.personalityPreviewTimer = setTimeout(() => {
                const tone = $('input[name="tone_setting"]:checked').val();
                const traits = [];
                
                $('input[name="personality_traits[]"]:checked').each(function() {
                    traits.push($(this).val());
                });

                // Update preview messages based on selections
                this.updatePreviewMessage('greeting', tone, traits);
                this.updatePreviewMessage('product', tone, traits);
                this.updatePreviewMessage('unknown', tone, traits);
            }, 300);
        }

        /**
         * Update preview message
         */
        updatePreviewMessage(type, tone, traits) {
            const messages = {
                greeting: {
                    professional: "Hello. I'm Aria, your assistant. How may I help you today?",
                    friendly: "Hi! I'm Aria, and I'm here to help. What brings you here today?",
                    casual: "Hey there! I'm Aria 👋 What can I help you with?"
                },
                product: {
                    professional: "I'd be happy to provide information about our products. We offer a comprehensive range of solutions designed to meet your business needs.",
                    friendly: "I'd love to tell you about our products! We have some great options that might be perfect for what you're looking for.",
                    casual: "Sure thing! Let me tell you about our awesome products - I think you'll really like what we have to offer!"
                },
                unknown: {
                    professional: "I apologize, but I don't have information about that specific topic. Would you like me to connect you with a specialist who can assist you?",
                    friendly: "Hmm, I'm not sure about that one, but I'd be happy to find someone who can help you with this question!",
                    casual: "Oops, that's not something I know about yet! But hey, let me get someone who can definitely help you out!"
                }
            };

            const message = messages[type][tone] || messages[type]['professional'];
            $(`#preview-${type}`).text(message);
        }

        /**
         * Update greeting preview
         */
        updateGreetingPreview(e) {
            const greeting = $(e.target).val() || "Hello! I'm Aria. How can I help you today?";
            $('#preview-greeting').text(greeting);
        }

        /**
         * Handle icon change
         */
        handleIconChange(e) {
            const iconType = $(e.target).val();
            $('#custom-icon-upload').toggle(iconType === 'custom');
            this.updateDesignPreview();
        }

        /**
         * Toggle avatar options
         */
        toggleAvatarOptions(e) {
            const showAvatar = $(e.target).is(':checked');
            $('#avatar-style-row').toggle(showAvatar);
            this.updateDesignPreview();
        }

        /**
         * Handle avatar change
         */
        handleAvatarChange(e) {
            const avatarStyle = $(e.target).val();
            $('#custom-avatar-upload').toggle(avatarStyle === 'custom');
            this.updateDesignPreview();
        }

        /**
         * Open media uploader
         */
        openMediaUploader(type, e) {
            e.preventDefault();
            
            const mediaUploader = wp.media({
                title: type === 'icon' ? ariaAdmin.strings.chooseIcon : ariaAdmin.strings.chooseAvatar,
                button: {
                    text: type === 'icon' ? ariaAdmin.strings.useIcon : ariaAdmin.strings.useAvatar
                },
                multiple: false
            });

            mediaUploader.on('select', () => {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                
                if (type === 'icon') {
                    $('#custom_icon').val(attachment.url);
                    $('#custom_icon_preview').html(`<img src="${attachment.url}" style="max-width: 60px; margin-left: 10px; vertical-align: middle;" />`);
                } else {
                    $('#custom_avatar').val(attachment.url);
                    $('input[value="custom"] + .aria-avatar-preview').html(`<img src="${attachment.url}" />`);
                }
                
                this.updateDesignPreview();
            });

            mediaUploader.open();
        }

        /**
         * Switch device preview
         */
        switchDevicePreview(e) {
            const $btn = $(e.currentTarget);
            const device = $btn.data('device');
            
            $('.aria-device-btn').removeClass('active');
            $btn.addClass('active');
            
            $('.aria-preview-screen').toggleClass('mobile-view', device === 'mobile');
        }

        /**
         * Update design preview
         */
        updateDesignPreview() {
            clearTimeout(this.designPreviewTimer);
            
            this.designPreviewTimer = setTimeout(() => {
                const settings = {
                    position: $('#position').val(),
                    theme: $('#theme').val(),
                    primaryColor: $('#primary_color').val(),
                    secondaryColor: $('#secondary_color').val(),
                    textColor: $('#text_color').val(),
                    buttonSize: $('#button_size').val(),
                    buttonStyle: $('#button_style').val(),
                    showAvatar: $('#show_avatar').is(':checked')
                };

                this.applyPreviewStyles(settings);
            }, 300);
        }

        /**
         * Apply preview styles
         */
        applyPreviewStyles(settings) {
            const $preview = $('#aria-preview-widget');
            
            // Position
            $preview.removeClass('position-bottom-right position-bottom-left position-bottom-center')
                    .addClass('position-' + settings.position);
            
            // Theme
            $preview.removeClass('theme-light theme-dark')
                    .addClass('theme-' + settings.theme);
            
            // Colors
            $preview.find('.aria-chat-header').css('background-color', settings.primaryColor);
            $preview.find('.aria-chat-button-preview').css('background-color', settings.primaryColor);
            $preview.find('.aria-message-user .aria-message-content').css('background-color', settings.secondaryColor);
            
            // Button size
            const sizeMap = {
                'small': '50px',
                'medium': '60px',
                'large': '70px'
            };
            $preview.find('.aria-chat-button-preview').css({
                'width': sizeMap[settings.buttonSize],
                'height': sizeMap[settings.buttonSize]
            });
            
            // Button style
            $preview.find('.aria-chat-button-preview')
                    .removeClass('style-rounded style-square style-circle')
                    .addClass('style-' + settings.buttonStyle);
            
            // Avatar visibility
            $preview.find('.aria-message-avatar, .aria-avatar-preview-small')
                    .toggle(settings.showAvatar);
        }

        /**
         * Switch AI provider
         */
        switchAIProvider(e) {
            const provider = $(e.target).val();
            $('.aria-provider-settings').hide();
            $(`#${provider}-settings`).show();
            this.updateApiKeyHelp(provider);
        }

        /**
         * Update API key help text
         */
        updateApiKeyHelp(provider) {
            let helpText = '';
            
            if (provider === 'openai') {
                helpText = `<p class="description">${ariaAdmin.strings.getApiKey} <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>. ${ariaAdmin.strings.apiKeyFormat}</p>`;
            } else if (provider === 'gemini') {
                helpText = `<p class="description">${ariaAdmin.strings.getApiKey} <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>.</p>`;
            }
            
            $('#api-key-help').html(helpText);
        }

        /**
         * Toggle API key visibility
         */
        toggleApiKey(e) {
            e.preventDefault();
            const $input = $('#api_key');
            const $button = $(e.currentTarget);
            const isPassword = $input.attr('type') === 'password';
            
            $input.attr('type', isPassword ? 'text' : 'password');
            $button.find('.dashicons')
                   .toggleClass('dashicons-visibility', !isPassword)
                   .toggleClass('dashicons-hidden', isPassword);
            
            // Update button text
            const buttonText = isPassword ? ' Hide' : ' Show';
            $button.contents().filter(function() {
                return this.nodeType === 3; // Text node
            }).last().replaceWith(buttonText);
        }

        /**
         * Test API connection
         */
        testApiConnection(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const provider = $('#ai_provider').val();
            let apiKey = $('#api_key').val();
            
            console.log('Test API Connection - Provider:', provider, 'Key length:', apiKey ? apiKey.length : 0);
            console.log('API Key value:', JSON.stringify(apiKey));
            console.log('API Key trimmed:', JSON.stringify(apiKey.trim()));
            console.log('ariaAdmin object:', ariaAdmin);
            
            // Trim the API key to remove any spaces
            apiKey = apiKey ? apiKey.trim() : '';
            
            // If no new key entered, test the saved one
            if (!apiKey || apiKey.length === 0) {
                console.log('Testing saved API key...');
                // Test saved key
                $button.prop('disabled', true)
                       .find('.dashicons').addClass('spin');
                
                $.post(ariaAdmin.ajaxUrl, {
                    action: 'aria_test_saved_api',
                    provider: provider,
                    nonce: ariaAdmin.nonce
                }, (response) => {
                    $button.prop('disabled', false)
                           .find('.dashicons').removeClass('spin');
                    
                    console.log('Test saved API response:', response);
                    
                    if (response && response.success) {
                        const message = (response.data && response.data.message) || 
                                      (ariaAdmin && ariaAdmin.strings && ariaAdmin.strings.apiConnected) || 
                                      'API connected successfully!';
                        alert(message);
                    } else {
                        const message = (response && response.data && response.data.message) || 
                                      (ariaAdmin && ariaAdmin.strings && ariaAdmin.strings.apiError) || 
                                      'API connection failed. Please check your credentials.';
                        alert(message);
                    }
                }).fail((xhr, status, error) => {
                    console.error('AJAX failed:', status, error);
                    $button.prop('disabled', false)
                           .find('.dashicons').removeClass('spin');
                    alert('Request failed: ' + error);
                });
                return;
            }
            
            // Check if it's a masked key
            if (apiKey.indexOf('*') !== -1) {
                console.log('Detected masked key');
                const message = (ariaAdmin && ariaAdmin.strings && ariaAdmin.strings.enterValidKey) || 
                              'Please enter a valid API key.';
                alert(message);
                return;
            }
            
            $button.prop('disabled', true)
                   .find('.dashicons').addClass('spin');
            
            $.post(ariaAdmin.ajaxUrl, {
                action: 'aria_test_api',
                provider: provider,
                api_key: apiKey,
                nonce: ariaAdmin.nonce
            }, (response) => {
                $button.prop('disabled', false)
                       .find('.dashicons').removeClass('spin');
                
                if (response && response.success) {
                    const message = (response.data && response.data.message) || 
                                  (ariaAdmin && ariaAdmin.strings && ariaAdmin.strings.apiConnected) || 
                                  'API connected successfully!';
                    alert(message);
                } else {
                    const message = (response && response.data && response.data.message) || 
                                  (ariaAdmin && ariaAdmin.strings && ariaAdmin.strings.apiError) || 
                                  'API connection failed. Please check your credentials.';
                    alert(message);
                }
            }).fail((xhr, status, error) => {
                console.error('AJAX failed:', status, error);
                $button.prop('disabled', false)
                       .find('.dashicons').removeClass('spin');
                alert('Request failed: ' + error);
            });
        }

        /**
         * Test saved API key directly
         */
        testSavedApiKey(e) {
            e.preventDefault();
            
            // Simple redirect approach - let PHP handle it directly
            const currentUrl = window.location.href;
            const separator = currentUrl.includes('?') ? '&' : '?';
            window.location.href = currentUrl + separator + 'test_key=1';
        }

        /**
         * Update temperature value display
         */
        updateTemperatureValue(e) {
            $('#temperature-value').text($(e.target).val());
        }

        /**
         * Toggle all checkboxes
         */
        toggleAllCheckboxes(e) {
            const isChecked = $(e.target).is(':checked');
            $('input[name="conversation_ids[]"], input[name="knowledge_ids[]"]').prop('checked', isChecked);
        }

        // Conversation methods removed - handled by inline JS on conversation page

        /**
         * Toggle schedule inputs
         */
        toggleScheduleInputs(e) {
            const $checkbox = $(e.target);
            const $timeInputs = $checkbox.closest('tr').find('.aria-time-inputs');
            $timeInputs.toggle($checkbox.is(':checked'));
        }

        /**
         * Handle show on all pages
         */
        handleShowOnAllPages(e) {
            const $checkbox = $(e.target);
            const isChecked = $checkbox.is(':checked');
            
            $('input[name="show_on_pages[]"]:not([value="all"])')
                .prop('checked', false)
                .prop('disabled', isChecked);
        }

        /**
         * Deactivate license
         */
        deactivateLicense(e) {
            if (confirm(ariaAdmin.strings.confirmDeactivate)) {
                $('#license_key').val('');
                $(e.target).closest('form').submit();
            }
        }

        /**
         * Retry failed processing
         */
        retryFailedProcessing(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const nonce = $button.data('nonce');
            
            if (!confirm(ariaAdmin.strings.confirmRetryFailed || 'Are you sure you want to retry failed processing entries?')) {
                return;
            }
            
            // Show loading state
            $button.prop('disabled', true)
                   .html('<span class="dashicons dashicons-update aria-spin"></span> ' + (ariaAdmin.strings.retrying || 'Retrying...'));
            
            $.post(ariaAdmin.ajaxUrl, {
                action: 'aria_retry_failed_processing',
                nonce: nonce
            })
            .done((response) => {
                if (response.success) {
                    alert(response.data.message || ariaAdmin.strings.retrySuccess || 'Failed entries have been scheduled for retry.');
                    // Refresh the page to show updated stats
                    location.reload();
                } else {
                    alert(response.data.message || ariaAdmin.strings.retryError || 'Failed to retry processing entries.');
                }
            })
            .fail(() => {
                alert(ariaAdmin.strings.ajaxError || 'Request failed. Please try again.');
            })
            .always(() => {
                $button.prop('disabled', false)
                       .text(ariaAdmin.strings.retryFailed || 'Retry Failed');
            });
        }

        /**
         * Handle form submission
         */
        handleFormSubmit(e) {
            const $form = $(e.target);
            const $submitButton = $form.find('button[type="submit"]');
            
            // Show saving state for settings forms
            $submitButton.prop('disabled', true).text(ariaAdmin && ariaAdmin.strings && ariaAdmin.strings.saving || 'Saving...');
            
            // Form will submit normally via PHP
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        window.ariaAdmin = new AriaAdmin();
    });

})(jQuery);