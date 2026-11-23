(function () {
    const boardEl = document.getElementById('chessBoard');
    const statusEl = document.getElementById('chessStatus');
    const thinkingEl = document.getElementById('chessThinking');
    const btnReset = document.getElementById('chessReset');
    const btnUndo = document.getElementById('chessUndo');
    const btnFriend = document.getElementById('modeFriend');
    const btnEasy = document.getElementById('modeEasy');
    const btnMedium = document.getElementById('modeMedium');
    const btnHard = document.getElementById('modeHard');

    const game = new Chess();
    let selected = null;
    let legalForSelected = [];
    let botDepth = 0;            // 0 = игра с другом
    let botThinking = false;

    const PIECES = {
        'p': '♟', 'r': '♜', 'n': '♞', 'b': '♝', 'q': '♛', 'k': '♚',
        'P': '♙', 'R': '♖', 'N': '♘', 'B': '♗', 'Q': '♕', 'K': '♔'
    };

    const FILES = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];

    // Ценность фигур (для оценки позиции)
    const VALUES = { p: 100, n: 320, b: 330, r: 500, q: 900, k: 20000 };

    // ---------- Рендер доски ----------
    function buildBoard() {
        boardEl.innerHTML = '';
        for (let rank = 8; rank >= 1; rank--) {
            for (let f = 0; f < 8; f++) {
                const square = FILES[f] + rank;
                const isLight = (f + rank) % 2 === 0;
                const div = document.createElement('div');
                div.className = 'chess-square ' + (isLight ? 'light' : 'dark');
                div.dataset.square = square;
                boardEl.appendChild(div);
            }
        }
    }

    function render() {
        boardEl.querySelectorAll('.chess-square').forEach(sq => {
            const s = sq.dataset.square;
            const piece = game.get(s);
            sq.textContent = piece
                ? PIECES[piece.color === 'w' ? piece.type.toUpperCase() : piece.type]
                : '';
            sq.classList.remove('selected', 'legal', 'check');
            if (selected === s) sq.classList.add('selected');
            if (legalForSelected.includes(s)) sq.classList.add('legal');
        });

        if (game.in_check()) {
            const turn = game.turn();
            for (let rank = 8; rank >= 1; rank--) {
                for (const f of FILES) {
                    const s = f + rank;
                    const p = game.get(s);
                    if (p && p.type === 'k' && p.color === turn) {
                        boardEl.querySelector(`[data-square="${s}"]`).classList.add('check');
                    }
                }
            }
        }

        updateStatus();
    }

    function updateStatus() {
        if (botThinking) {
            statusEl.textContent = 'Бот думает...';
            return;
        }
        if (game.in_checkmate()) {
            statusEl.textContent = `Мат! Победили ${game.turn() === 'w' ? 'чёрные' : 'белые'} 🏆`;
        } else if (game.in_stalemate()) {
            statusEl.textContent = 'Пат — ничья';
        } else if (game.in_draw()) {
            statusEl.textContent = 'Ничья';
        } else if (game.in_check()) {
            statusEl.textContent = `Шах! Ход: ${game.turn() === 'w' ? 'белые' : 'чёрные'}`;
        } else {
            statusEl.textContent = `Ход: ${game.turn() === 'w' ? 'белые' : 'чёрные'}`;
        }
    }

    // ---------- ИИ ----------

    // Оценка позиции с точки зрения белых.
    // > 0 — хорошо для белых, < 0 — хорошо для чёрных.
    function evaluateBoard(g) {
        let score = 0;
        const board = g.board(); // массив 8x8

        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const piece = board[r][c];
                if (!piece) continue;

                const val = VALUES[piece.type];

                // Бонус за центр (простая эвристика)
                let positional = 0;
                if (piece.type !== 'k') {
                    const centerDist =
                        Math.abs(r - 3.5) + Math.abs(c - 3.5);
                    positional = (7 - centerDist) * 2;
                }

                if (piece.color === 'w') {
                    score += val + positional;
                } else {
                    score -= val + positional;
                }
            }
        }
        return score;
    }

    // Минимакс с alpha-beta
    // depth — сколько полуходов ещё смотреть
    // alpha, beta — границы отсечения
    // maximizing — сейчас ход белых?
    function minimax(g, depth, alpha, beta, maximizing) {
        if (depth === 0 || g.game_over()) {
            return evaluateBoard(g);
        }

        const moves = g.moves({ verbose: true });

        // Сортируем: сначала взятия (это сильно ускоряет отсечение)
        moves.sort((a, b) => {
            const aCap = a.captured ? VALUES[a.captured] : 0;
            const bCap = b.captured ? VALUES[b.captured] : 0;
            return bCap - aCap;
        });

        if (maximizing) {
            let best = -Infinity;
            for (const m of moves) {
                g.move(m);
                const val = minimax(g, depth - 1, alpha, beta, false);
                g.undo();
                if (val > best) best = val;
                if (best > alpha) alpha = best;
                if (beta <= alpha) break; // отсечение
            }
            return best;
        } else {
            let best = Infinity;
            for (const m of moves) {
                g.move(m);
                const val = minimax(g, depth - 1, alpha, beta, true);
                g.undo();
                if (val < best) best = val;
                if (best < beta) beta = best;
                if (beta <= alpha) break; // отсечение
            }
            return best;
        }
    }

    // Возвращает лучший ход для бота
    function getBestMove(g, depth) {
        const moves = g.moves({ verbose: true });
        if (moves.length === 0) return null;

        // Случайный ход при глубине 1 — чтобы "лёгкий" бот не был предсказуемым
        if (depth === 1 && Math.random() < 0.3) {
            return moves[Math.floor(Math.random() * moves.length)];
        }

        moves.sort((a, b) => {
            const aCap = a.captured ? VALUES[a.captured] : 0;
            const bCap = b.captured ? VALUES[b.captured] : 0;
            return bCap - aCap;
        });

        const isWhite = g.turn() === 'w';
        let bestMove = moves[0];
        let bestScore = isWhite ? -Infinity : Infinity;

        for (const m of moves) {
            g.move(m);
            const score = minimax(g, depth - 1, -Infinity, Infinity, !isWhite);
            g.undo();

            if (isWhite ? score > bestScore : score < bestScore) {
                bestScore = score;
                bestMove = m;
            }
        }
        return bestMove;
    }

    // Бот делает ход (асинхронно, чтобы UI не подвисал)
    function botMove() {
        if (botDepth === 0) return;
        if (game.game_over()) return;

        botThinking = true;
        thinkingEl.style.display = 'block';
        updateStatus();

        setTimeout(() => {
            const move = getBestMove(game, botDepth);
            if (move) {
                game.move(move);
            }
            botThinking = false;
            thinkingEl.style.display = 'none';
            selected = null;
            legalForSelected = [];
            render();

            // Если после хода бота снова ход бота (не бывает, но на всякий)
            // и если игра не закончилась — ничего не делаем, ждём игрока
        }, 30);
    }

    // ---------- Клики ----------
    boardEl.addEventListener('click', (e) => {
        if (botThinking) return;
        const sq = e.target.closest('.chess-square');
        if (!sq) return;
        if (game.game_over()) return;

        // Ход бота — если сейчас его очередь, клики блокируем
        if (botDepth > 0 && game.turn() === 'b') return;

        const square = sq.dataset.square;
        const piece = game.get(square);

        if (!selected) {
            if (piece && piece.color === game.turn()) {
                selected = square;
                legalForSelected = game.moves({ square, verbose: true }).map(m => m.to);
                render();
            }
            return;
        }

        if (square === selected) {
            selected = null;
            legalForSelected = [];
            render();
            return;
        }

        if (piece && piece.color === game.turn()) {
            selected = square;
            legalForSelected = game.moves({ square, verbose: true }).map(m => m.to);
            render();
            return;
        }

        const move = game.move({
            from: selected,
            to: square,
            promotion: 'q'
        });

        selected = null;
        legalForSelected = [];
        render();

        if (move && botDepth > 0 && !game.game_over()) {
            // После хода игрока — ход бота
            botMove();
        }
    });

    // ---------- Кнопки ----------
    btnReset.addEventListener('click', () => {
        game.reset();
        selected = null;
        legalForSelected = [];
        botThinking = false;
        thinkingEl.style.display = 'none';
        render();
    });

    btnUndo.addEventListener('click', () => {
        if (botThinking) return;
        // Отменяем 2 хода (наш + ответ бота) или 1, если играем с другом
        game.undo();
        if (botDepth > 0) game.undo();
        selected = null;
        legalForSelected = [];
        render();
    });

    function setMode(depth, activeBtn) {
        botDepth = depth;
        [btnFriend, btnEasy, btnMedium, btnHard].forEach(b => b.classList.remove('active'));
        activeBtn.classList.add('active');

        // Новая партия при смене режима
        game.reset();
        selected = null;
        legalForSelected = [];
        botThinking = false;
        thinkingEl.style.display = 'none';
        render();

        // Если бот играет чёрными, а сейчас его очередь (после сброса — нет,
        // но на всякий случай)
        if (botDepth > 0 && game.turn() === 'b') {
            botMove();
        }
    }

    btnFriend.addEventListener('click', () => setMode(0, btnFriend));
    btnEasy.addEventListener('click', () => setMode(1, btnEasy));
    btnMedium.addEventListener('click', () => setMode(2, btnMedium));
    btnHard.addEventListener('click', () => setMode(3, btnHard));

    // ---------- Старт ----------
    buildBoard();
    render();
})();