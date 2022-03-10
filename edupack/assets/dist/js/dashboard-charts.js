/**
 * Get the last 12 months.
 */
const last12Months = ( count = 13 ) => {
    var date = new Date();
    var months = [],
        monthNames = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];
    for(var i = 1; i < count; i++) {
        let month = date.getMonth() - i;

        if ( month < 0 ) {
            month = 12 + month;
        }

        months.push( monthNames[month] )
    }
    return months.reverse();
};

const warningMarkup = () => {
    return `
    <div class="edupack-metabox--no-data">
        <h3>There is currently insufficient data to provide a report.
        Your report will appear here once sufficient data exists.</h3>
    </div>`;
}

var activeArchived = document.getElementById('edupack-active-archived-chart');

if ( activeArchived ) {

    // Run a fetch post that requires the user to be logged in.
    fetch(
        `${rest.url}edupack/stats/current-site-statuses`,
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': rest.nonce,
            },
        }
    ).then(response => response.json())
    .then(data => {

        /**
         * If there is no data then show a warning over the chart.
         */
        if ( data.length < 1 ) {
            activeArchived.insertAdjacentHTML( 'beforebegin', warningMarkup() );
            activeArchived.parentNode.classList.add( 'v-top' );
            document.getElementById( 'edupack-current-status-loader' ).classList.add( 'edupack-is-hidden' );
        } else {

            // Get the canvas context.
            var ctx = activeArchived.getContext('2d');

            // Remove the loader from the metabox.
            document.getElementById( 'edupack-current-status-loader' ).classList.add( 'edupack-is-hidden' );

            // Remove download hidden class.
            document.getElementById( 'edupack-current-status-download' ).classList.remove( 'edupack-is-hidden' );

            activeArchived.classList.remove( 'edupack-is-hidden' );

            // Create the chart.
            var currentStatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Archived', 'Stale'],
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#f87e6f', // active
                            '#0d1e4c', // archived
                            '#ef4437', // stale
                        ]
                    }]
                },
                options: {
                    responsive: true
                }
            });
        }
    });
}

const archivedOverTime = document.getElementById('edupack-archived-over-time-chart')

if ( archivedOverTime ) {

    fetch(
        `${rest.url}edupack/stats/status-over-time`,
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': rest.nonce,
            },
        }
    ).then(response => response.json())
    .then(data => {

        /**
         * If there is no data then show a warning over the chart.
         */
        if ( data.length < 1 ) {
            archivedOverTime.insertAdjacentHTML( 'beforebegin', warningMarkup() );
            archivedOverTime.parentNode.classList.add( 'v-top' );
            document.getElementById( 'edupack-over-time-loader' ).classList.add( 'edupack-is-hidden' );
        } else {

            if ( ! data['active'] ) {
                data['active'] = [];
            }

            if ( ! data['archived'] ) {
                data['archived'] = [];
            }

            // If we have less than 12 months of data fill in the blanks.
            let active = data['active'];
            let archived = data['archived'];

            // Get the canvas context.
            var ctx = archivedOverTime.getContext('2d');

            // Remove the loader from the metabox.
            document.getElementById( 'edupack-over-time-loader' ).classList.add( 'edupack-is-hidden' );

            // Remove download hidden class.
            document.getElementById( 'edupack-over-time-download' ).classList.remove( 'edupack-is-hidden' );

            archivedOverTime.classList.remove( 'edupack-is-hidden' );

            var overTimeChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: last12Months( ( data['active'].length + 1 ) ), // ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [
                        {
                            label: 'Activated',
                            data: active, // [1, 4, 12, 19, 22, 3, 7, 6, 2, 4, 11, 5],
                            borderColor: '#f87e6f',
                            backgroundColor: '#f87e6f', // activated
                        },
                        {
                            label: 'Archived',
                            data: archived, // [12, 19, 8, 3, 1, 10, 15, 22, 4, 10, 19, 4],
                            borderColor: '#0d1e4c',
                            backgroundColor: '#0d1e4c', // archived
                        },
                    ],
                },
                options: {
                    responsive: true
                }
            });
        }
    });
}

const staleOverTime = document.getElementById('edupack-stale-over-time-chart');

if ( staleOverTime ) {

    fetch(
        `${rest.url}edupack/stats/status-over-time`,
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': rest.nonce,
            },
            body: JSON.stringify({
                stale: true,
            })
        }
    ).then(response => response.json())
    .then(data => {

        /**
         * If there is no data then show a warning over the chart.
         */
        if ( data.length < 1 ) {
            staleOverTime.insertAdjacentHTML( 'beforebegin', warningMarkup() );
            staleOverTime.parentNode.classList.add( 'v-top' );
            document.getElementById( 'edupack-stale-over-time-loader' ).classList.add( 'edupack-is-hidden' );
        } else {

            // If we have less than 12 months of data fill in the blanks.
            let stale = data;

            // Get the canvas context.
            var ctx = staleOverTime.getContext('2d');

            // Remove the loader from the metabox.
            document.getElementById( 'edupack-stale-over-time-loader' ).classList.add( 'edupack-is-hidden' );

            // Remove download hidden class.
            document.getElementById( 'edupack-stale-over-time-download' ).classList.remove( 'edupack-is-hidden' );

            staleOverTime.classList.remove( 'edupack-is-hidden' );

            var staleOverTimeChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: last12Months( ( data.length + 1 ) ), // ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [
                        {
                            label: 'Stale Sites',
                            data: stale,
                            borderColor: '#ef4437',
                            backgroundColor: '#ef4437', // archived
                        },
                    ],
                },
                options: {
                    responsive: true
                }
            });
        }
    });
}

