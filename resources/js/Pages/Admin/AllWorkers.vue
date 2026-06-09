<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUnmounted } from "vue";
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
    latestLogs: Array, // Menerima data log awal dari AdminController
});

// --- STATE SLIDESHOW AUTOMATION ---
const currentIndex = ref(0);
let slideshowInterval = null;

// --- STATE HISTORI GRAFIK SLIDESHOW REAL-TIME ---
const chartLabels = ref([]);
const pmvHistory = ref([]);
const ppdHistory = ref([]);

// Mengambil data pekerja aktif saat ini berdasarkan perputaran indeks
const currentWorker = computed(() => {
    if (!props.workers || props.workers.length === 0) return null;
    return props.workers[currentIndex.value];
});

// Mengaitkan data sensor live berdasarkan unit_id pekerja yang sedang aktif
const liveWorkerData = ref(null);

// Sinkronisasi data sensor ketika giliran pekerja berganti + Reset Histori Grafik
const updateLiveSensorData = () => {
    if (!currentWorker.value) return;

    // Reset array grafik agar tidak tercampur dengan data pekerja sebelumnya
    chartLabels.value = [];
    pmvHistory.value = [];
    ppdHistory.value = [];

    // Cari log awal dari database props yang unit_id-nya sama dengan ID pekerja
    const matchedLog = props.latestLogs.find(
        (log) => log.unit_id == currentWorker.value.id,
    );
    liveWorkerData.value = matchedLog || null;

    // Masukkan entri poin awal jika log ditemukan
    if (liveWorkerData.value) {
        const sekarang = new Date().toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });
        chartLabels.value.push(sekarang);
        pmvHistory.value.push(safeParse(calculatedMetrics.value.pmv));
        ppdHistory.value.push(safeParse(calculatedMetrics.value.ppd));
    }
};

// --- FUNGSI SANITASI DATA PAYLOAD ---
const safeParse = (val) => {
    const parsed = parseFloat(val);
    if (isNaN(parsed) || !isFinite(parsed)) {
        return 0.0;
    }
    return parsed;
};

// --- LOGIKA REAL-TIME (LARAVEL ECHO) ---
onMounted(() => {
    updateLiveSensorData();

    // Jalankan perputaran otomatis slideshow setiap 10 detik
    slideshowInterval = setInterval(() => {
        if (props.workers && props.workers.length > 0) {
            currentIndex.value =
                (currentIndex.value + 1) % props.workers.length;
            updateLiveSensorData();
            console.log(
                "[SLIDESHOW] Beralih ke Pekerja:",
                currentWorker.value.name,
            );
        }
    }, 10000);

    // Mendengarkan transmisi data WebSocket MQTT secara real-time
    if (window.Echo) {
        window.Echo.channel("environment-monitor").listen(
            ".data.received",
            (e) => {
                const payload = e.data;
                if (!payload) return;

                // Jika data MQTT yang masuk memiliki ID Unit yang sama dengan pekerja yang aktif
                if (
                    currentWorker.value &&
                    payload.id == currentWorker.value.id
                ) {
                    liveWorkerData.value = {
                        airSpeed: safeParse(payload.airSpeed),
                        MRT: safeParse(payload.MRT),
                        Tdb: safeParse(payload.Tdb),
                        Twb: safeParse(payload.Twb),
                        RH: safeParse(payload.RH),
                        O2: safeParse(payload.O2),
                        CO: safeParse(payload.CO),
                        clo:
                            payload.clo !== undefined
                                ? safeParse(payload.clo)
                                : null,
                        met:
                            payload.met !== undefined
                                ? safeParse(payload.met)
                                : null,
                    };

                    // Push data ke grafik per detiknya secara realtime
                    const sekarang = new Date().toLocaleTimeString([], {
                        hour: "2-digit",
                        minute: "2-digit",
                        second: "2-digit",
                    });
                    chartLabels.value.push(sekarang);
                    chartLabels.value.push(sekarang);
                    pmvHistory.value.push(calculatedMetrics.value.pmv);
                    ppdHistory.value.push(calculatedMetrics.value.ppd);

                    // Batasi titik maksimum grafik 10 pergeseran
                    if (chartLabels.value.length > 10) {
                        chartLabels.value.shift();
                        pmvHistory.value.shift();
                        ppdHistory.value.shift();
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

// --- CLOTHING & METABOLIC FALLBACK KONDISIONAL (0 JIKA KOSONG) ---
const totalClo = computed(() => {
    if (
        liveWorkerData.value &&
        liveWorkerData.value.clo !== null &&
        liveWorkerData.value.clo !== undefined
    ) {
        return liveWorkerData.value.clo;
    }
    return 0.0; // Nilai default 0 yang ditampilkan ke UI Admin
});

const currentMetabolicRate = computed(() => {
    if (
        liveWorkerData.value &&
        liveWorkerData.value.met !== null &&
        liveWorkerData.value.met !== undefined
    ) {
        return liveWorkerData.value.met;
    }
    return 0.0; // Nilai default 0 yang ditampilkan ke UI Admin
});

// --- PERHITUNGAN PMV & PPD PEKERJA DINAMIS ---
const calculatedMetrics = computed(() => {
    if (!liveWorkerData.value)
        return {
            pmv: 0,
            ppd: 0,
            ta: 0,
            rh: 0,
            v: 0,
            tr: 0,
            twb: 0,
            o2: 0,
            co: 0,
        };

    const s = liveWorkerData.value;

    const ta = safeParse(s.Tdb !== undefined ? s.Tdb : s.tdb);
    const rh = safeParse(s.RH !== undefined ? s.RH : s.rh);
    const v = safeParse(s.airSpeed !== undefined ? s.airSpeed : s.air_speed);
    const tr = safeParse(s.MRT !== undefined ? s.MRT : s.mrt || ta);
    const twb = safeParse(s.Twb !== undefined ? s.Twb : s.twb);
    const o2 = safeParse(s.O2 !== undefined ? s.O2 : s.o2);
    const co = safeParse(s.CO !== undefined ? s.CO : s.co);

    // Untuk internal kalkulasi rumus, gunakan minimal 0.01 jika bernilai 0 agar fungsi matematika tidak rusak (division by zero)
    const Icl = totalClo.value === 0 ? 0.01 : totalClo.value;
    const M =
        currentMetabolicRate.value === 0 ? 40.0 : currentMetabolicRate.value; // Fallback ke batas terendah met (Sleeping/Rest) jika 0

    const fcl = Icl < 0.5 ? 1.0 + 0.2 * Icl : 1.05 + 0.1 * Icl;
    const pa =
        (rh / 100) * 0.6105 * Math.exp((17.27 * ta) / (ta + 237.3)) * 1000;
    let tcl = ta + (35.5 - ta) / (3.5 * Icl + 0.1);
    const hc = Math.max(
        2.38 * Math.pow(Math.abs(tcl - ta), 0.25),
        12.1 * Math.sqrt(v),
    );
    const ts = 0.303 * Math.exp(-0.036 * M) + 0.028;
    const radiation =
        3.96 *
        Math.pow(10, -8) *
        fcl *
        (Math.pow(tcl + 273, 4) - Math.pow(tr + 273, 4));
    const convection = fcl * hc * (tcl - ta);
    const evaporation = 3.05 * Math.pow(10, -3) * (5733 - 6.99 * M - pa);
    const sweat = 0.42 * (M - 58.15);
    const resp_latent = 1.7 * Math.pow(10, -5) * M * (5867 - pa);
    const resp_sensible = 0.0014 * M * (34 - ta);

    const L =
        M -
        evaporation -
        sweat -
        resp_latent -
        resp_sensible -
        radiation -
        convection;
    const pmv = ts * L;
    const ppd =
        100 -
        95 *
            Math.exp(-(0.03353 * Math.pow(pmv, 4) + 0.2179 * Math.pow(pmv, 2)));

    return { pmv, ppd, ta, rh, v, tr, twb, o2, co };
});

// --- STATUS GRADASI WARNA METRICS (BERDASARKAN NILAI PMV) ---
const environmentStatus = computed(() => {
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
        label: "Cold",
        color: "linear-gradient(135deg, #1e3a8a 50%, #172554 100%)",
    };
});

const toggleModal = () => (isModalOpen.value = !isModalOpen.value);

// --- CONFIG CHART OPTIONS ---
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
        <div class="p-6 bg-gray-100 h-auto">
            <div
                v-if="currentWorker"
                class="hidden lg:grid grid-cols-5 gap-4 auto-rows-min"
            >
                <div
                    class="col-span-2 row-span-1 bg-white px-6 py-1 rounded-2xl shadow-sm flex items-center self-start min-h-[35px]"
                >
                    <span class="text-black font-bold text-sm mr-4 shrink-0"
                        >Active Auto-Slideshow :</span
                    >
                    <div class="flex space-x-2 font-bold items-center">
                        <span
                            class="text-xs font-semibold px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full"
                        >
                            Pekerja {{ currentIndex + 1 }} dari
                            {{ workers.length }}
                        </span>
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
                            {{ calculatedMetrics.v.toFixed(2) }} m/s
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
                            {{ calculatedMetrics.twb.toFixed(2) }} &deg;C
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
                            {{ calculatedMetrics.ta.toFixed(1) }} &deg;C
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
                        <p
                            class="text-[10px] uppercase font-black opacity-75 tracking-wider"
                        >
                            Pekerja Dipantau :
                        </p>
                        <h2
                            class="text-2xl font-black mb-2 truncate border-b border-white/20 pb-1"
                        >
                            {{ currentWorker.name }}
                        </h2>
                        <p class="text-sm font-bold opacity-90">
                            Work Environment Status :
                        </p>
                        <h2 class="text-3xl font-bold mt-1">
                            {{ environmentStatus.label }}
                        </h2>
                        <div class="flex space-x-4 mt-2">
                            <p class="text-xs opacity-90 font-mono">
                                PMV Index:
                                {{ calculatedMetrics.pmv.toFixed(2) }}
                            </p>
                            <p class="text-xs opacity-90 font-mono">
                                PPD Index:
                                {{ calculatedMetrics.ppd.toFixed(2) }}%
                            </p>
                        </div>
                    </div>
                    <div
                        class="bg-white/20 text-sm py-1 px-4 rounded-xl text-center backdrop-blur-sm border border-white/10"
                    >
                        <span
                            class="font-sans font-bold text-xs text-white uppercase tracking-wider"
                            >Slideshow Mode (10s Loop)</span
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
                            {{ calculatedMetrics.tr.toFixed(2) }} &deg;C
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
                            {{ calculatedMetrics.rh.toFixed(0) }} %
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
                            {{ calculatedMetrics.o2.toFixed(1) }} %vol
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
                            <span class="text-[10px] text-gray-400 font-mono"
                                >Clo: {{ totalClo }} | Met:
                                {{ currentMetabolicRate }}</span
                            >
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
                            <span class="text-[10px] text-gray-400 font-mono"
                                >Clo: {{ totalClo }} | Met:
                                {{ currentMetabolicRate }}</span
                            >
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
                            {{ calculatedMetrics.co.toFixed(0) }} PPM
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
                Tidak ada data antrean pekerja yang aktif untuk disinkronisasi
                ke slideshow dashboard.
            </div>
        </div>

        <div
            v-if="isModalOpen"
            class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-[100] backdrop-blur-sm"
        >
            <div class="bg-white rounded-3xl max-w-sm w-full p-8 shadow-2xl">
                <h3 class="text-xl font-bold mb-6 text-gray-800 border-b pb-4">
                    Detailed Unit Report
                </h3>
                <div class="space-y-4" v-if="liveWorkerData">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 text-sm font-medium"
                            >Air Speed</span
                        >
                        <span class="font-mono font-bold text-indigo-600"
                            >{{ calculatedMetrics.v.toFixed(2) }} m/s</span
                        >
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 text-sm font-medium"
                            >Mean Radiant Temp</span
                        >
                        <span class="font-mono font-bold text-indigo-600"
                            >{{ calculatedMetrics.tr.toFixed(2) }} °C</span
                        >
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 text-sm font-medium"
                            >CO Level</span
                        >
                        <span class="font-mono font-bold text-indigo-600"
                            >{{ calculatedMetrics.co.toFixed(0) }} PPM</span
                        >
                    </div>
                </div>
                <button
                    @click="toggleModal"
                    class="mt-8 w-full bg-gray-900 text-white py-3 rounded-2xl font-bold"
                >
                    Close
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
