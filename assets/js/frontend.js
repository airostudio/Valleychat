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
            this.ageVerified = this.checkAgeVerification();

            // Fun loading messages
            this.loadingMessages = [
                "Just popping out to the warehouse...",
                "Checking our stock for you...",
                "Let me grab that information...",
                "Searching through our collection...",
                "Finding the perfect match...",
                "Looking that up right now...",
                "Give me just a sec..."
            ];

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
            this.$disclaimer = $('#vva-age-disclaimer');
            this.$confirmAge = $('#vva-confirm-age');
            this.$declineAge = $('#vva-decline-age');
            this.$minimize = $('.vva-minimize');
            this.$form = $('#vva-chat-form');
            this.$input = $('#vva-message-input');
            this.$messagesContainer = $('#vva-messages-container');
            this.$typingIndicator = $('#vva-typing-indicator');
        }

        bindEvents() {
            // Toggle chat window
            this.$toggle.on('click', () => this.toggleChat());
            this.$minimize.on('click', () => this.closeChat());

            // Age verification
            this.$confirmAge.on('click', () => this.confirmAge());
            this.$declineAge.on('click', () => this.declineAge());

            // Submit message
            this.$form.on('submit', (e) => this.handleSubmit(e));

            // Auto-resize input
            this.$input.on('input', () => this.adjustInputHeight());
        }

        checkAgeVerification() {
            // Check if user previously verified age (stored for session)
            return sessionStorage.getItem('vva_age_verified') === 'true';
        }

        confirmAge() {
            this.ageVerified = true;
            sessionStorage.setItem('vva_age_verified', 'true');

            // Hide disclaimer, show chat
            this.$disclaimer.removeClass('visible');
            setTimeout(() => {
                this.$disclaimer.hide();
                this.showChat();
            }, 300);
        }

        declineAge() {
            // Close everything
            this.$disclaimer.removeClass('visible');
            this.$toggle.removeClass('active');
            setTimeout(() => {
                this.$disclaimer.hide();
            }, 300);
            this.isOpen = false;
        }

        applyCustomColors() {
            const primaryColor = vvaData.primaryColor || '#e91e63';
            document.documentElement.style.setProperty('--vva-primary-color', primaryColor);
        }

        toggleChat() {
            if (this.isOpen) {
                this.closeChat();
            } else {
                if (!this.ageVerified) {
                    // Show age disclaimer first
                    this.showDisclaimer();
                } else {
                    // Already verified, show chat directly
                    this.showChat();
                }
            }
        }

        showDisclaimer() {
            this.isOpen = true;
            this.$toggle.addClass('active');
            this.$disclaimer.show();
            setTimeout(() => {
                this.$disclaimer.addClass('visible');
            }, 10);
        }

        showChat() {
            this.isOpen = true;
            this.$toggle.addClass('active');
            this.$window.show();
            setTimeout(() => {
                this.$window.addClass('visible');
            }, 10);

            // Start conversation if not started
            if (!this.conversationId) {
                this.startConversation();
            }

            // Focus input
            this.$input.focus();

            // Scroll to bottom
            this.scrollToBottom();
        }

        closeChat() {
            this.isOpen = false;
            this.$window.removeClass('visible');
            this.$disclaimer.removeClass('visible');
            this.$toggle.removeClass('active');
            setTimeout(() => {
                this.$window.hide();
                this.$disclaimer.hide();
            }, 300);
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
                    this.addMessage(response.data.message, 'assistant', response.data.products);
                } else {
                    this.addMessage('Sorry, I encountered an error. Please try again.', 'assistant');
                }
            } catch (error) {
                this.hideTypingIndicator();
                this.addMessage('Sorry, something went wrong. Please try again.', 'assistant');
                console.error('Error sending message:', error);
            }
        }

        addMessage(content, role, products = []) {
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

            // Convert line breaks and links, strip product tags
            const formattedContent = this.formatMessage(content);
            $content.html(formattedContent);

            $message.append($content);

            // Add quick reply buttons if message contains button tags
            if (role === 'assistant') {
                const buttons = this.extractButtons(content);
                if (buttons.length > 0) {
                    const $buttonsContainer = this.createQuickButtons(buttons);
                    $message.append($buttonsContainer);
                }
            }

            // Add product cards if products exist
            if (role === 'assistant' && products && products.length > 0) {
                const $productsContainer = $('<div>', {
                    class: 'vva-products-container'
                });

                products.forEach(product => {
                    const $productCard = this.createProductCard(product);
                    $productsContainer.append($productCard);
                });

                $message.append($productsContainer);
            }

            this.$messagesContainer.append($message);
            this.scrollToBottom();
        }

        formatMessage(content) {
            // Strip [PRODUCT:id] tags (they're rendered as cards instead)
            let formatted = content.replace(/\[PRODUCT:\d+\]/g, '');

            // Strip [BUTTON:text] tags (they're rendered as buttons instead)
            formatted = formatted.replace(/\[BUTTON:[^\]]+\]/g, '');

            // Convert line breaks
            formatted = formatted.replace(/\n/g, '<br>');

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

            // Get random loading message
            const randomMessage = this.loadingMessages[Math.floor(Math.random() * this.loadingMessages.length)];
            this.$typingIndicator.find('.vva-typing-message').text(randomMessage);

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

        createProductCard(product) {
            console.log('VVA: Creating product card for:', product);

            const $card = $('<div>', {
                class: 'vva-product-card',
                'data-product-id': product.id
            });

            // Product Image
            const $image = $('<div>', {
                class: 'vva-product-image'
            });
            if (product.image) {
                console.log('VVA: Product image URL:', product.image);
                $image.append($('<img>', {
                    src: product.image,
                    alt: product.name,
                    onerror: function() {
                        console.error('VVA: Failed to load image:', product.image);
                        $(this).attr('src', 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"%3E%3Crect fill="%23f0f0f0" width="200" height="200"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999" font-family="Arial" font-size="14"%3ENo Image%3C/text%3E%3C/svg%3E');
                    }
                }));
            } else {
                console.warn('VVA: No image URL for product:', product.id, product.name);
                // Add placeholder
                $image.html('<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#999;font-size:14px;">No Image Available</div>');
            }
            $card.append($image);

            // Product Info
            const $info = $('<div>', {
                class: 'vva-product-info'
            });

            const $name = $('<h4>', {
                class: 'vva-product-name',
                text: product.name
            });
            $info.append($name);

            // Price
            const $price = $('<div>', {
                class: 'vva-product-price'
            });

            if (product.on_sale && product.sale_price) {
                $price.append($('<span>', {
                    class: 'vva-price-regular',
                    html: '<del>' + this.formatPrice(product.regular_price) + '</del>'
                }));
                $price.append($('<span>', {
                    class: 'vva-price-sale',
                    text: this.formatPrice(product.sale_price)
                }));
            } else {
                $price.append($('<span>', {
                    class: 'vva-price-current',
                    text: this.formatPrice(product.price)
                }));
            }
            $info.append($price);

            $card.append($info);

            // Product Actions Container
            const $actions = $('<div>', {
                class: 'vva-product-actions'
            });

            // Add to Cart Button
            const $button = $('<button>', {
                class: 'vva-add-to-cart-btn',
                text: product.in_stock ? 'Add to Cart' : 'Out of Stock',
                disabled: !product.in_stock
            });

            if (product.in_stock) {
                $button.on('click', () => this.handleAddToCart(product.id, $button));
            }

            $actions.append($button);

            // View Product Link
            const $link = $('<a>', {
                href: product.url,
                class: 'vva-product-link',
                text: 'View Details',
                target: '_blank'
            });
            $actions.append($link);

            $card.append($actions);

            return $card;
        }

        async handleAddToCart(productId, $button) {
            const originalText = $button.text();
            $button.prop('disabled', true).text('Adding...');

            console.log('VVA: Attempting to add product to cart:', productId);

            try {
                const response = await $.ajax({
                    url: vvaData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vva_add_to_cart',
                        nonce: vvaData.nonce,
                        product_id: productId,
                        quantity: 1
                    }
                });

                console.log('VVA: Add to cart response:', response);

                if (response.success) {
                    $button.text('✓ Added!').addClass('added');

                    // Show success message
                    this.addMessage('Great choice! I\'ve added that to your cart. ' + response.data.message, 'assistant');

                    // Reset button after 2 seconds
                    setTimeout(() => {
                        $button.text(originalText).removeClass('added').prop('disabled', false);
                    }, 2000);
                } else {
                    console.error('VVA: Add to cart failed:', response.data);
                    $button.text('Failed').addClass('error');
                    const errorMsg = response.data && response.data.message ? response.data.message : 'Please try again.';
                    this.addMessage('Sorry, I couldn\'t add that to your cart. ' + errorMsg, 'assistant');

                    setTimeout(() => {
                        $button.text(originalText).removeClass('error').prop('disabled', false);
                    }, 2000);
                }
            } catch (error) {
                console.error('VVA: Error adding to cart:', error);
                $button.text('Error').addClass('error');
                this.addMessage('Sorry, something went wrong. Please try again.', 'assistant');

                setTimeout(() => {
                    $button.text(originalText).removeClass('error').prop('disabled', false);
                }, 2000);
            }
        }

        extractButtons(content) {
            // Extract button patterns like [BUTTON:Yes] or [BUTTON:No, thanks]
            const buttonRegex = /\[BUTTON:([^\]]+)\]/g;
            const buttons = [];
            let match;

            while ((match = buttonRegex.exec(content)) !== null) {
                buttons.push(match[1].trim());
            }

            return buttons;
        }

        createQuickButtons(buttons) {
            const $container = $('<div>', {
                class: 'vva-quick-buttons'
            });

            buttons.forEach(buttonText => {
                const $button = $('<button>', {
                    class: 'vva-quick-button',
                    text: buttonText
                });

                $button.on('click', () => {
                    // Send button text as message
                    this.$input.val(buttonText);
                    this.$form.submit();

                    // Disable all buttons after click
                    $container.find('.vva-quick-button').prop('disabled', true).addClass('clicked');
                });

                $container.append($button);
            });

            return $container;
        }

        formatPrice(price) {
            return '$' + parseFloat(price).toFixed(2);
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
