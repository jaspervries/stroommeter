/*
*   stroommeter - toolchain for reading energy meters
*   Copyright (C) 2022-2025  Jasper Vries
*
*   This program is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.

*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
var selectedDate = dayjs();
var selectedDate2 = dayjs().subtract(1, 'year');
var counter = 1;

$(function() {
    //init tabs
    $('#tabs').tabs();
    //init date options
    setDateFields();
    //load chart
    updateChart();
});

//button handlers
$('#day-previous').click(function(e) {
    e.preventDefault();
    selectedDate = selectedDate.subtract(1, 'day');
    setDateFields();
    updateChart();
});
$('#day-next').click(function(e) {
    e.preventDefault();
    selectedDate = selectedDate.add(1, 'day');
    setDateFields();
    updateChart();
});
$('#week-previous').click(function(e) {
    e.preventDefault();
    selectedDate = selectedDate.subtract(1, 'week');
    setDateFields();
    updateChart();
});
$('#week-next').click(function(e) {
    e.preventDefault();
    selectedDate = selectedDate.add(1, 'week');
    setDateFields();
    updateChart();
});
$('#month-previous').click(function(e) {
    e.preventDefault();
    selectedDate = selectedDate.subtract(1, 'month');
    setDateFields();
    updateChart();
});
$('#month-next').click(function(e) {
    e.preventDefault();
    selectedDate = selectedDate.add(1, 'month');
    setDateFields();
    updateChart();
});
$('#year-previous').click(function(e) {
    e.preventDefault();
    selectedDate = selectedDate.subtract(1, 'year');
    setDateFields();
    updateChart();
});
$('#year-next').click(function(e) {
    e.preventDefault();
    selectedDate = selectedDate.add(1, 'year');
    setDateFields();
    updateChart();
});

//field change handlers
$('#date-0').change(function() {
    selectedDate = dayjs($('#date-0').val());
    setDateFields();
    updateChart();
});
$('#date-1').change(function() {
    selectedDate = dayjs($('#date-1').val());
    setDateFields();
    updateChart();
});
$('#date-2').change(function() {
    selectedDate = dayjs($('#date-2').val());
    setDateFields();
    updateChart();
});
$('#date-4-s').change(function() {
    selectedDate2 = dayjs($('#date-4-s').val());
    setDateFields();
    updateChart();
});
$('#date-4-e').change(function() {
    selectedDate = dayjs($('#date-4-e').val());
    setDateFields();
    updateChart();
});
$('#date-5-s').change(function() {
    selectedDate2 = dayjs($('#date-5-s').val());
    setDateFields();
    updateChart();
});
$('#date-5-e').change(function() {
    selectedDate = dayjs($('#date-5-e').val());
    setDateFields();
    updateChart();
});
$('#date-6-s').change(function() {
    selectedDate2 = dayjs($('#date-6-s').val());
    setDateFields();
    updateChart();
});
$('#date-6-e').change(function() {
    selectedDate = dayjs($('#date-6-e').val());
    setDateFields();
    updateChart();
});
$('#counter-7').change(function() {
    counter = $('#counter-7').val();
    setDateFields();
    updateChart();
});

//tab change handler
$('#tabs').on('tabsactivate', function() {
    updateChart();
});

function setDateFields() {
    $('#date-0').val(selectedDate.format('YYYY-MM-DD'));
    $('#date-1').val(selectedDate.format('YYYY-MM-DD'));
    $('#date-2').val(selectedDate.format('YYYY-MM-DD'));
    $('#date-3').val(selectedDate.format('YYYY'));
    $('#date-4-s').val(selectedDate2.format('YYYY-MM-DD'));
    $('#date-4-e').val(selectedDate.format('YYYY-MM-DD'));
    $('#date-5-s').val(selectedDate2.format('YYYY-MM-DD'));
    $('#date-5-e').val(selectedDate.format('YYYY-MM-DD'));
    $('#date-6-s').val(selectedDate2.format('YYYY-MM-DD'));
    $('#date-6-e').val(selectedDate.format('YYYY-MM-DD'));
}

var options = {
    series: [],
    chart: {
        type: 'bar',
        height: 350
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            endingShape: 'rounded'
        },
    },
        dataLabels: {
        enabled: false
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
    },
    xaxis: {
        categories: [],
    },
    yaxis: {
        title: {
            text: 'kWh'
        }
    },
    fill: {
        opacity: 1
    },
    tooltip: {
        y: {
            formatter: function (val) {
                return val + " kWh"
            }
        }
    }
};

var chart = new ApexCharts(document.querySelector("#chart"), options);
chart.render();

function updateChart() {
    //check if date is valid
    if (selectedDate.format('YYYY-MM-DD') == 'Invalid Date') {
        return false;
    }
    if (selectedDate2.format('YYYY-MM-DD') == 'Invalid Date') {
        return false;
    }
    //get type
    var type = $('#tabs').tabs('option', 'active');
    //get chart data
    $.getJSON('chart.php', { type: type, date: selectedDate.format('YYYY-MM-DD'), date2: selectedDate2.format('YYYY-MM-DD'), counter: counter })
    .done(function(data) {
        chart.updateSeries(data.series);
        chart.updateOptions(data.options);
    });
}