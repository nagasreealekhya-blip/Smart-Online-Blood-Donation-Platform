const mobileMenu = document.getElementById('mobileMenu');
const mobileOverlay = document.getElementById('mobileNavOverlay');
const closeMobile = document.getElementById('closeMobile');
const notificationsList = document.getElementById('notificationsList');
const notificationItems = () => Array.from(document.querySelectorAll('.notification-item'));
const unreadCount = document.getElementById('unreadCount');
const notificationBadge = document.getElementById('notificationBadge');
const emptyState = document.getElementById('emptyState');
const markAllReadBtn = document.getElementById('markAllRead');
const deleteAllBtn = document.getElementById('deleteAllNotifications');
const searchInput = document.getElementById('searchNotifications');
const sortSelect = document.getElementById('sortNotifications');
const filterAll = document.getElementById('filterAll');
const filterUnread = document.getElementById('filterUnread');
const filterRead = document.getElementById('filterRead');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const successModal = document.getElementById('successModal');
const successMessage = document.getElementById('successMessage');
const confirmModal = document.getElementById('confirmModal');
const confirmTitle = document.getElementById('confirmTitle');
const confirmMessage = document.getElementById('confirmMessage');

let confirmCallback = null;

function toggleMobile(show) {
    mobileOverlay.classList.toggle('active', show);
}

function showSuccess(message) {
    successMessage.textContent = message;
    successModal.classList.add('active');
}

function updateCounts() {
    const unread = notificationItems().filter((item) => item.style.display !== 'none' && item.classList.contains('unread')).length;
    const total = notificationItems().filter((item) => item.style.display !== 'none').length;
    unreadCount.textContent = unread;
    notificationBadge.textContent = unread;
    emptyState.style.display = total ? 'none' : 'block';
    loadMoreBtn.style.display = 'inline-flex';
}

function applyFilters() {
    const activeTypes = filterAll.checked
        ? ['blood', 'donation', 'appointment', 'message', 'announcement']
        : [
            document.getElementById('filterBlood').checked && 'blood',
            document.getElementById('filterDonation').checked && 'donation',
            document.getElementById('filterAppointment').checked && 'appointment',
            document.getElementById('filterMessage').checked && 'message',
            document.getElementById('filterAnnouncement').checked && 'announcement'
        ].filter(Boolean);

    const query = searchInput.value.trim().toLowerCase();

    notificationItems().forEach((item) => {
        const typeMatch = activeTypes.includes(item.dataset.type);
        const readMatch = filterUnread.checked
            ? item.classList.contains('unread')
            : filterRead.checked
                ? item.classList.contains('read')
                : true;
        const textMatch = item.textContent.toLowerCase().includes(query);

        item.style.display = typeMatch && readMatch && textMatch ? '' : 'none';
    });

    updateCounts();
}

function dismissNotification(button) {
    button.closest('.notification-item').remove();
    updateCounts();
}

function markItemRead(button) {
    const item = button.closest('.notification-item');
    item.classList.remove('unread');
    item.classList.add('read');
    updateCounts();
}

function respondToRequest(button) {
    markItemRead(button);
    showSuccess('Response sent to the hospital team.');
}

function scheduleAppointment(button) {
    markItemRead(button);
    showSuccess('Appointment scheduling can be connected here.');
}

function viewMessage(button) {
    markItemRead(button);
    showSuccess('Message opened successfully.');
}

function exploreFeature(button) {
    markItemRead(button);
    showSuccess('Feature preview can be linked to another page.');
}

function closeModal() {
    successModal.classList.remove('active');
}

function closeConfirmModal() {
    confirmModal.classList.remove('active');
}

function confirmAction() {
    if (confirmCallback) {
        confirmCallback();
    }
    closeConfirmModal();
}

mobileMenu?.addEventListener('click', () => toggleMobile(true));
closeMobile?.addEventListener('click', () => toggleMobile(false));
mobileOverlay?.addEventListener('click', (event) => {
    if (event.target === mobileOverlay) {
        toggleMobile(false);
    }
});

searchInput?.addEventListener('input', applyFilters);
sortSelect?.addEventListener('change', () => {
    const items = notificationItems();
    const sorted = sortSelect.value === 'unread'
        ? items.sort((a, b) => Number(b.classList.contains('unread')) - Number(a.classList.contains('unread')))
        : sortSelect.value === 'oldest'
            ? items.reverse()
            : items;

    sorted.forEach((item) => notificationsList.appendChild(item));
});

[filterAll, filterUnread, filterRead,
    document.getElementById('filterBlood'),
    document.getElementById('filterDonation'),
    document.getElementById('filterAppointment'),
    document.getElementById('filterMessage'),
    document.getElementById('filterAnnouncement')
].forEach((control) => control?.addEventListener('change', applyFilters));

markAllReadBtn?.addEventListener('click', () => {
    notificationItems().forEach((item) => {
        item.classList.remove('unread');
        item.classList.add('read');
    });
    updateCounts();
});

deleteAllBtn?.addEventListener('click', () => {
    confirmTitle.textContent = 'Delete All Notifications';
    confirmMessage.textContent = 'This will remove all notifications from the page.';
    confirmCallback = () => {
        notificationItems().forEach((item) => item.remove());
        updateCounts();
    };
    confirmModal.classList.add('active');
});

loadMoreBtn?.addEventListener('click', () => {
    showSuccess('Additional notifications can be loaded from your backend here.');
});

updateCounts();

window.dismissNotification = dismissNotification;
window.respondToRequest = respondToRequest;
window.scheduleAppointment = scheduleAppointment;
window.viewMessage = viewMessage;
window.exploreFeature = exploreFeature;
window.closeModal = closeModal;
window.closeConfirmModal = closeConfirmModal;
window.confirmAction = confirmAction;
