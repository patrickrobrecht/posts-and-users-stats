function posts_and_users_stats_export_table_to_csv(table, filename) {
    // Temporary delimiters unlikely to be typed by keyboard to avoid accidentally splitting the actual contents
    const tmpColDelim = String.fromCharCode(11),
        tmpRowDelim = String.fromCharCode(0),
        // actual delimiters for CSV
        colDelim = '","',
        rowDelim = '"\r\n"',
        forbiddenStartCharacters = ['+', '-', '=', '@'],
        rows = table.find('tr'),
        csv = '"' + rows
            .map(function (i, row) {
                const $row = jQuery(row),
                    $cols = $row.find('td,th');
                return $cols
                    .map(function (j, col) {
                        const $col = jQuery(col);
                        let text = $col.text();
                        // Escape double quotes and trim result.
                        text = text.replace(/"/g, '""').trim();
                        // Prevent CSV injection.
                        let startCharacter = text.substring(0, 1);
                        if (forbiddenStartCharacters.includes(startCharacter)) {
                            text = "'" + text;
                        }
                        return text;
                    })
                    .get()
                    .join(tmpColDelim);
            }).get()
            .join(tmpRowDelim)
            .split(tmpRowDelim)
            .join(rowDelim)
            .split(tmpColDelim)
            .join(colDelim) + '"',
        csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);
    jQuery(this).attr({
        'download': filename,
        'href': csvData
    });
}

function posts_and_users_stats_bar_chart(div, xData, yData, xAxisTitle, yAxisTitle) {
    const data = {
        labels: xData,
        series: [
            yData
        ]
    };

    const options = {
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

    const responsiveOptions = [
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
    const data = {
        series: [
            {
                name: 'series-1',
                data: seriesData
            }
        ]
    };

    const options = {
        chartPadding: {
            top: 20,
            right: 30,
            bottom: 30,
            left: 30
        },
        axisX: {
            type: Chartist.FixedScaleAxis,
            divisor: 5,
            labelInterpolationFnc: function (value) {
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