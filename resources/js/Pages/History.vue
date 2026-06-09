<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";

const props = defineProps({
    logs: Array,
    currentFilter: String,
});

const selectedUnit = ref(props.currentFilter);
const progress = ref(0);

// Fungsi untuk mengambil data terbaru tanpa refresh halaman manual
const refreshTableData = () => {
    router.reload({
        only: ["logs"],
        preserveScroll: true,
        onSuccess: () => {
            console.log("Data otomatis diperbarui dari database.");
        },
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
            progress.value = 0; // Reset bar kembali ke 0%
        }
    }, 100); // 100ms * 100 langkah = 10.000ms (10 detik)
};

onMounted(() => {
    startLoadingBar();

    if (window.Echo) {
        window.Echo.channel("environment-data").listen(
            ".EnvironmentDataReceived",
            (e) => {
                // Realtime event handler backup area
            },
        );
    }
});

onUnmounted(() => {
    clearInterval(progressInterval);
    if (window.Echo) {
        window.Echo.leave("environment-data");
    }
});

const filterData = () => {
    router.get(
        route("history.index"),
        { unit: selectedUnit.value },
        { preserveState: true },
    );
};
</script>

<template>
    <Head title="History Log" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-0 lg:px-6">
                <div class="bg-white p-6 shadow-sm rounded-2xl">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-lg font-bold">
                            Log Riwayat Lingkungan
                        </h3>
                        <select
                            v-model="selectedUnit"
                            @change="filterData"
                            class="rounded-md border-gray-300 text-sm"
                        >
                            <option value="all">All Units (Average)</option>
                            <option value="1">Unit 1</option>
                            <option value="2">Unit 2</option>
                            <option value="3">Unit 3</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <div class="flex justify-between mb-1">
                            <span class="text-xs font-medium text-blue-700"
                                >Buffering Data (10s Cycle)</span
                            >
                            <span class="text-xs font-medium text-blue-700"
                                >{{ Math.round(progress / 10) }}s</span
                            >
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div
                                class="bg-blue-600 h-1.5 rounded-full transition-all duration-100 ease-linear"
                                :style="{ width: progress + '%' }"
                            ></div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr
                                    class="text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    <th class="px-6 py-3">Waktu</th>
                                    <th
                                        class="px-6 py-3 text-indigo-600 font-extrabold bg-indigo-50/50"
                                    >
                                        WBGT Index (°C)
                                    </th>
                                    <th class="px-6 py-3">Air Speed (m/s)</th>
                                    <th class="px-6 py-3">Wet Temp (°C)</th>
                                    <th class="px-6 py-3">Dry Temp (°C)</th>
                                    <th class="px-6 py-3">
                                        Radiation Temp (°C)
                                    </th>
                                    <th class="px-6 py-3">Air Humidity (%)</th>
                                    <th class="px-6 py-3">Oxygen Vol (%vol)</th>
                                    <th class="px-6 py-3">
                                        Carbon Monoxide Vol (PPM)
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="bg-white divide-y divide-gray-200 font-sans"
                            >
                                <tr
                                    v-for="log in props.logs"
                                    :key="log.id"
                                    class="hover:bg-gray-50/80 transition"
                                >
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-600"
                                    >
                                        {{ log.formatted_date }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-mono text-indigo-700 font-bold bg-indigo-50/30"
                                    >
                                        {{ log.wbgt || "0.00" }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-mono text-gray-700"
                                    >
                                        {{ log.air_speed }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-mono text-gray-700"
                                    >
                                        {{ log.twb }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-mono text-gray-700"
                                    >
                                        {{ log.tdb }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-mono text-gray-700"
                                    >
                                        {{ log.mrt }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-mono text-gray-700"
                                    >
                                        {{ log.rh }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-mono text-gray-700"
                                    >
                                        {{ log.o2 }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-mono text-gray-700"
                                    >
                                        {{ log.co }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
