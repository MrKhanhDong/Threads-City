export function pollPercent(votes, total) {
    return total > 0 ? Math.max(0, Math.min(100, Math.round(votes / total * 100))) : 0;
}

export function setupPolls(doc = document, pageWindow = window) {
    const busy = new Set();
    function render(card, poll) {
        const wrap = card.querySelector('.poll-items-wrap'); wrap.replaceChildren();
        poll.options.forEach(option => {
            const row = doc.createElement('div'); row.className = 'poll-display-result-row';
            row.classList.toggle('user-picked', Number(option.id) === Number(poll.user_voted_option));
            const bar = doc.createElement('div'); bar.className = 'poll-result-bar-bg';
            const percentage = pollPercent(option.votes, poll.total_votes); bar.style.width = `${percentage}%`;
            const label = doc.createElement('div'); label.className = 'poll-result-label-row';
            const text = doc.createElement('span'); text.className = 'poll-result-opt-name';
            text.textContent = option.text + (Number(option.id) === Number(poll.user_voted_option) ? ' ✓' : '');
            const percent = doc.createElement('span'); percent.className = 'poll-result-percent'; percent.textContent = `${percentage}%`;
            label.append(text, percent); row.append(bar, label); wrap.append(row);
        });
        card.querySelector('.poll-total-votes-count').textContent = `${poll.total_votes} phiếu bầu`;
        card.dataset.pollOptions = JSON.stringify(poll.options); card.dataset.pollTotal = String(poll.total_votes);
        card.dataset.pollPick = poll.user_voted_option || '';
        if (poll.closed) card.querySelector('.poll-deadline').textContent = 'Đã kết thúc';
    }
    doc.addEventListener('click', async event => {
        const button = event.target.closest('.btn-ajax-poll-vote'); if (!button) return;
        event.preventDefault(); event.stopPropagation();
        if (pageWindow.checkAuth && !pageWindow.checkAuth('tham gia bình chọn')) return;
        const id = button.dataset.threadId;
        if (busy.has(id)) return;
        busy.add(id);
        const cards = [...doc.querySelectorAll('[data-poll-id]')].filter(card => card.dataset.pollId === id);
        cards.forEach(card => card.querySelectorAll('button').forEach(option => { option.disabled = true; }));
        try {
            const response = await pageWindow.fetch(button.dataset.voteUrl, {
                method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': doc.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ option_id: Number(button.dataset.optionId) }),
            });
            const data = await response.json();
            if (data.poll && (response.ok || response.status === 409)) cards.forEach(card => render(card, data.poll));
            if (!response.ok) throw new Error(data.message || 'Không thể bình chọn. Hãy thử lại.');
        } catch (error) { pageWindow.showThreadsToast?.(error.message); }
        finally { busy.delete(id); cards.forEach(card => card.querySelectorAll('button').forEach(option => { option.disabled = false; })); }
    });
    const expire = () => {
        doc.querySelectorAll('[data-poll-id][data-expires-at]').forEach(card => {
            if (!card.querySelector('.btn-ajax-poll-vote') || Date.parse(card.dataset.expiresAt) > Date.now() || !card.dataset.expiresAt) return;
            render(card, { options: JSON.parse(card.dataset.pollOptions), total_votes: Number(card.dataset.pollTotal), user_voted_option: Number(card.dataset.pollPick), closed: true });
        });
    };
    doc.addEventListener('threads:updated', expire); expire();
    const timer = pageWindow.setInterval(expire, 10000);
    pageWindow.addEventListener('pagehide', () => pageWindow.clearInterval(timer));
}
