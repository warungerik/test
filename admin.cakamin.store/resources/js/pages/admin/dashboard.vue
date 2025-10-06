<script setup>
import { Head } from "@inertiajs/vue3";
import utility from "../admin/utility/page-title.vue";
import VueApexCharts from "vue3-apexcharts";
import { ref, computed } from "vue";

const props = defineProps({
    app: String,
    dataHistory: Array,
    revenue: Array,
    revenueMonthly: Array,
});
const activePeriod = ref('weekly');

function number_format(number, decimals = 0, dec_point = ",", thousands_sep = ".") {
    number = (number + "").replace(/[^0-9+\-Ee.]/g, "");
    let n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = thousands_sep,
        dec = dec_point,
        s = "",
        toFixedFix = function (n, prec) {
            let k = Math.pow(10, prec);
            return "" + Math.round(n * k) / k;
        };
    s = (prec ? toFixedFix(n, prec) : "" + Math.round(n)).split(".");
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || "").length < prec) {
        s[1] = s[1] || "";
        s[1] += new Array(prec - s[1].length + 1).join("0");
    }
    return s.join(dec);
}

function getDataByPeriod(period) {
    const today = new Date();

    if (period === 'weekly') {
        const dateRange = [];
        for (let i = 0; i <= 6; i++) {
            const date = new Date();
            date.setDate(today.getDate() - i);
            dateRange.push(date.toISOString().slice(0, 10));
        }


        const groupedData = {};
        dateRange.forEach(date => {
            groupedData[date] = { count: 0, revenue: 0, transactions: [] };
        });

        props.dataHistory.forEach(transaction => {
            const transactionDate = new Date(transaction.created_at).toISOString().slice(0, 10);
            if (groupedData[transactionDate]) {
                groupedData[transactionDate].count += 1;
                groupedData[transactionDate].revenue += Number(transaction.price);
                groupedData[transactionDate].transactions.push(transaction);
            }
        });

        return dateRange.map(date => ({
            date,
            ...groupedData[date],
            formattedDate: new Date(date).toLocaleDateString('id-ID', {
                weekday: 'short',
                day: 'numeric',
                month: 'short'
            })
        }));

    } else if (period === 'monthly') {
        // Current month only (1st to last day of current month)
        const currentYear = today.getFullYear();
        const currentMonth = today.getMonth(); // 0-indexed
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

        const dateRange = [];
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(currentYear, currentMonth, day);
            dateRange.push(date.toISOString().slice(0, 10));
        }

        const groupedData = {};
        dateRange.forEach(date => {
            groupedData[date] = { count: 0, revenue: 0, transactions: [] };
        });

        props.dataHistory.forEach(transaction => {
            const transactionDate = new Date(transaction.created_at).toISOString().slice(0, 10);
            const transactionYear = new Date(transaction.created_at).getFullYear();
            const transactionMonth = new Date(transaction.created_at).getMonth();

            // Only include transactions from current month and year
            if (transactionYear === currentYear && transactionMonth === currentMonth && groupedData[transactionDate]) {
                groupedData[transactionDate].count += 1;
                groupedData[transactionDate].revenue += transaction.price;
                groupedData[transactionDate].transactions.push(transaction);
            }
        });

        return dateRange.map(date => ({
            date,
            ...groupedData[date],
            formattedDate: new Date(date).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short'
            })
        }));

    } else if (period === 'yearly') {
        // Group by year (aggregate yearly data)
        const yearlyData = {};

        props.dataHistory.forEach(transaction => {
            const year = new Date(transaction.created_at).getFullYear().toString();
            if (!yearlyData[year]) {
                yearlyData[year] = { count: 0, revenue: 0, transactions: [] };
            }
            yearlyData[year].count += 1;
            yearlyData[year].revenue += transaction.price;
            yearlyData[year].transactions.push(transaction);
        });

        // Sort years and return data
        const sortedYears = Object.keys(yearlyData).sort((a, b) => parseInt(a) - parseInt(b));

        return sortedYears.map(year => ({
            date: year,
            ...yearlyData[year],
            formattedDate: year
        }));
    }

    return [];
}

// Computed data based on active period
const chartData = computed(() => {
    const data = getDataByPeriod(activePeriod.value);
    return {
        categories: data.map(d => d.formattedDate),
        transactions: data.map(d => d.count),
        revenue: data.map(d => Number(d.revenue)),
        details: data
    };
});
// Chart title based on period
const chartTitle = computed(() => {
    const today = new Date();
    switch (activePeriod.value) {
        case 'weekly':
            return 'Last 7 Days';
        case 'monthly':
            return `${today.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}`;
        case 'yearly':
            return 'Yearly Summary';
        default:
            return 'Transaction Summary';
    }
});

// Chart configuration
const chartOptions = computed(() => ({
    chart: {
        height: 380,
        type: activePeriod.value === 'yearly' ? "column" : "area",
        toolbar: { show: false },
    },
    colors: ["#22c55e", "#3b82f6"],
    dataLabels: {
        enabled: activePeriod.value === 'yearly',
        formatter: function (val, opts) {
            if (activePeriod.value === 'yearly') {
                return opts.w.config.series[opts.seriesIndex].name === 'Transactions' ?
                    val + ' trx' : 'Rp ' + number_format(val, 0, ",", ".");
            }
            return val;
        }
    },
    stroke: {
        curve: "smooth",
        width: activePeriod.value === 'yearly' ? 0 : [3, 3],
        lineCap: "round",
    },
    grid: {
        borderColor: "#45404a2e",
        padding: { left: 0, right: 0 },
        strokeDashArray: 4,
    },
    markers: {
        size: activePeriod.value === 'yearly' ? 0 : 4,
        hover: { size: 6 }
    },
    series: [
        {
            name: "Revenue (Rp)",
            type: activePeriod.value === 'yearly' ? "column" : "area",
            data: chartData.value.revenue,
        },
        {
            name: "Transactions",
            type: activePeriod.value === 'yearly' ? "column" : "line",
            data: chartData.value.transactions,
        }
    ],
    xaxis: {
        categories: chartData.value.categories,
        axisBorder: { show: true, color: "#45404a2e" },
        axisTicks: { show: true, color: "#45404a2e" },
        title: {
            text: activePeriod.value === 'yearly' ? 'Year' :
                activePeriod.value === 'monthly' ? 'Date' : 'Date'
        }
    },
    yaxis: activePeriod.value === 'yearly' ? [
        {
            title: { text: 'Revenue (Rp)' },
            labels: {
                formatter: (val) => "Rp " + number_format(val, 0, ",", ".")
            }
        },
        {
            opposite: true,
            title: { text: 'Transactions Count' },
            labels: {
                formatter: (val) => Math.round(val) + " trx"
            }
        }
    ] : [
        {
            title: { text: 'Revenue (Rp)' },
            labels: {
                formatter: (val) => "Rp " + number_format(val, 0, ",", ".")
            }
        },
        {
            opposite: true,
            title: { text: 'Transactions Count' },
            labels: {
                formatter: (val) => Math.round(val) + " trx"
            }
        }
    ],
    fill: {
        type: activePeriod.value === 'yearly' ? "solid" : "gradient",
        gradient: activePeriod.value === 'yearly' ? {} : {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.1,
            stops: [0, 90, 100],
        },
    },
    tooltip: {
        shared: true,
        intersect: false,
        y: [
            {
                formatter: (val) => "Rp " + number_format(val, 0, ",", ".")
            },
            {
                formatter: (val) => val + " transaksi"
            }
        ]
    },
    legend: { position: "top", horizontalAlign: "right" },
    plotOptions: activePeriod.value === 'yearly' ? {
        bar: {
            horizontal: false,
            columnWidth: '45%',
            dataLabels: {
                position: 'top',
            },
        }
    } : {}
}));

// Change period handler
const changePeriod = (period) => {
    activePeriod.value = period;
};

// Format date range
const formatDateRange = computed(() => {
    const data = chartData.value.details;
    if (data.length === 0) return '';

    if (activePeriod.value === 'yearly') {
        const years = data.map(d => d.date);
        return years.length > 1 ? `${years[0]} - ${years[years.length - 1]}` : years[0];
    }

    const startDate = new Date(data[0].date);
    const endDate = new Date(data[data.length - 1].date);

    return `${startDate.toLocaleDateString('id-ID')} - ${endDate.toLocaleDateString('id-ID')}`;
});

// Expanded state for collapsible rows
const expandedDates = ref(new Set());

const toggleExpanded = (date) => {
    if (expandedDates.value.has(date)) {
        expandedDates.value.delete(date);
    } else {
        expandedDates.value.add(date);
    }
};
</script>

<template>

    <Head :title="'Dashboard Admin'" />
    <utility :title="'Dashboard'"></utility>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-6">
            <div class="card bg-corner-img">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-9">
                            <p class="text-muted text-uppercase mb-0 fw-normal fs-13">
                                Total Revenue
                            </p>
                            <h4 class="mt-1 mb-0 fw-medium">
                                Rp {{ number_format(revenue, 0, ",", ".") }}
                            </h4>
                        </div>
                        <div class="col-3 align-self-center d-flex justify-content-end">
                            <div
                                class="d-flex justify-content-center align-items-center thumb-md border-dashed border-primary rounded">
                                <i class="iconoir-dollar-circle fs-22 align-self-center mb-0 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-6">
            <div class="card bg-corner-img">
                <div class="card-body">
                    <div class="row d-flex justify-content-center">
                        <div class="col-9">
                            <p class="text-muted text-uppercase mb-0 fw-normal fs-13">
                                Total Revenue Monthly
                            </p>
                            <h4 class="mt-1 mb-0 fw-medium">
                                Rp {{ number_format(revenueMonthly, 0, ",", ".") }}
                            </h4>
                        </div>
                        <div class="col-3 align-self-center d-flex justify-content-end">
                            <div
                                class="d-flex justify-content-center align-items-center thumb-md border-dashed border-info rounded">
                                <i class="iconoir-calendar fs-22 align-self-center mb-0 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">
                            <i class="iconoir-calendar-plus me-2"></i>
                            Transaction Summary - {{ chartTitle }}
                        </h5>
                        <small class="text-muted">{{ formatDateRange }}</small>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn"
                            :class="activePeriod === 'weekly' ? 'btn-primary' : 'btn-outline-primary'"
                            @click="changePeriod('weekly')">
                            Weekly
                        </button>
                        <button type="button" class="btn"
                            :class="activePeriod === 'monthly' ? 'btn-primary' : 'btn-outline-primary'"
                            @click="changePeriod('monthly')">
                            Monthly
                        </button>
                        <button type="button" class="btn"
                            :class="activePeriod === 'yearly' ? 'btn-primary' : 'btn-outline-primary'"
                            @click="changePeriod('yearly')">
                            Yearly
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ activePeriod === 'yearly' ? 'Year' : 'Date' }}</th>
                                    <th class="text-center">Transactions</th>
                                    <th class="text-end">Total Revenue</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="dayData in chartData.details" :key="dayData.date">
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ dayData.formattedDate }}</div>
                                            <small class="text-muted">{{ activePeriod === 'yearly' ? dayData.date :
                                                dayData.date }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge"
                                                :class="dayData.count > 0 ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info'">
                                                {{ dayData.count }} Transaksi
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <strong :class="dayData.revenue > 0 ? 'text-success' : 'text-muted'">
                                                Rp {{ number_format(dayData.revenue, 0, ",", ".") }}
                                            </strong>
                                        </td>
                                        <td class="text-center">
                                            <button v-if="dayData.count > 0" class="btn btn-sm btn-outline-info"
                                                @click="toggleExpanded(dayData.date)">
                                                <i class="fas fa-eye me-1"></i>
                                                {{ expandedDates.has(dayData.date) ? 'Hide' : 'Detail' }}
                                            </button>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                    </tr>

                                    <!-- Expanded Transaction Details -->
                                    <tr v-if="expandedDates.has(dayData.date) && dayData.count > 0">
                                        <td colspan="4" class="p-0">
                                            <div class="bg-light border-start border-info border-3 p-3">
                                                <h6 class="mb-3">
                                                    <i class="iconoir-list me-2"></i>
                                                    Detail Transaksi {{ dayData.formattedDate }}
                                                </h6>
                                                <div class="row g-2">
                                                    <div class="col-md-6" v-for="transaction in dayData.transactions"
                                                        :key="transaction.id">
                                                        <div class="card border-light">
                                                            <div class="card-body p-3">
                                                                <div
                                                                    class="d-flex justify-content-between align-items-start">
                                                                    <div>
                                                                        <h6 class="mb-1">{{ transaction.invoice_id }}
                                                                        </h6>
                                                                        <p class="mb-1 text-muted small">{{
                                                                            transaction.name }}</p>
                                                                        <span
                                                                            class="badge bg-warning-subtle text-warning">{{
                                                                                transaction.whatsapp }}</span>
                                                                    </div>
                                                                    <div class="text-end">
                                                                        <div class="fw-bold text-success">
                                                                            Rp {{ number_format(transaction.price, 0,
                                                                                ",", ".") }}
                                                                        </div>
                                                                        <small class="text-muted">
                                                                            {{ new
                                                                                Date(transaction.created_at).toLocaleTimeString('id-ID')
                                                                            }}
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-1">Revenue & Transaction Chart</h5>
                    <p class="text-muted small mb-0">{{ chartTitle }} - {{ formatDateRange }}</p>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="row w-100">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="text-success fw-bold fs-4">
                                        Rp {{number_format(chartData.revenue.reduce((a, b) => a + b, 0), 0, ",", ".")
                                        }}
                                    </div>
                                    <small class="text-muted">Total Revenue</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="text-primary fw-bold fs-4">
                                        {{chartData.transactions.reduce((a, b) => a + b, 0)}}
                                    </div>
                                    <small class="text-muted">Total Transactions</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="text-info fw-bold fs-4">
                                        {{chartData.details.filter(d => d.count > 0).length}}
                                    </div>
                                    <small class="text-muted">Active {{ activePeriod === 'yearly' ? 'Years' : 'Days'
                                        }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <apexchart :type="activePeriod === 'yearly' ? 'bar' : 'line'" height="380" :options="chartOptions"
                        :series="chartOptions.series" :key="activePeriod" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    components: { apexchart: VueApexCharts },
};
</script>
