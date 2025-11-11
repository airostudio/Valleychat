/**
 * Valley Virtual Assistant - Frontend JavaScript
 */

(function($) {
    'use strict';

    // Check if jQuery is available
    if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
        console.error('VVA: jQuery is not loaded!');
        return;
    }

    console.log('VVA: Initializing Valley Virtual Assistant...');
    console.log('VVA: User Agent:', navigator.userAgent);
    console.log('VVA: Screen size:', window.innerWidth + 'x' + window.innerHeight);
    console.log('VVA: Touch support:', 'ontouchstart' in window);

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
            console.log('VVA: Binding events to toggle button');

            // Toggle chat window - use both click and touchend for mobile
            this.$toggle.on('click touchend', (e) => {
                e.preventDefault();
                console.log('VVA: Toggle button clicked/touched');
                this.toggleChat();
            });

            this.$minimize.on('click touchend', (e) => {
                e.preventDefault();
                console.log('VVA: Minimize button clicked');
                this.closeChat();
            });

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

                console.log('VVA: Full AJAX response:', response);

                if (response.success) {
                    console.log('VVA: Assistant message:', response.data.message);
                    console.log('VVA: Products data:', response.data.products);
                    console.log('VVA: Number of products:', response.data.products ? response.data.products.length : 0);

                    // Add assistant message
                    this.addMessage(response.data.message, 'assistant', response.data.products);
                } else {
                    console.error('VVA: Response failed:', response);
                    this.addMessage('Sorry, I encountered an error. Please try again.', 'assistant');
                }
            } catch (error) {
                this.hideTypingIndicator();
                this.addMessage('Sorry, something went wrong. Please try again.', 'assistant');
                console.error('Error sending message:', error);
            }
        }

        addMessage(content, role, products = []) {
            console.log('VVA: addMessage called with role:', role, 'products:', products);

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
                console.log('VVA: Creating products container with', products.length, 'products');

                const $productsContainer = $('<div>', {
                    class: 'vva-products-container'
                });

                products.forEach(product => {
                    console.log('VVA: Processing product:', product);
                    console.log('VVA: Product ID:', product.id, 'Name:', product.name);
                    console.log('VVA: Product has image field?', 'image' in product);
                    console.log('VVA: Product image value:', product.image);
                    console.log('VVA: Product image type:', typeof product.image);

                    const $productCard = this.createProductCard(product);
                    $productsContainer.append($productCard);
                });

                $message.append($productsContainer);
                console.log('VVA: Products container appended to message');
            } else {
                console.log('VVA: No products to display. Role:', role, 'Products:', products);
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

            // Product Image (Top)
            const $image = $('<div>', {
                class: 'vva-product-image'
            });

            console.log('VVA: [createProductCard] Checking image for product', product.id);
            console.log('VVA: [createProductCard] product.image exists?', !!product.image);
            console.log('VVA: [createProductCard] product.image value:', product.image);

            if (product.image) {
                console.log('VVA: [createProductCard] Creating IMG element with src:', product.image);

                // Create img element using vanilla JS for better control (no lazy loading)
                const img = document.createElement('img');
                img.src = product.image;
                img.alt = product.name || 'Product Image';
                img.className = 'vva-product-img';

                // Explicitly set styles to ensure visibility
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                img.style.display = 'block';

                // Add load event listener
                img.addEventListener('load', function() {
                    console.log('VVA: ✅ [IMG LOAD SUCCESS] Image loaded successfully:', product.image);
                });

                // Add error event listener with fallback
                let errorCount = 0;
                img.addEventListener('error', function() {
                    errorCount++;
                    console.error('VVA: ❌ [IMG LOAD ERROR] Failed to load image (attempt ' + errorCount + '):', product.image);

                    if (errorCount === 1) {
                        // Try removing any query strings first
                        const cleanUrl = product.image.split('?')[0];
                        if (cleanUrl !== product.image) {
                            console.log('VVA: [IMG RETRY] Trying without query string:', cleanUrl);
                            this.src = cleanUrl;
                            return;
                        }
                    }

                    // Use placeholder SVG as final fallback
                    console.log('VVA: [IMG FALLBACK] Using placeholder SVG');
                    this.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"%3E%3Crect fill="%23f0f0f0" width="200" height="200"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999" font-family="Arial" font-size="14"%3ENo Image%3C/text%3E%3C/svg%3E';
                });

                $image.append(img);
                console.log('VVA: [createProductCard] IMG element appended to image container');
            } else {
                console.warn('VVA: [createProductCard] No image URL for product:', product.id, product.name);
                console.warn('VVA: [createProductCard] Showing placeholder instead');
                // Add placeholder
                $image.html('<div class="vva-image-placeholder">No Image Available</div>');
            }
            $card.append($image);

            // Add to Cart Button (Prominent - below image)
            const $addToCartBtn = $('<button>', {
                class: 'vva-add-to-cart-btn vva-add-to-cart-primary',
                html: product.in_stock ? '<span class="vva-cart-icon">🛒</span> Add to Cart' : 'Out of Stock',
                disabled: !product.in_stock
            });

            if (product.in_stock) {
                $addToCartBtn.on('click touchend', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.handleAddToCart(product.id, $addToCartBtn);
                });
            }

            $card.append($addToCartBtn);

            // Product Info (Name and Price)
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

            // Product Description (Bottom)
            if (product.short_description) {
                const $description = $('<div>', {
                    class: 'vva-product-description',
                    html: product.short_description
                });
                $card.append($description);
            }

            // View Product Link
            const $link = $('<a>', {
                href: product.url,
                class: 'vva-product-link',
                text: 'View Full Details',
                target: '_blank'
            });
            $card.append($link);

            return $card;
        }

        async handleAddToCart(productId, $button) {
            const originalHtml = $button.html();
            $button.prop('disabled', true).html('<span class="vva-cart-icon">⏳</span> Adding...');

            console.log('VVA: [ADD TO CART] Starting add to cart process');
            console.log('VVA: [ADD TO CART] Product ID:', productId);
            console.log('VVA: [ADD TO CART] AJAX URL:', vvaData.ajaxUrl);
            console.log('VVA: [ADD TO CART] Nonce:', vvaData.nonce ? 'Present' : 'MISSING');

            try {
                console.log('VVA: [ADD TO CART] Sending AJAX request...');

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

                console.log('VVA: [ADD TO CART] ✅ AJAX response received:', response);

                if (response.success) {
                    console.log('VVA: [ADD TO CART] ✅ SUCCESS - Product added to cart');
                    console.log('VVA: [ADD TO CART] Response data:', response.data);

                    $button.html('<span class="vva-cart-icon">✓</span> Added!').addClass('added');

                    // Show success message
                    this.addMessage('Great choice! I\'ve added that to your cart. ' + response.data.message, 'assistant');

                    // Update cart fragments (WooCommerce standard method)
                    if (response.data.fragments) {
                        console.log('VVA: [ADD TO CART] Updating cart fragments:', response.data.fragments);

                        // Update each fragment in the DOM
                        $.each(response.data.fragments, function(key, value) {
                            console.log('VVA: [ADD TO CART] Updating fragment:', key);
                            $(key).replaceWith(value);
                        });

                        // Trigger WooCommerce cart updated events
                        $(document.body).trigger('wc_fragment_refresh');
                        $(document.body).trigger('wc_fragments_refreshed');
                        $(document.body).trigger('added_to_cart', [response.data.fragments, '', $button]);

                        console.log('VVA: [ADD TO CART] ✅ WooCommerce cart events triggered');
                    }

                    // Update cart count if element exists
                    if (response.data.cart_count !== undefined) {
                        $('.cart-contents-count, .cart-count').text(response.data.cart_count);
                        $('.cart-contents-count, .cart-count').html(response.data.cart_count);
                        console.log('VVA: [ADD TO CART] Cart count updated to:', response.data.cart_count);
                    }

                    // VISUAL FEEDBACK: Make cart visible to customer
                    this.showCartAddedFeedback(response.data);

                    // Reset button after 2 seconds
                    setTimeout(() => {
                        $button.html(originalHtml).removeClass('added').prop('disabled', false);
                    }, 2000);
                } else {
                    console.error('VVA: [ADD TO CART] ❌ FAILED - Response not successful');
                    console.error('VVA: [ADD TO CART] Error data:', response.data);

                    $button.html('<span class="vva-cart-icon">✗</span> Failed').addClass('error');
                    const errorMsg = response.data && response.data.message ? response.data.message : 'Please try again.';
                    this.addMessage('Sorry, I couldn\'t add that to your cart. ' + errorMsg, 'assistant');

                    setTimeout(() => {
                        $button.html(originalHtml).removeClass('error').prop('disabled', false);
                    }, 3000);
                }
            } catch (error) {
                console.error('VVA: [ADD TO CART] ❌ EXCEPTION - AJAX request failed');
                console.error('VVA: [ADD TO CART] Error object:', error);
                console.error('VVA: [ADD TO CART] Error status:', error.status);
                console.error('VVA: [ADD TO CART] Error text:', error.statusText);
                console.error('VVA: [ADD TO CART] Response text:', error.responseText);

                $button.html('<span class="vva-cart-icon">✗</span> Error').addClass('error');
                this.addMessage('Sorry, something went wrong. Please try again.', 'assistant');

                setTimeout(() => {
                    $button.html(originalHtml).removeClass('error').prop('disabled', false);
                }, 3000);
            }
        }

        showCartAddedFeedback(data) {
            console.log('VVA: [CART FEEDBACK] Showing visual feedback to customer');

            // 1. Try to open mini-cart drawer (common theme patterns)
            const cartSelectors = [
                '.cart-contents',
                '.header-cart',
                '.mini-cart-trigger',
                '.cart-trigger',
                'a.cart-contents',
                '.widget_shopping_cart'
            ];

            let cartOpened = false;
            cartSelectors.forEach(selector => {
                const $cart = $(selector);
                if ($cart.length) {
                    console.log('VVA: [CART FEEDBACK] Found cart element:', selector);
                    $cart.trigger('click'); // Try to open it
                    cartOpened = true;
                }
            });

            // 2. Highlight/shake cart icon
            const $cartIcon = $('.cart-contents, .header-cart, .mini-cart-trigger, .cart-trigger');
            if ($cartIcon.length) {
                console.log('VVA: [CART FEEDBACK] Highlighting cart icon');
                $cartIcon.addClass('vva-cart-highlight');
                setTimeout(() => {
                    $cartIcon.removeClass('vva-cart-highlight');
                }, 2000);
            }

            // 3. Show floating success notification
            this.showCartNotification(data.product_name, data.cart_count);
        }

        showCartNotification(productName, cartCount) {
            // Remove any existing notification
            $('.vva-cart-notification').remove();

            // Create notification
            const $notification = $('<div>', {
                class: 'vva-cart-notification',
                html: `
                    <div class="vva-cart-notification-icon">✓</div>
                    <div class="vva-cart-notification-content">
                        <strong>Added to cart!</strong>
                        <p>${productName}</p>
                        <small>Cart has ${cartCount} item${cartCount !== 1 ? 's' : ''}</small>
                    </div>
                `
            });

            // Append to body
            $('body').append($notification);

            console.log('VVA: [CART FEEDBACK] Showing notification for:', productName);

            // Animate in
            setTimeout(() => {
                $notification.addClass('vva-cart-notification-show');
            }, 100);

            // Remove after 4 seconds
            setTimeout(() => {
                $notification.removeClass('vva-cart-notification-show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }, 4000);
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
        console.log('VVA: DOM ready, checking for widget...');

        // Only initialize if widget exists
        if ($('#vva-chat-widget').length) {
            console.log('VVA: Widget found, initializing...');
            try {
                new ValleyVirtualAssistant();
                console.log('VVA: Valley Virtual Assistant initialized successfully');
            } catch (error) {
                console.error('VVA: Failed to initialize:', error);
            }
        } else {
            console.warn('VVA: Widget element not found in DOM');
        }
    });

})(jQuery);
