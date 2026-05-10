<?php

if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['lietotajs', 'ipasnieks'], true)) {
    ?>

    <div class="chat-button" id="chatButton" onclick="toggleChat()">
        <i class="fas fa-comment"></i>
    </div>

    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <h4>Sazines čats</h4>

            <button class="chat-close" onclick="toggleChat()">
                <i class="fas fa-times"></i>
            </button>
        </div>


    </div>

    <script>
        function toggleChat() {
            const chatWindow = document.getElementById('chatWindow');

            if (chatWindow.style.display === 'flex') {
                chatWindow.style.display = 'none';
            } else {
                chatWindow.style.display = 'flex';
            }
        }

        document.addEventListener('click', function(event) {
            const chatWindow = document.getElementById('chatWindow');
            const chatButton = document.getElementById('chatButton');

            if (!chatWindow || !chatButton) return;

            if (
                !chatWindow.contains(event.target) &&
                !chatButton.contains(event.target)
            ) {
                if (chatWindow.style.display === 'flex') {
                    chatWindow.style.display = 'none';
                }
            }
        });
    </script>

    <?php
}
?>