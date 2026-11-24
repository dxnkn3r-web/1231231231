<?php include __DIR__ . '/includes/header.php'; ?>

<h1>✨ Подберите растение</h1>
<p style="margin-bottom:20px; color:#666;">Ответьте на 4 вопроса — мы подберём идеальные растения</p>

<div class="form-box">
    <form id="quiz-form">
        <div class="progress-bar">
            <div class="progress-fill" style="width:25%"></div>
        </div>

        <label>1. Освещённость участка:</label>
        <select name="light" required>
            <option value="Солнце">☀️ Солнце (целый день)</option>
            <option value="Полутень">⛅ Полутень</option>
            <option value="Тень">🌑 Тень</option>
        </select>

        <label>2. Тип почвы:</label>
        <select name="soil" required>
            <option value="Чернозем">🟤 Чернозём</option>
            <option value="Глинистая">🟫 Глинистая</option>
            <option value="Песчаная">🟨 Песчаная</option>
        </select>

        <label>3. Влажность:</label>
        <select name="moisture" required>
            <option value="Влажная">💧 Любит влагу</option>
            <option value="Засухоустойчивая">🌵 Засухоустойчивое</option>
        </select>

        <label>4. Морозостойкость:</label>
        <select name="frost" required>
            <option value="Высокая">❄️ Высокая</option>
            <option value="Средняя">🌡️ Средняя</option>
        </select>

        <button type="submit" class="btn-primary" style="width:100%; margin-top:15px;">
            🔍 Подобрать растения
        </button>
    </form>
</div>

<!-- Контейнер для результатов -->
<div id="results-container" style="margin-top:40px; display:none;">
    <!-- Красивый заголовок над сеткой -->
    <div id="results-header" style="background:#fff; padding:15px 20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05); border-left:5px solid #2e7d32; margin-bottom:20px;">
        <h2 id="results-title" style="margin:0; color:#2e7d32; font-size:1.4rem;">Мы нашли для вас:</h2>
    </div>

    <!-- Сама сетка с товарами -->
    <div id="results" class="grid" style="margin-top:0;"></div>
</div>

<script>
document.getElementById('quiz-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));

    const btn = e.target.querySelector('button');
    btn.textContent = '⏳ Подбираем...';
    btn.disabled = true;

    try {
        const res = await fetch('/api/recommend.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const products = await res.json();

        btn.textContent = '🔍 Подобрать растения';
        btn.disabled = false;

        // Показываем контейнер с результатами
        document.getElementById('results-container').style.display = 'block';

        // Обновляем заголовок
        const titleEl = document.getElementById('results-title');
        if (products.length === 0) {
            titleEl.innerHTML = '😔 К сожалению, точных совпадений нет. Попробуйте изменить параметры.';
            document.getElementById('results').innerHTML = '';
            return;
        } else {
            titleEl.innerHTML = `✨ Мы подобрали для вас <b>${products.length}</b> идеальных растений:`;
        }

        const resultsDiv = document.getElementById('results');
        resultsDiv.innerHTML = '';

        products.forEach(p => {
            // Проверяем наличие, чтобы правильно вывести кнопку
            let stockHtml = '';
            let btnHtml = '';

            if (p.stock_quantity > 0) {
                stockHtml = `<div style="font-size: 0.85rem; color: #2e7d32; margin-bottom: 10px;">● В наличии: ${p.stock_quantity} шт.</div>`;
                btnHtml = `<button onclick="addToCart(${p.id}, '${p.name.replace(/'/g, "\\'")}', ${p.price})">В корзину</button>`;
            } else {
                stockHtml = `<div style="font-size: 0.85rem; color: #c62828; margin-bottom: 10px;">● Нет в наличии</div>`;
                btnHtml = `<button disabled style="background:#ccc; cursor:not-allowed;">Нет в наличии</button>`;
            }

            resultsDiv.innerHTML += `
                <div class="card">
                    <a href="/product.php?id=${p.id}" style="display: block; overflow: hidden;">
                        <img src="${p.image_url}" alt="${p.name}" loading="lazy">
                    </a>
                    <div class="card-body">
                        <a href="/product.php?id=${p.id}" style="text-decoration:none; color:inherit;">
                            <h3>${p.name}</h3>
                        </a>
                        <p>${p.description.substring(0, 60)}...</p>
                        
                        <!-- Красивая цена и цена за штуку -->
                        <div style="margin-bottom: 5px;">
                            <span class="price" style="font-size: 1.3rem;">${Math.round(p.price).toLocaleString('ru-RU')} ₽</span>
                            <span style="font-size: 0.8rem; color: #888;">/ шт.</span>
                        </div>
                        
                        <!-- Бейдж совпадения -->
                        <div style="background: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; display: inline-block; margin-bottom: 10px;">
                            ✅ Совпадение: ${p.match_score}%
                        </div>

                        <!-- Наличие -->
                        ${stockHtml}

                        <!-- Кнопка в корзину -->
                        ${btnHtml}
                    </div>
                </div>
            `;
        });
    } catch (err) {
        alert('Ошибка загрузки. Проверьте соединение с сервером.');
        btn.textContent = '🔍 Подобрать растения';
        btn.disabled = false;
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>