<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    logs: Array,
    currentFilter: String
});

const selectedUnit = ref(props.currentFilter);
const progress = ref(0);

// Fungsi untuk mengambil data terbaru tanpa refresh halaman manual
const refreshTableData = () => {
    router.reload({
        only: ['logs'],
        preserveScroll: true,
        onSuccess: () => {
            console.log('Data otomatis diperbarui dari database.');
        }
    });
};

// Logika Loading Bar 10 Detik sebagai Pemicu
let progressInterval;
const startLoadingBar = () => {
    progress.value = 0;
    if (progressInterval) clearInterval(progressInterval);
    
    progressInterval = setInterval(() => {
        progress.value += 1; 

        // Titik Kritis: Ketika progres mencapai 100% (10 detik)
        if (progress.value >= 100) {
            refreshTableData(); // Panggil data dari database secara otomatis
            progress.value = 0;  // Reset bar kembali ke 0%
        }
    }, 100); // 100ms * 100 langkah = 10.000ms (10 detik)
};

onMounted(() => {
    startLoadingBar();

    // Reverb tetap bisa digunakan sebagai backup jika data masuk lebih cepat
    if (window.Echo) {
        window.Echo.channel('environment-data')
            .listen('.EnvironmentDataReceived', (e) => {
                // Opsional: Jika ingin reset bar setiap kali ada sinyal MQTT masuk
                // Namun acuan utama kita sekarang adalah interval 10 detik di atas
            });
    }
});

onUnmounted(() => {
    clearInterval(progressInterval);
    if (window.Echo) {
        window.Echo.leave('environment-data');
    }
});

const filterData = () => {
    router.get(route('history.index'), { unit: selectedUnit.value }, { preserveState: true });
};
</script>

<template>
    <Head title="History Log" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-0 lg:px-6">
                <div class="bg-white p-6 shadow-sm rounded-2xl">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-lg font-bold">Log Riwayat Lingkungan</h3>
                        <select v-model="selectedUnit" @change="filterData" class="rounded-md border-gray-300">
                            <option value="all">All Units (Average)</option>
                            <option value="1">Unit 1</option>
                            <option value="2">Unit 2</option>
                            <option value="3">Unit 3</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <div class="flex justify-between mb-1">
                            <span class="text-xs font-medium text-blue-700">Buffering Data (10s Cycle)</span>
                            <span class="text-xs font-medium text-blue-700">{{ Math.round(progress/10) }}s</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-100 ease-linear" 
                                 :style="{ width: progress + '%' }"></div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Air Speed (m/s)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Wet Temp (°C)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dry Temp (°C)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Radiation Temp (°C)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Air Humidity (%)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Oxygen Vol (%vol)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Carbon Monocside Vol (PPM)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="log in props.logs" :key="log.id">
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">{{ log.formatted_date }}</td>
                                    <td class="px-6 py-4 text-sm">{{ log.air_speed }}</td>
                                    <td class="px-6 py-4 text-sm">{{ log.twb }}</td>
                                    <td class="px-6 py-4 text-sm">{{ log.tdb }}</td>
                                    <td class="px-6 py-4 text-sm">{{ log.mrt }}</td>
                                    <td class="px-6 py-4 text-sm">{{ log.rh }}</td>
                                    <td class="px-6 py-4 text-sm">{{ log.o2 }}</td>
                                    <td class="px-6 py-4 text-sm">{{ log.co }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>