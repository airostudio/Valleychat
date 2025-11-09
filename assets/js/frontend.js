/**
 * Valley Virtual Assistant - Frontend JavaScript
 */

(function($) {
    'use strict';

    // Valley Virtual Assistant Class
    class ValleyVirtualAssistant {
        constructor() {
            this.conversationId = null;
            this.isOpen = false;
            this.isTyping = false;

            this.init();
        }

        init() {
            this.cacheElements();
            this.bindEvents();
            this.applyCustomColors();
        }

        cacheElements() {
            this.$widget = $('#vva-chat-widget');
            this.$toggle = $('#vva-chat-toggle');
            this.$window = $('#vva-chat-window');
            this.$minimize = $('.vva-minimize');
            this.$form = $('#vva-chat-form');
            this.$input = $('#vva-message-input');
            this.$messagesContainer = $('#vva-messages-container');
            this.$typingIndicator = $('#vva-typing-indicator');
        }

        bindEvents() {
            // Toggle chat window
            this.$toggle.on('click', () => this.toggleChat());
            this.$minimize.on('click', () => this.toggleChat());

            // Submit message
            this.$form.on('submit', (e) => this.handleSubmit(e));

            // Auto-resize input
            this.$input.on('input', () => this.adjustInputHeight());
        }

        applyCustomColors() {
            const primaryColor = vvaData.primaryColor || '#e91e63';
            document.documentElement.style.setProperty('--vva-primary-color', primaryColor);
        }

        toggleChat() {
            this.isOpen = !this.isOpen;

            if (this.isOpen) {
                this.$window.show();
                setTimeout(() => {
                    this.$window.addClass('visible');
                    this.$toggle.addClass('active');
                }, 10);

                // Start conversation if not started
                if (!this.conversationId) {
                    this.startConversation();
                }

                // Focus input
                this.$input.focus();

                // Scroll to bottom
                this.scrollToBottom();
            } else {
                this.$window.removeClass('visible');
                this.$toggle.removeClass('active');
                setTimeout(() => {
                    this.$window.hide();
                }, 300);
            }
        }

        async startConversation() {
            try {
                const response = await $.ajax({
                    url: vvaData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vva_start_conversation',
                        nonce: vvaData.nonce
                    }
                });

                if (response.success) {
                    this.conversationId = response.data.conversation_id;
                }
            } catch (error) {
                console.error('Error starting conversation:', error);
            }
        }

        async handleSubmit(e) {
            e.preventDefault();

            const message = this.$input.val().trim();

            if (!message || this.isTyping) {
                return;
            }

            // Add user message to UI
            this.addMessage(message, 'user');

            // Clear input
            this.$input.val('');

            // Show typing indicator
            this.showTypingIndicator();

            try {
                const response = await $.ajax({
                    url: vvaData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vva_send_message',
                        nonce: vvaData.nonce,
                        message: message,
                        conversation_id: this.conversationId
                    }
                });

                // Hide typing indicator
                this.hideTypingIndicator();

                if (response.success) {
                    // Add assistant message
                    this.addMessage(response.data.message, 'assistant');
                } else {
                    this.addMessage('Sorry, I encountered an error. Please try again.', 'assistant');
                }
            } catch (error) {
                this.hideTypingIndicator();
                this.addMessage('Sorry, something went wrong. Please try again.', 'assistant');
                console.error('Error sending message:', error);
            }
        }

        addMessage(content, role) {
            const $message = $('<div>', {
                class: 'vva-message vva-message-' + role
            });

            if (role === 'assistant') {
                const $avatar = $('<img>', {
                    src: vvaData.assistantAvatar,
                    alt: vvaData.assistantName,
                    class: 'vva-message-avatar'
                });
                $message.append($avatar);
            }

            const $content = $('<div>', {
                class: 'vva-message-content'
            });

            // Convert line breaks and links
            const formattedContent = this.formatMessage(content);
            $content.html(formattedContent);

            $message.append($content);

            this.$messagesContainer.append($message);
            this.scrollToBottom();
        }

        formatMessage(content) {
            // Convert line breaks
            let formatted = content.replace(/\n/g, '<br>');

            // Convert URLs to links
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            formatted = formatted.replace(urlRegex, '<a href="$1" target="_blank">$1</a>');

            // Convert **bold** to <strong>
            formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

            // Convert *italic* to <em>
            formatted = formatted.replace(/\*(.+?)\*/g, '<em>$1</em>');

            return '<p>' + formatted + '</p>';
        }

        showTypingIndicator() {
            this.isTyping = true;
            this.$typingIndicator.show();
            this.scrollToBottom();
        }

        hideTypingIndicator() {
            this.isTyping = false;
            this.$typingIndicator.hide();
        }

        scrollToBottom() {
            setTimeout(() => {
                this.$messagesContainer.animate({
                    scrollTop: this.$messagesContainer[0].scrollHeight
                }, 300);
            }, 100);
        }

        adjustInputHeight() {
            // Auto-resize input (optional enhancement)
            // Can be implemented for multi-line support
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if widget exists
        if ($('#vva-chat-widget').length) {
            new ValleyVirtualAssistant();
        }
    });

})(jQuery);
