(function () {
    // ===== ИСТОРИЯ ПОИСКА =====
    const searchInput = document.getElementById('searchInput');
    const searchBox = document.getElementById('searchHistoryBox');
    const searchForm = document.getElementById('searchForm');
    const HKEY = 'searchHistory';

    function getHistory() {
        try { return JSON.parse(localStorage.getItem(HKEY) || '[]'); }
        catch { return []; }
    }
    function pushHistory(q) {
        q = (q || '').trim();
        if (!q) return;
        let h = getHistory().filter(x => x.toLowerCase() !== q.toLowerCase());
        h.unshift(q);
        h = h.slice(0, 10);
        localStorage.setItem(HKEY, JSON.stringify(h));
    }
    function renderHistory() {
        const h = getHistory();
        if (!searchBox) return;
        if (h.length === 0) { searchBox.classList.add('hidden'); return; }
        searchBox.innerHTML =
            '<div class="history-title">Последние запросы:</div>' +
            h.map(q => `<div class="history-item" data-q="${q.replace(/"/g, '&quot;')}">${q}</div>`).join('') +
            '<div class="history-clear" id="historyClear">Очистить историю</div>';
        searchBox.classList.remove('hidden');
        searchBox.querySelectorAll('.history-item').forEach(el => {
            el.addEventListener('click', () => {
                searchInput.value = el.dataset.q;
                searchBox.classList.add('hidden');
                applyFilters();
            });
        });
        document.getElementById('historyClear')?.addEventListener('click', () => {
            localStorage.removeItem(HKEY);
            searchBox.classList.add('hidden');
        });
    }
    if (searchInput && searchBox && searchForm) {
        searchInput.addEventListener('focus', renderHistory);
        searchInput.addEventListener('input', () => { renderHistory(); applyFilters(); });
        document.addEventListener('click', (e) => {
            if (!searchBox.contains(e.target) && e.target !== searchInput) {
                searchBox.classList.add('hidden');
            }
        });
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            pushHistory(searchInput.value);
            searchBox.classList.add('hidden');
            applyFilters();
        });
    }

    // ===== ФИЛЬТРАЦИЯ =====
    const filtersForm = document.getElementById('filtersForm');
    const gamesGrid = document.getElementById('gamesGrid');
    const gamesCountEl = document.getElementById('gamesCount');
    const noGamesEl = document.getElementById('noGames');

    function getChecked(name) {
        return Array.from(document.querySelectorAll(`input[name="${name}"]:checked`)).map(i => i.value);
    }

    function applyFilters() {
        if (!gamesGrid) return;
        const searchText = (searchInput?.value || '').toLowerCase().trim();
        const genres = getChecked('genre');
        const gameTypes = getChecked('gameType');
        const platforms = getChecked('platform');
        const ages = getChecked('ageRating');

        let visible = 0;
        gamesGrid.querySelectorAll('.game-card-wrapper').forEach(card => {
            const name = (card.dataset.name || '').toLowerCase();
            const genre = card.dataset.genre || '';
            const gameType = card.dataset.gametype || '';
            const cardPlatforms = (card.dataset.platforms || '').split(',');
            const age = card.dataset.agerating || '';

            let ok = true;
            if (searchText && !name.includes(searchText)) ok = false;
            if (genres.length && !genres.includes(genre)) ok = false;
            if (gameTypes.length && !gameTypes.includes(gameType)) ok = false;
            if (platforms.length && !platforms.some(p => cardPlatforms.includes(p))) ok = false;
            if (ages.length && !ages.includes(age)) ok = false;

            card.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });

        if (gamesCountEl) gamesCountEl.textContent = visible;
        if (noGamesEl) noGamesEl.classList.toggle('hidden', visible !== 0);
    }

    filtersForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        applyFilters();
    });
    filtersForm?.addEventListener('change', applyFilters);

    // ===== СОРТИРОВКА =====
    document.querySelectorAll('.sort-buttons a[data-sort]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sort = link.dataset.sort;
            const cards = Array.from(gamesGrid.querySelectorAll('.game-card-wrapper'));
            cards.sort((a, b) => {
                if (sort === 'rating') {
                    return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
                }
                if (sort === 'name') {
                    return a.dataset.name.localeCompare(b.dataset.name, 'ru');
                }
                return 0;
            });
            cards.forEach(c => gamesGrid.appendChild(c));
        });
    });

    // ===== СРАВНЕНИЕ =====
    const CKEY = 'compareList';
    const comparePanel = document.getElementById('comparePanel');
    const compareCountEl = document.getElementById('compareCount');
    const compareGoBtn = document.getElementById('compareGoBtn');
    const compareClearBtn = document.getElementById('compareClearBtn');

    function getCompare() {
        try { return JSON.parse(localStorage.getItem(CKEY) || '[]'); }
        catch { return []; }
    }
    function setCompare(arr) {
        localStorage.setItem(CKEY, JSON.stringify(arr));
        updateCompareUI();
    }
    function updateCompareUI() {
        const arr = getCompare();
        if (!comparePanel) return;
        if (arr.length === 0) {
            comparePanel.classList.add('hidden');
        } else {
            comparePanel.classList.remove('hidden');
            compareCountEl.textContent = arr.length;
            if (compareGoBtn) compareGoBtn.href = '#';
        }
        document.querySelectorAll('.compare-add-btn').forEach(btn => {
            const id = Number(btn.dataset.gameId);
            btn.classList.toggle('added', arr.includes(id));
        });
    }
    document.querySelectorAll('.compare-add-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const id = Number(btn.dataset.gameId);
            let arr = getCompare();
            if (arr.includes(id)) {
                arr = arr.filter(x => x !== id);
            } else {
                if (arr.length >= 3) {
                    alert('Можно сравнивать максимум 3 игры');
                    return;
                }
                arr.push(id);
            }
            setCompare(arr);
        });
    });
    compareClearBtn?.addEventListener('click', () => setCompare([]));
    compareGoBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const arr = getCompare();
        if (arr.length > 0) alert('Выбрано игр: ' + arr.length + '. Функция сравнения в разработке.');
    });

    updateCompareUI();
    applyFilters();
})();