<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUnmounted } from "vue";

const props = defineProps({
    workers: Array,
    latestLogs: Array, // Menerima data log awal dari AdminController
});

// --- STATE SLIDESHOW AUTOMATION ---
const currentIndex = ref(0);
let slideshowInterval = null;

// Mengambil data pekerja aktif saat ini berdasarkan perputaran indeks
const currentWorker = computed(() => {
    if (!props.workers || props.workers.length === 0) return null;
    return props.workers[currentIndex.value];
});

// Mengaitkan data sensor live berdasarkan unit_id pekerja yang sedang aktif
const liveWorkerData = ref(null);

// Sinkronisasi data sensor ketika giliran pekerja berganti
const updateLiveSensorData = () => {
    if (!currentWorker.value) return;

    // Cari log awal dari database props yang unit_id-nya sama dengan ID pekerja
    const matchedLog = props.latestLogs.find(
        (log) => log.unit_id == currentWorker.value.id,
    );
    liveWorkerData.value = matchedLog || null;
};

// --- LOGIKA REAL-TIME (LARAVEL ECHO) ---
onMounted(() => {
    updateLiveSensorData();

    // Jalankan perputaran otomatis slideshow setiap 10 detik (10000 ms)
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

    // Tetap mendengarkan transmisi data WebSocket MQTT secara real-time
    if (window.Echo) {
        window.Echo.channel("environment-monitor").listen(
            ".data.received",
            (e) => {
                // Jika data MQTT yang masuk memiliki ID Unit yang sama dengan pekerja yang sedang tampil
                if (
                    currentWorker.value &&
                    e.data.id == currentWorker.value.id
                ) {
                    console.log(
                        "[LIVE UPDATE] Memperbarui data sensor teraktif untuk:",
                        currentWorker.value.name,
                    );
                    liveWorkerData.value = e.data;
                }
            },
        );
    }
});

onUnmounted(() => {
    // Hapus interval agar tidak terjadi memory leak saat admin keluar halaman
    if (slideshowInterval) clearInterval(slideshowInterval);
});

// --- DATA REFERENSI REKAYASA INSULASI THERMAL (READ ONLY) ---
const garmentOptions = [
    { desc: "Men's briefs", clo: 0.04 },
    { desc: "T-shirt", clo: 0.08 },
    { desc: "Ankle-Length athletic socks", clo: 0.02 },
    { desc: "Sandals", clo: 0.02 },
    { desc: "Sleeveless shirt", clo: 0.12 },
    { desc: "Short sleeve dresses", clo: 0.19 },
    { desc: "Straight trousers (thin)", clo: 0.15 },
    { desc: "Straight trousers (thick)", clo: 0.24 },
    { desc: "Coveralls", clo: 0.49 },
    { desc: "Long sleeve shirt (thick)", clo: 0.36 },
];

const activityOptions = [
    { label: "Sleeping", met: 0.7, w_m2: 40 },
    { label: "Seated", met: 1.0, w_m2: 60 },
    { label: "Standing", met: 1.2, w_m2: 70 },
    { label: "Walking (0.89 m/s)", met: 2.0, w_m2: 115 },
    { label: "Writing", met: 1.0, w_m2: 60 },
    { label: "Lifting/Packing", met: 2.1, w_m2: 120 },
];

// Simulasi nilai input statis pekerja demi kalkulasi PMV Admin slideshow (Default normal)
const totalClo = computed(() => 0.45); // Representasi pakaian standar kerja umum (Kaos + Celana panjang tipis)
const selectedActivity = computed(() => activityOptions[1]); // Default: Seated (60 w/m2)
const isModalOpen = ref(false);

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
    const parseVal = (val) => {
        if (typeof val === "number") return val;
        if (!val) return 0;
        const parts = val.toString().split(",");
        return parseFloat(parts[parts.length - 1].trim()) || 0;
    };

    const ta = parseVal(s.tdb || s.Tdb);
    const rh = parseVal(s.rh || s.RH);
    const v = parseVal(s.air_speed || s.airSpeed);
    const tr = parseVal(s.mrt || s.MRT || ta);
    const twb = parseVal(s.twb || s.Twb);
    const o2 = parseVal(s.o2 || s.O2);
    const co = parseVal(s.co || s.CO);

    const M = selectedActivity.value.w_m2;
    const Icl = totalClo.value;

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

// --- STATUS GRADASI WARNA METRICS ---
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
</script>

<template>
    <Head title="All Workers Monitor" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-bold text-xl text-gray-800 leading-tight">
                    Slideshow Real-time Monitoring
                </h2>
                <span
                    class="text-xs font-semibold px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full"
                >
                    Pekerja ke {{ currentIndex + 1 }} dari {{ workers.length }}
                </span>
            </div>
        </template>

        <div class="py-6 bg-gray-100 min-h-screen">
            <div
                v-if="currentWorker"
                class="max-w-7xl mx-auto sm:px-4 lg:px-8 space-y-6"
            >
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <div class="flex flex-col space-y-3 lg:col-span-1">
                        <div
                            class="px-6 py-6 rounded-3xl shadow-sm flex flex-col justify-between transition-all duration-500 min-h-[175px]"
                            :style="{ background: environmentStatus.color }"
                        >
                            <div class="text-white">
                                <p
                                    class="text-xs uppercase font-black opacity-75 tracking-wider"
                                >
                                    Pekerja Aktif Dipantau :
                                </p>
                                <h2
                                    class="text-2xl font-black mt-0.5 mb-2 leading-none border-b border-white/20 pb-2 truncate"
                                >
                                    {{ currentWorker.name }}
                                </h2>

                                <p class="text-xs font-medium opacity-85">
                                    Thermal Comfort Status :
                                </p>
                                <h3
                                    class="text-3xl font-black mt-0.5 tracking-tight"
                                >
                                    {{ environmentStatus.label }}
                                </h3>
                                <div
                                    class="flex space-x-4 mt-2 text-xs opacity-75 font-mono"
                                >
                                    <p>
                                        PMV :
                                        {{ calculatedMetrics.pmv.toFixed(2) }}
                                    </p>
                                    <p>
                                        PPD :
                                        {{ calculatedMetrics.ppd.toFixed(2) }}%
                                    </p>
                                </div>
                            </div>

                            <div
                                class="bg-white/20 border border-white/20 text-center py-1 rounded-xl mt-4"
                            >
                                <span class="font-mono text-xs text-white"
                                    >Auto-Switching Area Monitor</span
                                >
                            </div>
                        </div>

                        <button
                            @click="toggleModal"
                            class="w-full bg-indigo-600 text-white text-xs font-bold py-3 px-4 rounded-xl hover:bg-indigo-700 transition shadow-sm uppercase tracking-wider"
                        >
                            Show Sensors Details (Unit {{ currentWorker.id }})
                        </button>
                    </div>

                    <div
                        class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 lg:col-span-2 min-h-[233px] flex flex-col justify-between"
                    >
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <h3
                                    class="text-md font-bold text-gray-800 tracking-tight"
                                >
                                    Personal Adjustment Info
                                </h3>
                                <span
                                    class="text-[10px] uppercase font-bold tracking-widest bg-gray-100 px-2 py-0.5 rounded text-gray-500"
                                    >Read-Only Mode</span
                                >
                            </div>
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-6 opacity-75 pointer-events-none"
                            >
                                <div class="space-y-2">
                                    <label
                                        class="text-xs font-bold text-gray-500 block"
                                        >Clothing Layer Layer</label
                                    >
                                    <select
                                        class="w-full rounded-lg border-gray-300 text-xs bg-gray-50"
                                    >
                                        <option>
                                            Pakaian Standar Lapangan (0.45 Clo)
                                        </option>
                                    </select>
                                    <p
                                        class="text-[10px] text-gray-400 italic mt-1"
                                    >
                                        *Nilai input dinamis diisolasi pada akun
                                        pekerja.
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <label
                                        class="text-xs font-bold text-gray-500 block"
                                        >Current Activity Layer</label
                                    >
                                    <select
                                        class="w-full rounded-lg border-gray-300 text-xs bg-gray-50"
                                    >
                                        <option>
                                            {{ selectedActivity.label }} ({{
                                                selectedActivity.met
                                            }}
                                            met)
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-6 pt-4 border-t border-gray-100 flex justify-between text-xs text-gray-400"
                        >
                            <p class="flex items-baseline">
                                Assumed Insulation :
                                <span
                                    class="text-gray-800 font-bold text-lg ms-1.5 font-sans"
                                    >{{ totalClo.toFixed(2) }} Clo</span
                                >
                            </p>
                            <p class="flex items-baseline">
                                Assumed Metabolic :
                                <span
                                    class="text-gray-800 font-bold text-lg ms-1.5 font-sans"
                                    >{{ selectedActivity.w_m2 }} w/m²</span
                                >
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-4"
                >
                    <div
                        class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between min-h-[105px]"
                    >
                        <p class="text-indigo-500 text-[11px] font-bold">
                            Kecepatan Udara
                        </p>
                        <div class="flex justify-between items-baseline mt-1">
                            <h3 class="text-2xl font-black text-gray-800">
                                {{ calculatedMetrics.v.toFixed(2) }}
                                <span class="text-xs font-normal text-gray-500"
                                    >m/s</span
                                >
                            </h3>
                            <span class="text-red-400 font-black text-sm"
                                >↙</span
                            >
                        </div>
                    </div>
                    <div
                        class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between min-h-[105px]"
                    >
                        <p class="text-indigo-500 text-[11px] font-bold">
                            Suhu Basah
                        </p>
                        <div class="flex justify-between items-baseline mt-1">
                            <h3 class="text-2xl font-black text-gray-800">
                                {{ calculatedMetrics.twb.toFixed(2) }}
                                <span class="text-xs font-normal text-gray-500"
                                    >C</span
                                >
                            </h3>
                            <span class="text-gray-400 font-black text-sm"
                                >=</span
                            >
                        </div>
                    </div>
                    <div
                        class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between min-h-[105px]"
                    >
                        <p class="text-indigo-500 text-[11px] font-bold">
                            Suhu Kering
                        </p>
                        <div class="flex justify-between items-baseline mt-1">
                            <h3 class="text-2xl font-black text-gray-800">
                                {{ calculatedMetrics.ta.toFixed(1) }}
                                <span class="text-xs font-normal text-gray-500"
                                    >C</span
                                >
                            </h3>
                            <span class="text-gray-400 font-black text-sm"
                                >=</span
                            >
                        </div>
                    </div>
                    <div
                        class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between min-h-[105px]"
                    >
                        <p class="text-indigo-500 text-[11px] font-bold">
                            Suhu Radiasi (MRT)
                        </p>
                        <div class="flex justify-between items-baseline mt-1">
                            <h3 class="text-2xl font-black text-gray-800">
                                {{ calculatedMetrics.tr.toFixed(2) }}
                                <span class="text-xs font-normal text-gray-500"
                                    >C</span
                                >
                            </h3>
                            <span class="text-green-400 font-black text-sm"
                                >↗</span
                            >
                        </div>
                    </div>
                    <div
                        class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between min-h-[105px]"
                    >
                        <p class="text-indigo-500 text-[11px] font-bold">
                            Kelembapan Udara
                        </p>
                        <div class="flex justify-between items-baseline mt-1">
                            <h3 class="text-2xl font-black text-gray-800">
                                {{ calculatedMetrics.rh.toFixed(0) }}
                                <span class="text-xs font-normal text-gray-500"
                                    >%</span
                                >
                            </h3>
                            <span class="text-green-400 font-black text-sm"
                                >↗</span
                            >
                        </div>
                    </div>
                    <div
                        class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between min-h-[105px]"
                    >
                        <p class="text-indigo-500 text-[11px] font-bold">
                            Volume Oksigen
                        </p>
                        <div class="flex justify-between items-baseline mt-1">
                            <h3 class="text-2xl font-black text-gray-800">
                                {{ calculatedMetrics.o2.toFixed(1) }}
                                <span class="text-xs font-normal text-gray-500"
                                    >%vol</span
                                >
                            </h3>
                            <span class="text-red-400 font-black text-sm"
                                >↙</span
                            >
                        </div>
                    </div>
                    <div
                        class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between min-h-[105px]"
                    >
                        <p class="text-indigo-500 text-[11px] font-bold">
                            Volume Gas (CO)
                        </p>
                        <div class="flex justify-between items-baseline mt-1">
                            <h3 class="text-2xl font-black text-gray-800">
                                {{ calculatedMetrics.co.toFixed(0) }}
                                <span class="text-xs font-normal text-gray-500"
                                    >PPM</span
                                >
                            </h3>
                            <span class="text-red-400 font-black text-sm"
                                >↙</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-12 text-gray-500">
                Tidak ada data pekerja yang terdaftar untuk dikalkulasi.
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
