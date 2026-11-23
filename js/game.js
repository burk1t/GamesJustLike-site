(function () {
    // ===== НЕДАВНО ПРОСМОТРЕННЫЕ =====
    const RKEY = 'recentlyViewed';
    const gameIdEl = document.querySelector('[data-game-id]');
    const gameId = gameIdEl ? Number(gameIdEl.dataset.gameId) : null;

    // Пытаемся взять id из URL
    const urlMatch = window.location.pathname.match(/\/Game\/(\d+)/i);
    const currentGameId = gameId || (urlMatch ? Number(urlMatch[1]) : null);

    if (currentGameId) {
        let ids = [];
        try { ids = JSON.parse(localStorage.getItem(RKEY) || '[]'); }
        catch { ids = []; }

        ids = ids.filter(x => x !== currentGameId);
        ids.unshift(currentGameId);
        ids = ids.slice(0, 10);
        localStorage.setItem(RKEY, JSON.stringify(ids));
    }

    // ===== МОДАЛКА ЦЕН CHEAPSHARK =====
    const modal = document.getElementById('pricesModal');
    const modalTitle = document.getElementById('pricesTitle');
    const modalBody = document.getElementById('pricesBody');
    const closeBtn = document.getElementById('pricesClose');
    const btns = document.querySelectorAll('.show-prices-btn');

    function openModal() { modal?.classList.remove('hidden'); }
    function closeModal() { modal?.classList.add('hidden'); }

    closeBtn?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    btns.forEach(btn => {
        btn.addEventListener('click', async () => {
            const title = btn.dataset.title;
            if (!title) return;

            modalTitle.textContent = `Цены на «${title}»`;
            modalBody.innerHTML = '<p class="prices-loading">Загружаем актуальные цены...</p>';
            openModal();

            try {
                const r = await fetch('/Home/GetPrices?title=' + encodeURIComponent(title));
                const json = await r.json();

                if (!json.success || !json.data) {
                    modalBody.innerHTML = '<p class="prices-empty">Не удалось найти игру в базе CheapShark.</p>';
                    return;
                }

                const deals = json.data.deals || [];

                if (deals.length === 0) {
                    modalBody.innerHTML = '<p class="prices-empty">Актуальные предложения не найдены.</p>';
                    return;
                }

                let html = '<table class="prices-table">';
                html += '<thead><tr><th>Магазин</th><th>Цена</th><th>Скидка</th><th></th></tr></thead><tbody>';

                for (const d of deals) {
                    html += `<tr>
                        <td>${d.storeName}</td>
                        <td class="prices-price">$${Number(d.price).toFixed(2)}</td>
                        <td>${d.savings ? '-' + Number(d.savings).toFixed(0) + '%' : '—'}</td>
                        <td>${d.dealUrl ? `<a href="${d.dealUrl}" target="_blank" rel="noopener" class="arcade-btn arcade-btn-primary" style="padding:6px 12px;font-size:12px;">Купить</a>` : ''}</td>
                    </tr>`;
                }

                html += '</tbody></table>';
                modalBody.innerHTML = html;

            } catch {
                modalBody.innerHTML = '<p class="prices-empty">Ошибка загрузки. Попробуйте позже.</p>';
            }
        });
    });
})();