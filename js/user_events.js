
(function () {
    'use strict';

    async function sendEvent(productId, eventType) {
        try {
            const response = await fetch('api/event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    event_type: eventType
                })
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Ошибка отправки события');
            }

            return result;
        } catch (error) {
            console.error('Ошибка события:', error);
            alert('Не удалось отправить событие на сервер. Проверьте работу API.');
            return { success: false };
        }
    }

    document.querySelectorAll('.game-action-btn').forEach(button => {
        button.addEventListener('click', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            const id = parseInt(this.dataset.gameId, 10);
            const type = this.dataset.event;

            if (!id || !type) return;

            this.disabled = true;

            const result = await sendEvent(id, type);

            if (result.success) {
                if (type === 'cart') {
                    this.textContent = '✓ В корзине';
                } else if (type === 'purchase') {
                    this.textContent = '✓ Куплено';
                } else if (type === 'view') {
                    this.textContent = '✓ Просмотрено';
                }

                this.classList.add('success');
            }

            this.disabled = false;
        });
    });
})();
