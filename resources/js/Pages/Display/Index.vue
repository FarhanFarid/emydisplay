<script setup>
    import { ref, onMounted, onBeforeUnmount } from 'vue'
    import flagshiplogo from '@/Media/logo/ijnflagship.png'
    import mislogo from '@/Media/logo/Logo MIS-01.png'
    import Card from '@/Components/Card.vue'

    // Clock state
    const time = ref('')
    const date = ref('')
    let clockInterval = null

    function updateClock() {
        const now = new Date()
        time.value = now.toLocaleTimeString('en-GB', { hour12: false })
        date.value = now.toLocaleDateString('en-GB', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        })
    }

    // Patient data
    const patients = ref({
        waiting: [],
        resus: [],
        obs: [],
        iso: [],
        family: []
    })

    // Track current page for each category
    const currentPage = ref({
        waiting: 0,
        resus: 0,
        obs: 0,
        iso: 0,
        family: 0
    })

    const pageSize = 7
    let carouselInterval = null

    // Fetch and group patients
    async function loadPatients() {
        try {
            const res = await fetch(route('display.getemypatient'))
            const json = await res.json()

            if (json.status === 'success') {
            patients.value = { waiting: [], resus: [], obs: [], iso: [], family: [] }

            json.data.forEach(patient => {
                const ploc = (patient.ploc || '').toLowerCase()

                if (!patient.ploc) {
                patients.value.waiting.push(patient)
                } else if (ploc.includes('resus')) {
                patients.value.resus.push(patient)
                } else if (ploc.includes('observation')) {
                patients.value.obs.push(patient)
                } else if (ploc.includes('isolation')) {
                patients.value.iso.push(patient)
                } else if (ploc.includes('opd')) {
                patients.value.family.push(patient)
                } else {
                patients.value.waiting.push(patient)
                }
            })

            // Reset pages if needed
            Object.keys(currentPage.value).forEach(cat => {
                currentPage.value[cat] = 0
            })
            }
        } catch (err) {
            console.error('Error loading patients:', err)
        }
    }

    // Auto advance pages
    function startCarousel() {
        carouselInterval = setInterval(() => {
            Object.keys(patients.value).forEach(cat => {
            const totalPages = Math.ceil(patients.value[cat].length / pageSize)
            if (totalPages > 1) {
                currentPage.value[cat] = (currentPage.value[cat] + 1) % totalPages
            }
            })
        }, 10000) // change page every 5s
    }

    function stopCarousel() {
        clearInterval(carouselInterval)
    }

    onMounted(() => {
        clockInterval = setInterval(updateClock, 1000)
        updateClock()

        loadPatients()
        setInterval(loadPatients, 30000) // refresh every 30s

        startCarousel()
    })

    onBeforeUnmount(() => {
        clearInterval(clockInterval)
        stopCarousel()
    })

    // Computed helper to get only visible patients for a category
    function getVisiblePatients(cat) {
        const start = currentPage.value[cat] * pageSize
        return patients.value[cat].slice(start, start + pageSize)
    }
</script>


<template>
    <div class="min-h-screen bg-slate-200 p-6">
        <!-- Header -->
        <div class="max-w-10xl mx-auto bg-sky-300 shadow-lg rounded-lg p-6 mb-8 flex items-center justify-between h-20">

            <div class="flex flex-col text-left leading-tight">
                <span class="text-xl font-bold">{{ time }}</span>
                <span class="text-sm">{{ date }}</span>
            </div>

            <h1 class="text-4xl font-black text-dark pl-40">
                EMERGENCY DEPARTMENT
            </h1>

            <div class="flex gap-2">
                <img :src="flagshiplogo" alt="Logo" class="h-20 object-contain" />
                <img :src="mislogo" alt="Logo" class="h-18 object-contain" />
            </div>
        </div>


        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

            <Card title="WAITING AREA" bgColor="bg-sky-50" titleColor="bg-sky-200" class="h-120">
                <div class="h-full overflow-hidden relative">
                    <table class="min-w-full text-left border-collapse border border-gray-400">
                        <colgroup>
                            <col style="width: 10%;" />   <!-- MRN -->
                            <col style="width: 12%;" />   <!-- Category -->
                            <col style="width: 15%;" />   <!-- Date -->
                            <col style="width: 13%;" />   <!-- Time -->
                            <col style="width: 50%;" />   <!-- Doctor (biggest) -->
                        </colgroup>
                        <thead class="bg-gray-200 text-lg">
                            <tr>
                                <th rowspan="2" class="px-2 py-1 text-center border border-gray-400 font-bold">MRN</th>
                                <th colspan="3" class="px-2 py-1 text-center border border-gray-400 font-bold">Triage</th>
                                <th rowspan="2" class="px-2 py-1 text-center border border-gray-400 font-bold">Doctor</th>
                            </tr>
                            <tr>
                                <th class="px-2 py-1 text-center border border-gray-400 font-bold">Category</th>
                                <th class="px-2 py-1 text-center border border-gray-400 font-bold">Date</th>
                                <th class="px-2 py-1 text-center border border-gray-400 font-bold">Time</th>
                            </tr>
                        </thead>
                        <!-- Use transition-group as tbody -->
                        <transition-group name="fade" tag="tbody">
                            <tr v-if="getVisiblePatients('waiting').length === 0" key="empty">
                                <td colspan="5" class="text-center py-3 text-gray-800 italic">
                                    No patients in this area
                                </td>
                            </tr>
                            <tr v-for="patient in getVisiblePatients('waiting')" :key="patient.mrn" class="border-b hover:bg-gray-100 odd:bg-white even:bg-gray-50">
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300">{{ patient.mrn || '-' }}</td>
                                <td
                                    class="px-2 border border-gray-300 py-1 font-bold text-black text-center text-lg"
                                    :style="{
                                        backgroundColor:
                                        patient.triagecat === '1 - Resuscitation' ? '#fca5a5' : // red-300
                                        patient.triagecat === '2 - Emergency' ? '#ffa443' :    // orange-300
                                        patient.triagecat === '3 - Urgent' ? '#fde047' :       // yellow-300
                                        patient.triagecat === '4 - Semi Urgent' ? '#6aff8a' :  // green-300
                                        patient.triagecat === '5 - Non Urgent' ? '#7dfffe' :   // cyan-300
                                        ''
                                    }"
                                >
                                {{ patient.triagecat ? patient.triagecat.split(' - ')[0] : '-' }}
                                </td>
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300">{{ patient.triagedate || '-' }}</td>
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300">{{ patient.triagetime ? patient.triagetime.slice(0,5) : '-' }}</td>
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300 uppercase">{{ patient.edoc || '-' }}</td>
                            </tr>
                        </transition-group>
                    </table>
                </div>
            </Card>

            <Card title="RESUSCITATION ROOM" bgColor="bg-sky-50" titleColor="bg-sky-200" class="h-120">
                <div class="h-full overflow-hidden relative">
                    <table class="min-w-full text-left border-collapse border border-gray-400">
                        <colgroup>
                            <col style="width: 10%;" />   <!-- MRN -->
                            <col style="width: 12%;" />   <!-- Category -->
                            <col style="width: 15%;" />   <!-- Date -->
                            <col style="width: 13%;" />   <!-- Time -->
                            <col style="width: 50%;" />   <!-- Doctor (biggest) -->
                        </colgroup>
                        <thead class="bg-gray-200 text-lg">
                            <tr>
                                <th rowspan="2" class="px-2 py-1 text-center border border-gray-400 font-bold">MRN</th>
                                <th colspan="3" class="px-2 py-1 text-center border border-gray-400 font-bold">Triage</th>
                                <th rowspan="2" class="px-2 py-1 text-center border border-gray-400 font-bold">Doctor</th>
                            </tr>
                            <tr>
                                <th class="px-2 py-1 text-center border border-gray-400 font-bold">Category</th>
                                <th class="px-2 py-1 text-center border border-gray-400 font-bold">Date</th>
                                <th class="px-2 py-1 text-center border border-gray-400 font-bold">Time</th>
                            </tr>
                        </thead>
                        <!-- Use transition-group as tbody -->
                        <transition-group name="fade" tag="tbody">
                            <tr v-if="getVisiblePatients('resus').length === 0" key="empty">
                                <td colspan="5" class="text-center py-3 text-gray-800 italic">
                                    No patients in this area
                                </td>
                            </tr>
                            <tr v-for="patient in getVisiblePatients('resus')" :key="patient.mrn" class="border-b hover:bg-gray-100 odd:bg-white even:bg-gray-50">
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300">{{ patient.mrn || '-' }}</td>
                                <td
                                    class="px-2 border border-gray-300 py-1 font-bold text-black text-center text-lg"
                                    :style="{
                                        backgroundColor:
                                        patient.triagecat === '1 - Resuscitation' ? '#fca5a5' : // red-300
                                        patient.triagecat === '2 - Emergency' ? '#ffa443' :    // orange-300
                                        patient.triagecat === '3 - Urgent' ? '#fde047' :       // yellow-300
                                        patient.triagecat === '4 - Semi Urgent' ? '#6aff8a' :  // green-300
                                        patient.triagecat === '5 - Non Urgent' ? '#7dfffe' :   // cyan-300
                                        ''
                                    }"
                                >
                                {{ patient.triagecat ? patient.triagecat.split(' - ')[0] : '-' }}
                                </td>
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300">{{ patient.triagedate || '-' }}</td>
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300">{{ patient.triagetime ? patient.triagetime.slice(0,5) : '-' }}</td>
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300 uppercase">{{ patient.edoc || '-' }}</td>
                            </tr>
                        </transition-group>
                    </table>
                </div>
            </Card>

            <Card title="EMERGENCY WARD" bgColor="bg-sky-50" titleColor="bg-sky-200" class="h-120">
                <div class="h-full overflow-hidden relative">
                    <table class="min-w-full text-left border-collapse border border-gray-400">
                        <colgroup>
                            <col style="width: 10%;" />   <!-- MRN -->
                            <col style="width: 12%;" />   <!-- Category -->
                            <col style="width: 15%;" />   <!-- Date -->
                            <col style="width: 13%;" />   <!-- Time -->
                            <col style="width: 50%;" />   <!-- Doctor (biggest) -->
                        </colgroup>
                        <thead class="bg-gray-200 text-lg">
                            <tr>
                                <th rowspan="2" class="px-2 py-1 text-center border border-gray-400 font-bold">MRN</th>
                                <th colspan="3" class="px-2 py-1 text-center border border-gray-400 font-bold">Triage</th>
                                <th rowspan="2" class="px-2 py-1 text-center border border-gray-400 font-bold">Doctor</th>
                            </tr>
                            <tr>
                                <th class="px-2 py-1 text-center border border-gray-400 font-bold">Category</th>
                                <th class="px-2 py-1 text-center border border-gray-400 font-bold">Date</th>
                                <th class="px-2 py-1 text-center border border-gray-400 font-bold">Time</th>
                            </tr>
                        </thead>
                        <!-- Use transition-group as tbody -->
                        <transition-group name="fade" tag="tbody">
                            <tr v-if="getVisiblePatients('obs').length === 0" key="empty">
                                <td colspan="5" class="text-center py-3 text-gray-800 italic">
                                    No patients in this area
                                </td>
                            </tr>
                            <tr v-for="patient in getVisiblePatients('obs')" :key="patient.mrn" class="border-b hover:bg-gray-100 odd:bg-white even:bg-gray-50">
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300">{{ patient.mrn || '-' }}</td>
                                <td
                                    class="px-2 border border-gray-300 py-1 font-bold text-black text-center text-lg"
                                    :style="{
                                        backgroundColor:
                                        patient.triagecat === '1 - Resuscitation' ? '#fca5a5' : // red-300
                                        patient.triagecat === '2 - Emergency' ? '#ffa443' :    // orange-300
                                        patient.triagecat === '3 - Urgent' ? '#fde047' :       // yellow-300
                                        patient.triagecat === '4 - Semi Urgent' ? '#6aff8a' :  // green-300
                                        patient.triagecat === '5 - Non Urgent' ? '#7dfffe' :   // cyan-300
                                        ''
                                    }"
                                >
                                {{ patient.triagecat ? patient.triagecat.split(' - ')[0] : '-' }}
                                </td>
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300">{{ patient.triagedate || '-' }}</td>
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300">{{ patient.triagetime ? patient.triagetime.slice(0,5) : '-' }}</td>
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300 uppercase">{{ patient.edoc || '-' }}</td>
                            </tr>
                        </transition-group>
                    </table>
                </div>
            </Card>

            <Card title="ISOLATION ROOM" bgColor="bg-sky-50" titleColor="bg-sky-200" class="h-120">
                <div class="h-full overflow-hidden relative">
                    <table class="min-w-full text-left border-collapse border border-gray-400">
                        <colgroup>
                            <col style="width: 10%;" />   <!-- MRN -->
                            <col style="width: 12%;" />   <!-- Category -->
                            <col style="width: 15%;" />   <!-- Date -->
                            <col style="width: 13%;" />   <!-- Time -->
                            <col style="width: 50%;" />   <!-- Doctor (biggest) -->
                        </colgroup>
                        <thead class="bg-gray-200 text-lg">
                            <tr>
                                <th rowspan="2" class="px-2 py-1 text-center border border-gray-400 font-bold">MRN</th>
                                <th colspan="3" class="px-2 py-1 text-center border border-gray-400 font-bold">Triage</th>
                                <th rowspan="2" class="px-2 py-1 text-center border border-gray-400 font-bold">Doctor</th>
                            </tr>
                            <tr>
                                <th class="px-2 py-1 text-center border border-gray-400 font-bold">Category</th>
                                <th class="px-2 py-1 text-center border border-gray-400 font-bold">Date</th>
                                <th class="px-2 py-1 text-center border border-gray-400 font-bold">Time</th>
                            </tr>
                        </thead>
                        <!-- Use transition-group as tbody -->
                        <transition-group name="fade" tag="tbody">
                            <tr v-if="getVisiblePatients('iso').length === 0" key="empty">
                                <td colspan="5" class="text-center py-3 text-gray-800 italic">
                                    No patients in this area
                                </td>
                            </tr>
                            <tr v-for="patient in getVisiblePatients('iso')" :key="patient.mrn" class="border-b hover:bg-gray-100 odd:bg-white even:bg-gray-50">
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300">{{ patient.mrn || '-' }}</td>
                                <td
                                    class="px-2 border border-gray-300 py-1 font-bold text-black text-center text-lg"
                                    :style="{
                                        backgroundColor:
                                        patient.triagecat === '1 - Resuscitation' ? '#fca5a5' : // red-300
                                        patient.triagecat === '2 - Emergency' ? '#ffa443' :    // orange-300
                                        patient.triagecat === '3 - Urgent' ? '#fde047' :       // yellow-300
                                        patient.triagecat === '4 - Semi Urgent' ? '#6aff8a' :  // green-300
                                        patient.triagecat === '5 - Non Urgent' ? '#7dfffe' :   // cyan-300
                                        ''
                                    }"
                                >
                                {{ patient.triagecat ? patient.triagecat.split(' - ')[0] : '-' }}
                                </td>
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300">{{ patient.triagedate || '-' }}</td>
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300">{{ patient.triagetime ? patient.triagetime.slice(0,5) : '-' }}</td>
                                <td class="px-1 py-1 text-lg font-semibold text-center border border-gray-300 uppercase">{{ patient.edoc || '-' }}</td>
                            </tr>
                        </transition-group>
                    </table>
                </div>
            </Card>
        </div>
    </div>
</template>

<!-- <style scoped>
    .fade-enter-active, .fade-leave-active {
        transition: opacity 0.3s ease;
        position: absolute; /* keeps old and new content stacked */
        width: 100%;
    }

        .fade-enter-from, .fade-leave-to {
        opacity: 0;
    }

</style> -->
