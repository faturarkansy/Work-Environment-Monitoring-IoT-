<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, nextTick } from "vue";
import axios from "axios";

const props = defineProps({
    workerData: Object,
    history: Array,
});

const page = usePage();
const liveWorkerData = ref(props.workerData);
const currentTime = ref("--:--:--");

// State Utama untuk Mengontrol Aktivitas Perhitungan & Penyimpanan Real-time
const isLogging = ref(false);

// State Pengontrol Bukanya Dropdown Baru
const isActivityDropdownOpen = ref(false);
const activeGarmentDropdownIndex = ref(null);

// --- DATA REFERENSI PAKAIAN ---
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

// --- DATA REFERENSI AKTIVITAS ---
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
    setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString("id-ID", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: false,
        });
    }, 1000);

    if (window.Echo) {
        window.Echo.channel("environment-monitor").listen(
            ".data.received",
            (e) => {
                liveWorkerData.value = e.data;

                if (isLogging.value) {
                    nextTick(() => {
                        sendWorkerMetricsToDatabase();
                    });
                }
            },
        );
    }
});

// --- STATE INPUT USER ---
const selectedGarments = ref([{ value: 0 }]);
const selectedActivity = ref(null);
const isModalOpen = ref(false);

const addGarment = () => selectedGarments.value.push({ value: 0 });
const removeGarment = (index) => {
    if (selectedGarments.value.length > 1) {
        selectedGarments.value.splice(index, 1);
        if (activeGarmentDropdownIndex.value === index) {
            activeGarmentDropdownIndex.value = null;
        }
    }
};

const getGarmentDesc = (cloValue) => {
    const found = garmentOptions.find(
        (opt) => opt.clo === parseFloat(cloValue),
    );
    return found ? found.desc : "Select Clothing";
};

const totalClo = computed(() => {
    return selectedGarments.value.reduce(
        (sum, item) => sum + parseFloat(item.value),
        0,
    );
});

// --- FUNGSI SAKELAR TOMBOL: INPUT / CANCEL ---
const toggleLogging = () => {
    if (!isLogging.value) {
        if (totalClo.value === 0) {
            alert("Harap pilih jenis insulasi pakaian (Clo) terlebih dahulu!");
            return;
        }
        if (!selectedActivity.value || selectedActivity.value.w_m2 === 0) {
            alert("Harap pilih jenis aktivitas kerja (Met) terlebih dahulu!");
            return;
        }

        isLogging.value = true;
        console.log("[SYSTEM] Pengiriman data realtime ke database DIMULAI.");

        if (liveWorkerData.value) {
            nextTick(() => {
                sendWorkerMetricsToDatabase();
            });
        }
    } else {
        isLogging.value = false;
        console.log(
            "[SYSTEM] Pengiriman data realtime ke database DIHENTIKAN.",
        );
    }
};

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

    if (!isLogging.value) {
        return { pmv: 0, ppd: 0, ta, rh, v, tr, twb, o2, co };
    }

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
    if (!isLogging.value) {
        return {
            label: "Waiting Data...",
            color: "linear-gradient(135deg, #9ca3af 50%, #6b7280 100%)",
        };
    }

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

// --- FUNGSI REKAM DATA PERSONAL WORKER KE DATABASE ---
const sendWorkerMetricsToDatabase = () => {
    if (!isLogging.value) return;

    const metrics = calculatedMetrics.value;
    const currentClo = totalClo.value;
    const currentMet = selectedActivity.value ? selectedActivity.value.w_m2 : 0;

    axios
        .post(route("worker.store-log"), {
            pmv: parseFloat(metrics.pmv.toFixed(4)),
            ppd: parseFloat(metrics.ppd.toFixed(4)),
            clothing_insulation: currentClo,
            activity_name: selectedActivity.value
                ? selectedActivity.value.label
                : "Unknown",
            activity_met: currentMet,
        })
        .then((response) => {
            console.log(
                "[AXIOS SUCCESS] Riwayat personal tersimpan di DB:",
                response.data.message,
            );
        })
        .catch((error) => {
            console.error("[AXIOS ERROR] Gagal menyimpan log:", error);
        });
};

const toggleModal = () => (isModalOpen.value = !isModalOpen.value);
</script>

<template>
    <Head title="My Work Status" />

    <AuthenticatedLayout>
        <div class="py-6 bg-gray-50 min-h-screen font-sans">
            <div class="max-w-7xl mx-auto px-6 sm:px-4 lg:px-8 space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
                    <div class="flex flex-col space-y-4 lg:col-span-1">
                        <div
                            class="px-6 py-4 rounded-2xl shadow-md flex flex-col justify-between min-h-[140px] transition-all duration-500"
                            :style="{ background: environmentStatus.color }"
                        >
                            <div class="text-white">
                                <p class="text-lg opacity-90 font-medium">
                                    Thermal Comfort Status :
                                </p>
                                <h2
                                    class="text-3xl font-bold mt-2 tracking-tight"
                                >
                                    {{ environmentStatus.label }}
                                </h2>
                                <div class="flex space-x-4 mt-1">
                                    <p class="text-base opacity-75 font-mono">
                                        PMV Index:
                                        {{ calculatedMetrics.pmv.toFixed(2) }} |
                                        PPD Index:
                                        {{ calculatedMetrics.ppd.toFixed(2) }}%
                                    </p>
                                </div>
                            </div>
                            <div
                                class="bg-white/90 text-sm py-1 px-4 rounded-full text-center backdrop-blur-sm mt-4"
                            >
                                <span
                                    class="font-mono font-bold text-lg text-gray-800"
                                >
                                    {{ currentTime }} WIB
                                </span>
                            </div>
                        </div>

                        <button
                            @click="toggleModal"
                            class="w-full bg-black text-white text-[14px] font-bold h-[40px] px-4 rounded-3xl hover:bg-gray-900 transition shadow-sm uppercase tracking-wider cursor-pointer"
                        >
                            Show Sensors Details
                        </button>
                    </div>

                    <div
                        class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 lg:col-span-2 min-h-[233px] flex flex-col justify-between shadow-[0_0_4px_#00000015]"
                    >
                        <div>
                            <h3
                                class="text-[17px] font-bold text-[#333] mb-5 tracking-tight font-sans"
                            >
                                Personal Adjustment
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-3">
                                    <label
                                        class="text-xs font-bold text-gray-700 block uppercase tracking-wider"
                                        >Clothing Layer (Clo)</label
                                    >
                                    <div
                                        v-for="(
                                            garment, index
                                        ) in selectedGarments"
                                        :key="index"
                                        class="flex gap-3 items-center relative"
                                    >
                                        <div class="flex-1 relative">
                                            <button
                                                type="button"
                                                :disabled="isLogging"
                                                @click="
                                                    activeGarmentDropdownIndex =
                                                        activeGarmentDropdownIndex ===
                                                        index
                                                            ? null
                                                            : index
                                                "
                                                class="w-full border border-[#ccd3dd] rounded-3xl px-[25px] h-[40px] transition-all duration-200 bg-white text-[14px] text-[#333] flex items-center justify-between focus:border-[#6a53f2] focus:outline-none cursor-pointer disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed"
                                                :class="{
                                                    'border-[#6a53f2] ring-2 ring-indigo-100':
                                                        activeGarmentDropdownIndex ===
                                                        index,
                                                }"
                                            >
                                                <span class="truncate">{{
                                                    getGarmentDesc(
                                                        garment.value,
                                                    )
                                                }}</span>
                                                <svg
                                                    class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0 ms-2"
                                                    :class="{
                                                        'rotate-180':
                                                            activeGarmentDropdownIndex ===
                                                            index,
                                                    }"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 9l-7 7-7-7"
                                                    />
                                                </svg>
                                            </button>

                                            <div
                                                v-if="
                                                    activeGarmentDropdownIndex ===
                                                    index
                                                "
                                                class="absoluteSub z-[60] absolute w-full mt-1.5 bg-white border border-gray-200 rounded-2xl shadow-xl max-h-[220px] overflow-y-auto py-1 animate-in fade-in slide-in-from-top-1 duration-150"
                                            >
                                                <div
                                                    v-for="opt in garmentOptions"
                                                    :key="opt.desc"
                                                    @click="
                                                        garment.value = opt.clo;
                                                        activeGarmentDropdownIndex =
                                                            null;
                                                    "
                                                    class="px-[25px] py-2 text-[14px] cursor-pointer transition-colors duration-150 flex items-center justify-between hover:bg-gray-50 hover:text-[#6a53f2]"
                                                    :class="
                                                        garment.value ===
                                                        opt.clo
                                                            ? 'bg-indigo-50 text-[#6a53f2] font-semibold'
                                                            : 'text-gray-700'
                                                    "
                                                >
                                                    {{ opt.desc }}
                                                    <span
                                                        class="text-xs font-mono text-gray-400"
                                                        >({{
                                                            opt.clo
                                                        }}
                                                        Clo)</span
                                                    >
                                                </div>
                                            </div>
                                        </div>

                                        <button
                                            @click="removeGarment(index)"
                                            :disabled="
                                                selectedGarments.length === 1 ||
                                                isLogging
                                            "
                                            class="text-xs font-bold text-gray-500 hover:text-red-600 disabled:opacity-20 shrink-0 font-sans transition-colors cursor-pointer"
                                        >
                                            Delete
                                        </button>
                                    </div>

                                    <button
                                        @click="addGarment"
                                        :disabled="isLogging"
                                        class="text-xs font-bold text-[#6a53f2] hover:text-[#583fe4] hover:underline flex items-center mt-1 disabled:opacity-20 transition-colors cursor-pointer"
                                    >
                                        + Add more clothing
                                    </button>
                                </div>

                                <div class="space-y-3 relative">
                                    <label
                                        class="text-xs font-bold text-gray-700 block uppercase tracking-wider"
                                        >Current Activity (Met)</label
                                    >
                                    <div class="relative">
                                        <button
                                            type="button"
                                            :disabled="isLogging"
                                            @click="
                                                isActivityDropdownOpen =
                                                    !isActivityDropdownOpen
                                            "
                                            class="w-full border border-[#ccd3dd] rounded-3xl px-[25px] h-[40px] transition-all duration-200 bg-white text-[14px] text-[#333] flex items-center justify-between focus:border-[#6a53f2] focus:outline-none cursor-pointer disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed"
                                            :class="{
                                                'border-[#6a53f2] ring-2 ring-indigo-100':
                                                    isActivityDropdownOpen,
                                            }"
                                        >
                                            <span>{{
                                                selectedActivity
                                                    ? selectedActivity.label
                                                    : "Select Activity"
                                            }}</span>
                                            <svg
                                                class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                                :class="{
                                                    'rotate-180':
                                                        isActivityDropdownOpen,
                                                }"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 9l-7 7-7-7"
                                                />
                                            </svg>
                                        </button>

                                        <div
                                            v-if="isActivityDropdownOpen"
                                            class="absolute z-[60] w-full mt-1.5 bg-white border border-gray-200 rounded-2xl shadow-xl overflow-hidden py-1 animate-in fade-in slide-in-from-top-1 duration-150"
                                        >
                                            <div
                                                v-for="act in activityOptions"
                                                :key="act.label"
                                                @click="
                                                    selectedActivity = act;
                                                    isActivityDropdownOpen = false;
                                                "
                                                class="px-[25px] py-2 text-[14px] cursor-pointer transition-colors duration-150 flex items-center justify-between hover:bg-gray-50 hover:text-[#6a53f2]"
                                                :class="
                                                    selectedActivity?.label ===
                                                    act.label
                                                        ? 'bg-indigo-50 text-[#6a53f2] font-semibold'
                                                        : 'text-gray-700'
                                                "
                                            >
                                                {{ act.label }}
                                                <span
                                                    class="text-xs font-mono text-gray-400"
                                                    >({{ act.w_m2 }} w/m²)</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-8 pt-5 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 font-sans"
                        >
                            <div class="space-y-3 w-full sm:w-auto">
                                <div
                                    class="flex items-center text-[14px] text-gray-600 font-medium"
                                >
                                    <span
                                        class="w-[120px] shrink-0 text-gray-500"
                                        >Total Insulation</span
                                    >
                                    <span class="text-gray-300 mx-1">:</span>
                                    <span
                                        class="text-gray-900 font-bold text-[15px] px-3 py-0.5 font-sans shadow-sm tracking-wide"
                                    >
                                        {{ totalClo.toFixed(2) }} Clo
                                    </span>
                                </div>
                                <div
                                    class="flex items-center text-[14px] text-gray-600 font-medium"
                                >
                                    <span
                                        class="w-[120px] shrink-0 text-gray-500"
                                        >Metabolic Rate</span
                                    >
                                    <span class="text-gray-300 mx-1">:</span>
                                    <span
                                        class="text-gray-900 font-bold text-[15px] px-3 py-0.5 font-sans shadow-sm tracking-wide"
                                    >
                                        {{
                                            selectedActivity
                                                ? selectedActivity.w_m2
                                                : "0.00"
                                        }}
                                        w/m²
                                    </span>
                                </div>
                            </div>

                            <div
                                class="w-full sm:w-auto shrink-0 flex sm:justify-end"
                            >
                                <button
                                    @click="toggleLogging"
                                    type="button"
                                    :class="
                                        isLogging
                                            ? 'bg-red-500 hover:bg-red-600'
                                            : 'bg-[#6a53f2] hover:bg-[#583fe4]'
                                    "
                                    class="w-full sm:w-[160px] h-[40px] shrink-0 flex-none text-white text-[14px] font-bold flex items-center justify-center rounded-3xl border-none transition-all duration-200 cursor-pointer shadow-md uppercase tracking-wider font-sans"
                                >
                                    {{ isLogging ? "Cancel" : "Input" }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="isModalOpen"
            class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-[100] backdrop-blur-sm"
        >
            <div
                class="bg-white rounded-[30px] max-w-md w-full p-6 shadow-2xl border border-gray-100 font-sans"
            >
                <div
                    class="flex justify-between items-center border-b pb-3 mb-4"
                >
                    <h3 class="text-lg font-bold text-[#333]">
                        Environmental Sensors Report
                    </h3>
                    <span
                        class="text-xs bg-indigo-50 text-indigo-600 font-bold px-2.5 py-1 rounded-md"
                    >
                        Unit ID: {{ liveWorkerData?.id || "?" }}
                    </span>
                </div>
                <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100 px-5"
                    >
                        <span
                            class="text-gray-500 text-xs font-semibold uppercase tracking-wider"
                            >Air Speed</span
                        >
                        <span class="font-mono font-black text-[#333] text-sm"
                            >{{ calculatedMetrics.v.toFixed(2) }} m/s</span
                        >
                    </div>
                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100 px-5"
                    >
                        <span
                            class="text-gray-500 text-xs font-semibold uppercase tracking-wider"
                            >Wet Temperature</span
                        >
                        <span class="font-mono font-black text-[#333] text-sm"
                            >{{ calculatedMetrics.twb.toFixed(2) }} °C</span
                        >
                    </div>
                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100 px-5"
                    >
                        <span
                            class="text-gray-500 text-xs font-semibold uppercase tracking-wider"
                            >Dry Temperature</span
                        >
                        <span class="font-mono font-black text-[#333] text-sm"
                            >{{ calculatedMetrics.ta.toFixed(1) }} °C</span
                        >
                    </div>
                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100 px-5"
                    >
                        <span
                            class="text-gray-500 text-xs font-semibold uppercase tracking-wider"
                            >Radiation Temperature</span
                        >
                        <span class="font-mono font-black text-[#333] text-sm"
                            >{{ calculatedMetrics.tr.toFixed(2) }} °C</span
                        >
                    </div>
                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100 px-5"
                    >
                        <span
                            class="text-gray-500 text-xs font-semibold uppercase tracking-wider"
                            >Air Humidity</span
                        >
                        <span class="font-mono font-black text-[#333] text-sm"
                            >{{ calculatedMetrics.rh.toFixed(0) }} %</span
                        >
                    </div>
                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100 px-5"
                    >
                        <span
                            class="text-gray-500 text-xs font-semibold uppercase tracking-wider"
                            >Oxygen Volume</span
                        >
                        <span class="font-mono font-black text-[#333] text-sm"
                            >{{ calculatedMetrics.o2.toFixed(1) }} %vol</span
                        >
                    </div>
                    <div
                        class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100 px-5"
                    >
                        <span
                            class="text-gray-500 text-xs font-semibold uppercase tracking-wider"
                            >Carbon Monoxide Volume</span
                        >
                        <span class="font-mono font-black text-[#333] text-sm"
                            >{{ calculatedMetrics.co.toFixed(0) }} PPM</span
                        >
                    </div>
                </div>
                <button
                    @click="toggleModal"
                    class="mt-5 w-full bg-gray-900 text-white py-2.5 rounded-2xl font-bold text-sm hover:bg-black transition cursor-pointer uppercase tracking-wider h-[40px]"
                >
                    Close Report
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
