<?php
</div>

<script>

const ctx = document.getElementById('qualityChart');

new Chart(ctx, {

    type: 'bar',

    data: {

        labels: [
            'Today',
            'Week',
            'Zero Calls',
            'Average Score'
        ],

        datasets: [{

            label: 'Quality Data',

            data: [
                <?= $todayCalls ?>,
                <?= $weekCalls ?>,
                <?= $zeroCalls ?>,
                <?= $avg ?>
            ],

            backgroundColor: [
                '#d4af37',
                '#3b82f6',
                '#ef4444',
                '#22c55e'
            ],

            borderRadius: 10
        }]
    },

    options: {

        responsive:true,

        plugins:{
            legend:{
                labels:{
                    color:'white'
                }
            }
        },

        scales:{

            y:{
                ticks:{
                    color:'white'
                },
                grid:{
                    color:'rgba(255,255,255,.06)'
                }
            },

            x:{
                ticks:{
                    color:'white'
                },
                grid:{
                    color:'rgba(255,255,255,.06)'
                }
            }
        }
    }
});

</script>

</body>
</html>