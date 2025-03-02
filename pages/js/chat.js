// BuddyGo Chat System
$(document).ready(function() {
    // Variables
    const chatMessages = $('#chatMessages');
    const messageInput = $('#messageInput');
    const chatForm = $('#chatForm');
    const groupId = new URLSearchParams(window.location.search).get('group_id');
    let lastMessageId = 0;
    
    // Initialize
    init();
    
    function init() {
        // Scroll to bottom of chat
        scrollToBottom();
        
        // Get last message ID
        if (chatMessages.find('.message').length > 0) {
            lastMessageId = chatMessages.find('.message').last().data('message-id');
        }
        
        // Set up polling for new messages
        setInterval(fetchNewMessages, 3000);
        
        // Set up event listeners
        setupEventListeners();
    }
    
    function setupEventListeners() {
        // Form submission
        chatForm.on('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
        
        // Mobile sidebar toggle
        $('.mobile-toggle').on('click', function() {
            $('.chat-sidebar').toggleClass('show');
            $('.sidebar-backdrop').toggleClass('show');
        });
        
        // Close sidebar when backdrop is clicked
        $('.sidebar-backdrop').on('click', function() {
            $('.chat-sidebar').removeClass('show');
            $('.sidebar-backdrop').removeClass('show');
        });
        
        // Auto-resize textarea
        $('#messageInput').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Handle report user modal
        const reportUserModal = document.getElementById('reportUserModal');
        if (reportUserModal) {
            reportUserModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const userName = button.getAttribute('data-user-name');
                
                document.getElementById('reportedUserId').value = userId;
                document.getElementById('reportedUserName').textContent = userName;
            });
            
            reportUserModal.addEventListener('hidden.bs.modal', function() {
                document.querySelector('#reportUserModal form').reset();
            });
        }
        
        // Handle no-show report modal
        const reportNoShowModal = document.getElementById('reportNoShowModal');
        if (reportNoShowModal) {
            reportNoShowModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const userName = button.getAttribute('data-user-name');
                
                document.getElementById('noShowUserId').value = userId;
                document.getElementById('noShowUserName').textContent = userName;
            });
            
            reportNoShowModal.addEventListener('hidden.bs.modal', function() {
                document.querySelector('#reportNoShowModal form').reset();
            });
        }
        
        // Clear modal forms on close
        $('.modal').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
        });

        // Handle image upload button click
        $('#imageUploadBtn').on('click', function() {
            $('#imageUpload').click(); // Trigger the file input click
        });

        // Handle file input change event
        $('#imageUpload').on('change', function() {
            const file = this.files[0];
            if (file) {
                // Check if the file is an image
                if (!file.type.match('image.*')) {
                    alert('กรุณาเลือกไฟล์รูปภาพเท่านั้น');
                    return;
                }
                
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('ขนาดไฟล์ต้องไม่เกิน 5MB');
                    return;
                }
                
                // Create FormData object
                const formData = new FormData();
                formData.append('image', file);
                formData.append('group_id', groupId);
                
                // Show loading indicator
                const loadingMessage = $('<div class="message message-self loading"><div class="message-content">กำลังอัพโหลดรูปภาพ...</div></div>');
                chatMessages.append(loadingMessage);
                scrollToBottom();
                
                // Upload the image
                $.ajax({
                    url: 'ajax/upload_image.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Remove loading message
                        loadingMessage.remove();
                        
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                // Add message with image to chat
                                addMessageToChat({
                                    message_id: data.message_id,
                                    user_id: currentUserId,
                                    message: data.message || '',
                                    created_at: data.created_at,
                                    image_path: data.image_path,
                                    is_self: true
                                });
                                
                                // Update last message ID
                                lastMessageId = data.message_id;
                                
                                // Scroll to bottom
                                scrollToBottom();
                            } else {
                                showError('ไม่สามารถอัพโหลดรูปภาพได้: ' + (data.error || 'เกิดข้อผิดพลาด'));
                            }
                        } catch (e) {
                            showError('เกิดข้อผิดพลาดในการประมวลผลข้อมูล');
                        }
                    },
                    error: function() {
                        // Remove loading message
                        loadingMessage.remove();
                        showError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                    }
                });
                
                // Reset file input
                $(this).val('');
            }
        });
    }
    
    function sendMessage() {
        const message = messageInput.val().trim();
        
        if (!message || !groupId) {
            return;
        }
        
        // Disable input while sending
        messageInput.prop('disabled', true);
        
        $.ajax({
            url: 'ajax/send_message.php',
            type: 'POST',
            data: {
                group_id: groupId,
                message: message
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Clear input
                    messageInput.val('');
                    
                    // Add message to chat
                    addMessageToChat({
                        message_id: response.message_id,
                        user_id: currentUserId,
                        message: message,
                        created_at: response.created_at,
                        is_self: true
                    });
                    
                    // Update last message ID
                    lastMessageId = response.message_id;
                } else {
                    showError('ไม่สามารถส่งข้อความได้: ' + (response.error || 'เกิดข้อผิดพลาด'));
                }
            },
            error: function() {
                showError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            },
            complete: function() {
                // Re-enable input
                messageInput.prop('disabled', false);
                messageInput.focus();
            }
        });
    }
    
    function fetchNewMessages() {
        if (!groupId) {
            return;
        }
        
        $.ajax({
            url: 'ajax/get_messages.php',
            type: 'GET',
            data: {
                group_id: groupId,
                last_id: lastMessageId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Add new messages
                    if (response.messages && response.messages.length > 0) {
                        response.messages.forEach(function(msg) {
                            addMessageToChat({
                                message_id: msg.message_id,
                                user_id: msg.user_id,
                                username: msg.username,
                                profile_picture: msg.profile_picture,
                                message: msg.message,
                                created_at: msg.created_at,
                                image_path: msg.image_path || null,
                                is_self: msg.user_id == currentUserId
                            });
                        });
                        
                        // Update last message ID
                        lastMessageId = response.messages[response.messages.length - 1].message_id;
                        
                        // Scroll to bottom if user was already at bottom
                        if (isAtBottom()) {
                            scrollToBottom();
                        }
                    }
                }
            }
        });
    }
    
    function addMessageToChat(msg) {
        // Check if message already exists
        if ($('.message[data-message-id="' + msg.message_id + '"]').length > 0) {
            return;
        }
        
        const messageClass = msg.is_self ? 'message-self' : 'message-other';
        let messageHtml = '<div class="message ' + messageClass + '" data-message-id="' + msg.message_id + '">';
        
        // Add header for other users' messages
        if (!msg.is_self) {
            messageHtml += '<div class="message-header">';
            // Use default.jpg if profile_picture is not available
            const profilePic = msg.profile_picture || 'default.jpg';
            messageHtml += '<img src="../uploads/profile/' + profilePic + '" alt="Profile" onerror="this.src=\'../uploads/profile/default.jpg\'">';
            messageHtml += '<span>' + msg.username + '</span>';
            messageHtml += '</div>';
        }
        
        messageHtml += '<div class="message-content">';
        
        // Add image if exists
        if (msg.image_path) {
            messageHtml += '<img src="' + msg.image_path + '" alt="Uploaded Image" class="chat-image" data-bs-toggle="modal" data-bs-target="#imagePreviewModal">';
        }
        
        // Add message text
        if (msg.message) {
            messageHtml += msg.message.replace(/\n/g, '<br>');
        }
        
        messageHtml += '</div>';
        
        // Add timestamp
        const date = new Date(msg.created_at);
        const formattedDate = date.toLocaleDateString('th-TH') + ' ' + date.toLocaleTimeString('th-TH', {hour: '2-digit', minute:'2-digit'});
        messageHtml += '<div class="message-time">' + formattedDate + '</div>';
        
        messageHtml += '</div>';
        
        // Append to chat
        chatMessages.append(messageHtml);
    }
    
    function scrollToBottom() {
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }
    
    function isAtBottom() {
        const scrollTop = chatMessages.scrollTop();
        const scrollHeight = chatMessages[0].scrollHeight;
        const clientHeight = chatMessages[0].clientHeight;
        
        return scrollTop + clientHeight >= scrollHeight - 50;
    }
    
    function showError(message) {
        // Create error toast
        const errorToast = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>');
        
        // Append to chat container
        $('.chat-container').prepend(errorToast);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            errorToast.alert('close');
        }, 5000);
    }
}); 