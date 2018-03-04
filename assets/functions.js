function exportTableToCSV($table, filename) {
	var tmpColDelim = String.fromCharCode(11), tmpRowDelim = String.fromCharCode(0), // Temporary delimiters unlikely to be typed by keyboard to avoid accidentally splitting the actual contents
	colDelim = '","', rowDelim = '"\r\n"', // actual delimiters for CSV
	$rows = $table.find('tr'),
	csv = '"' + $rows.map(function(i, row) {
		var $row = jQuery(row), $cols = $row.find('td,th');
		return $cols.map(function(j, col) {
			var $col = jQuery(col), text = $col.text();
			return text.replace(/"/g, '""'); // escape double quotes
		}).get().join(tmpColDelim);
	}).get().join(tmpRowDelim).split(tmpRowDelim)
			.join(rowDelim).split(tmpColDelim)
			.join(colDelim) + '"',
	csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);
	jQuery(this).attr({
		'download' : filename,
		'href' : csvData
	});
}

function posts_and_users_stats_bar_chart(div, xData, yData, xAxisTitle, yAxisTitle) {
    var data = {
        labels: xData,
    	series: [
        	yData
		]
	};

    var options = {
        seriesBarDistance: 20,
        chartPadding: {
            top: 20,
            right: 30,
            bottom: 30,
            left: 30
        },
        axisY: {
            onlyInteger: true
        },
        plugins: [
            Chartist.plugins.ctAxisTitle({
                axisX: {
                    axisTitle: xAxisTitle,
                    axisClass: 'ct-axis-title ct-x-axis-title',
                    offset: {
                        x: 0,
                        y: 40
                    },
                    textAnchor: 'middle'
                },
                axisY: {
                    axisTitle: yAxisTitle,
                    axisClass: 'ct-axis-title ct-y-axis-title',
                    offset: {
                        x: 0,
                        y: 25
                    },
                    flipTitle: true
                }
            })
        ]
    };

    var responsiveOptions = [
        ['screen and (max-width: 640px)', {
            seriesBarDistance: 5,
            axisX: {
                labelInterpolationFnc: function (value) {
                    return value[0];
                }
            }
        }]
    ];

    new Chartist.Bar(div, data, options, responsiveOptions);
}

function posts_and_users_stats_time_line_chart(div, seriesData, dateFormat, xAxisTitle, yAxisTitle) {
    var data = {
        series: [
            {
                name: 'series-1',
                data: seriesData
            }
        ]
    };

    var options = {
        chartPadding: {
            top: 20,
            right: 30,
            bottom: 30,
            left: 30
        },
        axisX: {
            type: Chartist.FixedScaleAxis,
            divisor: 5,
            labelInterpolationFnc: function(value) {
                return moment(value).format(dateFormat);
            }
        },
        lineSmooth: Chartist.Interpolation.step(),
        plugins: [
            Chartist.plugins.ctAxisTitle({
                axisX: {
                    axisTitle: xAxisTitle,
                    axisClass: 'ct-axis-title ct-x-axis-title',
                    offset: {
                        x: 0,
                        y: 40
                    },
                    textAnchor: 'middle'
                },
                axisY: {
                    axisTitle: yAxisTitle,
                    axisClass: 'ct-axis-title ct-y-axis-title',
                    offset: {
                        x: 0,
                        y: 25
                    },
                    flipTitle: true
                }
            })
        ]
    };

    new Chartist.Line(div, data, options)
}