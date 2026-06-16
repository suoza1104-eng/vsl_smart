(function () {
    const data = window.ADMIN_CHARTS || {};
    const palette = {
        green: '#2f9e44',
        blue: '#1971c2',
        red: '#c92a2a',
        yellow: '#e67700',
        gray: '#495057'
    };

    function mapRows(rows) {
        return {
            labels: (rows || []).map((row) => row.label),
            values: (rows || []).map((row) => Number(row.total || 0))
        };
    }

    function chart(id, type, labels, datasets) {
        const canvas = document.getElementById(id);
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }
        new Chart(canvas, {
            type,
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    const visits = mapRows(data.visitsByDay);
    const leads = mapRows(data.leadsByDay);
    const clicks = mapRows(data.clicksByDay);
    const allLabels = Array.from(new Set([...visits.labels, ...leads.labels, ...clicks.labels])).sort();
    const series = (mapped) => allLabels.map((label) => {
        const index = mapped.labels.indexOf(label);
        return index >= 0 ? mapped.values[index] : 0;
    });

    chart('visitsChart', 'bar', visits.labels, [{ label: 'Visitas', data: visits.values, backgroundColor: palette.blue }]);
    chart('leadsChart', 'bar', leads.labels, [{ label: 'Inscritos', data: leads.values, backgroundColor: palette.green }]);
    chart('clicksChart', 'bar', clicks.labels, [{ label: 'Cliques', data: clicks.values, backgroundColor: palette.yellow }]);
    chart('visitsClicksChart', 'line', allLabels, [
        { label: 'Visitas', data: series(visits), borderColor: palette.blue, backgroundColor: palette.blue },
        { label: 'Cliques', data: series(clicks), borderColor: palette.yellow, backgroundColor: palette.yellow }
    ]);
    chart('visitsLeadsChart', 'line', allLabels, [
        { label: 'Visitas', data: series(visits), borderColor: palette.blue, backgroundColor: palette.blue },
        { label: 'Inscrições', data: series(leads), borderColor: palette.green, backgroundColor: palette.green }
    ]);

    const headlineRows = data.headlinePerf || [];
    chart('headlineChart', 'bar', headlineRows.map((row) => row.title), [
        { label: 'Visitas', data: headlineRows.map((row) => Number(row.visits || 0)), backgroundColor: palette.blue },
        { label: 'Leads', data: headlineRows.map((row) => Number(row.leads || 0)), backgroundColor: palette.green },
        { label: 'Cliques', data: headlineRows.map((row) => Number(row.clicks || 0)), backgroundColor: palette.yellow }
    ]);

    const offerRows = data.offerPerf || [];
    chart('offerChart', 'bar', offerRows.map((row) => row.name), [
        { label: 'Visitas', data: offerRows.map((row) => Number(row.visits || 0)), backgroundColor: palette.blue },
        { label: 'Leads', data: offerRows.map((row) => Number(row.leads || 0)), backgroundColor: palette.green },
        { label: 'Cliques', data: offerRows.map((row) => Number(row.clicks || 0)), backgroundColor: palette.yellow }
    ]);
})();

