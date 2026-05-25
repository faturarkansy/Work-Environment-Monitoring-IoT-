<template>
    <Head title="Dashboard Monitor" />

    <AuthenticatedLayout>
        <div class="p-6 bg-gray-100 h-auto">

            <div class="hidden lg:grid grid-cols-5 gap-4 auto-rows-min">
                
                <div class="col-span-2 row-span-1 bg-white px-6 py-1 rounded-2xl shadow-sm flex items-center self-start min-h-[30px]">
                    <span class="text-black font-bold text-md mr-6 shrink-0">Filtered by :</span>
                    <div class="flex space-x-4 font-bold items-center">
                        <button class="bg-indigo-500 text-white px-6 py-1.5 rounded-full text-sm transition shadow-sm">All</button>
                        <button class="text-gray-600 px-3 py-1.5 text-sm hover:text-indigo-500">Unit 1</button>
                        <button class="text-gray-600 px-3 py-1.5 text-sm hover:text-indigo-500">Unit 2</button>
                        <button class="text-gray-600 px-3 py-1.5 text-sm hover:text-indigo-500">Unit 3</button>
                    </div>
                </div>

                <div class="col-span-1 row-span-2 bg-white p-5 py-4 rounded-xl shadow-sm">
                    <p class="text-indigo-500 text-md font-semibold mb-2">Air Speed</p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">{{ sensors.airSpeed }} m/s</h3>
                        <div class="w-4 h-4">
                            <img v-if="getTrendImage('airSpeed')" :src="getTrendImage('airSpeed')" class="w-full h-full object-contain" />
                            <span v-else class="text-gray-400 font-bold text-xl">=</span>
                        </div>
                    </div>
                </div>

                <div class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm">
                    <p class="text-indigo-500 text-md font-semibold mb-2">Wet Temperature</p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">{{ sensors.Twb }} &deg;C</h3>
                        <div class="w-4 h-4">
                            <img v-if="getTrendImage('Twb')" :src="getTrendImage('Twb')" class="w-full h-full object-contain" />
                            <span v-else class="text-gray-400 font-bold text-xl">=</span>
                        </div>
                    </div>
                </div>

                <div class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm">
                    <p class="text-indigo-500 text-md font-semibold mb-2">Dry Temperature</p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">{{ sensors.Tdb }} &deg;C</h3>
                        <div class="w-4 h-4">
                            <img v-if="getTrendImage('Tdb')" :src="getTrendImage('Tdb')" class="w-full h-full object-contain" />
                            <span v-else class="text-gray-400 font-bold text-xl">=</span>
                        </div>
                    </div>
                </div>

                <div class="col-span-2 row-span-3 px-6 py-4 rounded-2xl shadow-md flex flex-col justify-between transition-all duration-500" 
                     :style="{ background: environmentStatus.color }">
                    <div class="text-white">
                        <p class="text-lg font-bold text-white opacity-90">Work Environment Status :</p>
                        <h2 class="text-3xl font-bold mt-2">{{ environmentStatus.label }}</h2>
                        <div class="flex space-x-4 mt-1">
                            <p class="text-xs opacity-80">PMV Index: {{ pmvValue.toFixed(2) }}</p>
                            <p class="text-xs opacity-80">PPD Index: {{ ppdValue.toFixed(2) }}%</p>
                        </div>
                    </div>
                    <div class="bg-white text-sm py-1 px-4 rounded-xl text-center backdrop-blur-sm">
                        <span class="font-mono font-bold text-lg text-gray-800">{{ currentTime }} WIB</span>
                    </div>
                </div>

                <div class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm">
                    <p class="text-indigo-500 text-md font-semibold mb-2">Radiation Temperature</p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">{{ sensors.MRT }} &deg;C</h3>
                        <div class="w-4 h-4">
                            <img v-if="getTrendImage('MRT')" :src="getTrendImage('MRT')" class="w-full h-full object-contain" />
                            <span v-else class="text-gray-400 font-bold text-xl">=</span>
                        </div>
                    </div>
                </div>

                <div class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm">
                    <p class="text-indigo-500 text-md font-semibold mb-2">Air Humidity</p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">{{ sensors.RH }} %</h3>
                        <div class="w-4 h-4">
                            <img v-if="getTrendImage('RH')" :src="getTrendImage('RH')" class="w-full h-full object-contain" />
                            <span v-else class="text-gray-400 font-bold text-xl">=</span>
                        </div>
                    </div>
                </div>

                <div class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm">
                    <p class="text-indigo-500 text-md font-semibold mb-2">Oxygen Volume</p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">{{ sensors.O2 }} %vol</h3>
                        <div class="w-4 h-4">
                            <img v-if="getTrendImage('O2')" :src="getTrendImage('O2')" class="w-full h-full object-contain" />
                            <span v-else class="text-gray-400 font-bold text-xl">=</span>
                        </div>
                    </div>
                </div>

                <div class="col-span-2 row-span-5 bg-white p-5 rounded-2xl shadow-sm">
                    <h3 class="text-gray-800 text-md font-bold mb-4">PMV Movement Index</h3>
                    <div class="h-64">
                        <Line :data="pmvLineData" :options="pmvChartOptions" />
                    </div>
                </div>

                <div class="col-span-2 row-span-5 bg-white p-5 rounded-2xl shadow-sm">
                    <h3 class="text-gray-800 text-md font-bold mb-4">PPD Movement Index (%)</h3>
                    <div class="h-64">
                        <Line :data="ppdLineData" :options="ppdChartOptions" />
                    </div>
                </div>

                <div class="col-span-1 row-span-2 bg-white p-5 rounded-xl shadow-sm">
                    <p class="text-indigo-500 text-md font-semibold mb-2">Carbon Monocside Volume</p>
                    <div class="flex justify-between items-center">
                        <h3 class="text-3xl font-bold">{{ sensors.CO || 0 }} PPM</h3>
                        <div class="w-4 h-4">
                            <img v-if="getTrendImage('CO')" :src="getTrendImage('CO')" class="w-full h-full object-contain" />
                            <span v-else class="text-gray-400 font-bold text-xl">=</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:hidden grid grid-cols-2 md:grid-cols-3 gap-4 auto-rows-min">
                <div class="col-span-2 md:col-span-3 px-6 py-4 rounded-2xl shadow-md flex flex-col justify-between min-h-[140px]" :style="{ background: environmentStatus.color }">
                    <div class="text-white">
                        <p class="text-lg opacity-90">Status Lingkungan Kerja :</p>
                        <h2 class="text-3xl font-bold mt-2">{{ environmentStatus.label }}</h2>
                        <div class="flex space-x-4">
                            <p class="text-xs opacity-70">PMV: {{ pmvValue.toFixed(2) }} | PPD: {{ ppdValue.toFixed(2) }}%</p>
                        </div>
                    </div>
                    <div class="bg-white/90 text-sm py-1 px-4 rounded-full text-center mt-4">
                        <span class="font-mono font-bold text-lg text-gray-800">{{ currentTime }} WIB</span>
                    </div>
                </div>

                <div class="col-span-2 md:col-span-3 bg-white p-5 rounded-2xl shadow-sm space-y-8">
                    <div>
                        <h3 class="text-gray-800 text-xs font-bold mb-2">PMV Index</h3>
                        <div class="h-40">
                             <Line :data="pmvLineData" :options="pmvChartOptions" />
                        </div>
                    </div>
                    <div>
                        <h3 class="text-gray-800 text-xs font-bold mb-2">PPD Index (%)</h3>
                        <div class="h-40">
                             <Line :data="ppdLineData" :options="ppdChartOptions" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Line } from 'vue-chartjs';
import { 
    Chart as ChartJS, Title, Tooltip, Legend, 
    CategoryScale, LinearScale, PointElement, LineElement, Filler 
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, CategoryScale, LinearScale, PointElement, LineElement, Filler);

// --- STATE DATA SENSOR ---
const sensors = ref({ airSpeed: 0, MRT: 0, Tdb: 0, Twb: 0, RH: 0, O2: 0, CO: 0 });
const prevSensors = ref({ airSpeed: 0, MRT: 0, Tdb: 0, Twb: 0, RH: 0, O2: 0, CO: 0 }); 
const pmvValue = ref(0);
const ppdValue = ref(0); 
const chartLabels = ref([]);
const pmvHistory = ref([]);
const ppdHistory = ref([]);
const currentTime = ref("-- : -- : --");

// --- LOGIKA PERHITUNGAN PMV (FANGER) ---
const calculatePMV = (s) => {
    const M = 97.44; const W = 0; const Icl = 0.4;
    const ta = s.Tdb; const tr = s.MRT; const rh = s.RH; const v = s.airSpeed;
    const fcl = Icl < 0.5 ? 1.0 + 0.2 * Icl : 1.05 + 0.1 * Icl;
    const pa = (rh / 100) * 0.6105 * Math.exp((17.27 * ta) / (ta + 237.3)) * 1000;
    let tcl = ta + (35.5 - ta) / (3.5 * Icl + 0.1); 
    const hc = Math.max(2.38 * Math.pow(Math.abs(tcl - ta), 0.25), 12.1 * Math.sqrt(v));
    const ts = 0.303 * Math.exp(-0.036 * M) + 0.028;
    const radiation = 3.96 * Math.pow(10, -8) * fcl * (Math.pow(tcl + 273, 4) - Math.pow(tr + 273, 4));
    const convection = fcl * hc * (tcl - ta);
    const evaporation = 3.05 * Math.pow(10, -3) * (5733 - 6.99 * (M - W) - pa);
    const resp_sensible = 0.0014 * M * (34 - ta);
    const resp_latent = 1.7 * Math.pow(10, -5) * M * (5867 - pa);
    const sweat = 0.42 * (M - W - 58.15);
    const L = (M - W) - evaporation - sweat - resp_latent - resp_sensible - radiation - convection;
    return ts * L;
};

// --- LOGIKA PERHITUNGAN PPD ---
const calculatePPD = (pmv) => {
    const exponent = -(0.03353 * Math.pow(pmv, 4) + 0.2179 * Math.pow(pmv, 2));
    return 100 - 95 * Math.exp(exponent);
};

// --- STATUS LINGKUNGAN DINAMIS ---
const environmentStatus = computed(() => {
    const p = pmvValue.value;
    if (p <= 0.5 && p >= -0.5) return { label: 'Normal', color: 'linear-gradient(135deg, #7cba52 50%, #4F7C31 100%)' };
    if (p > 0.5 && p <= 1.5) return { label: 'Slightly Warm', color: 'linear-gradient(135deg, #facc15 50%, #ca8a04 100%)' };
    if (p > 1.5 && p <= 2.5) return { label: 'Warm', color: 'linear-gradient(135deg, #fb923c 50%, #ea580c 100%)' };
    if (p > 2.5) return { label: 'Hot', color: 'linear-gradient(135deg, #ef4444 50%, #b91d1d 100%)' };
    if (p < -0.5 && p >= -1.5) return { label: 'Slightly Cool', color: 'linear-gradient(135deg, #60a5fa 50%, #2563eb 100%)' };
    if (p < -1.5 && p >= -2.5) return { label: 'Cool', color: 'linear-gradient(135deg, #3b82f6 50%, #1d4ed8 100%)' };
    return { label: 'Cold', color: 'linear-gradient(135deg, #1e3a8a 50%, #172554 100%)' };
});

const getTrendImage = (key) => {
    const current = sensors.value[key];
    const previous = prevSensors.value[key];
    if (current > previous) return '/images/Up.png';
    else if (current < previous) return '/images/Down.png';
    else return '/images/Equal.png';
};

// --- LOGIKA REAL-TIME ---
let timeInterval;
onMounted(() => {
    // TIMER UNTUK JAM REAL-TIME
    timeInterval = setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString('id-ID', { hour12: false });
    }, 1000);

    if (window.Echo) {
        window.Echo.channel('environment-monitor')
            .listen('.data.received', (e) => {
                prevSensors.value = { ...sensors.value };
                sensors.value = e.data;

                // Update PMV & PPD
                const calculatedPmv = calculatePMV(e.data);
                pmvValue.value = calculatedPmv;
                ppdValue.value = calculatePPD(calculatedPmv);

                const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                chartLabels.value.push(now);
                pmvHistory.value.push(calculatedPmv);
                ppdHistory.value.push(ppdValue.value);

                if (chartLabels.value.length > 15) {
                    chartLabels.value.shift();
                    pmvHistory.value.shift();
                    ppdHistory.value.shift();
                }
            });
    }
});

onUnmounted(() => {
    clearInterval(timeInterval);
});

// --- CHART DATA & OPTIONS ---
const pmvLineData = computed(() => ({
    labels: [...chartLabels.value],
    datasets: [{ label: 'PMV', borderColor: '#6366f1', borderWidth: 2, pointRadius: 2, tension: 0.4, data: [...pmvHistory.value] }]
}));

const ppdLineData = computed(() => ({
    labels: [...chartLabels.value],
    datasets: [{ label: 'PPD', borderColor: '#f43f5e', borderWidth: 2, pointRadius: 2, tension: 0.4, data: [...ppdHistory.value] }]
}));

const pmvChartOptions = { 
    responsive: true, maintainAspectRatio: false, animation: false,
    scales: { y: { min: -3, max: 3, ticks: { stepSize: 1 } } },
    plugins: { legend: { display: false } }
};

const ppdChartOptions = { 
    responsive: true, maintainAspectRatio: false, animation: false,
    scales: { y: { min: 0, max: 100, ticks: { stepSize: 20 } } },
    plugins: { legend: { display: false } }
};
</script>