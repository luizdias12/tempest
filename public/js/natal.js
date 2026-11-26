(function() {
    'use strict';

    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    function criarNeve() {
        var container = document.querySelector('.snowflakes');

        if (!container) return;

        var quantidade = window.innerWidth < 768 ? 40 : 90;

        for (var i = 0; i < quantidade; i++) {
            var floco = document.createElement('div');
            floco.className = 'snowflake';

            var tamanho = Math.random() * 6 + 2;
            var duracao = Math.random() * 12 + 8;
            var atraso = Math.random() * 10;
            var balanco = Math.random() * 60 - 30;

            floco.style.cssText =
                'left: ' + (Math.random() * 100) + 'vw;' +
                'width: ' + tamanho + 'px;' +
                'height: ' + tamanho + 'px;' +
                'opacity: ' + (Math.random() * 0.6 + 0.4) + ';' +
                'animation-duration: ' + duracao + 's;' +
                'animation-delay: -' + atraso + 's;' +
                '--sway: ' + balanco + 'px;' +
                '--sway-duracao: ' + (duracao * 1.5) + 's;';

            var inner = document.createElement('div');
            inner.className = 'inner';
            floco.appendChild(inner);

            container.appendChild(floco);
        }
    }

    function corNeonAleatoria() {
        var cores = [
            '#39ff14', '#ff073a', '#0ff0fc', '#f4f804', '#ff61f6',
            '#4dffc3', '#fc28fc', '#ff9d00', '#a6ff00', '#00fff7'
        ];

        return cores[Math.floor(Math.random() * cores.length)];
    }

    function criarLuzes() {
        var container = document.querySelector('.lights');

        if (!container) return;

        function posicionar() {
            container.innerHTML = '';

            for (var i = 0; i < 30; i++) {
                var cor = corNeonAleatoria();
                var tamanho = Math.floor(Math.random() * 8) + 4;

                var bulb = document.createElement('div');
                bulb.className = 'bulb';
                bulb.style.top = (Math.floor(Math.random() * 96) + 2) + 'vh';
                bulb.style.left = (Math.floor(Math.random() * 96) + 2) + 'vw';
                bulb.style.width = tamanho + 'px';
                bulb.style.height = tamanho + 'px';
                bulb.style.backgroundColor = cor;
                bulb.style.boxShadow =
                    '0 0 5px #fff, inset 0 0 5px #fff, ' +
                    '0 0 20px ' + cor + ', inset 0 0 20px ' + cor + ', ' +
                    '0 0 40px ' + cor + ', inset 0 0 40px ' + cor;

                container.appendChild(bulb);
            }
        }

        posicionar();
        setInterval(posicionar, 2000);
    }

    function criarPapaiNoelVoador() {
        if (!window.NATAL_SANTA) return;

        var duracao = 13;

        var noel = document.createElement('img');
        noel.src = window.NATAL_SANTA;
        noel.alt = '';
        noel.className = 'natal-santa';
        document.body.appendChild(noel);

        var texto = document.createElement('div');
        texto.className = 'natal-ho';
        texto.textContent = 'Ho ho ho!';
        document.body.appendChild(texto);

        function voar() {
            // reinicia a animação do zero a cada passagem
            noel.style.animation = 'none';
            texto.style.animation = 'none';
            void noel.offsetHeight;

            noel.style.animation = 'natal-voar ' + duracao + 's linear forwards';
            texto.style.animation = 'natal-hohoho ' + duracao + 's ease-out forwards';

            var proximo = (Math.random() * (290 - 120) + 120) * 1000;
            setTimeout(voar, proximo);
        }

        setTimeout(voar, Math.random() * 15000 + 5000);
    }

    criarNeve();
    criarLuzes();
    criarPapaiNoelVoador();
})();
