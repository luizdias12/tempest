(function () {
    'use strict';

    var canvasAurora = document.querySelector('.aurora-cortinas');
    var canvasEstrelas = document.querySelector('.aurora-particulas');
    var canvasSolo = document.querySelector('.aurora-solo-canvas');
    var canvasReflexo = document.querySelector('.aurora-reflexo');
    var campoSolo = document.querySelector('.aurora-solo');

    if (!canvasAurora) return;

    var ctxAurora = canvasAurora.getContext('2d');
    var ctxEstrelas = canvasEstrelas ? canvasEstrelas.getContext('2d') : null;
    var ctxSolo = canvasSolo ? canvasSolo.getContext('2d') : null;
    var ctxReflexo = canvasReflexo ? canvasReflexo.getContext('2d') : null;

    if (!ctxAurora) return;

    var reduzirMovimento = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var LAGO = 0.93;
    var REFLEXO = 0.07;

    var largura = 0;
    var altura = 0;
    var linhaLago = 0;
    var dprNitido = Math.min(window.devicePixelRatio || 1, 2);
    var ultimoQuadro = 0;
    var proximoQuadro = 0;
    var estrelas = [];
    var gradientes = [];
    var gradientesReflexo = [];
    var perfis = [];
    var pinheiros = [];
    var rachaduras = [];
    var meteoro = null;
    var proximoMeteoro = 0;

    // Aurora real: corpo esverdeado que desbota para cima e borda inferior luminosa.
    var camadas = [
        { cor: '167, 139, 250', borda: '232, 214, 255', alfa: 0.12, base: 0.49, amp: 0.07, vel: 0.030, fase: 5.3, centro: 0.66, sigma: 0.20, centro2: null, sigma2: 0,    raios: 0.07 },
        { cor: '96, 165, 250',  borda: '200, 228, 255', alfa: 0.16, base: 0.23, amp: 0.09, vel: 0.040, fase: 4.1, centro: 0.76, sigma: 0.24, centro2: 0.38, sigma2: 0.14, raios: 0.10 },
        { cor: '45, 212, 191',  borda: '196, 255, 246', alfa: 0.24, base: 0.31, amp: 0.10, vel: 0.075, fase: 2.4, centro: 0.56, sigma: 0.26, centro2: 0.20, sigma2: 0.16, raios: 0.14 },
        { cor: '74, 222, 128',  borda: '214, 255, 232', alfa: 0.30, base: 0.42, amp: 0.13, vel: 0.050, fase: 0.6, centro: 0.30, sigma: 0.30, centro2: 0.74, sigma2: 0.20, raios: 0.18 }
    ];

    var camadaPrincipal = 3;

    function prepararGradientes() {
        gradientes = [];

        for (var i = 0; i < camadas.length; i++) {
            var camada = camadas[i];
            var p = camada.base;
            var g = ctxAurora.createLinearGradient(0, 0, 0, altura);

            g.addColorStop(0, 'rgba(' + camada.cor + ', 0)');
            g.addColorStop(Math.max(0, p - 0.36), 'rgba(' + camada.cor + ', 0.05)');
            g.addColorStop(Math.max(0, p - 0.22), 'rgba(' + camada.cor + ', 0.20)');
            g.addColorStop(Math.max(0, p - 0.11), 'rgba(' + camada.cor + ', 0.48)');
            g.addColorStop(p, 'rgba(' + camada.cor + ', 0.90)');
            g.addColorStop(Math.min(1, p + 0.06), 'rgba(' + camada.cor + ', 0.40)');
            g.addColorStop(Math.min(1, p + 0.16), 'rgba(' + camada.cor + ', 0)');

            gradientes.push(g);
        }
    }

    function prepararGradientesReflexo() {
        gradientesReflexo = [];

        if (!ctxReflexo || !canvasReflexo) return;

        var h = canvasReflexo.height;

        for (var i = 0; i < camadas.length; i++) {
            var cor = camadas[i].cor;
            var g = ctxReflexo.createLinearGradient(0, 0, 0, h);

            g.addColorStop(0, 'rgba(' + cor + ', 0.85)');
            g.addColorStop(0.35, 'rgba(' + cor + ', 0.30)');
            g.addColorStop(1, 'rgba(' + cor + ', 0)');

            gradientesReflexo.push(g);
        }
    }

    function envoltoria(x, camada, t) {
        var desloc = Math.sin(t * camada.vel * 0.6 + camada.fase) * 0.03 * largura;

        var d1 = (x - (camada.centro * largura + desloc)) / (camada.sigma * largura);
        var v = Math.exp(-0.5 * d1 * d1);

        if (camada.centro2 !== null) {
            var d2 = (x - (camada.centro2 * largura - desloc * 0.8)) / (camada.sigma2 * largura);
            v = Math.max(v, 0.75 * Math.exp(-0.5 * d2 * d2));
        }

        return v;
    }

    function pe(x, camada, t) {
        var dobra =
            0.55 * Math.sin(x * 0.0017 + t * camada.vel + camada.fase) +
            0.28 * Math.sin(x * 0.0046 - t * camada.vel * 1.8 + camada.fase * 1.9) +
            0.17 * Math.sin(x * 0.0112 + t * camada.vel * 2.4 + camada.fase * 3.3);

        return altura * (camada.base + camada.amp * dobra);
    }

    function desenharCortinas(t) {
        ctxAurora.clearRect(0, 0, largura, altura);
        ctxAurora.globalCompositeOperation = 'lighter';
        ctxAurora.lineCap = 'butt';

        var passo = largura < 768 ? 3 : 4;

        for (var c = 0; c < camadas.length; c++) {
            var camada = camadas[c];

            ctxAurora.strokeStyle = gradientes[c];
            ctxAurora.lineWidth = largura < 768 ? 2 : 2.6;

            for (var x = 0; x < largura; x += passo) {
                var env = envoltoria(x, camada, t);

                if (env < 0.02) continue;

                // Raios finos com baixo contraste, para não virar listra de "código de barras".
                var raios = 1 - camada.raios *
                    (0.5 + 0.5 * Math.sin(x * 0.045 + t * 0.8 + camada.fase)) *
                    (0.5 + 0.5 * Math.sin(x * 0.011 - t * 0.3 + camada.fase * 2.1));
                var pulso = 0.85 + 0.15 * Math.sin(t * 0.5 + camada.fase);

                ctxAurora.globalAlpha = Math.min(1, camada.alfa * env * raios * pulso);
                ctxAurora.beginPath();
                ctxAurora.moveTo(x, 0);
                ctxAurora.lineTo(x, pe(x, camada, t));
                ctxAurora.stroke();
            }

            // Fio luminoso acompanhando a borda inferior, traçado em segmentos
            // para sumir junto com o envelope.
            ctxAurora.strokeStyle = 'rgba(' + camada.borda + ', 1)';
            ctxAurora.lineWidth = largura < 768 ? 1.4 : 1.8;

            var passoBorda = passo * 2;

            for (var xb = 0; xb < largura; xb += passoBorda) {
                var envb = envoltoria(xb, camada, t);

                if (envb < 0.06) continue;

                ctxAurora.globalAlpha = Math.min(0.6, envb * camada.alfa * 2);
                ctxAurora.beginPath();
                ctxAurora.moveTo(xb, pe(xb, camada, t));
                ctxAurora.lineTo(xb + passoBorda, pe(xb + passoBorda, camada, t));
                ctxAurora.stroke();
            }
        }

        ctxAurora.globalAlpha = 1;
        ctxAurora.globalCompositeOperation = 'source-over';
    }

    function criarEstrela(inicial) {
        var profundidade = Math.random();

        return {
            x: Math.random() * largura,
            y: inicial ? Math.random() * Math.max(1, linhaLago - 20) : Math.random() * altura * 0.1,
            raio: 0.35 + profundidade * 1.25,
            alfa: 0.14 + profundidade * 0.5,
            fase: Math.random() * Math.PI * 2,
            cintila: 0.5 + Math.random() * 1.4,
            deriva: 0.5 + profundidade * 3.5
        };
    }

    function desenharEstrelas(t, delta) {
        if (!ctxEstrelas) return;

        ctxEstrelas.clearRect(0, 0, largura, altura);
        ctxEstrelas.globalCompositeOperation = 'lighter';

        for (var i = 0; i < estrelas.length; i++) {
            var e = estrelas[i];

            e.y += e.deriva * delta;

            if (e.y > linhaLago - 8) {
                e.y = -4;
                e.x = Math.random() * largura;
            }

            var brilho = e.alfa * (0.55 + 0.45 * Math.sin(e.fase + t * e.cintila));

            ctxEstrelas.beginPath();
            ctxEstrelas.arc(e.x, e.y, e.raio * 3, 0, Math.PI * 2);
            ctxEstrelas.fillStyle = 'rgba(186, 230, 253, ' + (brilho * 0.18).toFixed(3) + ')';
            ctxEstrelas.fill();

            ctxEstrelas.beginPath();
            ctxEstrelas.arc(e.x, e.y, e.raio, 0, Math.PI * 2);
            ctxEstrelas.fillStyle = 'rgba(240, 249, 255, ' + brilho.toFixed(3) + ')';
            ctxEstrelas.fill();
        }

        ctxEstrelas.globalCompositeOperation = 'source-over';
    }

    function criarMeteoro(t) {
        var paraDireita = Math.random() < 0.5;
        var ang = (20 + Math.random() * 20) * Math.PI / 180;
        var x0;

        if (paraDireita) {
            x0 = largura * (0.05 + Math.random() * 0.55);
        } else {
            ang = Math.PI - ang;
            x0 = largura * (0.45 + Math.random() * 0.5);
        }

        meteoro = {
            x0: x0,
            y0: altura * (0.04 + Math.random() * 0.16),
            ang: ang,
            dist: Math.min(largura, altura) * (0.55 + Math.random() * 0.3),
            cauda: 90 + Math.random() * 70,
            inicio: t,
            duracao: 0.9 + Math.random() * 0.5
        };
    }

    function desenharMeteoro(t) {
        if (!ctxEstrelas) return;

        if (!meteoro) {
            if (proximoMeteoro === 0) {
                proximoMeteoro = t + 6 + Math.random() * 6;
            }

            if (t < proximoMeteoro) return;

            criarMeteoro(t);
        }

        var m = meteoro;
        var prog = (t - m.inicio) / m.duracao;

        if (prog >= 1) {
            meteoro = null;
            proximoMeteoro = t + 35 + Math.random() * 45;
            return;
        }

        var viajou = m.dist * prog;
        var x = m.x0 + Math.cos(m.ang) * viajou;
        var y = m.y0 + Math.sin(m.ang) * viajou;
        var cauda = m.cauda * (0.4 + 0.6 * prog);
        var x2 = x - Math.cos(m.ang) * cauda;
        var y2 = y - Math.sin(m.ang) * cauda;
        var alfa = Math.sin(Math.PI * prog);

        ctxEstrelas.globalCompositeOperation = 'lighter';
        ctxEstrelas.lineCap = 'round';

        var g = ctxEstrelas.createLinearGradient(x, y, x2, y2);
        g.addColorStop(0, 'rgba(255, 255, 255, ' + (0.9 * alfa).toFixed(3) + ')');
        g.addColorStop(0.3, 'rgba(190, 230, 255, ' + (0.45 * alfa).toFixed(3) + ')');
        g.addColorStop(1, 'rgba(140, 200, 255, 0)');

        ctxEstrelas.strokeStyle = g;
        ctxEstrelas.lineWidth = 1.8;
        ctxEstrelas.beginPath();
        ctxEstrelas.moveTo(x, y);
        ctxEstrelas.lineTo(x2, y2);
        ctxEstrelas.stroke();

        ctxEstrelas.beginPath();
        ctxEstrelas.arc(x, y, 1.6, 0, Math.PI * 2);
        ctxEstrelas.fillStyle = 'rgba(255, 255, 255, ' + (0.9 * alfa).toFixed(3) + ')';
        ctxEstrelas.fill();

        ctxEstrelas.globalCompositeOperation = 'source-over';
    }

    function desenharReflexo(t) {
        if (!ctxReflexo || !canvasReflexo) return;

        var rw = canvasReflexo.width;
        var rh = canvasReflexo.height;

        ctxReflexo.clearRect(0, 0, rw, rh);
        ctxReflexo.globalCompositeOperation = 'lighter';
        ctxReflexo.lineCap = 'butt';

        var passo = largura < 768 ? 5 : 7;

        for (var c = 0; c < camadas.length; c++) {
            var camada = camadas[c];

            ctxReflexo.strokeStyle = gradientesReflexo[c];
            ctxReflexo.lineWidth = largura < 768 ? 4 : 5;

            for (var x = 0; x < rw; x += passo) {
                // Pequena ondulação horizontal simula a distorção da água.
                var onda = Math.sin(x * 0.028 + t * 1.1 + camada.fase) * 4 +
                           Math.sin(x * 0.007 - t * 0.5) * 6;
                var env = envoltoria(x + onda, camada, t);

                if (env < 0.05) continue;

                var cintila = 0.55 + 0.45 * Math.sin(x * 0.06 + t * 1.7 + camada.fase * 2);

                ctxReflexo.globalAlpha = Math.min(0.7, camada.alfa * env * cintila);
                ctxReflexo.beginPath();
                ctxReflexo.moveTo(x, 0);
                ctxReflexo.lineTo(x, rh);
                ctxReflexo.stroke();
            }
        }

        // Fio de luz colado na linha d'água.
        ctxReflexo.strokeStyle = 'rgba(220, 255, 240, 1)';
        ctxReflexo.lineWidth = 1.4;

        for (var xb = 0; xb < rw; xb += passo) {
            var envb = envoltoria(xb, camadas[camadaPrincipal], t);

            if (envb < 0.08) continue;

            ctxReflexo.globalAlpha = Math.min(0.5, envb * 0.45);
            ctxReflexo.beginPath();
            ctxReflexo.moveTo(xb, 0);
            ctxReflexo.lineTo(xb + passo, 0);
            ctxReflexo.stroke();
        }

        ctxReflexo.globalAlpha = 1;
        ctxReflexo.globalCompositeOperation = 'source-over';
    }

    function alturaDoPerfil(pontos, x) {
        for (var i = 1; i < pontos.length; i++) {
            if (pontos[i].x >= x) {
                var a = pontos[i - 1];
                var b = pontos[i];
                var f = (x - a.x) / (b.x - a.x || 1);
                return a.y + (b.y - a.y) * f;
            }
        }

        return pontos.length ? pontos[pontos.length - 1].y : linhaLago;
    }

    function montarPerfis() {
        var especificacoes = [
            { base: 0.015, amp: 0.019, fase: 1.3, passo: 12 },
            { base: 0.006, amp: 0.012, fase: 4.7, passo: 8 }
        ];

        perfis = [];

        for (var i = 0; i < especificacoes.length; i++) {
            var e = especificacoes[i];
            var pontos = [];

            for (var x = -30; x <= largura + 30; x += e.passo) {
                var onda =
                    0.55 * Math.sin(x * 0.0042 + e.fase) +
                    0.30 * Math.sin(x * 0.0115 + e.fase * 1.9) +
                    0.15 * Math.sin(x * 0.031 + e.fase * 3.3);
                var y = altura * (LAGO - e.base - e.amp * (0.5 + 0.5 * onda));

                pontos.push({ x: x, y: y });
            }

            perfis.push(pontos);
        }

        // Pinheiros apoiados no perfil mais próximo.
        pinheiros = [];
        var quantidade = Math.max(5, Math.round(largura / 220));

        for (var k = 0; k < quantidade; k++) {
            var px = largura * (k + 0.5) / quantidade + (Math.random() - 0.5) * 40;
            var py = alturaDoPerfil(perfis[1], px);

            pinheiros.push({
                x: px,
                y: py + 1,
                altura: 6 + Math.random() * 8,
                largura: 3 + Math.random() * 3
            });
        }

        // Rachaduras finas no gelo.
        rachaduras = [];

        for (var r = 0; r < 4; r++) {
            var x0 = Math.random() * largura;
            var y0 = altura * (LAGO + 0.008 + Math.random() * 0.03);
            var direcao = Math.random() < 0.5 ? -1 : 1;
            var pontosR = [{ x: x0, y: y0 }];

            for (var s = 1; s <= 3; s++) {
                pontosR.push({
                    x: x0 + direcao * s * (24 + Math.random() * 40),
                    y: y0 + s * (1 + Math.random() * 3)
                });
            }

            rachaduras.push(pontosR);
        }
    }

    function preencherPerfil(pontos, cor) {
        if (!pontos.length) return;

        ctxSolo.fillStyle = cor;
        ctxSolo.beginPath();
        ctxSolo.moveTo(pontos[0].x, pontos[0].y);

        for (var i = 1; i < pontos.length; i++) {
            ctxSolo.lineTo(pontos[i].x, pontos[i].y);
        }

        ctxSolo.lineTo(pontos[pontos.length - 1].x, linhaLago);
        ctxSolo.lineTo(pontos[0].x, linhaLago);
        ctxSolo.closePath();
        ctxSolo.fill();

        // Luz da aurora raspando o cume.
        ctxSolo.strokeStyle = 'rgba(165, 235, 235, 0.22)';
        ctxSolo.lineWidth = 1;
        ctxSolo.beginPath();
        ctxSolo.moveTo(pontos[0].x, pontos[0].y);

        for (var j = 1; j < pontos.length; j++) {
            ctxSolo.lineTo(pontos[j].x, pontos[j].y);
        }

        ctxSolo.stroke();
    }

    function desenharSolo() {
        if (!ctxSolo) return;

        ctxSolo.clearRect(0, 0, largura, altura);

        // Véu escuro do lago congelado (leve, para não sumir com o rodapé).
        var agua = ctxSolo.createLinearGradient(0, linhaLago, 0, altura);
        agua.addColorStop(0, 'rgba(3, 10, 20, 0)');
        agua.addColorStop(0.3, 'rgba(3, 10, 20, 0.08)');
        agua.addColorStop(1, 'rgba(2, 7, 15, 0.30)');
        ctxSolo.fillStyle = agua;
        ctxSolo.fillRect(0, linhaLago, largura, altura - linhaLago);

        ctxSolo.strokeStyle = 'rgba(160, 214, 230, 0.10)';
        ctxSolo.lineWidth = 1;

        for (var r = 0; r < rachaduras.length; r++) {
            var pontosR = rachaduras[r];
            ctxSolo.beginPath();
            ctxSolo.moveTo(pontosR[0].x, pontosR[0].y);

            for (var s = 1; s < pontosR.length; s++) {
                ctxSolo.lineTo(pontosR[s].x, pontosR[s].y);
            }

            ctxSolo.stroke();
        }

        preencherPerfil(perfis[0], 'rgba(11, 24, 41, 0.85)');
        preencherPerfil(perfis[1], 'rgba(4, 10, 19, 0.92)');

        ctxSolo.fillStyle = 'rgba(3, 8, 15, 0.95)';

        for (var p = 0; p < pinheiros.length; p++) {
            var arvore = pinheiros[p];
            ctxSolo.beginPath();
            ctxSolo.moveTo(arvore.x, arvore.y - arvore.altura);
            ctxSolo.lineTo(arvore.x - arvore.largura / 2, arvore.y);
            ctxSolo.lineTo(arvore.x + arvore.largura / 2, arvore.y);
            ctxSolo.closePath();
            ctxSolo.fill();
        }

        // Brilho de prata na linha do horizonte.
        var brilho = ctxSolo.createLinearGradient(0, linhaLago - 8, 0, linhaLago + 16);
        brilho.addColorStop(0, 'rgba(150, 220, 220, 0)');
        brilho.addColorStop(0.4, 'rgba(150, 220, 220, 0.14)');
        brilho.addColorStop(1, 'rgba(150, 220, 220, 0)');
        ctxSolo.fillStyle = brilho;
        ctxSolo.fillRect(0, linhaLago - 8, largura, 24);

        ctxSolo.strokeStyle = 'rgba(175, 235, 235, 0.24)';
        ctxSolo.lineWidth = 1;
        ctxSolo.beginPath();
        ctxSolo.moveTo(0, linhaLago);
        ctxSolo.lineTo(largura, linhaLago);
        ctxSolo.stroke();
    }

    function quadro(agora) {
        window.requestAnimationFrame(quadro);

        // ~30 fps bastam para um movimento lento e poupam a máquina.
        if (agora < proximoQuadro) return;
        proximoQuadro = agora + 33;

        if (!ultimoQuadro) ultimoQuadro = agora;
        var delta = Math.min((agora - ultimoQuadro) / 1000, 0.1);
        ultimoQuadro = agora;

        var t = agora / 1000;

        desenharCortinas(t);
        desenharEstrelas(t, delta);
        desenharMeteoro(t);

        if (!campoSolo || campoSolo.classList.contains('visivel')) {
            desenharReflexo(t);
        }
    }

    // O solo só aparece no fim da página (ou quando ela cabe na tela),
    // para não passar por cima de tabelas e paginação durante a rolagem.
    function atualizarVisibilidadeSolo() {
        if (!campoSolo) return;

        var fim = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
        var y = window.scrollY || window.pageYOffset || 0;

        campoSolo.classList.toggle('visivel', fim <= 80 || y >= fim - 80);
    }

    function redimensionar() {
        largura = window.innerWidth;
        altura = window.innerHeight;
        linhaLago = Math.round(altura * LAGO);

        canvasAurora.width = largura;
        canvasAurora.height = altura;

        if (canvasEstrelas && ctxEstrelas) {
            canvasEstrelas.width = Math.floor(largura * dprNitido);
            canvasEstrelas.height = Math.floor(altura * dprNitido);
            canvasEstrelas.style.width = largura + 'px';
            canvasEstrelas.style.height = altura + 'px';
            ctxEstrelas.setTransform(dprNitido, 0, 0, dprNitido, 0, 0);
        }

        if (canvasSolo && ctxSolo) {
            canvasSolo.width = Math.floor(largura * dprNitido);
            canvasSolo.height = Math.floor(altura * dprNitido);
            ctxSolo.setTransform(dprNitido, 0, 0, dprNitido, 0, 0);
        }

        if (canvasReflexo && ctxReflexo) {
            canvasReflexo.width = largura;
            canvasReflexo.height = Math.max(1, Math.round(altura * REFLEXO));
        }

        prepararGradientes();
        prepararGradientesReflexo();
        montarPerfis();
        desenharSolo();

        var total = largura < 768 ? 46 : 96;
        estrelas = [];

        for (var i = 0; i < total; i++) {
            estrelas.push(criarEstrela(true));
        }

        atualizarVisibilidadeSolo();
    }

    var espera;
    window.addEventListener('resize', function () {
        clearTimeout(espera);
        espera = setTimeout(redimensionar, 150);
    });

    window.addEventListener('scroll', atualizarVisibilidadeSolo, { passive: true });
    window.addEventListener('load', atualizarVisibilidadeSolo);

    redimensionar();

    if (reduzirMovimento) {
        desenharCortinas(0);
        desenharEstrelas(0, 0);
        desenharReflexo(0);
        return;
    }

    window.requestAnimationFrame(quadro);
})();
