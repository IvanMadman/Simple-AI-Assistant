// AI Chat Assistant Interface
(function($) {
    'use strict';

    // Chat UI elements
    const chatBubble = $('#aica-chat-bubble');
    const chatWindow = $('#aica-chat-window');
    const chatMessages = $('#aica-chat-messages');
    const userInput = $('#aica-user-input');
    const sendButton = $('#aica-send-button');
    const closeButton = $('#aica-close-button');

    // Chat state
    let isChatOpen = false;
    let isWaitingForResponse = false;
    let isMinimized = false;
    let chatHistory = [];
    let contextOption = aicaData.contextOption;
    let currentPageUrl = '';
    let userConsented = false;
    let guestId = '';
    
    // Initialize the chat interface
    function initChat() {
        chatBubble.text(aicaData.chatBubbleText);
        chatBubble.css('background-color', aicaData.chatBubbleColor);
        chatBubble.on('click', toggleChat);
        sendButton.on('click', sendMessage);
        closeButton.on('click', closeChat);
        userInput.on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        if (aicaData.requireConsent === 'yes' && !localStorage.getItem('aica_user_consented')) {
        showConsentPrompt();
        } else {
        userConsented = true;
        }

        // Close chat when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#aica-chat-window, #aica-chat-bubble').length) {
                closeChat();
            }
        });
        currentPageUrl = aicaData.pageUrl;
        
        // Get or set guest ID
        guestId = getCookie('aica_guest_id');
        if (!guestId) {
            guestId = generateUUID();
            setCookie('aica_guest_id', guestId, 30);
        }
        

        // Add delete data button
        const deleteDataButton = $('<button>')
            .attr('id', 'aica-delete-data')
            .text('Delete My Data')
            .on('click', deleteUserData);
        chatWindow.append(deleteDataButton);       
    }

    // Show consent prompt
    function showConsentPrompt() {
        const consentPrompt = $('<div>')
            .addClass('aica-consent-prompt')
            .html(`
                <p>${aicaData.consentMessage}</p>
                <div>
                    <button id="aica-consent-agree">I Agree</button>
                    <button id="aica-consent-disagree">I Disagree</button>
                </div>
            `);
        
        chatWindow.append(consentPrompt);

        $('#aica-consent-agree').on('click', function() {
            userConsented = true;
            localStorage.setItem('aica_user_consented', 'true');
            consentPrompt.fadeOut(300, function() {
                $(this).remove();
                openChat();
            });
        });

        $('#aica-consent-disagree').on('click', function() {
            consentPrompt.fadeOut(300, function() {
                $(this).remove();
                closeChat();
                chatBubble.hide();
            });
        });
    }
    
    // Delete user data
    function deleteUserData() {
        if (confirm('Are you sure you want to delete all your chat data? This action cannot be undone.')) {
            $.ajax({
                url: aicaData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aica_delete_user_data',
                    nonce: aicaData.nonce,
                    guest_id: guestId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        chatHistory = [];
                        chatMessages.empty();
                        addMessage('ai', aicaData.welcomeMessage);
                    } else {
                        showError(response.data.message || 'Failed to delete data. Please try again.');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showError('Failed to connect to the server. Please check your internet connection.');
                    console.error('AJAX error:', textStatus, errorThrown);
                }
            });
        }
    }
    
    // Toggle chat window visibility
    function toggleChat() {
        if (isChatOpen) {
            if (isMinimized) {
                maximizeChat();
            } else {
                minimizeChat();
            }
        } else {
            openChat();
        }
    }

    // Open chat window
    function openChat() {
        chatWindow.slideDown(300);
        chatBubble.addClass('active');
        isChatOpen = true;
        isMinimized = false;
        userInput.focus();
        
        // Display welcome message if chat is empty
        if (chatMessages.children().length === 0) {
            addMessage('ai', aicaData.welcomeMessage);
        }
    }

    // Close chat window
    function closeChat() {
        chatWindow.slideUp(300);
        chatBubble.removeClass('active');
        isChatOpen = false;
        isMinimized = false;
    }

    
    

    // Send user message
    function sendMessage() {
        if (!userConsented) {
            showConsentPrompt();
            return;
        }
        const message = userInput.val().trim();
        if (message && !isWaitingForResponse) {
            addMessage('user', message);
            userInput.val('');
            getAIResponse(message);
        }
    }

    // Add a message to the chat window
    function addMessage(sender, message) {
        const messageElement = $('<div>')
            .addClass('aica-message')
            .addClass(sender === 'user' ? 'aica-user-message' : 'aica-ai-message');

        const avatar = $('<div>')
            .addClass('aica-avatar')
            .text(sender === 'user' ? 'You' : 'AI');

        const content = $('<div>')
            .addClass('aica-message-content')
            .text(message);

        messageElement.append(avatar, content);
        chatMessages.append(messageElement);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    // Show typing indicator
    function showTypingIndicator() {
        const typingIndicator = $('<div>')
            .addClass('aica-typing-indicator')
            .text('AI is typing...');
        chatMessages.append(typingIndicator);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    // Hide typing indicator
    function hideTypingIndicator() {
        $('.aica-typing-indicator').remove();
    }

    // Show error message
    function showError(message) {
        const errorElement = $('<div>')
            .addClass('aica-error-message')
            .text(message);
        chatMessages.append(errorElement);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }


    // Clear chat history
    function clearChatHistory() {
        $.ajax({
            url: aicaData.ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_clear_history',
                nonce: aicaData.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    chatMessages.empty();
                    addMessage('ai', 'Chat history has been cleared.');
                    if (aicaData.debug) {
                        console.log('Chat history cleared successfully');
                    }
                } else {
                    showError('Failed to clear chat history.');
                    if (aicaData.debug) {
                        console.error('Failed to clear chat history:', response.data.message);
                    }
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                showError('Failed to connect to the server. Please check your internet connection.');
                if (aicaData.debug) {
                    console.error('AJAX error:', textStatus, errorThrown);
                }
            }
        });
    }
	
	// Get AI response via AJAX
    function getAIResponse(message) {
        
        isWaitingForResponse = true;
        showTypingIndicator();

        $.ajax({
            url: aicaData.ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_chat',
                nonce: aicaData.nonce,
                message: message,
                pageContent: aicaData.pageContent,
                pageUrl: aicaData.pageUrl,
                contextOption: contextOption,
                chatHistory: chatHistory,
                guest_id: guestId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const aiMessage = response.data.response;
                    addMessage('ai', aiMessage);
                    updateTokenUsage(response.data.token_usage);
                    
                    // Update chat history
                    chatHistory.push({role: 'user', content: message});
                    chatHistory.push({role: 'assistant', content: aiMessage});
                } else {
                    showError('An error occurred. Please try again.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                showError('Failed to connect to the server. Please check your internet connection.');
                
            },
            complete: function() {
                isWaitingForResponse = false;
                hideTypingIndicator();
            }
        });
    }

    // Update token usage
    function updateTokenUsage(tokens) {
        $.ajax({
            url: aicaData.ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_update_token_usage',
                nonce: aicaData.nonce,
                tokens: tokens,
                guest_id: guestId
            },
            dataType: 'json',
            success: function(response) {
                if (aicaData.debug) {
                    console.log('Token usage updated:', tokens);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if (aicaData.debug) {
                    console.error('Failed to update token usage:', textStatus, errorThrown);
                }
            }
        });
    }

    // Handle page changes
    function handlePageChange() {
        const newPageContent = $('body').text().trim();
        const newPageUrl = window.location.href;

        if (newPageUrl !== currentPageUrl) {
            aicaData.pageContent = newPageContent;
            aicaData.pageUrl = newPageUrl;
            currentPageUrl = newPageUrl;
            
            // Reset chat history and first message flag
            chatHistory = [];
            

            if (isChatOpen) {
                addMessage('system', 'The page has changed. Starting a new conversation.');
                chatMessages.empty();
                addMessage('ai', aicaData.welcomeMessage);
            }
        }
    }

    // Get a cookie value by name
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    // Set a cookie
    function setCookie(name, value, days, sameSite = 'Lax') {
        const expires = new Date(Date.now() + days * 864e5).toUTCString();
        document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=${sameSite}; Secure`;
    }

    // Generate a UUID
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }


    // Listen for page changes (for single-page applications)
    $(window).on('popstate', handlePageChange);

    // You may also want to call handlePageChange() when your SPA updates the page content
    // For example, if you're using a custom event when your SPA updates the page:
    // $(document).on('spaPageUpdate', handlePageChange);

    // Initialize on document ready
    $(document).ready(function() {
        initChat();
        handlePageChange(); // Initial page context setup
    });

})(jQuery);

   