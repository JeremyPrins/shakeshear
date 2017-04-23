$(init);

function init() {

    Highcharts.chart('container', {
        credits: {
            enabled: false
        },
        title: {
            text: ''
        },

        chart: {
            type: 'line',
            width: 900,
            backgroundColor: 'transparent'

        },
        series: [{
            name: "Percentage",
            data: chartdata
        }]

    });
}