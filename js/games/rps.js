(function () {
    const playerScoreEl = document.getElementById('rpsPlayerScore');
    const botScoreEl = document.getElementById('rpsBotScore');
    const drawScoreEl = document.getElementById('rpsDrawScore');
    const playerPick = document.getElementById('rpsPlayerPick');
    const botPick = document.getElementById('rpsBotPick');
    const resultEl = document.getElementById('rpsResult');
    const btnReset = document.getElementById('rpsReset');
    const btns = document.querySelectorAll('.rps-btn');

    const ICONS = { rock: '✊', paper: '✋', scissors: '✌' };
    const BEATS = { rock: 'scissors', paper: 'rock', scissors: 'paper' };

    let pScore = 0, bScore = 0, dScore = 0;
    let locked = false;

    function refresh() {
        playerScoreEl.textContent = pScore;
        botScoreEl.textContent = bScore;
        drawScoreEl.textContent = dScore;
    }

    function play(playerMove) {
        if (locked) return;
        locked = true;

        const moves = ['rock', 'paper', 'scissors'];
        const botMove = moves[Math.floor(Math.random() * 3)];

        let spins = 0;
        const spinInterval = setInterval(() => {
            const r1 = moves[Math.floor(Math.random() * 3)];
            const r2 = moves[Math.floor(Math.random() * 3)];
            playerPick.textContent = ICONS[r1];
            botPick.textContent = ICONS[r2];
            if (++spins > 8) {
                clearInterval(spinInterval);
                finish(playerMove, botMove);
            }
        }, 60);
    }

    function finish(playerMove, botMove) {
        playerPick.textContent = ICONS[playerMove];
        botPick.textContent = ICONS[botMove];

        if (playerMove === botMove) {
            dScore++;
            resultEl.textContent = 'Ничья 🤝';
            resultEl.className = 'rps-result draw';
        } else if (BEATS[playerMove] === botMove) {
            pScore++;
            resultEl.textContent = 'Вы победили! 🎉';
            resultEl.className = 'rps-result win';
        } else {
            bScore++;
            resultEl.textContent = 'Компьютер победил 🤖';
            resultEl.className = 'rps-result lose';
        }

        refresh();
        locked = false;
    }

    btns.forEach(b => b.addEventListener('click', () => play(b.dataset.move)));

    btnReset.addEventListener('click', () => {
        pScore = bScore = dScore = 0;
        playerPick.textContent = '❔';
        botPick.textContent = '❔';
        resultEl.textContent = 'Выбери свой ход';
        resultEl.className = 'rps-result';
        refresh();
    });
})();