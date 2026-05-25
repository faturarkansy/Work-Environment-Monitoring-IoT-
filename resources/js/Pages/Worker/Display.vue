<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";

const props = defineProps({
    workerData: Object,
    history: Array,
});

const page = usePage();
const liveWorkerData = ref(props.workerData);

// --- DATA REFERENSI PAKAIAN (image_49c1c0.png) ---
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

// --- DATA REFERENSI AKTIVITAS (image_49c1a0.jpg) ---
const activityOptions = [
    { label: "Sleeping", met: 0.7, w_m2: 40 },
    { label: "Seated", met: 1.0, w_m2: 60 },
    { label: "Standing", met: 1.2, w_m2: 70 },
    { label: "Walking (0.89 m/s)", met: 2.0, w_m2: 115 },
    { label: "Writing", met: 1.0, w_m2: 60 },
    { label: "Lifting/Packing", met: 2.1, w_m2: 120 },
];

// --- LOGIKA REAL-TIME (LARAVEL ECHO) ---
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel("environment-monitor").listen(
            ".data.received",
            (e) => {
                liveWorkerData.value = e.data;
            },
        );
    }
});

// --- STATE INPUT USER ---
const selectedGarments = ref([{ value: 0 }]);
const selectedActivity = ref(activityOptions[1]); // Default: Seated
const isModalOpen = ref(false);

const addGarment = () => selectedGarments.value.push({ value: 0 });
const removeGarment = (index) => {
    if (selectedGarments.value.length > 1)
        selectedGarments.value.splice(index, 1);
};

const totalClo = computed(() => {
    return selectedGarments.value.reduce(
        (sum, item) => sum + parseFloat(item.value),
        0,
    );
});

// --- PERHITUNGAN PMV & PPD DINAMIS ---
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

    if (M === 0 || Icl === 0)
        return { pmv: 0, ppd: 0, ta, rh, v, tr, twb, o2, co };

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

// --- STATUS LINGKUNGAN DINAMIS ---
const environmentStatus = computed(() => {
    const p = calculatedMetrics.value.pmv;
    if (p <= 0.5 && p >= -0.5)
        return {
            label: "Normal",
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
    <Head title="My Work Status" />

    <AuthenticatedLayout>
        <div class="py-6 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-4 lg:px-8 space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <div class="flex flex-col space-y-3 lg:col-span-1">
                        <div
                            class="col-span-2 md:col-span-3 px-6 py-4 rounded-2xl shadow-md flex flex-col justify-between min-h-[140px]"
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
                                    <p class="text-xs opacity-70">
                                        PMV:
                                        {{ calculatedMetrics.pmv.toFixed(2) }} |
                                        PPD:
                                        {{ calculatedMetrics.ppd.toFixed(2) }}%
                                    </p>
                                </div>
                            </div>
                            <div
                                class="bg-white/90 text-sm py-1 px-4 rounded-full text-center mt-4"
                            >
                                <span
                                    class="font-mono font-bold text-lg text-gray-800"
                                    >{{ currentTime }} WIB</span
                                >
                            </div>
                        </div>

                        <button
                            @click="toggleModal"
                            class="w-full bg-black text-white text-xs font-bold py-3 px-4 rounded-xl hover:bg-gray-900 transition shadow-sm uppercase tracking-wider"
                        >
                            Show Sensors Details
                        </button>
                    </div>

                    <div
                        class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 lg:col-span-2 min-h-[233px] flex flex-col justify-between"
                    >
                        <div>
                            <h3
                                class="text-md font-bold text-gray-800 mb-4 tracking-tight"
                            >
                                Personal Adjustment
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label
                                        class="text-xs font-bold text-gray-700 block"
                                        >Clothing Layer (Clo)</label
                                    >
                                    <div
                                        v-for="(
                                            garment, index
                                        ) in selectedGarments"
                                        :key="index"
                                        class="flex gap-2 items-center"
                                    >
                                        <select
                                            v-model="garment.value"
                                            class="flex-1 rounded-lg border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500 py-2"
                                        >
                                            <option :value="0">
                                                Select Clothing
                                            </option>
                                            <option
                                                v-for="opt in garmentOptions"
                                                :key="opt.desc"
                                                :value="opt.clo"
                                            >
                                                {{ opt.desc }}
                                            </option>
                                        </select>
                                        <button
                                            @click="removeGarment(index)"
                                            :disabled="
                                                selectedGarments.length === 1
                                            "
                                            class="text-xs font-bold text-gray-800 hover:text-red-600 disabled:opacity-20 shrink-0"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                    <button
                                        @click="addGarment"
                                        class="text-xs font-bold text-gray-800 hover:underline flex items-center mt-1"
                                    >
                                        + Add more
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <label
                                        class="text-xs font-bold text-gray-700 block"
                                        >Current Activity (Met)</label
                                    >
                                    <select
                                        v-model="selectedActivity"
                                        class="w-full rounded-lg border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500 py-2"
                                    >
                                        <option :value="null" disabled>
                                            Select Activity
                                        </option>
                                        <option
                                            v-for="act in activityOptions"
                                            :key="act.label"
                                            :value="act"
                                        >
                                            {{ act.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-6 pt-4 border-t border-gray-100 flex justify-between text-xs text-gray-400"
                        >
                            <p class="flex items-baseline">
                                Total Insulation :
                                <span
                                    class="text-gray-800 font-bold text-lg ms-1.5 font-sans"
                                    >{{ totalClo.toFixed(2) }} Clo</span
                                >
                            </p>
                            <p class="flex items-baseline">
                                Metabolic Rate :
                                <span
                                    class="text-gray-800 font-bold text-lg ms-1.5 font-sans"
                                    >{{
                                        selectedActivity
                                            ? selectedActivity.w_m2
                                            : "0.00"
                                    }}
                                    w/m²</span
                                >
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="isModalOpen"
            class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-[100] backdrop-blur-sm"
        >
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
                <div
                    class="flex justify-between items-center border-b pb-3 mb-4"
                >
                    <h3 class="text-lg font-bold text-gray-800">
                        Environmental Sensors Report
                    </h3>
                    <span
                        class="text-xs bg-indigo-50 text-indigo-600 font-bold px-2.5 py-1 rounded-md"
                        >Unit ID: {{ liveWorkerData?.id || "?" }}</span
                    >
                </div>

                <div class="space-y-3.5 max-h-[400px] overflow-y-auto pr-1">
                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100"
                    >
                        <span class="text-gray-500 text-xs font-semibold"
                            >Kecepatan Udara</span
                        >
                        <div class="flex items-center space-x-2">
                            <span
                                class="font-mono font-black text-gray-800 text-sm"
                                >{{ calculatedMetrics.v.toFixed(2) }} m/s</span
                            >
                            <span class="text-red-400 font-black text-xs"
                                >↙</span
                            >
                        </div>
                    </div>

                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100"
                    >
                        <span class="text-gray-500 text-xs font-semibold"
                            >Suhu Basah</span
                        >
                        <div class="flex items-center space-x-2">
                            <span
                                class="font-mono font-black text-gray-800 text-sm"
                                >{{ calculatedMetrics.twb.toFixed(2) }} °C</span
                            >
                            <span class="text-gray-400 font-black text-xs"
                                >=</span
                            >
                        </div>
                    </div>

                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100"
                    >
                        <span class="text-gray-500 text-xs font-semibold"
                            >Suhu Kering</span
                        >
                        <div class="flex items-center space-x-2">
                            <span
                                class="font-mono font-black text-gray-800 text-sm"
                                >{{ calculatedMetrics.ta.toFixed(1) }} °C</span
                            >
                            <span class="text-gray-400 font-black text-xs"
                                >=</span
                            >
                        </div>
                    </div>

                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100"
                    >
                        <span class="text-gray-500 text-xs font-semibold"
                            >Suhu Radiasi (MRT)</span
                        >
                        <div class="flex items-center space-x-2">
                            <span
                                class="font-mono font-black text-gray-800 text-sm"
                                >{{ calculatedMetrics.tr.toFixed(2) }} °C</span
                            >
                            <span class="text-green-400 font-black text-xs"
                                >↗</span
                            >
                        </div>
                    </div>

                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100"
                    >
                        <span class="text-gray-500 text-xs font-semibold"
                            >Kelembapan Udara</span
                        >
                        <div class="flex items-center space-x-2">
                            <span
                                class="font-mono font-black text-gray-800 text-sm"
                                >{{ calculatedMetrics.rh.toFixed(0) }} %</span
                            >
                            <span class="text-green-400 font-black text-xs"
                                >↗</span
                            >
                        </div>
                    </div>

                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100"
                    >
                        <span class="text-gray-500 text-xs font-semibold"
                            >Volume Oksigen</span
                        >
                        <div class="flex items-center space-x-2">
                            <span
                                class="font-mono font-black text-gray-800 text-sm"
                                >{{
                                    calculatedMetrics.o2.toFixed(1)
                                }}
                                %vol</span
                            >
                            <span class="text-red-400 font-black text-xs"
                                >↙</span
                            >
                        </div>
                    </div>

                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100"
                    >
                        <span class="text-gray-500 text-xs font-semibold"
                            >Volume Gas (CO)</span
                        >
                        <div class="flex items-center space-x-2">
                            <span
                                class="font-mono font-black text-gray-800 text-sm"
                                >{{ calculatedMetrics.co.toFixed(0) }} PPM</span
                            >
                            <span class="text-red-400 font-black text-xs"
                                >↙</span
                            >
                        </div>
                    </div>
                </div>

                <button
                    @click="toggleModal"
                    class="mt-5 w-full bg-gray-900 text-white py-2.5 rounded-xl font-bold text-sm hover:bg-black transition"
                >
                    Close Report
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
