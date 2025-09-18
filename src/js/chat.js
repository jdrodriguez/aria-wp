/**
 * Aria Chat Widget JavaScript
 *
 * @package
 * @since 1.0.0
 */

(function () {
	'use strict';

	/**
	 * Aria Chat Widget Class
	 */
	class AriaChat {
		constructor(config) {
			this.config = Object.assign(
				{
					position: 'bottom-right',
					theme: 'light',
					primaryColor: '#2271b1',
					secondaryColor: '#f0f0f1',
					textColor: '#1e1e1e',
					chatWidth: 380,
					chatHeight: 600,
					buttonSize: 'medium',
					buttonStyle: 'rounded',
					buttonIcon: 'chat-bubble',
					customIcon: '',
					avatarStyle: 'initials',
					customAvatar: '',
					autoOpenDelay: 0,
					enableSound: true,
					enableAnimations: true,
					showAvatar: true,
					requireEmail: false,
					gdprEnabled: false,
					apiEndpoint: window.ariaChat
						? window.ariaChat.ajaxUrl
						: '/wp-admin/admin-ajax.php',
					mobileFullscreen: true,
					enableAnalytics: true,
					detectSystemTheme: true,
					debug: Boolean(window.ariaChat && window.ariaChat.debug),
					assetsBaseUrl: this.ensureTrailingSlash(
						window.ariaChat && window.ariaChat.pluginUrl
							? window.ariaChat.pluginUrl
							: '/wp-content/plugins/aria/'
					),
				},
				config
			);

			this.isOpen = false;
			this.isMinimized = false;
			this.conversationId = null;
			this.messageQueue = [];
			this.isTyping = false;
			this.sessionData = {};

			this.init();
		}

		/**
		 * Ensure a URL string ends with a trailing slash.
		 *
		 * @param {string} url URL to normalize.
		 * @return {string} Normalized URL with trailing slash.
		 */
		ensureTrailingSlash(url) {
			if (!url) {
				return '';
			}

			return url.endsWith('/') ? url : `${url}/`;
		}

		/**
		 * Initialize the chat widget
		 */
		init() {
			this.createChatElements();
			this.bindEvents();
			this.applyStyles();
			this.applyTheme();
			this.loadSession();

			// Auto-open if configured
			if (this.config.autoOpenDelay > 0) {
				setTimeout(() => {
					if (!this.isOpen && !this.hasInteracted()) {
						this.open();
					}
				}, this.config.autoOpenDelay * 1000);
			}

			// Check for returning visitor
			if (this.isReturningVisitor()) {
				this.showWelcomeBack();
			}
		}

		/**
		 * Create chat widget elements
		 */
		createChatElements() {
			// Main container
			this.container = document.createElement('div');
			this.container.className = `aria-chat-widget aria-position-${this.config.position} aria-theme-${this.config.theme}`;
			this.container.setAttribute('role', 'complementary');
			this.container.setAttribute('aria-label', 'Chat with Aria');

			// Chat button
			this.createChatButton();

			// Chat window
			this.createChatWindow();

			// Append to body
			document.body.appendChild(this.container);
		}

		/**
		 * Create chat button
		 */
		createChatButton() {
			this.button = document.createElement('button');
			this.button.className = 'aria-chat-button';
			this.button.setAttribute('aria-label', 'Open chat');
			this.button.innerHTML = `
                <span class="aria-button-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.2L4 17.2V4h16v12z"/>
                        <circle cx="8" cy="9.5" r="1.5"/>
                        <circle cx="12" cy="9.5" r="1.5"/>
                        <circle cx="16" cy="9.5" r="1.5"/>
                    </svg>
                </span>
            `;

			this.container.appendChild(this.button);
		}

		/**
		 * Create chat window
		 */
		createChatWindow() {
			this.window = document.createElement('div');
			this.window.className = 'aria-chat-window';
			this.window.style.display = 'none';
			this.window.setAttribute('role', 'dialog');
			this.window.setAttribute('aria-labelledby', 'aria-chat-title');

			this.window.innerHTML = `
                <div class="aria-chat-header">
                    <div class="aria-header-content">
                        ${
							this.config.showAvatar
								? `
                            <div class="aria-avatar aria-logo-light" aria-label="Aria"></div>
                        `
								: ''
						}
                        <div class="aria-header-info">
                            <h3 id="aria-chat-title" class="aria-header-title">Aria</h3>
                            <span class="aria-header-status">
                                <span class="aria-status-dot"></span>
                                ${window.ariaChat ? window.ariaChat.strings.online : 'Online'}
                            </span>
                        </div>
                    </div>
                    <div class="aria-header-actions">
                        <button class="aria-close-btn" aria-label="${window.ariaChat ? window.ariaChat.strings.close : 'Close'}">
                            <svg viewBox="0 0 24 24" width="20" height="20">
                                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="aria-chat-messages" role="log" aria-live="polite">
                    <div class="aria-messages-container"></div>
                    <div class="aria-typing-indicator" style="display: none;">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
                
                <div class="aria-chat-input-container">
                    <form class="aria-chat-form">
                        <div class="aria-input-group">
                            <input 
                                type="text" 
                                class="aria-message-input" 
                                placeholder="${window.ariaChat ? window.ariaChat.strings.typeMessage : 'Type your message...'}"
                                aria-label="${window.ariaChat ? window.ariaChat.strings.typeMessage : 'Type your message...'}"
                                autocomplete="off"
                            />
                            <button type="submit" class="aria-send-btn" aria-label="${window.ariaChat ? window.ariaChat.strings.send : 'Send'}">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="aria-powered-by">
                    <span>${window.ariaChat ? window.ariaChat.strings.poweredBy : 'Powered by'} Aria</span>
                    ${this.config.gdprEnabled ? `<span class="aria-privacy-notice">${window.ariaChat ? window.ariaChat.gdprMessage : 'By using this chat, you agree to our privacy policy.'}</span>` : ''}
                </div>
            `;

			this.container.appendChild(this.window);

			// Cache DOM elements
			this.messagesContainer = this.window.querySelector(
				'.aria-messages-container'
			);
			this.messageInput = this.window.querySelector(
				'.aria-message-input'
			);
			this.chatForm = this.window.querySelector('.aria-chat-form');
			this.typingIndicator = this.window.querySelector(
				'.aria-typing-indicator'
			);
		}

		/**
		 * Bind events
		 */
		bindEvents() {
			// Chat button click
			this.button.addEventListener('click', () => this.toggle());

			// Window controls
			this.window
				.querySelector('.aria-close-btn')
				.addEventListener('click', () => this.close());

			// Form submission
			this.chatForm.addEventListener('submit', (e) =>
				this.handleSubmit(e)
			);

			// Input events
			this.messageInput.addEventListener('keypress', (e) =>
				this.handleKeypress(e)
			);
			this.messageInput.addEventListener('input', (e) =>
				this.handleInput(e)
			);

			// Window resize
			window.addEventListener('resize', () => this.handleResize());

			// Page visibility
			document.addEventListener('visibilitychange', () =>
				this.handleVisibilityChange()
			);
		}

		/**
		 * Load session data
		 */
		loadSession() {
			const sessionId = this.getSessionId();
			const savedData = localStorage.getItem('aria_session_' + sessionId);

			if (savedData) {
				try {
					this.sessionData = JSON.parse(savedData);
					this.conversationId = this.sessionData.conversationId;

					// Restore messages if recent
					if (
						this.sessionData.messages &&
						this.sessionData.timestamp
					) {
						const hourAgo = Date.now() - 60 * 60 * 1000;
						if (this.sessionData.timestamp > hourAgo) {
							this.restoreMessages(this.sessionData.messages);
						}
					}
				} catch (e) {
					this.logError('Failed to load Aria session:', e);
				}
			}
		}

		/**
		 * Save session data
		 */
		saveSession() {
			const sessionId = this.getSessionId();
			this.sessionData.conversationId = this.conversationId;
			this.sessionData.timestamp = Date.now();
			this.sessionData.messages = this.getMessageHistory();

			localStorage.setItem(
				'aria_session_' + sessionId,
				JSON.stringify(this.sessionData)
			);
		}

		/**
		 * Get or create session ID
		 */
		getSessionId() {
			let sessionId = sessionStorage.getItem('aria_session_id');
			if (!sessionId) {
				sessionId =
					'aria_' +
					Date.now() +
					'_' +
					Math.random().toString(36).substr(2, 9);
				sessionStorage.setItem('aria_session_id', sessionId);
			}
			return sessionId;
		}

		/**
		 * Check if returning visitor
		 */
		isReturningVisitor() {
			return localStorage.getItem('aria_returning_visitor') === 'true';
		}

		/**
		 * Check if user has interacted
		 */
		hasInteracted() {
			return sessionStorage.getItem('aria_interacted') === 'true';
		}

		/**
		 * Toggle chat window
		 */
		toggle() {
			if (this.isOpen) {
				this.close();
			} else {
				this.open();
			}
		}

		/**
		 * Open chat window
		 */
		open() {
			this.isOpen = true;
			this.window.style.display = 'block';
			this.button.classList.add('aria-button-hidden');

			// Animate in
			if (this.config.enableAnimations) {
				this.window.classList.add('aria-window-opening');
				setTimeout(() => {
					this.window.classList.remove('aria-window-opening');
					this.window.classList.add('aria-window-open');
				}, 10);
			} else {
				this.window.classList.add('aria-window-open');
			}

			// Focus input
			setTimeout(() => {
				this.messageInput.focus();
			}, 300);

			// Mark as interacted
			sessionStorage.setItem('aria_interacted', 'true');

			// Show user info form if first time and no user info
			if (
				!this.conversationId &&
				(!this.sessionData.name || !this.sessionData.email)
			) {
				this.showUserInfoForm();
			}

			// Track event
			this.trackEvent('chat_opened');
		}

		/**
		 * Close chat window
		 */
		close() {
			this.isOpen = false;

			// Animate out
			if (this.config.enableAnimations) {
				this.window.classList.add('aria-window-closing');
				setTimeout(() => {
					this.window.style.display = 'none';
					this.window.classList.remove(
						'aria-window-open',
						'aria-window-closing'
					);
					this.button.classList.remove('aria-button-hidden');
				}, 300);
			} else {
				this.window.style.display = 'none';
				this.window.classList.remove('aria-window-open');
				this.button.classList.remove('aria-button-hidden');
			}

			// Save session
			this.saveSession();

			// Track event
			this.trackEvent('chat_closed');
		}

		/**
		 * Send initial greeting
		 */
		sendInitialGreeting() {
			// Check if we have user info
			if (!this.sessionData.name || !this.sessionData.email) {
				// Show collection form instead of greeting
				this.showUserInfoForm();
				return;
			}

			// Start conversation with user info
			this.sendRequest(
				'aria_start_conversation',
				{
					page_url: window.location.href,
					page_title: document.title,
					name: this.sessionData.name,
					email: this.sessionData.email,
					phone: this.sessionData.phone || null,
				},
				(response) => {
					if (response.success) {
						this.conversationId = response.data.conversation_id;
						this.addMessage(response.data.greeting, 'aria');

						// Store session ID if provided
						if (response.data.session_id) {
							sessionStorage.setItem(
								'aria_session_id',
								response.data.session_id
							);
						}

						// Save returning visitor status
						localStorage.setItem('aria_returning_visitor', 'true');
					}
				}
			);
		}

		/**
		 * Show welcome back message
		 */
		showWelcomeBack() {
			// Add subtle notification
			const badge = this.button.querySelector('.aria-notification-badge');
			if (badge) {
				badge.style.display = 'block';
			}
		}

		/**
		 * Show user info collection form
		 */
		showUserInfoForm() {
			// Get localized strings with proper fallbacks
			const strings =
				window.ariaChat && window.ariaChat.strings
					? window.ariaChat.strings
					: {};
			const welcomeMessage =
				strings.welcomeMessage ||
				'Welcome! Please introduce yourself so I can assist you better.';
			const enterName = strings.enterName || 'Your name';
			const enterEmail = strings.enterEmail || 'Your email';
			const enterPhone =
				strings.enterPhone || 'Your phone number (optional)';
			const addNote = strings.addNote || 'How can we help you today?';
			const startChat = strings.startChat || 'Start Chat';

			// Create a special message with form
			const formHtml = `
                <div class="aria-user-info-form">
                    <p>${welcomeMessage}</p>
                    <form class="aria-info-form" id="aria-user-info-form">
                        <div class="aria-form-group">
                            <input type="text" 
                                   id="aria-user-name" 
                                   class="aria-form-input" 
                                   placeholder="${enterName}"
                                   required />
                        </div>
                        <div class="aria-form-group">
                            <input type="email" 
                                   id="aria-user-email" 
                                   class="aria-form-input" 
                                   placeholder="${enterEmail}"
                                   required />
                        </div>
                        <div class="aria-form-group">
                            <input type="tel" 
                                   id="aria-user-phone" 
                                   class="aria-form-input" 
                                   placeholder="${enterPhone}"
                                   pattern="[+]?[0-9]{1,4}?[-.\s]?[(]?[0-9]{1,3}?[)]?[-.\s]?[0-9]{1,4}[-.\s]?[0-9]{1,4}[-.\s]?[0-9]{1,9}" />
                        </div>
                        <div class="aria-form-group">
                            <textarea id="aria-user-message" 
                                      class="aria-form-textarea" 
                                      placeholder="${addNote}"
                                      rows="3"
                                      required></textarea>
                        </div>
                        <button type="submit" class="aria-form-submit">
                            ${startChat}
                        </button>
                    </form>
                </div>
            `;

			const messageEl = document.createElement('div');
			messageEl.className = 'aria-message aria-message-system';
			messageEl.innerHTML = formHtml;
			this.messagesContainer.appendChild(messageEl);

			// Bind form submit
			const form = messageEl.querySelector('#aria-user-info-form');
			form.addEventListener('submit', (e) =>
				this.handleUserInfoSubmit(e)
			);

			// Focus name input
			setTimeout(() => {
				const nameInput = form.querySelector('#aria-user-name');
				if (nameInput) nameInput.focus();
			}, 100);

			// Hide main input temporarily
			this.chatForm.style.display = 'none';
		}

		/**
		 * Handle user info form submission.
		 *
		 * @param {Event} e Submit event.
		 */
		handleUserInfoSubmit(e) {
			e.preventDefault();

			const form = e.target;
			const nameValue = (
				form.querySelector('#aria-user-name')?.value || ''
			).trim();
			if (!nameValue) {
				return;
			}

			const emailValue = (
				form.querySelector('#aria-user-email')?.value || ''
			).trim();
			if (!emailValue) {
				return;
			}

			const messageValue = (
				form.querySelector('#aria-user-message')?.value || ''
			).trim();
			if (!messageValue) {
				return;
			}

			// Validate email
			if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
				this.addMessage(
					window.ariaChat
						? window.ariaChat.strings.invalidEmail
						: 'Please enter a valid email address.',
					'aria'
				);
				return;
			}

			// Store user info
			this.sessionData.name = nameValue;
			this.sessionData.email = emailValue;
			const phone = (
				form.querySelector('#aria-user-phone')?.value || ''
			).trim();
			this.sessionData.phone = phone;
			this.saveSession();

			// Remove form
			form.closest('.aria-message').remove();

			// Show main input
			this.chatForm.style.display = '';

			// Add the user's message to the chat
			this.addMessage(messageValue, 'user');

			// Show typing indicator
			this.showTyping();

			// Send the message directly without greeting
			this.sendMessage(messageValue);
		}

		/**
		 * Handle form submission.
		 *
		 * @param {Event} e Submit event.
		 */
		handleSubmit(e) {
			e.preventDefault();

			const message = this.messageInput.value.trim();
			if (!message) return;

			// Check if email required and not provided
			if (this.config.requireEmail && !this.sessionData.email) {
				this.requestEmail(message);
				return;
			}

			// Add user message
			this.addMessage(message, 'user');

			// Clear input
			this.messageInput.value = '';

			// Show typing indicator
			this.showTyping();

			// Send message
			this.sendMessage(message);
		}

		/**
		 * Request email from user.
		 *
		 * @param {?string} pendingMessage Message waiting to be sent.
		 */
		requestEmail(pendingMessage = null) {
			this.addMessage(
				window.ariaChat
					? window.ariaChat.strings.emailRequired
					: 'Please provide your email to continue.',
				'aria'
			);
			if (pendingMessage) {
				this.pendingMessage = pendingMessage;
			}
			this.awaitingEmail = true;
			this.messageInput.setAttribute('type', 'email');
			this.messageInput.setAttribute(
				'placeholder',
				window.ariaChat && window.ariaChat.strings.enterEmail
					? window.ariaChat.strings.enterEmail
					: 'Your email'
			);
		}

		/**
		 * Handle keypress events.
		 *
		 * @param {KeyboardEvent} e Keypress event.
		 */
		handleKeypress(e) {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				this.chatForm.dispatchEvent(new Event('submit'));
			}
		}

		/**
		 * Handle input events.
		 *
		 * @param {Event} _event Input event.
		 */
		handleInput(_event) {
			// Could implement "user is typing" indicator here
		}

		/**
		 * Add message to chat.
		 *
		 * @param {string} text    Message content.
		 * @param {string} sender  Message sender.
		 * @param {Object} options Additional render options.
		 */
		addMessage(text, sender = 'user', options = {}) {
			const messageEl = document.createElement('div');
			messageEl.className = `aria-message aria-message-${sender}`;

			// Build message HTML
			let messageHtml = '';

			if (sender === 'aria' && this.config.showAvatar) {
				const logoClass =
					this.currentTheme === 'dark'
						? 'aria-logo-dark'
						: 'aria-logo-light';
				messageHtml += `
                    <div class="aria-message-avatar aria-avatar-aria ${logoClass}" aria-label="Aria"></div>
                `;
			}

			messageHtml += `
                <div class="aria-message-content">
                    <div class="aria-message-text">${this.formatMessageText(text)}</div>
                    <div class="aria-message-time">${this.formatTime()}</div>
                </div>
            `;

			messageEl.innerHTML = messageHtml;

			// Add to container
			this.messagesContainer.appendChild(messageEl);

			// Animate in
			if (this.config.enableAnimations) {
				messageEl.classList.add('aria-message-new');
				setTimeout(
					() => messageEl.classList.remove('aria-message-new'),
					10
				);
			}

			// Scroll to bottom
			this.scrollToBottom();

			// Play sound
			if (
				this.config.enableSound &&
				sender === 'aria' &&
				!options.skipSound
			) {
				this.playNotificationSound();
			}

			// Save session
			this.saveSession();
		}

		/**
		 * Send message to server.
		 *
		 * @param {string} message Message to send.
		 */
		sendMessage(message) {
			// Check for email in message if awaiting
			if (this.awaitingEmail) {
				const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				if (emailRegex.test(message)) {
					this.sessionData.email = message;
					this.awaitingEmail = false;
					this.addMessage(
						window.ariaChat
							? window.ariaChat.strings.thankYouEmail
							: 'Thank you! How can I help you today?',
						'aria'
					);

					// Reset input type
					this.messageInput.setAttribute('type', 'text');
					this.messageInput.setAttribute(
						'placeholder',
						window.ariaChat
							? window.ariaChat.strings.typeMessage
							: 'Type your message...'
					);

					// Send pending message
					if (this.pendingMessage) {
						setTimeout(() => {
							this.showTyping();
							this.sendMessage(this.pendingMessage);
							this.pendingMessage = null;
						}, 1000);
					}
					return;
				}
				this.addMessage(
					window.ariaChat
						? window.ariaChat.strings.invalidEmail
						: 'Please enter a valid email address.',
					'aria'
				);
				return;
			}

			// Send to server
			this.sendRequest(
				'aria_send_message',
				{
					conversation_id: this.conversationId,
					session_id: this.getSessionId(),
					message,
					name: this.sessionData.name || null,
					email: this.sessionData.email || null,
					phone: this.sessionData.phone || null,
				},
				(response) => {
					this.hideTyping();

					if (response.success) {
						this.addMessage(response.data.response, 'aria');

						// Handle special actions
						if (response.data.action) {
							this.handleSpecialAction(response.data.action);
						}

						// Update conversation ID if provided
						if (response.data.conversation_id) {
							this.conversationId = response.data.conversation_id;
						}
					} else {
						this.addMessage(
							response.data?.message ||
								(window.ariaChat
									? window.ariaChat.strings.errorMessage
									: null) ||
								'Something went wrong. Please try again.',
							'aria'
						);
					}
				}
			);
		}

		/**
		 * Handle special actions from server.
		 *
		 * @param {Object} action Action payload from API.
		 */
		handleSpecialAction(action) {
			switch (action.type) {
				case 'request_human':
					this.requestHumanHandoff();
					break;
				case 'show_products':
					this.showProductSuggestions(action.data);
					break;
				case 'collect_feedback':
					this.showFeedbackRequest();
					break;
				case 'show_articles':
					this.showArticleSuggestions(action.data);
					break;
				case 'collect_email':
					this.requestEmail();
					break;
				case 'end_conversation':
					this.endConversation();
					break;
			}
		}

		/**
		 * Request human handoff
		 */
		requestHumanHandoff() {
			this.addMessage(
				window.ariaChat
					? window.ariaChat.strings.connectingHuman
					: 'Connecting you with a human...',
				'system'
			);
			// Implementation would depend on integration
		}

		/**
		 * Show product suggestions.
		 *
		 * @param {Array<Object>} products Product summaries to render.
		 */
		showProductSuggestions(products) {
			// Create product carousel or list
			const productsHtml = products
				.map(
					(product) => `
                <div class="aria-product-suggestion">
                    <img src="${product.image}" alt="${product.title}">
                    <h4>${product.title}</h4>
                    <p>${product.price}</p>
                    <a href="${product.url}" target="_blank">${window.ariaChat ? window.ariaChat.strings.viewProduct : 'View Product'}</a>
                </div>
            `
				)
				.join('');

			const productMessage = document.createElement('div');
			productMessage.className = 'aria-message aria-message-products';
			productMessage.innerHTML = productsHtml;

			this.messagesContainer.appendChild(productMessage);
			this.scrollToBottom();
		}

		/**
		 * Show feedback request
		 */
		showFeedbackRequest() {
			const feedbackHtml = `
                <div class="aria-feedback-request">
                    <p>${window.ariaChat ? window.ariaChat.strings.wasHelpful : 'Was this conversation helpful?'}</p>
                    <button class="aria-feedback-btn" data-rating="positive">👍</button>
                    <button class="aria-feedback-btn" data-rating="negative">👎</button>
                </div>
            `;

			const feedbackMessage = document.createElement('div');
			feedbackMessage.className = 'aria-message aria-message-feedback';
			feedbackMessage.innerHTML = feedbackHtml;

			this.messagesContainer.appendChild(feedbackMessage);

			// Bind feedback events
			feedbackMessage
				.querySelectorAll('.aria-feedback-btn')
				.forEach((btn) => {
					btn.addEventListener('click', (e) =>
						this.submitFeedback(e.target.dataset.rating)
					);
				});

			this.scrollToBottom();
		}

		/**
		 * Submit feedback.
		 *
		 * @param {number|string} rating Selected rating value.
		 */
		submitFeedback(rating) {
			this.sendRequest(
				'aria_submit_feedback',
				{
					conversation_id: this.conversationId,
					rating,
				},
				(response) => {
					if (response.success) {
						// Replace feedback buttons with thank you
						const feedbackEl = this.messagesContainer.querySelector(
							'.aria-feedback-request'
						);
						if (feedbackEl) {
							feedbackEl.innerHTML = `<p>${window.ariaChat ? window.ariaChat.strings.thanksFeedback : 'Thank you for your feedback!'}</p>`;
						}
					}
				}
			);
		}

		/**
		 * Show typing indicator
		 */
		showTyping() {
			this.isTyping = true;
			this.typingIndicator.style.display = 'flex';
			this.scrollToBottom();
		}

		/**
		 * Hide typing indicator
		 */
		hideTyping() {
			this.isTyping = false;
			this.typingIndicator.style.display = 'none';
		}

		/**
		 * Send AJAX request.
		 *
		 * @param {string}   action   AJAX action name.
		 * @param {Object}   data     Request payload.
		 * @param {Function} callback Callback invoked with response.
		 */
		sendRequest(action, data, callback) {
			const xhr = new XMLHttpRequest();
			xhr.open('POST', this.config.apiEndpoint, true);
			xhr.setRequestHeader(
				'Content-Type',
				'application/x-www-form-urlencoded'
			);

			xhr.onload = () => {
				if (xhr.status === 200) {
					try {
						const response = JSON.parse(xhr.responseText);
						callback(response);
					} catch (e) {
						this.logError('Failed to parse response:', e);
						callback({ success: false });
					}
				} else {
					this.logError(
						'AJAX request failed:',
						xhr.status,
						xhr.statusText,
						xhr.responseText
					);
					callback({ success: false });
				}
			};

			xhr.onerror = () => {
				callback({ success: false });
			};

			// Build request data
			// Debug logging
			if (!window.ariaChat || !window.ariaChat.nonce) {
				this.logError(
					'AriaChat: Missing nonce. ariaChat object:',
					window.ariaChat
				);
				callback({
					success: false,
					data: {
						message:
							'Configuration error: Missing security token. Please refresh the page.',
					},
				});
				return;
			}

			const params = new URLSearchParams({
				action,
				nonce: window.ariaChat.nonce,
				...data,
			});

			xhr.send(params.toString());
		}

		/**
		 * Scroll to bottom of messages
		 */
		scrollToBottom() {
			const messages = this.window.querySelector('.aria-chat-messages');
			messages.scrollTop = messages.scrollHeight;
		}

		/**
		 * Format time
		 */
		formatTime() {
			const now = new Date();
			return now.toLocaleTimeString([], {
				hour: '2-digit',
				minute: '2-digit',
			});
		}

		/**
		 * Apply styles based on configuration
		 */
		applyStyles() {
			// Create or update the dynamic style element
			let styleElement = document.getElementById('aria-dynamic-styles');
			if (!styleElement) {
				styleElement = document.createElement('style');
				styleElement.id = 'aria-dynamic-styles';
				document.head.appendChild(styleElement);
			}

			const pluginUrl = this.config.assetsBaseUrl;

			// Generate CSS variables and styles
			const css = `
                :root {
                    --aria-primary-color: ${this.config.primaryColor};
                    --aria-secondary-color: ${this.config.secondaryColor};
                    --aria-text-color: ${this.config.textColor};
                    --aria-chat-width: ${this.config.chatWidth}px;
                    --aria-chat-height: ${this.config.chatHeight}px;
                }

                /* Button size styles */
                .aria-chat-button {
                    width: ${this.getButtonSize()}px !important;
                    height: ${this.getButtonSize()}px !important;
                    background-color: var(--aria-primary-color) !important;
                    border-radius: ${this.config.buttonStyle === 'rounded' ? '50%' : '8px'} !important;
                }

                /* Chat window dimensions */
                .aria-chat-window {
                    width: var(--aria-chat-width) !important;
                    height: var(--aria-chat-height) !important;
                }

                /* Position styles */
                .aria-chat-widget.aria-position-${this.config.position} {
                    position: fixed !important;
                    z-index: 999999 !important;
                    ${this.getPositionStyles()}
                }

                /* Color scheme */
                .aria-chat-widget {
                    --aria-primary: var(--aria-primary-color);
                    --aria-secondary: var(--aria-secondary-color);
                    --aria-text: var(--aria-text-color);
                }

                .aria-chat-header {
                    background-color: var(--aria-primary-color) !important;
                    color: white !important;
                }

                .aria-chat-input {
                    border-color: var(--aria-secondary-color) !important;
                    color: var(--aria-text-color) !important;
                }

                .aria-chat-send {
                    background-color: var(--aria-primary-color) !important;
                }

                .aria-message-bot .aria-message-content {
                    background-color: var(--aria-secondary-color) !important;
                    color: var(--aria-text-color) !important;
                }

                /* Avatar visibility */
                .aria-message-avatar {
                    display: ${this.config.showAvatar ? 'flex' : 'none'} !important;
                }

                /* Animation control */
                .aria-chat-widget {
                    animation: ${this.config.enableAnimations ? 'initial' : 'none'} !important;
                    transition: ${this.config.enableAnimations ? 'all 0.3s ease' : 'none'} !important;
                }

                .aria-logo-light {
                    background-image: url('${pluginUrl}public/images/aria.png') !important;
                }

                .aria-logo-dark {
                    background-image: url('${pluginUrl}public/images/aria-lite.png') !important;
                }
            `;

			styleElement.textContent = css;

			// Apply button icon
			this.applyButtonIcon();

			// Apply avatar style
			this.applyAvatarStyle();
		}

		/**
		 * Get button size in pixels based on config
		 */
		getButtonSize() {
			const sizes = {
				small: 50,
				medium: 60,
				large: 70,
			};
			return sizes[this.config.buttonSize] || 60;
		}

		/**
		 * Get position CSS styles
		 */
		getPositionStyles() {
			const positions = {
				'bottom-right': 'bottom: 20px; right: 20px;',
				'bottom-left': 'bottom: 20px; left: 20px;',
				'bottom-center':
					'bottom: 20px; left: 50%; transform: translateX(-50%);',
				'top-right': 'top: 20px; right: 20px;',
				'top-left': 'top: 20px; left: 20px;',
				'top-center':
					'top: 20px; left: 50%; transform: translateX(-50%);',
			};
			return positions[this.config.position] || positions['bottom-right'];
		}

		/**
		 * Apply button icon based on config
		 */
		applyButtonIcon() {
			if (!this.button) return;

			const iconContainer =
				this.button.querySelector('.aria-button-icon');
			if (!iconContainer) return;

			// If custom icon is provided, use it
			if (this.config.customIcon) {
				iconContainer.innerHTML = `<img src="${this.config.customIcon}" alt="Chat" style="width: 24px; height: 24px;">`;
				return;
			}

			// Otherwise use built-in icons
			const icons = {
				'chat-bubble': `<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.2L4 17.2V4h16v12z"/>
                    <circle cx="8" cy="9.5" r="1.5"/>
                    <circle cx="12" cy="9.5" r="1.5"/>
                    <circle cx="16" cy="9.5" r="1.5"/>
                </svg>`,
				message: `<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8l8 5 8-5v10zm-8-7L4 6h16l-8 5z"/>
                </svg>`,
				help: `<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/>
                </svg>`,
				support: `<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>`,
			};

			iconContainer.innerHTML =
				icons[this.config.buttonIcon] || icons['chat-bubble'];
		}

		/**
		 * Apply avatar style based on config
		 */
		applyAvatarStyle() {
			// Avatar style will be applied when messages are created
			// This is handled in the addMessage method
		}

		/**
		 * Apply theme based on config or system preference
		 */
		applyTheme() {
			let theme = this.config.theme;

			// Detect system theme if enabled
			if (this.config.detectSystemTheme && window.matchMedia) {
				const darkModeQuery = window.matchMedia(
					'(prefers-color-scheme: dark)'
				);
				theme = darkModeQuery.matches ? 'dark' : 'light';

				// Listen for theme changes
				darkModeQuery.addEventListener('change', (e) => {
					this.setTheme(e.matches ? 'dark' : 'light');
				});
			}

			this.setTheme(theme);
		}

		/**
		 * Set theme.
		 *
		 * @param {string} theme Theme slug.
		 */
		setTheme(theme) {
			this.currentTheme = theme;
			this.container.classList.remove(
				'aria-theme-light',
				'aria-theme-dark'
			);
			this.container.classList.add(`aria-theme-${theme}`);

			// Update all logo images
			this.updateLogos();
		}

		/**
		 * Update logo images based on theme
		 */
		updateLogos() {
			// Update header avatars
			const avatars = this.container.querySelectorAll('.aria-avatar');
			avatars.forEach((avatar) => {
				if (this.currentTheme === 'dark') {
					avatar.classList.remove('aria-logo-light');
					avatar.classList.add('aria-logo-dark');
				} else {
					avatar.classList.remove('aria-logo-dark');
					avatar.classList.add('aria-logo-light');
				}
			});

			// Update message avatars
			const messageAvatars =
				this.container.querySelectorAll('.aria-avatar-aria');
			messageAvatars.forEach((avatar) => {
				if (this.currentTheme === 'dark') {
					avatar.classList.remove('aria-logo-light');
					avatar.classList.add('aria-logo-dark');
				} else {
					avatar.classList.remove('aria-logo-dark');
					avatar.classList.add('aria-logo-light');
				}
			});
		}

		/**
		 * Escape HTML.
		 *
		 * @param {string} text Text to escape.
		 * @return {string} Escaped HTML string.
		 */
		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}

		/**
		 * Get message history
		 */
		getMessageHistory() {
			const messages = [];
			this.messagesContainer
				.querySelectorAll('.aria-message')
				.forEach((msg) => {
					// Skip system messages (like the user info form)
					if (msg.classList.contains('aria-message-system')) {
						return;
					}

					const textElement = msg.querySelector('.aria-message-text');
					if (textElement) {
						const sender = msg.classList.contains(
							'aria-message-user'
						)
							? 'user'
							: 'aria';
						const text = textElement.textContent;
						messages.push({ sender, text });
					}
				});
			return messages;
		}

		/**
		 * Restore messages from persisted session.
		 *
		 * @param {Array<Object>} messages Serialized message history.
		 */
		restoreMessages(messages) {
			messages.forEach((msg) => {
				this.addMessage(msg.text, msg.sender, { skipSound: true });
			});
		}

		/**
		 * Play notification sound
		 */
		playNotificationSound() {
			// Create and play a subtle notification sound
			// Using a simple oscillator for cross-browser compatibility
			try {
				const audioContext = new (window.AudioContext ||
					window.webkitAudioContext)();
				const oscillator = audioContext.createOscillator();
				const gainNode = audioContext.createGain();

				oscillator.connect(gainNode);
				gainNode.connect(audioContext.destination);

				oscillator.frequency.value = 800;
				oscillator.type = 'sine';

				gainNode.gain.setValueAtTime(0, audioContext.currentTime);
				gainNode.gain.linearRampToValueAtTime(
					0.1,
					audioContext.currentTime + 0.01
				);
				gainNode.gain.exponentialRampToValueAtTime(
					0.01,
					audioContext.currentTime + 0.1
				);

				oscillator.start(audioContext.currentTime);
				oscillator.stop(audioContext.currentTime + 0.1);
			} catch (e) {
				// Ignore if Web Audio API is not supported
			}
		}

		/**
		 * Handle window resize
		 */
		handleResize() {
			// Adjust chat window for mobile if needed
			if (window.innerWidth < 768 && this.config.mobileFullscreen) {
				this.window.classList.add('aria-mobile-fullscreen');
			} else {
				this.window.classList.remove('aria-mobile-fullscreen');
			}
		}

		/**
		 * Handle visibility change
		 */
		handleVisibilityChange() {
			if (document.hidden) {
				// Page is hidden, pause any animations
				this.pauseAnimations();
			} else {
				// Page is visible again
				this.resumeAnimations();
			}
		}

		/**
		 * Pause animations
		 */
		pauseAnimations() {
			this.container.classList.add('aria-animations-paused');
		}

		/**
		 * Resume animations
		 */
		resumeAnimations() {
			this.container.classList.remove('aria-animations-paused');
		}

		/**
		 * Track analytics events.
		 *
		 * @param {string} eventName Event identifier.
		 * @param {Object} data      Additional payload.
		 */
		trackEvent(eventName, data = {}) {
			// Send analytics event
			if (this.config.enableAnalytics) {
				this.sendRequest(
					'aria_track_event',
					{
						event: eventName,
						conversation_id: this.conversationId,
						...data,
					},
					() => {
						// Silent tracking
					}
				);
			}

			// Also track with Google Analytics if available
			if (
				typeof window !== 'undefined' &&
				typeof window.gtag === 'function'
			) {
				window.gtag('event', eventName, {
					event_category: 'Aria Chat',
					...data,
				});
			}
		}

		/**
		 * Show article suggestions.
		 *
		 * @param {Array<Object>} articles Article entries to display.
		 */
		showArticleSuggestions(articles) {
			const articlesHtml = articles
				.map(
					(article) => `
                <div class="aria-article-suggestion">
                    <h4><a href="${article.url}" target="_blank">${article.title}</a></h4>
                    <p>${article.excerpt}</p>
                </div>
            `
				)
				.join('');

			const articleMessage = document.createElement('div');
			articleMessage.className = 'aria-message aria-message-articles';
			articleMessage.innerHTML = `
                <div class="aria-articles-container">
                    <p>${window.ariaChat ? window.ariaChat.strings.helpfulArticles || 'Here are some helpful articles:' : 'Here are some helpful articles:'}:</p>
                    ${articlesHtml}
                </div>
            `;

			this.messagesContainer.appendChild(articleMessage);
			this.scrollToBottom();
		}

		/**
		 * End conversation
		 */
		endConversation() {
			this.addMessage(
				window.ariaChat
					? window.ariaChat.strings.conversationEnded ||
							'Thank you for chatting with me. Have a great day!'
					: 'Thank you for chatting with me. Have a great day!',
				'aria'
			);

			// Show feedback request after a delay
			setTimeout(() => {
				this.showFeedbackRequest();
			}, 1500);

			// Clear conversation ID to start fresh next time
			setTimeout(() => {
				this.conversationId = null;
				this.saveSession();
			}, 5000);
		}

		/**
		 * Format message text with markdown support.
		 *
		 * @param {string} text Message text to format.
		 * @return {string} HTML markup.
		 */
		formatMessageText(text) {
			// Get the last user message for context
			const messages =
				this.messagesContainer.querySelectorAll('.aria-message');
			let lastUserMessage = '';
			for (let i = messages.length - 1; i >= 0; i--) {
				if (messages[i].classList.contains('aria-message-user')) {
					const textEl =
						messages[i].querySelector('.aria-message-text');
					if (textEl) {
						lastUserMessage = textEl.textContent.toLowerCase();
						break;
					}
				}
			}

			// Extract URLs first
			const links = [];
			const urlRegex = /(https?:\/\/[^\s<]+|www\.[^\s<]+)/g;
			const markdownLinkRegex = /\[([^\]]+)\]\(([^)]+)\)/g;

			// Extract markdown links
			let markdownMatch;
			while ((markdownMatch = markdownLinkRegex.exec(text)) !== null) {
				links.push({
					text: markdownMatch[1],
					url: markdownMatch[2],
					fullMatch: markdownMatch[0],
					context: {
						userMessage: lastUserMessage,
						responseText: text.toLowerCase(),
					},
				});
			}

			// Extract plain URLs
			let urlMatch;
			while ((urlMatch = urlRegex.exec(text)) !== null) {
				// Skip if this URL is part of a markdown link
				let isMarkdownLink = false;
				for (const link of links) {
					if (link.fullMatch.includes(urlMatch[0])) {
						isMarkdownLink = true;
						break;
					}
				}
				if (!isMarkdownLink) {
					const url = urlMatch[0];
					const href = url.startsWith('www.') ? 'http://' + url : url;
					links.push({
						text: this.getLinkText(
							href,
							lastUserMessage,
							text.toLowerCase()
						),
						url: href,
						fullMatch: url,
						context: {
							userMessage: lastUserMessage,
							responseText: text.toLowerCase(),
						},
					});
				}
			}

			// Remove links from text
			let cleanText = text;
			links.forEach((link) => {
				cleanText = cleanText.replace(link.fullMatch, '');
			});

			// Clean up extra spaces and punctuation
			cleanText = cleanText
				.replace(/\s+/g, ' ')
				.replace(/\s+([.,!?])/g, '$1')
				.trim();

			// Format the clean text
			let formatted = this.escapeHtml(cleanText);

			// Bold
			formatted = formatted.replace(
				/\*\*(.*?)\*\*/g,
				'<strong>$1</strong>'
			);

			// Italic
			formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');

			// Make phone numbers clickable
			// Match various phone number formats
			const phoneRegex =
				/(\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/g;
			formatted = formatted.replace(phoneRegex, (match) => {
				// Clean the number for the tel: link
				const cleanNumber = match.replace(/[^\d+]/g, '');
				return `<a href="tel:${cleanNumber}" class="aria-phone-link">${match}</a>`;
			});

			// Line breaks
			formatted = formatted.replace(/\n/g, '<br>');

			// Create result with buttons
			let result = formatted;

			if (links.length > 0) {
				result += '<div class="aria-link-buttons">';
				links.forEach((link) => {
					result += `<a href="${link.url}" target="_blank" rel="noopener" class="aria-link-button">${link.text}</a>`;
				});
				result += '</div>';
			}

			return result;
		}

		/**
		 * Get user-friendly link text based on context.
		 *
		 * @param {string} url               Link URL.
		 * @param {string} [userMessage='']  Last user message.
		 * @param {string} [responseText=''] Assistant response text.
		 * @return {string} Suggested button label.
		 */
		getLinkText(url, userMessage = '', responseText = '') {
			try {
				const urlObj = new URL(url);
				const pathname = urlObj.pathname.toLowerCase();
				const lowerUserMessage = userMessage.toLowerCase();
				const lowerResponse = responseText.toLowerCase();

				// Check user's question context first
				if (
					lowerUserMessage.includes('reservation') ||
					lowerUserMessage.includes('book') ||
					lowerUserMessage.includes('table')
				) {
					return 'Make a Reservation';
				}
				if (
					lowerUserMessage.includes('hour') ||
					lowerUserMessage.includes('open') ||
					lowerUserMessage.includes('close')
				) {
					return 'View Hours';
				}
				if (
					lowerUserMessage.includes('menu') ||
					lowerUserMessage.includes('food') ||
					lowerUserMessage.includes('dish')
				) {
					return 'View Menu';
				}
				if (
					lowerUserMessage.includes('direction') ||
					lowerUserMessage.includes('location') ||
					lowerUserMessage.includes('where')
				) {
					return 'Get Directions';
				}
				if (
					lowerUserMessage.includes('contact') ||
					lowerUserMessage.includes('phone') ||
					lowerUserMessage.includes('email')
				) {
					return 'Contact Us';
				}
				if (
					lowerUserMessage.includes('work') ||
					lowerUserMessage.includes('job') ||
					lowerUserMessage.includes('career') ||
					lowerUserMessage.includes('hiring')
				) {
					return 'Join Our Team';
				}

				// Check URL patterns as fallback
				if (
					pathname.includes('careers') ||
					pathname.includes('jobs') ||
					pathname.includes('employment')
				) {
					return 'Join Our Team';
				}
				if (pathname.includes('menu')) {
					return 'View Menu';
				}
				if (
					pathname.includes('reservation') ||
					pathname.includes('booking')
				) {
					return 'Make a Reservation';
				}
				if (
					pathname.includes('apply') ||
					pathname.includes('application')
				) {
					return 'Submit Application';
				}
				if (pathname.includes('contact')) {
					return 'Contact Us';
				}
				if (
					pathname.includes('location') ||
					pathname.includes('directions')
				) {
					return 'Get Directions';
				}
				if (pathname.includes('hour')) {
					return 'View Hours';
				}
				if (pathname.includes('about')) {
					return 'Learn More';
				}

				// Check response content for additional context
				if (
					lowerResponse.includes('reservation') ||
					lowerResponse.includes('book')
				) {
					return 'Make a Reservation';
				}
				if (
					lowerResponse.includes('hour') ||
					lowerResponse.includes('open') ||
					lowerResponse.includes('close')
				) {
					return 'View Hours';
				}
				if (lowerResponse.includes('menu')) {
					return 'View Menu';
				}

				// Default: use domain name
				return 'Visit ' + urlObj.hostname.replace('www.', '');
			} catch (e) {
				return 'Visit Link';
			}
		}

		/**
		 * Log debug information when debug mode is enabled.
		 *
		 * @param {...*} args Values to log.
		 */
		logDebug(...args) {
			if (!this.config.debug) {
				return;
			}

			// eslint-disable-next-line no-console
			console.log(...args);
		}

		/**
		 * Log error information.
		 *
		 * @param {...*} args Error details to log.
		 */
		logError(...args) {
			// eslint-disable-next-line no-console
			console.error(...args);
		}

		/**
		 * Destroy chat widget
		 */
		destroy() {
			// Remove event listeners
			this.button.removeEventListener('click', this.toggle);
			window.removeEventListener('resize', this.handleResize);
			document.removeEventListener(
				'visibilitychange',
				this.handleVisibilityChange
			);

			// Save session before destroying
			this.saveSession();

			// Remove from DOM
			this.container.remove();

			// Clear references
			this.container = null;
			this.button = null;
			this.window = null;
			this.messagesContainer = null;
			this.messageInput = null;
			this.chatForm = null;
			this.typingIndicator = null;
		}
	}

	/**
	 * Aria Embed Chat Class
	 */
	class AriaEmbedChat {
		constructor(container, config) {
			this.container = container;
			this.config = Object.assign(
				{
					apiEndpoint: window.ariaChat
						? window.ariaChat.ajaxUrl
						: '/wp-admin/admin-ajax.php',
					nonce: window.ariaChat ? window.ariaChat.nonce : '',
					enableSound: true,
					debug: Boolean(window.ariaChat && window.ariaChat.debug),
					assetsBaseUrl: this.ensureTrailingSlash(
						window.ariaChat && window.ariaChat.pluginUrl
							? window.ariaChat.pluginUrl
							: '/wp-content/plugins/aria/'
					),
				},
				config
			);

			this.conversationId = null;
			this.isTyping = false;
			this.sessionData = {};

			this.init();
		}

		/**
		 * Ensure a URL string ends with a trailing slash.
		 *
		 * @param {string} url URL to normalize.
		 * @return {string} Normalized URL with trailing slash.
		 */
		ensureTrailingSlash(url) {
			if (!url) {
				return '';
			}

			return url.endsWith('/') ? url : `${url}/`;
		}

		/**
		 * Initialize embed chat
		 */
		init() {
			this.cacheElements();
			this.bindEvents();
			this.loadSession();
			this.applyStyles();
			this.detectTheme();
		}

		/**
		 * Cache DOM elements
		 */
		cacheElements() {
			// Form elements
			this.formView = this.container.querySelector(
				'.aria-embed-form-view'
			);
			this.intakeForm = this.container.querySelector(
				'.aria-embed-intake-form'
			);
			this.nameInput = this.container.querySelector('#aria-embed-name');
			this.emailInput = this.container.querySelector('#aria-embed-email');
			this.phoneInput = this.container.querySelector('#aria-embed-phone');
			this.messageInput = this.container.querySelector(
				'#aria-embed-message'
			);

			// Chat elements
			this.chatView = this.container.querySelector(
				'.aria-embed-chat-view'
			);
			this.messagesContainer = this.container.querySelector(
				'.aria-embed-messages-container'
			);
			this.chatForm = this.container.querySelector(
				'.aria-embed-chat-form'
			);
			this.chatInput = this.container.querySelector(
				'.aria-embed-message-input'
			);
			this.typingIndicator = this.container.querySelector(
				'.aria-typing-indicator'
			);
			this.closeButton = this.container.querySelector('.aria-close-btn');
		}

		/**
		 * Bind events
		 */
		bindEvents() {
			// Intake form submission
			this.intakeForm.addEventListener('submit', (e) =>
				this.handleIntakeSubmit(e)
			);

			// Chat form submission
			this.chatForm.addEventListener('submit', (e) =>
				this.handleChatSubmit(e)
			);

			// Close button
			if (this.closeButton) {
				this.closeButton.addEventListener('click', () =>
					this.switchToForm()
				);
			}
		}

		/**
		 * Load session data
		 */
		loadSession() {
			const sessionId = this.getSessionId();
			const savedData = sessionStorage.getItem(
				'aria_embed_session_' + sessionId
			);

			if (savedData) {
				try {
					this.sessionData = JSON.parse(savedData);
					this.conversationId = this.sessionData.conversationId;
				} catch (e) {
					this.logError('Failed to load session:', e);
				}
			}
		}

		/**
		 * Save session data
		 */
		saveSession() {
			const sessionId = this.getSessionId();
			this.sessionData.conversationId = this.conversationId;
			sessionStorage.setItem(
				'aria_embed_session_' + sessionId,
				JSON.stringify(this.sessionData)
			);
		}

		/**
		 * Get session ID
		 */
		getSessionId() {
			let sessionId = sessionStorage.getItem('aria_embed_session_id');
			if (!sessionId) {
				sessionId =
					'aria_embed_' +
					Date.now() +
					'_' +
					Math.random().toString(36).substr(2, 9);
				sessionStorage.setItem('aria_embed_session_id', sessionId);
			}
			return sessionId;
		}

		/**
		 * Handle intake form submission.
		 *
		 * @param {Event} e Submit event.
		 */
		handleIntakeSubmit(e) {
			e.preventDefault();

			const nameValue = (this.nameInput?.value || '').trim();
			if (!nameValue) {
				return;
			}

			const emailValue = (this.emailInput?.value || '').trim();
			if (!emailValue) {
				return;
			}

			const messageValue = (this.messageInput?.value || '').trim();
			if (!messageValue) {
				return;
			}

			// Validate email
			if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
				this.showError(
					window.ariaChat
						? window.ariaChat.strings.invalidEmail
						: 'Please enter a valid email address.'
				);
				return;
			}

			const phone = (this.phoneInput?.value || '').trim();

			// Store user info
			this.sessionData.name = nameValue;
			this.sessionData.email = emailValue;
			this.sessionData.phone = phone;
			this.saveSession();

			// Switch to chat view
			this.switchToChat();

			// Start conversation with greeting and initial message
			this.startConversation(nameValue, emailValue, phone, messageValue);
		}

		/**
		 * Switch to chat view
		 */
		switchToChat() {
			this.formView.style.display = 'none';
			this.chatView.style.display = 'flex';

			// Set height if specified, otherwise use default
			const height = this.container.getAttribute('data-aria-height');
			if (height) {
				this.chatView.style.height = height;
			} else {
				// Ensure chat view has a minimum height
				this.chatView.style.height = '600px';
			}

			// Force layout recalculation
			void this.chatView.offsetHeight;

			// Reapply theme to ensure all elements are updated
			if (this.currentTheme) {
				this.logDebug('Reapplying theme:', this.currentTheme);
				this.applyTheme(this.currentTheme);
			} else {
				this.logDebug('No current theme set, detecting...');
				this.detectTheme();
			}
		}

		/**
		 * Switch back to form view
		 */
		switchToForm() {
			this.chatView.style.display = 'none';
			this.formView.style.display = 'block';

			// Clear form
			this.intakeForm.reset();

			// Clear messages
			this.messagesContainer.innerHTML = '';

			// Reset conversation
			this.conversationId = null;
			this.sessionData = {};
			this.saveSession();
		}

		/**
		 * Start conversation.
		 *
		 * @param {string} name           Visitor name.
		 * @param {string} email          Visitor email.
		 * @param {string} phone          Visitor phone number.
		 * @param {string} initialMessage First message to send.
		 */
		startConversation(name, email, phone, initialMessage) {
			// Add user's initial message to chat immediately
			this.addMessage(initialMessage, 'user');

			// Show typing indicator
			this.showTyping();

			// Send the message directly instead of getting a greeting first
			this.sendRequest(
				'aria_send_message',
				{
					message: initialMessage,
					conversation_id: null, // Will create new conversation
					session_id: this.getSessionId(),
					name,
					email,
					phone,
				},
				(response) => {
					this.hideTyping();

					if (response.success) {
						this.conversationId = response.data.conversation_id;
						this.saveSession();
						this.addMessage(response.data.response, 'aria');
					} else {
						this.addMessage(
							response.data.message ||
								'An error occurred. Please try again.',
							'aria',
							'error'
						);
					}
				}
			);
		}

		/**
		 * Handle chat form submission.
		 *
		 * @param {Event} e Submit event.
		 */
		handleChatSubmit(e) {
			e.preventDefault();

			const message = this.chatInput.value.trim();
			if (!message) return;

			this.sendMessage(message);
		}

		/**
		 * Send message.
		 *
		 * @param {string} message Message text.
		 */
		sendMessage(message) {
			// Add user message
			this.addMessage(message, 'user');

			// Clear input
			this.chatInput.value = '';

			// Show typing indicator
			this.showTyping();

			// Send to server
			this.sendRequest(
				'aria_send_message',
				{
					message,
					conversation_id: this.conversationId,
					session_id: this.getSessionId(),
					name: this.sessionData.name || '',
					email: this.sessionData.email || '',
					phone: this.sessionData.phone || '',
				},
				(response) => {
					this.hideTyping();

					if (response.success) {
						this.conversationId = response.data.conversation_id;
						this.addMessage(response.data.response, 'aria');
					} else {
						this.addMessage(
							response.data.message ||
								'An error occurred. Please try again.',
							'aria',
							'error'
						);
					}
				}
			);
		}

		/**
		 * Add message to chat.
		 *
		 * @param {string} content Message content.
		 * @param {string} sender  Message sender.
		 * @param {string} type    Message type.
		 */
		addMessage(content, sender = 'aria', type = 'text') {
			// Ensure messages container exists
			if (!this.messagesContainer) {
				this.logError('Messages container not found');
				return;
			}

			const messageEl = document.createElement('div');
			messageEl.className = `aria-message aria-message-${sender} ${type === 'error' ? 'aria-message-error' : ''}`;

			if (sender === 'aria') {
				const logoClass =
					this.currentTheme === 'dark'
						? 'aria-logo-dark'
						: 'aria-logo-light';
				messageEl.innerHTML = `
                    <div class="aria-message-avatar aria-avatar-aria ${logoClass}" aria-label="Aria"></div>
                    <div class="aria-message-content">
                        ${this.formatMessage(content)}
                    </div>
                `;
			} else {
				messageEl.innerHTML = `
                    <div class="aria-message-content">
                        ${this.escapeHtml(content)}
                    </div>
                `;
			}

			this.messagesContainer.appendChild(messageEl);

			// Ensure message is visible
			messageEl.style.opacity = '1';

			// Delay scroll to ensure DOM is updated
			setTimeout(() => {
				this.scrollToBottom();
			}, 10);

			// Play sound for Aria messages
			if (sender === 'aria' && this.config.enableSound) {
				this.playNotificationSound();
			}
		}

		/**
		 * Show typing indicator
		 */
		showTyping() {
			this.isTyping = true;
			this.typingIndicator.style.display = 'flex';
			this.scrollToBottom();
		}

		/**
		 * Hide typing indicator
		 */
		hideTyping() {
			this.isTyping = false;
			this.typingIndicator.style.display = 'none';
		}

		/**
		 * Scroll to bottom of messages
		 */
		scrollToBottom() {
			const scrollContainer = this.chatView.querySelector(
				'.aria-embed-chat-messages'
			);
			if (scrollContainer) {
				scrollContainer.scrollTop = scrollContainer.scrollHeight;
			}
		}

		/**
		 * Send AJAX request.
		 *
		 * @param {string}   action   AJAX action name.
		 * @param {Object}   data     Request payload.
		 * @param {Function} callback Callback invoked with response data.
		 */
		sendRequest(action, data, callback) {
			const formData = new FormData();
			formData.append('action', action);
			formData.append('nonce', this.config.nonce);

			for (const key in data) {
				if (data[key] !== null && data[key] !== undefined) {
					formData.append(key, data[key]);
				}
			}

			fetch(this.config.apiEndpoint, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			})
				.then((response) => response.json())
				.then(callback)
				.catch((error) => {
					this.logError('Aria request failed:', error);
					this.hideTyping();
					this.addMessage(
						'Connection error. Please try again.',
						'aria',
						'error'
					);
				});
		}

		/**
		 * Show error message.
		 *
		 * @param {string} message Error message to display.
		 */
		showError(message) {
			const errorEl = document.createElement('div');
			errorEl.className = 'aria-error-message';
			errorEl.textContent = message;
			this.intakeForm.appendChild(errorEl);

			setTimeout(() => {
				errorEl.remove();
			}, 3000);
		}

		/**
		 * Format message text.
		 *
		 * @param {string} text Raw message content.
		 * @return {string} HTML markup.
		 */
		formatMessage(text) {
			// Get the last user message for context
			const lastUserMessage = this.getLastUserMessage();

			// Extract URLs first
			const links = [];
			const urlRegex = /(https?:\/\/[^\s<]+|www\.[^\s<]+)/g;
			const markdownLinkRegex = /\[([^\]]+)\]\(([^)]+)\)/g;

			// Extract markdown links
			let markdownMatch;
			while ((markdownMatch = markdownLinkRegex.exec(text)) !== null) {
				links.push({
					text: markdownMatch[1],
					url: markdownMatch[2],
					fullMatch: markdownMatch[0],
				});
			}

			// Extract plain URLs
			let urlMatch;
			while ((urlMatch = urlRegex.exec(text)) !== null) {
				// Skip if this URL is part of a markdown link
				let isMarkdownLink = false;
				for (const link of links) {
					if (link.fullMatch.includes(urlMatch[0])) {
						isMarkdownLink = true;
						break;
					}
				}
				if (!isMarkdownLink) {
					const url = urlMatch[0];
					const href = url.startsWith('www.') ? 'http://' + url : url;
					links.push({
						text: this.getLinkText(href, lastUserMessage, text),
						url: href,
						fullMatch: url,
					});
				}
			}

			// Remove links from text
			let cleanText = text;
			links.forEach((link) => {
				cleanText = cleanText.replace(link.fullMatch, '');
			});

			// Clean up extra spaces and punctuation
			cleanText = cleanText
				.replace(/\s+/g, ' ')
				.replace(/\s+([.,!?])/g, '$1')
				.trim();

			// Format the clean text
			let formatted = this.escapeHtml(cleanText);

			// Bold
			formatted = formatted.replace(
				/\*\*(.*?)\*\*/g,
				'<strong>$1</strong>'
			);

			// Italic
			formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');

			// Make phone numbers clickable
			// Match various phone number formats
			const phoneRegex =
				/(\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/g;
			formatted = formatted.replace(phoneRegex, (match) => {
				// Clean the number for the tel: link
				const cleanNumber = match.replace(/[^\d+]/g, '');
				return `<a href="tel:${cleanNumber}" class="aria-phone-link">${match}</a>`;
			});

			// Line breaks
			formatted = formatted.replace(/\n/g, '<br>');

			// Create result with buttons
			let result = formatted;

			if (links.length > 0) {
				result += '<div class="aria-link-buttons">';
				links.forEach((link) => {
					result += `<a href="${link.url}" target="_blank" rel="noopener" class="aria-link-button">${link.text}</a>`;
				});
				result += '</div>';
			}

			return result;
		}

		/**
		 * Get user-friendly link text based on context.
		 *
		 * @param {string} url               Link URL.
		 * @param {string} [userMessage='']  Last user message.
		 * @param {string} [responseText=''] Assistant response text.
		 * @return {string} Suggested link label.
		 */
		getLinkText(url, userMessage = '', responseText = '') {
			try {
				const urlObj = new URL(url);
				const pathname = urlObj.pathname.toLowerCase();
				const lowerUserMessage = userMessage.toLowerCase();
				const lowerResponse = responseText.toLowerCase();

				// Check user's question context first
				if (
					lowerUserMessage.includes('reservation') ||
					lowerUserMessage.includes('book') ||
					lowerUserMessage.includes('table')
				) {
					return 'Make a Reservation';
				}
				if (
					lowerUserMessage.includes('hour') ||
					lowerUserMessage.includes('open') ||
					lowerUserMessage.includes('close')
				) {
					return 'View Hours';
				}
				if (
					lowerUserMessage.includes('menu') ||
					lowerUserMessage.includes('food') ||
					lowerUserMessage.includes('dish')
				) {
					return 'View Menu';
				}
				if (
					lowerUserMessage.includes('direction') ||
					lowerUserMessage.includes('location') ||
					lowerUserMessage.includes('where')
				) {
					return 'Get Directions';
				}
				if (
					lowerUserMessage.includes('contact') ||
					lowerUserMessage.includes('phone') ||
					lowerUserMessage.includes('email')
				) {
					return 'Contact Us';
				}
				if (
					lowerUserMessage.includes('work') ||
					lowerUserMessage.includes('job') ||
					lowerUserMessage.includes('career') ||
					lowerUserMessage.includes('hiring')
				) {
					return 'Join Our Team';
				}

				// Check URL patterns as fallback
				if (
					pathname.includes('careers') ||
					pathname.includes('jobs') ||
					pathname.includes('employment')
				) {
					return 'Join Our Team';
				}
				if (pathname.includes('menu')) {
					return 'View Menu';
				}
				if (
					pathname.includes('reservation') ||
					pathname.includes('booking')
				) {
					return 'Make a Reservation';
				}
				if (
					pathname.includes('apply') ||
					pathname.includes('application')
				) {
					return 'Submit Application';
				}
				if (pathname.includes('contact')) {
					return 'Contact Us';
				}
				if (
					pathname.includes('location') ||
					pathname.includes('directions')
				) {
					return 'Get Directions';
				}
				if (pathname.includes('hour')) {
					return 'View Hours';
				}
				if (pathname.includes('about')) {
					return 'Learn More';
				}

				// Check response content for additional context
				if (
					lowerResponse.includes('reservation') ||
					lowerResponse.includes('book')
				) {
					return 'Make a Reservation';
				}
				if (
					lowerResponse.includes('hour') ||
					lowerResponse.includes('open') ||
					lowerResponse.includes('close')
				) {
					return 'View Hours';
				}
				if (lowerResponse.includes('menu')) {
					return 'View Menu';
				}

				// Default: use domain name
				return 'Visit ' + urlObj.hostname.replace('www.', '');
			} catch (e) {
				return 'Visit Link';
			}
		}

		/**
		 * Escape HTML.
		 *
		 * @param {string} text Text to escape.
		 * @return {string} Escaped string.
		 */
		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}

		/**
		 * Play notification sound
		 */
		playNotificationSound() {
			try {
				const audioContext = new (window.AudioContext ||
					window.webkitAudioContext)();
				const oscillator = audioContext.createOscillator();
				const gainNode = audioContext.createGain();

				oscillator.connect(gainNode);
				gainNode.connect(audioContext.destination);

				oscillator.frequency.value = 800;
				oscillator.type = 'sine';

				gainNode.gain.setValueAtTime(0, audioContext.currentTime);
				gainNode.gain.linearRampToValueAtTime(
					0.1,
					audioContext.currentTime + 0.01
				);
				gainNode.gain.exponentialRampToValueAtTime(
					0.01,
					audioContext.currentTime + 0.1
				);

				oscillator.start(audioContext.currentTime);
				oscillator.stop(audioContext.currentTime + 0.1);
			} catch (e) {
				// Ignore if Web Audio API is not supported
			}
		}

		/**
		 * Apply styles based on configuration
		 */
		applyStyles() {
			// Create or update the dynamic style element for embed
			let styleElement = document.getElementById(
				'aria-embed-dynamic-styles'
			);
			if (!styleElement) {
				styleElement = document.createElement('style');
				styleElement.id = 'aria-embed-dynamic-styles';
				document.head.appendChild(styleElement);
			}

			// Generate CSS variables and styles for embed
			const css = `
                :root {
                    --aria-embed-primary-color: ${this.config.primaryColor || '#2271b1'};
                    --aria-embed-secondary-color: ${this.config.secondaryColor || '#f0f0f1'};
                    --aria-embed-text-color: ${this.config.textColor || '#1e1e1e'};
                    --aria-embed-chat-width: ${this.config.chatWidth || 380}px;
                    --aria-embed-chat-height: ${this.config.chatHeight || 600}px;
                }

                /* Embed chat styling */
                .aria-embed-container {
                    width: var(--aria-embed-chat-width) !important;
                    height: var(--aria-embed-chat-height) !important;
                }

                /* Form styling */
                .aria-embed-form-view .aria-form-submit {
                    background-color: var(--aria-embed-primary-color) !important;
                    color: white !important;
                }

                /* Chat header */
                .aria-embed-chat-header {
                    background-color: var(--aria-embed-primary-color) !important;
                    color: white !important;
                }

                /* Message styling */
                .aria-embed-chat-messages .aria-message-bot .aria-message-content {
                    background-color: var(--aria-embed-secondary-color) !important;
                    color: var(--aria-embed-text-color) !important;
                }

                .aria-embed-chat-messages .aria-message-user .aria-message-content {
                    background-color: var(--aria-embed-primary-color) !important;
                    color: white !important;
                }

                /* Input styling */
                .aria-embed-message-input {
                    border-color: var(--aria-embed-secondary-color) !important;
                    color: var(--aria-embed-text-color) !important;
                }

                .aria-embed-send-btn {
                    background-color: var(--aria-embed-primary-color) !important;
                    color: white !important;
                }

                /* Avatar visibility */
                .aria-message-avatar {
                    display: ${this.config.showAvatar !== false ? 'flex' : 'none'} !important;
                }

                /* Animation control */
                .aria-embed-container {
                    animation: ${this.config.enableAnimations !== false ? 'initial' : 'none'} !important;
                    transition: ${this.config.enableAnimations !== false ? 'all 0.3s ease' : 'none'} !important;
                }
            `;

			styleElement.textContent = css;
		}

		/**
		 * Detect and apply theme
		 */
		detectTheme() {
			// First check if theme is already set on container
			const currentTheme = this.container.getAttribute('data-theme');
			if (currentTheme) {
				this.applyTheme(currentTheme);
			} else if (window.matchMedia) {
				// Otherwise check for system preference
				const darkModeQuery = window.matchMedia(
					'(prefers-color-scheme: dark)'
				);
				const applyTheme = (isDark) => {
					const theme = isDark ? 'dark' : 'light';
					this.container.setAttribute('data-theme', theme);
					this.applyTheme(theme);
				};

				// Apply initial theme
				applyTheme(darkModeQuery.matches);

				// Listen for changes
				darkModeQuery.addEventListener('change', (e) => {
					applyTheme(e.matches);
				});
			}
		}

		/**
		 * Apply theme to embed.
		 *
		 * @param {string} theme Theme slug.
		 */
		applyTheme(theme) {
			this.currentTheme = theme;

			// Update container theme class
			this.container.classList.remove(
				'aria-theme-light',
				'aria-theme-dark'
			);
			this.container.classList.add(`aria-theme-${theme}`);

			// Update chat view theme class if it exists
			if (this.chatView) {
				this.chatView.classList.remove(
					'aria-theme-light',
					'aria-theme-dark'
				);
				this.chatView.classList.add(`aria-theme-${theme}`);
			}

			// Update all avatars in the embed
			const avatars = this.container.querySelectorAll('.aria-avatar');
			avatars.forEach((avatar) => {
				if (theme === 'dark') {
					avatar.classList.remove('aria-logo-light');
					avatar.classList.add('aria-logo-dark');
				} else {
					avatar.classList.remove('aria-logo-dark');
					avatar.classList.add('aria-logo-light');
				}
			});

			// Update message avatars
			const messageAvatars =
				this.container.querySelectorAll('.aria-avatar-aria');
			messageAvatars.forEach((avatar) => {
				if (theme === 'dark') {
					avatar.classList.remove('aria-logo-light');
					avatar.classList.add('aria-logo-dark');
				} else {
					avatar.classList.remove('aria-logo-dark');
					avatar.classList.add('aria-logo-light');
				}
			});
		}

		/**
		 * Log debug information when debug mode is enabled.
		 *
		 * @param {...*} args Values to log.
		 */
		logDebug(...args) {
			if (!this.config.debug) {
				return;
			}

			// eslint-disable-next-line no-console
			console.log(...args);
		}

		/**
		 * Log error information.
		 *
		 * @param {...*} args Error details to log.
		 */
		logError(...args) {
			// eslint-disable-next-line no-console
			console.error(...args);
		}

		/**
		 * Get the last user message from the conversation
		 *
		 * @return {string} The last user message or empty string
		 */
		getLastUserMessage() {
			const messages =
				this.messagesContainer.querySelectorAll('.aria-message-user');
			if (messages.length > 0) {
				const lastMessage = messages[messages.length - 1];
				const textElement = lastMessage.querySelector(
					'.aria-message-content'
				);
				return textElement ? textElement.textContent.trim() : '';
			}
			return '';
		}
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAriaChat);
	} else {
		initAriaChat();
	}

	function initAriaChat() {
		// Check if Aria is enabled (check both cases for compatibility)
		const chatConfig = window.ariaChat || window.AriaChat;
		if (typeof chatConfig === 'undefined' || !chatConfig.enabled) {
			return;
		}

		// Store global reference for the instance to use
		window.ariaChatConfig = chatConfig;

		// Add logo styles globally for both widget and embed
		const style = document.createElement('style');
		const pluginUrl = (
			chatConfig.pluginUrl || '/wp-content/plugins/aria/'
		).replace(/\/?$/, '/');
		style.textContent = `
            .aria-logo-light {
                background-image: url('${pluginUrl}public/images/aria.png') !important;
            }
            .aria-logo-dark {
                background-image: url('${pluginUrl}public/images/aria-lite.png') !important;
            }
        `;
		document.head.appendChild(style);

		// Check for embed containers
		const embedContainers = document.querySelectorAll(
			'[data-aria-embed="true"]'
		);
		if (embedContainers.length > 0) {
			// Initialize embed instances
			embedContainers.forEach((container) => {
				new AriaEmbedChat(container, chatConfig.config);
			});
		} else {
			// Create regular chat widget instance
			window.ariaChatInstance = new AriaChat(chatConfig.config);
		}
	}
})();
