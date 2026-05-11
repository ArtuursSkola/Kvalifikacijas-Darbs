<?php
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['lietotājs', 'lietotajs', 'ipasnieks'], true)) {

    $currentUserId = $_SESSION['user_id'] ?? 0;
    
    if ($currentUserId > 0) {
        $stmt = $savienojums->prepare("
            SELECT COUNT(*) as count 
            FROM est_zinas 
            WHERE sanemeja_id = ? AND izlasita = 0
        ");
        $stmt->bind_param('i', $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $unreadCount = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
    }
    $mediaBaseUrl = app_absolute_url('');
?>


    <div class="chat-button-wrapper">
        <div class="chat-button" id="chatButton" onclick="toggleChat()">
            <i class="fas fa-comment"></i>

            <?php if ($unreadCount > 0): ?>
                <div class="chat-notification">
                    <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>


<div class="chat-window" id="chatWindow">
    <div class="chat-header">
        <h4 id="chatTitle">Sarunas</h4>
        <button class="chat-close" onclick="toggleChat()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    

    <div class="chat-list" id="chatList">
        <div class="chat-list-header">
            <h5>Jūsu sarunas</h5>
        </div>
        <div class="chat-conversations" id="chatConversations">

        </div>
    </div>

    <div class="chat-messages-view" id="chatMessagesView" style="display: none;">
        <div class="chat-messages-header">
            <button class="chat-back" onclick="showChatList()">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div class="chat-participant-info">
                <div class="chat-participant-name" id="chatParticipantName"></div>
                <div class="chat-participant-status" id="chatParticipantStatus"></div>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages">

        </div>
        <div class="chat-input">
            <input type="text" id="chatInput" placeholder="Rakstiet ziņu..." onkeypress="handleChatKeyPress(event)">
            <button onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>
<script>
let currentChatUserId = null;
let currentChatUserName = '';

function toggleChat() {
    const chatWindow = document.getElementById('chatWindow');
    const chatButton = document.getElementById('chatButton');
    
    if (chatWindow.style.display === 'flex') {
        chatWindow.style.display = 'none';
    } else {
        chatWindow.style.display = 'flex';
        loadChatList();
    }
}

function showChatList() {
    document.getElementById('chatList').style.display = 'block';
    document.getElementById('chatMessagesView').style.display = 'none';
    document.getElementById('chatTitle').textContent = 'Sarunas';
    currentChatUserId = null;
}

function openChat(userId, userName) {
    currentChatUserId = userId;
    currentChatUserName = userName;
    
    document.getElementById('chatList').style.display = 'none';
    document.getElementById('chatMessagesView').style.display = 'flex';
    document.getElementById('chatTitle').textContent = userName;
    document.getElementById('chatParticipantName').textContent = userName;
    document.getElementById('chatParticipantStatus').textContent = 'Online';
    
    loadChatMessages(userId);
    markMessagesAsRead();
    document.getElementById('chatInput').focus();
}

function loadChatList() {
    fetch('<?php echo app_absolute_url('api/zinas_saraksts.php'); ?>')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('chatConversations');
            container.innerHTML = '';
            
            if (data.length === 0) {
                container.innerHTML = '<div class="no-conversations">Nav sarunu</div>';
                return;
            }
            
            data.forEach(chat => {
                const conversationDiv = document.createElement('div');
                conversationDiv.className = 'chat-conversation';
                conversationDiv.onclick = () => openChat(chat.user_id, chat.username);

                const avatarHtml = chat.profila_bilde
                    ? `<img src="<?php echo app_absolute_url(''); ?>${chat.profila_bilde}"
                            style="width:40px;height:40px;border-radius:50%;object-fit:cover;"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                       <div class="conversation-avatar" style="display:none;"><i class="fas fa-user"></i></div>`
                    : `<div class="conversation-avatar"><i class="fas fa-user"></i></div>`;

                conversationDiv.innerHTML = `
                    <div style="margin-right:15px;width:40px;height:40px;flex-shrink:0;">
                        ${avatarHtml}
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-name">${chat.username}</div>
                        <div class="conversation-message">${chat.last_message}</div>
                    </div>
                    <div class="conversation-meta">
                        <div class="conversation-time">${formatTime(chat.created_at)}</div>
                        ${chat.unread_count > 0 ? `<div class="conversation-badge">${chat.unread_count > 9 ? '9+' : chat.unread_count}</div>` : ''}
                    </div>
                `;
                
                container.appendChild(conversationDiv);
            });
        })
        .catch(error => console.error('Error loading chat list:', error));
}

function loadChatMessages(userId) {
    fetch('<?php echo app_absolute_url('api/zinas_atvert.php'); ?>?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('chatMessages');
            container.innerHTML = '';
            
            data.forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `chat-message ${message.sutitaja_id == <?php echo (int)($currentUserId ?? 0); ?> ? 'sent' : 'received'}`;
                
                messageDiv.innerHTML = `
                    <div class="message-content">${message.zina}</div>
                    <div class="message-time">${formatTime(message.created_at)}</div>
                    ${message.sutitaja_id == <?php echo (int)($currentUserId ?? 0); ?> ? `<div class="message-status">
           <i class="fas ${message.izlasita ? 'fa-check-double' : 'fa-check'}"></i>
       </div>`
                    : ''}
                `;
                
                container.appendChild(messageDiv);
            });
            
            container.scrollTop = container.scrollHeight;
        })
        .catch(error => console.error('Error loading messages:', error));
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (message === '' || !currentChatUserId) return;
    
    const formData = new FormData();
    formData.append('receiver_id', currentChatUserId);
    formData.append('message', message);

    fetch('<?php echo app_absolute_url('api/zinas_sutat.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            loadChatMessages(currentChatUserId);
        } else {
            alert('Kļūda sūtot ziņu');
        }
    })
    .catch(error => console.error('Error sending message:', error));
}

function handleChatKeyPress(event) {
    if (event.key === 'Enter') {
        sendMessage();
    }
}

function markMessagesAsRead() {
    fetch('<?php echo app_absolute_url('api/zinas_lasitas.php'); ?>', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {

            const badge = document.querySelector('.chat-notification');
            if (badge) badge.remove();
        }
    })
    .catch(error => console.error('Error marking messages as read:', error));
}

function formatTime(dateString) {
    const normalized = String(dateString || '').replace(' ', 'T');
    const localParsed = new Date(normalized);
    const utcParsed = new Date(normalized + 'Z');
    const now = new Date();


    let date = localParsed;
    if (!Number.isNaN(utcParsed.getTime())) {
        const localDelta = Number.isNaN(localParsed.getTime()) ? Number.POSITIVE_INFINITY : Math.abs(now - localParsed);
        const utcDelta = Math.abs(now - utcParsed);
        if (utcDelta < localDelta) date = utcParsed;
    }
    if (Number.isNaN(date.getTime())) return '';

    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Tagad';
    if (diffMins < 60) return diffMins + ' min';
    if (diffHours < 24) return diffHours + ' st.';
    if (diffDays < 7) return diffDays + ' d.';

    return date.toLocaleDateString('lv-LV');
}

function startChatWithUser(userId, userName, listingId = null) {
    const chatWindow = document.getElementById('chatWindow');
    if (chatWindow.style.display !== 'flex') {
        chatWindow.style.display = 'flex';
    }
    loadChatList();
    openChat(userId, userName);
}


document.addEventListener('click', function(event) {
    const chatWindow = document.getElementById('chatWindow');
    const chatButton = document.getElementById('chatButton');

    if (!chatWindow || !chatButton) return;


    if (event.target.closest('[onclick*="startChatWithUser"]')) return;

    if (!chatWindow.contains(event.target) && !chatButton.contains(event.target)) {
        if (chatWindow.style.display === 'flex') {
            chatWindow.style.display = 'none';
        }
    }
});


setInterval(() => {
    if (document.getElementById('chatWindow').style.display === 'flex' && currentChatUserId === null) {
        loadChatList();
    }
}, 30000);
</script>
<?php
}
?>