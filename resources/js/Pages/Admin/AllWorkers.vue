<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUnmounted, nextTick } from "vue";
import { Line } from "vue-chartjs";
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler,
} from "chart.js";

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler,
);

const props = defineProps({
    workers: Array,
    latestLogs: Array,
});

// --- STATE SLIDESHOW AUTOMATION ---
const currentIndex = ref(0);
const countdown = ref(10);
let slideshowInterval = null;

// --- STATE HISTORI GRAFIK SLIDESHOW REAL-TIME ---
const chartLabels = ref([]);
const pmvHistory = ref([]);
const ppdHistory = ref([]);

// --- STATE MEMORY UNTUK DATA SENSOR & STATUS WORKER ---
const ambientData = ref({
    airSpeed: 0,
    MRT: 0,
    Tdb: 0,
    Twb: 0,
    RH: 0,
    O2: 0,
    CO: 0,
});
const workerStates = ref({});

// 🔑 1. FUNGSI FILTER BARU: Menyaring secara ketat agar hanya mengambil user dengan role 'worker'
const filteredWorkers = computed(() => {
    if (!props.workers || !Array.isArray(props.workers)) return [];
    return props.workers.filter((worker) => worker.role === "worker");
});

// 🔑 2. SINKRONISASI INDEKS: Mengambil data pekerja aktif dari hasil array yang sudah disaring
const currentWorker = computed(() => {
    if (filteredWorkers.value.length === 0) return null;
    return filteredWorkers.value[currentIndex.value];
});

// Sinkronisasi data sensor ketika giliran pekerja berganti + Reset Histori Grafik
const updateLiveSensorData = () => {
    if (!currentWorker.value) return;

    chartLabels.value = [];
    pmvHistory.value = [];
    ppdHistory.value = [];

    const activeWorkerState = workerStates.value[currentWorker.value.id];
    if (activeWorkerState) {
        const sekarang = new Date().toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });
        chartLabels.value.push(sekarang);
        pmvHistory.value.push(activeWorkerState.pmv);
        ppdHistory.value.push(activeWorkerState.ppd);
    }
};

// --- FUNGSI SANITASI DATA PAYLOAD ---
const safeParse = (val) => {
    const parsed = parseFloat(val);
    return isNaN(parsed) || !isFinite(parsed) ? 0.0 : parsed;
};

// --- LOGIKA REAL-TIME (LARAVEL ECHO + TIMERS) ---
onMounted(() => {
    if (props.latestLogs && Array.isArray(props.latestLogs)) {
        props.latestLogs.forEach((log) => {
            if (log && log.user_id) {
                workerStates.value[log.user_id] = {
                    pmv: safeParse(log.pmv),
                    ppd: safeParse(log.ppd),
                    clothing_insulation: safeParse(log.clothing_insulation),
                    activity_name: log.activity_name || "Unknown",
                    activity_met: safeParse(log.activity_met),
                };
            }
        });
    }

    updateLiveSensorData();

    // 🔑 3. SINKRONISASI TIMER INTERVAL: slideshow berputar mengacu pada panjang filteredWorkers
    slideshowInterval = setInterval(() => {
        if (filteredWorkers.value.length > 0) {
            countdown.value--;
            if (countdown.value <= 0) {
                currentIndex.value =
                    (currentIndex.value + 1) % filteredWorkers.value.length;
                updateLiveSensorData();
                countdown.value = 10;
            }
        }
    }, 1000);

    // Echo Pipeline
    if (window.Echo) {
        window.Echo.channel("environment-monitor").listen(
            ".data.received",
            (e) => {
                const payload = e.data;
                if (!payload) return;

                if (payload.id && !payload.user_id) {
                    ambientData.value = {
                        airSpeed: safeParse(
                            payload.airSpeed || payload.air_speed,
                        ),
                        MRT: safeParse(payload.MRT || payload.mrt),
                        Tdb: safeParse(payload.Tdb || payload.tdb),
                        Twb: safeParse(payload.Twb || payload.twb),
                        RH: safeParse(payload.RH || payload.rh),
                        O2: safeParse(payload.O2 || payload.o2),
                        CO: safeParse(payload.CO || payload.co),
                    };

                    if (currentWorker.value) {
                        const sekarang = new Date().toLocaleTimeString([], {
                            hour: "2-digit",
                            minute: "2-digit",
                            second: "2-digit",
                        });
                        chartLabels.value.push(sekarang);

                        const activeState = workerStates.value[
                            currentWorker.value.id
                        ] || { pmv: 0, ppd: 0 };
                        pmvHistory.value.push(activeState.pmv);
                        ppdHistory.value.push(activeState.ppd);

                        if (chartLabels.value.length > 10) {
                            chartLabels.value.shift();
                            pmvHistory.value.shift();
                            ppdHistory.value.shift();
                        }
                    }
                } else if (payload && payload.user_id) {
                    workerStates.value[payload.user_id] = {
                        pmv: safeParse(payload.pmv),
                        ppd: safeParse(payload.ppd),
                        clothing_insulation: safeParse(
                            payload.clothing_insulation,
                        ),
                        activity_name: payload.activity_name || "Unknown",
                        activity_met: safeParse(payload.activity_met),
                    };

                    if (
                        currentWorker.value &&
                        payload.user_id == currentWorker.value.id
                    ) {
                        const sekarang = new Date().toLocaleTimeString([], {
                            hour: "2-digit",
                            minute: "2-digit",
                            second: "2-digit",
                        });
                        chartLabels.value.push(sekarang);
                        pmvHistory.value.push(safeParse(payload.pmv));
                        ppdHistory.value.push(safeParse(payload.ppd));

                        if (chartLabels.value.length > 10) {
                            chartLabels.value.shift();
                            pmvHistory.value.shift();
                            ppdHistory.value.shift();
                        }
                    }
                }
            },
        );
    }
});

onUnmounted(() => {
    if (slideshowInterval) clearInterval(slideshowInterval);
});

const isModalOpen = ref(false);

const totalClo = computed(() => {
    if (currentWorker.value && workerStates.value[currentWorker.value.id]) {
        return workerStates.value[currentWorker.value.id].clothing_insulation;
    }
    return 0.0;
});

const currentMetabolicRate = computed(() => {
    if (currentWorker.value && workerStates.value[currentWorker.value.id]) {
        return workerStates.value[currentWorker.value.id].activity_met;
    }
    return 0.0;
});

const calculatedMetrics = computed(() => {
    const ambient = ambientData.value;
    const worker = currentWorker.value
        ? workerStates.value[currentWorker.value.id]
        : null;

    return {
        pmv: worker ? worker.pmv : 0,
        ppd: worker ? worker.ppd : 0,
        clothing_insulation: worker ? worker.clothing_insulation : 0,
        activity_name: worker ? worker.activity_name : "Not Inputted Yet",
        activity_met: worker ? worker.activity_met : 0,
        ta: ambient.Tdb,
        rh: ambient.RH,
        v: ambient.airSpeed,
        tr: ambient.MRT,
        twb: ambient.Twb,
        o2: ambient.O2,
        co: ambient.CO,
    };
});

const environmentStatus = computed(() => {
    if (!currentWorker.value) return { label: "Waiting...", color: "#9ca3af" };

    const p = calculatedMetrics.value.pmv;

    if (p <= 0.5 && p >= -0.5)
        return {
            label: "Neutral",
            color: "linear-gradient(135deg, #7cba52 50%, #4F7C31 100%)",
        };
    if (p > 0.5 && p <= 1.5)
        return {
            label: "Slightly Warm",
            color: "linear-gradient(135deg, #facc15 50%, #ca8a04 100%)",
        };
    if (p > 1.5 && p <= 2.5)
        return {
            label: "Warm",
            color: "linear-gradient(135deg, #fb923c 50%, #ea580c 100%)",
        };
    if (p > 2.5)
        return {
            label: "Hot",
            color: "linear-gradient(135deg, #ef4444 50%, #b91d1d 100%)",
        };
    if (p < -0.5 && p >= -1.5)
        return {
            label: "Slightly Cool",
            color: "linear-gradient(135deg, #60a5fa 50%, #2563eb 100%)",
        };
    if (p < -1.5 && p >= -2.5)
        return {
            label: "Cool",
            color: "linear-gradient(135deg, #3b82f6 50%, #1d4ed8 100%)",
        };

    return {
        label: "Cold / Unknown",
        color: "linear-gradient(135deg, #1e3a8a 50%, #172554 100%)",
    };
});

const toggleModal = () => (isModalOpen.value = !isModalOpen.value);

const pmvLineData = computed(() => ({
    labels: [...chartLabels.value],
    datasets: [
        {
            label: "PMV Index",
            borderColor: "#6366f1",
            backgroundColor: "rgba(99, 102, 241, 0.05)",
            borderWidth: 2,
            pointRadius: 2,
            tension: 0.4,
            fill: true,
            data: [...pmvHistory.value],
        },
    ],
}));

const ppdLineData = computed(() => ({
    labels: [...chartLabels.value],
    datasets: [
        {
            label: "PPD Index (%)",
            borderColor: "#f43f5e",
            backgroundColor: "rgba(244, 63, 94, 0.05)",
            borderWidth: 2,
            pointRadius: 2,
            tension: 0.4,
            fill: true,
            data: [...ppdHistory.value],
        },
    ],
}));

const pmvChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    scales: { y: { min: -3, max: 3, ticks: { stepSize: 1 } } },
    plugins: { legend: { display: false } },
};
const ppdChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    scales: { y: { min: 0, max: 100, ticks: { stepSize: 20 } } },
    plugins: { legend: { display: false } },
};
</script>

<template>
    <Head title="All Workers Monitor" />

    <AuthenticatedLayout>
        <div class="p-6 bg-gray-100 h-auto font-sans">
            <div
                v-if="currentWorker"
                class="hidden lg:grid grid-cols-5 gap-4 auto-rows-min"
            >
                <div
                    class="col-span-2 row-span-1 bg-white px-6 py-1 rounded-2xl shadow-sm flex items-center self-start min-h-[30px]"
                >
                    <span class="text-black font-bold text-base mr-4 shrink-0"
                        >Worker Name :</span
                    >
                    <div class="flex space-x-4 font-bold items-center">
                        <div
                            class="bg-indigo-100 text-indigo-700 px-6 py-1 rounded-full text-base transition shadow-sm"
                        >
                            {{ currentWorker.name }}
                        </div>
                    </div>
                    <span class="text-gray-500 font-bold text-sm ml-auto">
                        Pekerja {{ currentIndex + 1 }} dari
                        {{ filteredWorkers.length }}
                    </span>
                </div>

                <div
                    class="col-span-1 row-span-2 bg-white p-5 py-4 rounded-xl shadow-sm"
                >
                    <p class="text-indigo-500 text-md font-semibold mb-2">
                        Air Speed
                    </p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">
                            {{ ambientData.airSpeed.toFixed(2) }} m/s
                        </h3>
                        <div class="w-4 h-4 text-gray-400 font-bold text-xl">
                            =
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm"
                >
                    <p class="text-indigo-500 text-md font-semibold mb-2">
                        Wet Temperature
                    </p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">
                            {{ ambientData.Twb.toFixed(2) }} &deg;C
                        </h3>
                        <div class="w-4 h-4 text-gray-400 font-bold text-xl">
                            =
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm"
                >
                    <p class="text-indigo-500 text-md font-semibold mb-2">
                        Dry Temperature
                    </p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">
                            {{ ambientData.Tdb.toFixed(1) }} &deg;C
                        </h3>
                        <div class="w-4 h-4 text-gray-400 font-bold text-xl">
                            =
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-2 row-span-3 px-6 py-4 rounded-2xl shadow-md flex flex-col justify-between transition-all duration-500"
                    :style="{ background: environmentStatus.color }"
                >
                    <div class="text-white">
                        <p class="text-lg opacity-90 font-medium">
                            Thermal Comfort Status :
                        </p>
                        <h2 class="text-3xl font-bold mt-2 tracking-tight">
                            {{ environmentStatus.label }}
                        </h2>
                        <div class="flex space-x-4 mt-1">
                            <p class="text-base opacity-75 font-mono">
                                PMV Index:
                                {{
                                    (
                                        workerStates[currentWorker.id]?.pmv || 0
                                    ).toFixed(2)
                                }}
                                | PPD Index:
                                {{
                                    (
                                        workerStates[currentWorker.id]?.ppd || 0
                                    ).toFixed(2)
                                }}%
                            </p>
                        </div>
                    </div>

                    <div
                        class="bg-white/90 text-sm py-1.5 px-4 rounded-xl text-center backdrop-blur-sm mt-4 shadow-sm"
                    >
                        <div
                            class="font-sans font-bold text-[14px] text-gray-800 flex items-center justify-center gap-1.5"
                        >
                            <span
                                class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse shrink-0"
                            ></span>
                            Next Slide in
                            <span
                                class="font-mono font-black text-base text-indigo-700"
                                >{{ countdown }}s</span
                            >
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm"
                >
                    <p class="text-indigo-500 text-md font-semibold mb-2">
                        Radiation Temperature
                    </p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">
                            {{ ambientData.MRT.toFixed(2) }} &deg;C
                        </h3>
                        <div class="w-4 h-4 text-gray-400 font-bold text-xl">
                            =
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm"
                >
                    <p class="text-indigo-500 text-md font-semibold mb-2">
                        Air Humidity
                    </p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">
                            {{ ambientData.RH.toFixed(0) }} %
                        </h3>
                        <div class="w-4 h-4 text-gray-400 font-bold text-xl">
                            =
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm"
                >
                    <p class="text-indigo-500 text-md font-semibold mb-2">
                        Oxygen Volume
                    </p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">
                            {{ ambientData.O2.toFixed(1) }} %vol
                        </h3>
                        <div class="w-4 h-4 text-gray-400 font-bold text-xl">
                            =
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-4 row-span-5 grid grid-cols-1 md:grid-cols-2 gap-4"
                >
                    <div
                        class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between"
                    >
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-gray-800 text-sm font-bold">
                                PMV Real-time Index Movement
                            </h3>
                            <span
                                class="text-[11px] text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full font-mono font-bold"
                            >
                                Clo:
                                {{
                                    workerStates[currentWorker.id]
                                        ?.clothing_insulation || "0.00"
                                }}
                                |
                                {{
                                    workerStates[currentWorker.id]
                                        ?.activity_name || "Not Inputted Yet"
                                }}
                                ({{
                                    workerStates[currentWorker.id]
                                        ?.activity_met || 0
                                }}
                                W/m²)
                            </span>
                        </div>
                        <div class="h-56">
                            <Line
                                :data="pmvLineData"
                                :options="pmvChartOptions"
                            />
                        </div>
                    </div>

                    <div
                        class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between"
                    >
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-gray-800 text-sm font-bold">
                                PPD Real-time Movement Index (%)
                            </h3>
                            <span
                                class="text-[11px] text-rose-600 bg-rose-50 px-2.5 py-0.5 rounded-full font-mono font-bold"
                            >
                                Clo:
                                {{
                                    workerStates[currentWorker.id]
                                        ?.clothing_insulation || "0.00"
                                }}
                                |
                                {{
                                    workerStates[currentWorker.id]
                                        ?.activity_name || "Not Inputted Yet"
                                }}
                            </span>
                        </div>
                        <div class="h-56">
                            <Line
                                :data="ppdLineData"
                                :options="ppdChartOptions"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm"
                >
                    <p class="text-indigo-500 text-md font-semibold mb-2">
                        Carbon Monoxide
                    </p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">
                            {{ ambientData.CO.toFixed(0) }} PPM
                        </h3>
                        <div class="w-4 h-4 text-gray-400 font-bold text-xl">
                            =
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="text-center py-12 text-gray-500 bg-white rounded-2xl shadow-sm p-6"
            >
                Tidak ada data antrean pekerja aktif ber-role Worker untuk
                disinkronisasi ke slideshow dashboard.
            </div>
        </div>
    </AuthenticatedLayout>
</template>
