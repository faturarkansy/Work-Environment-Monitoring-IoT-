<script setup>
import { ref, onMounted, computed } from 'vue'; // Tambahkan computed
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Bar, Line } from 'vue-chartjs';
import { 
    Chart as ChartJS, Title, Tooltip, Legend, BarElement, 
    CategoryScale, LinearScale, PointElement, LineElement, Filler 
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, Filler);

// --- STATE DATA SENSOR ---
const sensors = ref({ airSpeed: 0, MRT: 0, Tdb: 0, Twb: 0, RH: 0, O2: 0 });
const chartLabels = ref([]);
const tempHistory = ref([]);
const humidityHistory = ref([]);

// --- GUNAKAN COMPUTED UNTUK GRAFIK (ANTI-CRASH) ---
const lineData = computed(() => ({
    labels: [...chartLabels.value],
    datasets: [{ 
        label: 'Suhu Kering (Tdb) °C', 
        borderColor: '#f87171', 
        backgroundColor: 'rgba(248, 113, 113, 0.2)',
        fill: true,
        data: [...tempHistory.value] // Gunakan spread agar ChartJS menerima array baru
    }]
}));

const barData = computed(() => ({
    labels: [...chartLabels.value],
    datasets: [{ 
        label: 'Kelembapan (RH) %', 
        backgroundColor: '#3b82f6', 
        data: [...humidityHistory.value]
    }]
}));

const chartOptions = { 
    responsive: true, 
    maintainAspectRatio: false,
    animation: false // Matikan animasi sementara untuk memastikan performa stabil
};

// --- LOGIKA REAL-TIME ---
onMounted(() => {
    console.log("=== Dashboard Aktif - Menunggu Data MQTT ===");

    window.Echo.channel('environment-monitor')
        .listen('.data.received', (e) => {
            console.log("Data MQTT Diterima:", e.data);
            
            // 1. Update kartu angka (Ini yang membuat angka di kotak berubah)
            sensors.value = e.data;

            // 2. Update Label Waktu
            const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            
            // 3. Masukkan data ke array histori
            chartLabels.value.push(now);
            tempHistory.value.push(e.data.Tdb);
            humidityHistory.value.push(e.data.RH);

            // 4. Batasi histori (maks 10 data) agar tidak berat
            if (chartLabels.value.length > 10) {
                chartLabels.value.shift();
                tempHistory.value.shift();
                humidityHistory.value.shift();
            }
            
            // Tidak perlu lagi: lineData.value = { ... } karena sudah pakai computed!
        });
});
</script>

<template>
    <Head title="Dashboard Monitor" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Work Environment Monitor (Live)</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
                        <p class="text-sm text-gray-500 uppercase font-bold">Suhu Kering (Tdb)</p>
                        <p class="text-3xl font-black">{{ sensors.Tdb }}°C</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
                        <p class="text-sm text-gray-500 uppercase font-bold">Kelembapan (RH)</p>
                        <p class="text-3xl font-black">{{ sensors.RH }}%</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
                        <p class="text-sm text-gray-500 uppercase font-bold">Oksigen (O2)</p>
                        <p class="text-3xl font-black">{{ sensors.O2 }} %vol</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-4 rounded-lg shadow flex justify-between items-center">
                        <span class="text-gray-600">Mean Radiant (MRT)</span>
                        <span class="font-bold text-xl">{{ sensors.MRT }}°C</span>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow flex justify-between items-center">
                        <span class="text-gray-600">Suhu Basah (Twb)</span>
                        <span class="font-bold text-xl">{{ sensors.Twb }}°C</span>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow flex justify-between items-center">
                        <span class="text-gray-600">Air Speed</span>
                        <span class="font-bold text-xl text-teal-600">{{ sensors.airSpeed }} m/s</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="font-bold text-gray-700 mb-4 text-center text-sm uppercase">Tren Suhu (Real-time)</h3>
                        <div class="h-72">
                            <Line :data="lineData" :options="chartOptions" />
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="font-bold text-gray-700 mb-4 text-center text-sm uppercase">Tren Kelembapan (Real-time)</h3>
                        <div class="h-72">
                            <Bar :data="barData" :options="chartOptions" />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>