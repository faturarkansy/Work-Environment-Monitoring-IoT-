<template>
    <Head title="Dashboard Monitor" />

    <AuthenticatedLayout>
        <div class="p-6 bg-gray-100 h-auto">
            <div class="hidden lg:grid grid-cols-5 gap-4 auto-rows-min">
                <div
                    class="col-span-2 row-span-1 bg-white px-6 py-1 rounded-2xl shadow-sm flex items-center self-start min-h-[30px]"
                >
                    <span class="text-black font-bold text-md mr-6 shrink-0"
                        >Filtered by :</span
                    >

                    <div class="flex space-x-4 font-bold items-center">
                        <button
                            @click="selectedFilter = 'all'"
                            :class="
                                selectedFilter === 'all'
                                    ? 'bg-indigo-500 text-white px-6 py-1.5 rounded-full text-sm transition shadow-sm cursor-pointer'
                                    : 'text-gray-600 px-3 py-1.5 text-sm hover:text-indigo-500 transition cursor-pointer'
                            "
                        >
                            All
                        </button>

                        <button
                            @click="selectedFilter = '1'"
                            :class="
                                selectedFilter === '1'
                                    ? 'bg-indigo-500 text-white px-6 py-1.5 rounded-full text-sm transition shadow-sm cursor-pointer'
                                    : 'text-gray-600 px-3 py-1.5 text-sm hover:text-indigo-500 transition cursor-pointer'
                            "
                        >
                            Unit 1
                        </button>

                        <button
                            @click="selectedFilter = '2'"
                            :class="
                                selectedFilter === '2'
                                    ? 'bg-indigo-500 text-white px-6 py-1.5 rounded-full text-sm transition shadow-sm cursor-pointer'
                                    : 'text-gray-600 px-3 py-1.5 text-sm hover:text-indigo-500 transition cursor-pointer'
                            "
                        >
                            Unit 2
                        </button>

                        <button
                            @click="selectedFilter = '3'"
                            :class="
                                selectedFilter === '3'
                                    ? 'bg-indigo-500 text-white px-6 py-1.5 rounded-full text-sm transition shadow-sm cursor-pointer'
                                    : 'text-gray-600 px-3 py-1.5 text-sm hover:text-indigo-500 transition cursor-pointer'
                            "
                        >
                            Unit 3
                        </button>
                    </div>
                </div>

                <div
                    class="col-span-1 row-span-2 bg-white p-5 py-4 rounded-xl shadow-sm"
                >
                    <p class="text-indigo-500 text-md font-semibold mb-2">
                        Air Speed
                    </p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">
                            {{ sensors.airSpeed.toFixed(2) }} m/s
                        </h3>
                        <div class="w-4 h-4">
                            <img
                                v-if="getTrendImage('airSpeed')"
                                :src="getTrendImage('airSpeed')"
                                class="w-full h-full object-contain"
                            />
                            <span v-else class="text-gray-400 font-bold text-xl"
                                >=</span
                            >
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
                            {{ sensors.Twb.toFixed(2) }} &deg;C
                        </h3>
                        <div class="w-4 h-4">
                            <img
                                v-if="getTrendImage('Twb')"
                                :src="getTrendImage('Twb')"
                                class="w-full h-full object-contain"
                            />
                            <span v-else class="text-gray-400 font-bold text-xl"
                                >=</span
                            >
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
                            {{ sensors.Tdb.toFixed(1) }} &deg;C
                        </h3>
                        <div class="w-4 h-4">
                            <img
                                v-if="getTrendImage('Tdb')"
                                :src="getTrendImage('Tdb')"
                                class="w-full h-full object-contain"
                            />
                            <span v-else class="text-gray-400 font-bold text-xl"
                                >=</span
                            >
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-2 row-span-3 px-6 py-4 rounded-2xl shadow-md flex flex-col justify-between transition-all duration-500"
                    :style="{ background: environmentStatus.color }"
                >
                    <div class="text-white">
                        <p class="text-lg font-bold text-white opacity-90">
                            Thermal Safety Status :
                        </p>
                        <h2 class="text-3xl font-bold mt-2 tracking-tight">
                            {{ environmentStatus.label }}
                        </h2>
                        <div class="flex space-x-4 mt-1">
                            <p class="text-base font-mono opacity-95">
                                WBGT Index:
                                {{ wbgtValue.toFixed(2) }} &deg;C
                            </p>
                        </div>
                    </div>
                    <div
                        class="bg-white text-sm py-1 px-4 rounded-xl text-center backdrop-blur-sm mt-4"
                    >
                        <span class="font-mono font-bold text-lg text-gray-800"
                            >{{ currentTime }} WIB</span
                        >
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
                            {{ sensors.MRT.toFixed(2) }} &deg;C
                        </h3>
                        <div class="w-4 h-4">
                            <img
                                v-if="getTrendImage('MRT')"
                                :src="getTrendImage('MRT')"
                                class="w-full h-full object-contain"
                            />
                            <span v-else class="text-gray-400 font-bold text-xl"
                                >=</span
                            >
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
                            {{ sensors.RH.toFixed(0) }} %
                        </h3>
                        <div class="w-4 h-4">
                            <img
                                v-if="getTrendImage('RH')"
                                :src="getTrendImage('RH')"
                                class="w-full h-full object-contain"
                            />
                            <span v-else class="text-gray-400 font-bold text-xl"
                                >=</span
                            >
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
                            {{ sensors.O2.toFixed(1) }} %vol
                        </h3>
                        <div class="w-4 h-4">
                            <img
                                v-if="getTrendImage('O2')"
                                :src="getTrendImage('O2')"
                                class="w-full h-full object-contain"
                            />
                            <span v-else class="text-gray-400 font-bold text-xl"
                                >=</span
                            >
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-4 row-span-5 bg-white p-5 rounded-2xl shadow-sm"
                >
                    <h3 class="text-gray-800 text-md font-bold mb-4">
                        WBGT Real-time Movement Index (&deg;C)
                    </h3>
                    <div class="h-64">
                        <Line
                            :data="wbgtLineData"
                            :options="wbgtChartOptions"
                        />
                    </div>
                </div>

                <div
                    class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm"
                >
                    <p class="text-indigo-500 text-md font-semibold mb-2">
                        Carbon Monoxide Volume
                    </p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">
                            {{ sensors.CO.toFixed(2) }} PPM
                        </h3>
                        <div class="w-4 h-4">
                            <img
                                v-if="getTrendImage('CO')"
                                :src="getTrendImage('CO')"
                                class="w-full h-full object-contain"
                            />
                            <span v-else class="text-gray-400 font-bold text-xl"
                                >=</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:hidden flex flex-col space-y-4">
                <div
                    class="px-6 py-4 rounded-2xl shadow-md flex flex-col justify-between min-h-[140px]"
                    :style="{ background: environmentStatus.color }"
                >
                    <div class="text-white">
                        <p class="text-lg opacity-90">
                            Status Lingkungan Kerja :
                        </p>
                        <h2 class="text-3xl font-bold mt-2">
                            {{ environmentStatus.label }}
                        </h2>
                        <div class="flex space-x-4">
                            <p class="text-sm opacity-90">
                                WBGT Index: {{ wbgtValue.toFixed(2) }} &deg;C
                            </p>
                        </div>
                    </div>
                    <div
                        class="bg-white/90 text-sm py-1 px-4 rounded-full text-center mt-4"
                    >
                        <span class="font-mono font-bold text-lg text-gray-800"
                            >{{ currentTime }} WIB</span
                        >
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm">
                    <h3 class="text-gray-800 text-xs font-bold mb-2">
                        WBGT Movement Index (&deg;C)
                    </h3>
                    <div class="h-40">
                        <Line
                            :data="wbgtLineData"
                            :options="wbgtChartOptions"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div
                        class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between"
                    >
                        <p class="text-indigo-500 text-xs font-semibold mb-1">
                            Air Speed
                        </p>
                        <div
                            class="flex justify-between items-center space-x-1"
                        >
                            <h4
                                class="text-lg font-bold text-gray-800 tracking-tight"
                            >
                                {{ sensors.airSpeed.toFixed(2) }}
                                <span
                                    class="text-[10px] font-normal text-gray-400"
                                    >m/s</span
                                >
                            </h4>
                            <div
                                class="w-3 h-3 shrink-0 flex items-center justify-center"
                            >
                                <img
                                    v-if="getTrendImage('airSpeed')"
                                    :src="getTrendImage('airSpeed')"
                                    class="w-full h-full object-contain"
                                />
                                <span
                                    v-else
                                    class="text-gray-400 font-bold text-xs"
                                    >=</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between"
                    >
                        <p class="text-indigo-500 text-xs font-semibold mb-1">
                            Wet Temp
                        </p>
                        <div
                            class="flex justify-between items-center space-x-1"
                        >
                            <h4
                                class="text-lg font-bold text-gray-800 tracking-tight"
                            >
                                {{ sensors.Twb.toFixed(1) }}
                                <span
                                    class="text-[10px] font-normal text-gray-400"
                                    >&deg;C</span
                                >
                            </h4>
                            <div
                                class="w-3 h-3 shrink-0 flex items-center justify-center"
                            >
                                <img
                                    v-if="getTrendImage('Twb')"
                                    :src="getTrendImage('Twb')"
                                    class="w-full h-full object-contain"
                                />
                                <span
                                    v-else
                                    class="text-gray-400 font-bold text-xs"
                                    >=</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between"
                    >
                        <p class="text-indigo-500 text-xs font-semibold mb-1">
                            Dry Temp
                        </p>
                        <div
                            class="flex justify-between items-center space-x-1"
                        >
                            <h4
                                class="text-lg font-bold text-gray-800 tracking-tight"
                            >
                                {{ sensors.Tdb.toFixed(1) }}
                                <span
                                    class="text-[10px] font-normal text-gray-400"
                                    >&deg;C</span
                                >
                            </h4>
                            <div
                                class="w-3 h-3 shrink-0 flex items-center justify-center"
                            >
                                <img
                                    v-if="getTrendImage('Tdb')"
                                    :src="getTrendImage('Tdb')"
                                    class="w-full h-full object-contain"
                                />
                                <span
                                    v-else
                                    class="text-gray-400 font-bold text-xs"
                                    >=</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between"
                    >
                        <p class="text-indigo-500 text-xs font-semibold mb-1">
                            Radiation Temp
                        </p>
                        <div
                            class="flex justify-between items-center space-x-1"
                        >
                            <h4
                                class="text-lg font-bold text-gray-800 tracking-tight"
                            >
                                {{ sensors.MRT.toFixed(1) }}
                                <span
                                    class="text-[10px] font-normal text-gray-400"
                                    >&deg;C</span
                                >
                            </h4>
                            <div
                                class="w-3 h-3 shrink-0 flex items-center justify-center"
                            >
                                <img
                                    v-if="getTrendImage('MRT')"
                                    :src="getTrendImage('MRT')"
                                    class="w-full h-full object-contain"
                                />
                                <span
                                    v-else
                                    class="text-gray-400 font-bold text-xs"
                                    >=</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between"
                    >
                        <p class="text-indigo-500 text-xs font-semibold mb-1">
                            Air Humidity
                        </p>
                        <div
                            class="flex justify-between items-center space-x-1"
                        >
                            <h4
                                class="text-lg font-bold text-gray-800 tracking-tight"
                            >
                                {{ sensors.RH.toFixed(0) }}
                                <span
                                    class="text-[10px] font-normal text-gray-400"
                                    >%</span
                                >
                            </h4>
                            <div
                                class="w-3 h-3 shrink-0 flex items-center justify-center"
                            >
                                <img
                                    v-if="getTrendImage('RH')"
                                    :src="getTrendImage('RH')"
                                    class="w-full h-full object-contain"
                                />
                                <span
                                    v-else
                                    class="text-gray-400 font-bold text-xs"
                                    >=</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between"
                    >
                        <p class="text-indigo-500 text-xs font-semibold mb-1">
                            Oxygen Volume
                        </p>
                        <div
                            class="flex justify-between items-center space-x-1"
                        >
                            <h4
                                class="text-lg font-bold text-gray-800 tracking-tight"
                            >
                                {{ sensors.O2.toFixed(1) }}
                                <span
                                    class="text-[10px] font-normal text-gray-400"
                                    >%vol</span
                                >
                            </h4>
                            <div
                                class="w-3 h-3 shrink-0 flex items-center justify-center"
                            >
                                <img
                                    v-if="getTrendImage('O2')"
                                    :src="getTrendImage('O2')"
                                    class="w-full h-full object-contain"
                                />
                                <span
                                    v-else
                                    class="text-gray-400 font-bold text-xs"
                                    >=</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between"
                    >
                        <p class="text-indigo-500 text-xs font-semibold mb-1">
                            CO Volume
                        </p>
                        <div
                            class="flex justify-between items-center space-x-1"
                        >
                            <h4
                                class="text-lg font-bold text-gray-800 tracking-tight"
                            >
                                {{ sensors.CO.toFixed(2) }}
                                <span
                                    class="text-[10px] font-normal text-gray-400"
                                    >PPM</span
                                >
                            </h4>
                            <div
                                class="w-3 h-3 shrink-0 flex items-center justify-center"
                            >
                                <img
                                    v-if="getTrendImage('CO')"
                                    :src="getTrendImage('CO')"
                                    class="w-full h-full object-contain"
                                />
                                <span
                                    v-else
                                    class="text-gray-400 font-bold text-xs"
                                    >=</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-indigo-500 to-purple-600 p-4 rounded-xl shadow-sm flex items-center justify-between text-white"
                    >
                        <div>
                            <p class="text-[10px] font-bold opacity-75">
                                Active Nodes
                            </p>
                            <h4 class="text-xs font-black tracking-wide">
                                All Systems Live
                            </h4>
                        </div>
                        <span class="text-[8px] animate-ping font-mono">●</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
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

// --- STATE DATA SENSOR ---
const sensors = ref({
    airSpeed: 0,
    MRT: 0,
    Tdb: 0,
    Twb: 0,
    RH: 0,
    O2: 0,
    CO: 0,
});

const selectedFilter = ref("all");

const prevSensors = ref({
    airSpeed: 0,
    MRT: 0,
    Tdb: 0,
    Twb: 0,
    RH: 0,
    O2: 0,
    CO: 0,
});
const wbgtValue = ref(0);
const chartLabels = ref([]);
const wbgtHistory = ref([]);
const currentTime = ref("-- : -- : --");

// --- FUNGSI SANITASI DATA PAYLOAD MQTT ---
const safeParse = (val) => {
    const parsed = parseFloat(val);
    if (isNaN(parsed) || !isFinite(parsed)) {
        return 0.0;
    }
    return parsed;
};

// --- LOGIKA KALKULASI INDEKS WBGT INDOOR ---
const calculateWBGT = (s) => {
    const tdb = s.Tdb;
    const twb = s.Twb;
    const mrt = s.MRT;
    const v = s.airSpeed;

    let tg = mrt;
    if (v > 0.0) {
        tg = 0.4 * mrt + 0.6 * tdb;
    }

    return 0.7 * twb + 0.3 * tg;
};

// --- STATUS KLASIFIKASI BEBAN PANAS BERDASARKAN WBGT ---
const environmentStatus = computed(() => {
    const w = wbgtValue.value;
    if (w === 0) return { label: "Waiting Data...", color: "#9ca3af" };
    if (w <= 27.7)
        return {
            label: "Normal",
            color: "linear-gradient(135deg, #7cba52 50%, #4F7C31 100%)",
        };
    if (w > 27.7 && w <= 29.4)
        return {
            label: "Slightly Hot",
            color: "linear-gradient(135deg, #facc15 50%, #ca8a04 100%)",
        };
    if (w > 29.4 && w <= 31.0)
        return {
            label: "Warm/High Stress",
            color: "linear-gradient(135deg, #fb923c 50%, #ea580c 100%)",
        };
    return {
        label: "Extreme Hot",
        color: "linear-gradient(135deg, #ef4444 50%, #b91d1d 100%)",
    };
});

const getTrendImage = (key) => {
    const current = sensors.value[key];
    const previous = prevSensors.value[key];
    if (current > previous) return "/images/Up.png";
    else if (current < previous) return "/images/Down.png";
    else return "/images/Equal.png";
};

// --- LOGIKA REAL-TIME (LARAVEL ECHO) ---
let timeInterval;
onMounted(() => {
    timeInterval = setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString("id-ID", {
            hour12: false,
        });
    }, 1000);

    if (window.Echo) {
        window.Echo.channel("environment-monitor").listen(
            ".data.received",
            (e) => {
                // SINKRONISASI TREN: Salin data lama sebelum diganti
                prevSensors.value = { ...sensors.value };

                // Ekstrak objek pembungkus utama event data Laravel Broadcast
                const payload = e.data;

                if (!payload) {
                    console.error(
                        "[ECHO] Struktur payload event data tidak valid.",
                    );
                    return;
                }

                // Memasukkan data dengan proteksi fungsi safeParse dari properti payload
                sensors.value = {
                    airSpeed: safeParse(payload.airSpeed),
                    MRT: safeParse(payload.MRT),
                    Tdb: safeParse(payload.Tdb),
                    Twb: safeParse(payload.Twb),
                    RH: safeParse(payload.RH),
                    O2: safeParse(payload.O2),
                    CO: safeParse(payload.CO),
                };

                // Hitung WBGT Realtime tiap kali payload MQTT diterima
                const calculatedWbgt = calculateWBGT(sensors.value);
                wbgtValue.value = calculatedWbgt;

                const now = new Date().toLocaleTimeString([], {
                    hour: "2-digit",
                    minute: "2-digit",
                    second: "2-digit",
                });
                chartLabels.value.push(now);
                wbgtHistory.value.push(calculatedWbgt);

                if (chartLabels.value.length > 15) {
                    chartLabels.value.shift();
                    wbgtHistory.value.shift();
                }
            },
        );
    }
});

onUnmounted(() => {
    clearInterval(timeInterval);
});

// --- CHART DATA UNTUK WBGT INDEX ---
const wbgtLineData = computed(() => ({
    labels: [...chartLabels.value],
    datasets: [
        {
            label: "WBGT (°C)",
            borderColor: "#6366f1",
            backgroundColor: "rgba(99, 102, 241, 0.1)",
            borderWidth: 2.5,
            pointRadius: 3,
            tension: 0.35,
            fill: true,
            data: [...wbgtHistory.value],
        },
    ],
}));

const wbgtChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    scales: {
        y: {
            min: 15,
            max: 40,
            ticks: { stepSize: 5 },
        },
    },
    plugins: { legend: { display: false } },
};
</script>
