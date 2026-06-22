// Real-time dashboard updates
document.addEventListener('DOMContentLoaded', function() {
    // Only run if Echo is available and user is authenticated
    if (typeof window.Echo === 'undefined') return;

    const tenantId = document.querySelector('meta[name="tenant-id"]')?.content;
    if (!tenantId) return;

    const channel = window.Echo.private(`tenant.${tenantId}`);

    // Call started - update active calls counter and show notification
    channel.listen('CallStarted', (data) => {
        console.log('Call started:', data);
        updateCounter('calls-today', 1);
        showNotification(`Apel nou de la ${data.caller_number}`, 'info');
        addCallToTable(data);
    });

    // Call ended - update metrics
    channel.listen('CallEnded', (data) => {
        console.log('Call ended:', data);
        updateCallStatus(data.id, data.status, data.duration_seconds);
    });

    // Transcript update - show live transcript
    channel.listen('CallTranscriptUpdated', (data) => {
        console.log('Transcript:', data);
        appendTranscript(data.call_id, data.role, data.content);
    });

    // Bot status change
    channel.listen('BotStatusChanged', (data) => {
        console.log('Bot status:', data);
        updateBotStatus(data.id, data.is_active);
    });

    // Usage update - update progress bar
    channel.listen('UsageUpdated', (data) => {
        console.log('Usage:', data);
        updateUsageBar(data.percentage);
    });

    // HITL: assignee changed for a conversation. Update the badge in any
    // visible inbox card and play a soft notification if the current user
    // just got assigned the conversation.
    channel.listen('.conversation.assignment-changed', (data) => {
        updateAssigneeBadge(data.conversation_id, data.assignee_type, data.assignee_id);

        const meId = parseInt(document.querySelector('meta[name="user-id"]')?.content || '0');
        if (data.assignee_type === 'user' && data.assignee_id === meId && data.actor_user_id !== meId) {
            showNotification('O conversație ți-a fost atribuită.', 'info');
        }
    });
});

// Helper functions
function updateCounter(id, increment) {
    const el = document.getElementById(id);
    if (el) {
        const current = parseInt(el.textContent) || 0;
        el.textContent = current + increment;
    }
}

function showNotification(message, type = 'info') {
    const container = document.getElementById('live-notifications');
    if (!container) return;

    const colors = {
        info: 'bg-blue-50 text-blue-700 border-blue-200',
        success: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        error: 'bg-red-50 text-red-700 border-red-200',
    };

    const notification = document.createElement('div');
    notification.className = `p-3 rounded-lg border ${colors[type] || colors.info} text-sm animate-fade-in`;
    notification.textContent = message;
    container.prepend(notification);

    setTimeout(() => notification.remove(), 5000);
}

function addCallToTable(data) {
    const tbody = document.getElementById('recent-calls-body');
    if (!tbody) return;

    const row = document.createElement('tr');
    row.className = 'border-t border-slate-100';
    row.innerHTML = `
        <td class="py-3 px-4 text-sm">${data.bot_name || '\u2014'}</td>
        <td class="py-3 px-4 text-sm">${data.caller_number || '\u2014'}</td>
        <td class="py-3 px-4"><span class="px-2 py-1 text-xs rounded-full bg-blue-50 text-blue-700">\u00cen curs</span></td>
        <td class="py-3 px-4 text-sm text-slate-500">\u2014</td>
        <td class="py-3 px-4 text-sm text-slate-500">Acum</td>
    `;
    tbody.prepend(row);
}

function updateCallStatus(callId, status, duration) {
    // Update call row if visible
    const statusBadges = {
        completed: '<span class="px-2 py-1 text-xs rounded-full bg-emerald-50 text-emerald-700">Completat</span>',
        failed: '<span class="px-2 py-1 text-xs rounded-full bg-red-50 text-red-700">E\u0219uat</span>',
    };
    // In a real implementation, find the row by call ID and update
}

function appendTranscript(callId, role, content) {
    const container = document.getElementById(`transcript-${callId}`);
    if (!container) return;

    const isBot = role === 'assistant';
    const bubble = document.createElement('div');
    bubble.className = isBot
        ? 'flex justify-start mb-3'
        : 'flex justify-end mb-3';
    bubble.innerHTML = `
        <div class="max-w-[80%] px-4 py-2 rounded-2xl text-sm ${
            isBot ? 'bg-slate-100 text-slate-900' : 'bg-primary-600 text-white'
        }">
            ${content}
        </div>
    `;
    container.appendChild(bubble);
    container.scrollTop = container.scrollHeight;
}

function updateBotStatus(botId, isActive) {
    const indicator = document.getElementById(`bot-status-${botId}`);
    if (indicator) {
        indicator.className = isActive
            ? 'w-2.5 h-2.5 rounded-full bg-emerald-500'
            : 'w-2.5 h-2.5 rounded-full bg-slate-300';
    }
}

function updateAssigneeBadge(conversationId, assigneeType, assigneeId) {
    document.querySelectorAll(`[data-conversation-id="${conversationId}"] [data-assignee-badge]`).forEach((el) => {
        el.textContent = assigneeType === 'user' ? 'Operator' : 'Bot';
        el.dataset.assigneeType = assigneeType;
        el.classList.toggle('bg-amber-50', assigneeType === 'user');
        el.classList.toggle('text-amber-700', assigneeType === 'user');
        el.classList.toggle('bg-emerald-50', assigneeType === 'bot');
        el.classList.toggle('text-emerald-700', assigneeType === 'bot');
    });
}

/**
 * ConversationMessageReceived broadcast — real-time pentru operator inbox.
 * Backend: app/Events/ConversationMessageReceived.php (via MessageObserver).
 * Frontend (operator/inbox views) ascultă custom events `sambla:conversation.*`.
 */
(function wireConversationListeners() {
    if (typeof window.Echo === 'undefined') return;
    const tid = document.querySelector('meta[name="tenant-id"]')?.content;
    if (!tid) return;
    try {
        const ch = window.Echo.private('tenant.' + tid);
        ch.listen('.conversation.message', (data) => {
            window.dispatchEvent(new CustomEvent('sambla:conversation.message', { detail: data }));
            if (data.direction === 'inbound' && data.needs_human) {
                if (typeof showNotification === 'function') {
                    showNotification('Vizitator cere operator: ' + (data.content || '').slice(0, 80), 'warn');
                }
            }
        });
        ch.listen('.conversation.status', (data) => {
            window.dispatchEvent(new CustomEvent('sambla:conversation.status', { detail: data }));
        });
        ch.listen('.conversation.typing', (data) => {
            window.dispatchEvent(new CustomEvent('sambla:conversation.typing', { detail: data }));
        });
        ch.listen('.conversation.read', (data) => {
            window.dispatchEvent(new CustomEvent('sambla:conversation.read', { detail: data }));
        });
    } catch (e) {
        console.warn('wireConversationListeners failed', e);
    }
})();

function updateUsageBar(percentage) {
    const bar = document.getElementById('usage-bar');
    if (bar) {
        bar.style.width = `${percentage}%`;
        bar.className = bar.className.replace(/bg-\w+-\d+/g, '');
        if (percentage >= 100) bar.classList.add('bg-red-500');
        else if (percentage >= 80) bar.classList.add('bg-amber-500');
        else bar.classList.add('bg-emerald-500');
    }
}
