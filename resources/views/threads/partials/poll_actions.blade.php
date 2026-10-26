<script>
if (!window.__pollVoteHandlerBound) {
    window.__pollVoteHandlerBound = true;
    document.addEventListener('click', function(e) {
        const voteBtn = e.target.closest('.btn-ajax-poll-vote');
        if (!voteBtn) return;
        e.preventDefault();
        e.stopPropagation();

        if (typeof window.checkAuth === 'function' && !window.checkAuth('tham gia bình chọn thăm dò ý kiến')) {
            return;
        }

        const threadId = voteBtn.getAttribute('data-thread-id');
        const optionId = voteBtn.getAttribute('data-option-id');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        voteBtn.disabled = true;

        fetch(`/threads/${threadId}/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ option_id: optionId })
        })
        .then(res => {
            if (res.status === 401) {
                voteBtn.disabled = false;
                window.checkAuth('tham gia bình chọn thăm dò ý kiến');
                return;
            }
            return res.json();
        })
        .then(data => {
            if (data && data.success && data.poll) {
                const card = document.getElementById(`pollCard-${threadId}`);
                if (card) {
                    const wrap = card.querySelector('.poll-items-wrap');
                    const userPickId = data.poll.user_voted_option;
                    if (wrap) {
                        wrap.innerHTML = data.poll.options.map(opt => {
                            const isPicked = (opt.id === userPickId);
                            const checkSvg = isPicked ? `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="ms-1 text-white"><polyline points="20 6 9 17 4 12"></polyline></svg>` : '';
                            return `
                                <div class="poll-display-result-row ${isPicked ? 'user-picked' : ''}">
                                    <div class="poll-result-bar-bg" style="width: ${opt.percentage}%;"></div>
                                    <div class="poll-result-label-row">
                                        <span class="poll-result-opt-name">${opt.text} ${checkSvg}</span>
                                        <span class="poll-result-percent">${opt.percentage}%</span>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }
                    const countSpan = card.querySelector('.poll-total-votes-count');
                    if (countSpan) {
                        countSpan.textContent = `${data.poll.total_votes} phiếu bầu`;
                    }
                }
            } else {
                voteBtn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            voteBtn.disabled = false;
        });
    });
}
</script>
