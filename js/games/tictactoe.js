(function () {
    const boardEl = document.getElementById('tttBoard');
    const statusEl = document.getElementById('tttStatus');
    const btnAI = document.getElementById('modeAI');
    const btnFr = document.getElementById('modeFriend');
    const btnReset = document.getElementById('tttReset');

    const cells = boardEl.querySelectorAll('.ttt-cell');

    let board = Array(9).fill('');
    let current = 'X';
    let vsAI = false;
    let gameOver = false;

    let scoreX = 0, scoreO = 0, scoreD = 0;

    const WINS = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8],
        [0, 3, 6], [1, 4, 7], [2, 5, 8],
        [0, 4, 8], [2, 4, 6]
    ];

    function render() {
        cells.forEach((c, i) => {
            c.textContent = board[i];
            c.classList.toggle('x', board[i] === 'X');
            c.classList.toggle('o', board[i] === 'O');
            c.classList.remove('win');
        });
    }

    function checkWinner() {
        for (const [a, b, c] of WINS) {
            if (board[a] && board[a] === board[b] && board[a] === board[c]) {
                return { winner: board[a], line: [a, b, c] };
            }
        }
        if (board.every(v => v)) return { winner: 'D', line: [] };
        return null;
    }

    function updateStatus(text) {
        statusEl.textContent = text;
    }

    function endGame(res) {
        gameOver = true;
        if (res.winner === 'D') {
            scoreD++;
            document.getElementById('tttScoreDraw').textContent = scoreD;
            updateStatus('Ничья! 🤝');
        } else {
            res.line.forEach(i => cells[i].classList.add('win'));
            if (res.winner === 'X') {
                scoreX++;
                document.getElementById('tttScoreX').textContent = scoreX;
            } else {
                scoreO++;
                document.getElementById('tttScoreO').textContent = scoreO;
            }
            updateStatus(`Победил: ${res.winner} 🎉`);
        }
    }

    function minimax(b, player) {
        const res = (function () {
            for (const [a, x, c] of WINS) {
                if (b[a] && b[a] === b[x] && b[a] === b[c]) return { winner: b[a] };
            }
            if (b.every(v => v)) return { winner: 'D' };
            return null;
        })();

        if (res) {
            if (res.winner === 'O') return { score: 10 };
            if (res.winner === 'X') return { score: -10 };
            return { score: 0 };
        }

        const moves = [];
        for (let i = 0; i < 9; i++) {
            if (!b[i]) {
                const nb = b.slice();
                nb[i] = player;
                const r = minimax(nb, player === 'O' ? 'X' : 'O');
                moves.push({ index: i, score: r.score });
            }
        }

        if (player === 'O') {
            return moves.reduce((a, m) => m.score > a.score ? m : a);
        }
        return moves.reduce((a, m) => m.score < a.score ? m : a);
    }

    function aiMove() {
        if (gameOver) return;
        const m = minimax(board, 'O');
        if (m) place(m.index, 'O');
    }

    function place(i, player) {
        if (board[i] || gameOver) return;

        board[i] = player;
        render();

        const res = checkWinner();
        if (res) { endGame(res); return; }

        current = player === 'X' ? 'O' : 'X';
        updateStatus(`Ход: ${current === 'X' ? '❌' : '⭕'}`);

        if (vsAI && current === 'O') {
            setTimeout(aiMove, 350);
        }
    }

    cells.forEach(c => {
        c.addEventListener('click', () => {
            if (gameOver) return;
            if (vsAI && current === 'O') return;
            place(Number(c.dataset.index), current);
        });
    });

    function reset() {
        board = Array(9).fill('');
        current = 'X';
        gameOver = false;
        render();
        updateStatus('Ход: ❌');
    }

    btnReset.addEventListener('click', reset);

    btnAI.addEventListener('click', () => {
        vsAI = true;
        btnAI.classList.add('active');
        btnFr.classList.remove('active');
        reset();
    });

    btnFr.addEventListener('click', () => {
        vsAI = false;
        btnFr.classList.add('active');
        btnAI.classList.remove('active');
        reset();
    });

    reset();
})();