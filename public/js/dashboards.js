/**
 * Dashboard de Helpdesk: monta os 4 gráficos a partir do payload JSON
 * embutido na view (window.DASHBOARD). Sem Ajax — os dados vêm do servidor
 * já agregados, e a página inteira recarrega ao trocar o período.
 */
(function () {
    'use strict';

    var dados = window.DASHBOARD;

    if (!dados) {
        return;
    }

    var CORES_BUCKETS = ['#9aa4b2', '#2f6feb', '#d98324', '#3f9c6d'];

    function semGrafico(id, mensagem) {
        var el = document.getElementById(id);

        if (!el) {
            return;
        }

        var aviso = document.createElement('p');
        aviso.className = 'historico-empty';
        aviso.textContent = mensagem;
        el.parentNode.replaceChild(aviso, el);
    }

    if (typeof Chart === 'undefined') {
        ['dashSerie', 'dashBuckets', 'dashGrupos', 'dashResponsaveis'].forEach(function (id) {
            semGrafico(id, 'Gráfico indisponível: a biblioteca Chart.js não carregou.');
        });
        return;
    }

    Chart.defaults.color = '#cbd5e1';
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.plugins.legend.display = false;

    var grade = 'rgba(255, 255, 255, 0.08)';

    function rotuloDia(iso) {
        var partes = iso.split('-');
        return partes[2] + '/' + partes[1];
    }

    /* ---------------- Abertos e resolvidos por dia ---------------- */

    var serie = dados.serie || [];

    if (serie.length) {
        new Chart(document.getElementById('dashSerie'), {
            type: 'line',
            data: {
                labels: serie.map(function (item) {
                    return rotuloDia(item.data);
                }),
                datasets: [
                    {
                        label: 'Abertos',
                        data: serie.map(function (item) {
                            return item.abertos;
                        }),
                        borderColor: '#2f6feb',
                        backgroundColor: 'rgba(47, 111, 235, 0.18)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        borderWidth: 2
                    },
                    {
                        label: 'Resolvidos',
                        data: serie.map(function (item) {
                            return item.resolvidos;
                        }),
                        borderColor: '#3f9c6d',
                        backgroundColor: 'rgba(63, 156, 109, 0.18)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: true, labels: { boxWidth: 12, usePointStyle: true } },
                    tooltip: { callbacks: { label: function (ctx) { return ctx.dataset.label + ': ' + ctx.parsed.y; } } }
                },
                scales: {
                    x: { grid: { color: grade }, ticks: { maxTicksLimit: 10, autoSkip: true } },
                    y: { beginAtZero: true, grid: { color: grade }, ticks: { precision: 0 } }
                }
            }
        });
    } else {
        semGrafico('dashSerie', 'Sem movimento no período.');
    }

    /* ---------------- Fase dos chamados do período ---------------- */

    var b = dados.buckets || {};
    var fatias = [
        { rotulo: 'Não iniciado', valor: b.nao_iniciados || 0 },
        { rotulo: 'Em atendimento', valor: b.em_atendimento || 0 },
        { rotulo: 'Aguardando terceiros', valor: b.aguardando_terceiros || 0 },
        { rotulo: 'Encerrado', valor: b.encerrados || 0 }
    ];

    var canvasBuckets = document.getElementById('dashBuckets');

    if (canvasBuckets) {
        if (fatias.every(function (f) { return f.valor === 0; })) {
            semGrafico('dashBuckets', 'Nenhum chamado aberto no período.');
        } else {
            new Chart(canvasBuckets, {
                type: 'doughnut',
                data: {
                    labels: fatias.map(function (f) { return f.rotulo; }),
                    datasets: [{
                        data: fatias.map(function (f) { return f.valor; }),
                        backgroundColor: CORES_BUCKETS,
                        borderColor: 'rgba(15, 23, 42, 0.9)',
                        borderWidth: 2
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    var total = ctx.dataset.data.reduce(function (a, v) { return a + v; }, 0);
                                    var pct = total ? Math.round((ctx.parsed / total) * 100) : 0;
                                    return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    /* ---------------- Barras horizontais (fila atual) ---------------- */

    function barraHorizontal(id, itens, rotulo, cor) {
        var canvas = document.getElementById(id);

        if (!canvas) {
            return;
        }

        if (!itens || !itens.length) {
            semGrafico(id, 'Nenhum chamado aberto.');
            return;
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: itens.map(function (item) { return item[rotulo]; }),
                datasets: [{
                    label: 'Chamados',
                    data: itens.map(function (item) { return item.total; }),
                    backgroundColor: cor,
                    borderRadius: 6,
                    maxBarThickness: 26
                }]
            },
            options: {
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    tooltip: { callbacks: { label: function (ctx) { return ctx.parsed.x + ' chamado(s)'; } } }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: grade }, ticks: { precision: 0 } },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    barraHorizontal('dashGrupos', dados.por_grupo, 'grupo', 'rgba(47, 111, 235, 0.75)');
    barraHorizontal('dashResponsaveis', dados.por_responsavel, 'responsavel', 'rgba(131, 230, 230, 0.75)');
})();
