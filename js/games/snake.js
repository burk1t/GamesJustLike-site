(function () {
    const canvas = document.getElementById('snakeCanvas');
    const ctx = canvas.getContext('2d');
    const scoreEl = document.getElementById('snakeScore');
    const bestEl = document.getElementById('snakeBest');
    const overlay = document.getElementById('snakeOverlay');
    const overText = document.getElementById('snakeOverText');
    const btnRestart = document.getElementById('snakeRestart');

    const SIZE = 20;
    const CELL = canvas.width / SIZE;

    let snake, dir, nextDir, food, score, speed, timer, paused, over;

    let best = Number(localStorage.getItem('snakeBest') || 0);
    bestEl.textContent = best;

    function reset() {
        snake = [{ x: 10, y: 10 }, { x: 9, y: 10 }, { x: 8, y: 10 }];
        dir = { x: 1, y: 0 };
        nextDir = dir;
        score = 0;
        speed = 110;
        paused = false;
        over = false;

        scoreEl.textContent = 0;
        overlay.classList.add('hidden');

        placeFood();
        clearInterval(timer);
        timer = setInterval(tick, speed);
    }

    function placeFood() {
        while (true) {
            const f = {
                x: Math.floor(Math.random() * SIZE),
                y: Math.floor(Math.random() * SIZE)
            };
            if (!snake.some(s => s.x === f.x && s.y === f.y)) {
                food = f;
                return;
            }
        }
    }

    function tick() {
        if (paused || over) return;

        dir = nextDir;
        const head = { x: snake[0].x + dir.x, y: snake[0].y + dir.y };

        if (head.x < 0 || head.y < 0 || head.x >= SIZE || head.y >= SIZE) {
            return end();
        }
        if (snake.some(s => s.x === head.x && s.y === head.y)) {
            return end();
        }

        snake.unshift(head);

        if (head.x === food.x && head.y === food.y) {
            score++;
            scoreEl.textContent = score;
            if (score > best) {
                best = score;
                bestEl.textContent = best;
                localStorage.setItem('snakeBest', best);
            }
            placeFood();
            if (speed > 60) {
                speed -= 3;
                clearInterval(timer);
                timer = setInterval(tick, speed);
            }
        } else {
            snake.pop();
        }

        draw();
    }

    function draw() {
        ctx.fillStyle = '#0b0f14';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.strokeStyle = 'rgba(255,255,255,0.03)';
        for (let i = 0; i <= SIZE; i++) {
            ctx.beginPath();
            ctx.moveTo(i * CELL, 0); ctx.lineTo(i * CELL, canvas.height);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(0, i * CELL); ctx.lineTo(canvas.width, i * CELL);
            ctx.stroke();
        }

        ctx.fillStyle = '#ff5f6d';
        ctx.beginPath();
        ctx.arc(food.x * CELL + CELL / 2, food.y * CELL + CELL / 2, CELL / 2 - 2, 0, Math.PI * 2);
        ctx.fill();

        snake.forEach((s, i) => {
            const isHead = i === 0;
            ctx.fillStyle = isHead ? '#7fa5ff' : '#3f6df6';
            ctx.fillRect(s.x * CELL + 1, s.y * CELL + 1, CELL - 2, CELL - 2);
        });
    }

    function end() {
        over = true;
        clearInterval(timer);
        overText.textContent = `Игра окончена. Счёт: ${score}`;
        overlay.classList.remove('hidden');
    }

    document.addEventListener('keydown', (e) => {
        const k = e.key.toLowerCase();

        if (k === ' ' || e.code === 'Space') {
            e.preventDefault();
            paused = !paused;
            return;
        }

        const dirs = {
            arrowup: { x: 0, y: -1 },
            arrowdown: { x: 0, y: 1 },
            arrowleft: { x: -1, y: 0 },
            arrowright: { x: 1, y: 0 },
            w: { x: 0, y: -1 }, s: { x: 0, y: 1 },
            a: { x: -1, y: 0 }, d: { x: 1, y: 0 }
        };

        const nd = dirs[k];
        if (!nd) return;

        if (nd.x === -dir.x && nd.y === -dir.y) return;

        nextDir = nd;
        e.preventDefault();
    });

    btnRestart.addEventListener('click', reset);

    reset();
    draw();
})();