<script setup>
import { reactive, computed, watch, ref } from 'vue'
import axios from 'axios'
import { Head } from '@inertiajs/vue3'
import utility from '../../admin/utility/page-title.vue'

const props = defineProps({
    order: { type: Array, default: () => ({}) }
})

const submitting = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const formData = reactive({})

function deepMerge(target, source) {
    for (const key in source) {
        if (
            source[key] &&
            typeof source[key] === "object" &&
            !Array.isArray(source[key])
        ) {
            if (!target[key] || typeof target[key] !== "object") {
                target[key] = {}
            }
            deepMerge(target[key], source[key])
        } else {
            target[key] = source[key]
        }
    }
}
watch(
    () => props.order,
    (newVal) => {
        if (newVal && typeof newVal === "object") {
            deepMerge(formData, newVal)
        }
    },
    { immediate: true }
)

const previewOrder = computed(() => {
    const charset = (formData.string || '').toString()
    const len = Number(formData.length_random_order) || 0
    const sample = []
    if (charset.length > 0 && len > 0) {
        for (let i = 0; i < Math.min(len, 12); i++) {
            sample.push(charset[i % charset.length])
        }
    }
    return `${formData.prefix_order || ''}${sample.join('')}`
})

function buildPayload() {
    return {
        prefix_order: String(formData.prefix_order || ''),
        length_random_order: Number(formData.length_random_order || 0),
        string: String(formData.string || ''),
        count_pending: Number(formData.count_pending ?? 0),
        transaksi_delay: Number(formData.transaksi_delay ?? 0),
        exp_order: Number(formData.exp_order ?? 0),
        custom_notes: {
            pending: String(formData.custom_notes?.pending || ''),
            failed: String(formData.custom_notes?.failed || ''),
            success: String(formData.custom_notes?.success || '')
        }
    }
}
function validate(payload) {
    if (!payload.prefix_order) return 'Prefix order tidak boleh kosong.'
    if (!payload.length_random_order || payload.length_random_order < 4) return 'Panjang random minimal 4.'
    if (!payload.string || payload.string.length < 4) return 'Character set minimal 4 karakter.'
    if (payload.exp_order < 1) return 'Expired order minimal 1 menit.'
    return ''
}

async function submitKonfigurasi() {
    errorMsg.value = ''
    successMsg.value = ''

    const payload = buildPayload()
    const err = validate(payload)
    if (err) {
        toastify['error']?.("Whoops!", err)
        return
    }

    submitting.value = true
    try {
        const res = await axios.post(route('api.konfigurasi-order'), payload, {
            headers: { 'Content-Type': 'application/json' }
        })
        toastify['success']?.("Gotcha!", res.data.message)
    } catch (e) {
        toastify['error']?.("Whoops!", e.response.data.message)
    } finally {
        submitting.value = false
    }
}
</script>

<template>

    <Head :title="'Konfigurasi Order'" />
    <utility :title="'Order'"></utility>
    <ul class="nav nav-tabs" id="orderTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general"
                type="button" role="tab" aria-controls="general" aria-selected="true">
                General
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button"
                role="tab" aria-controls="notes" aria-selected="false">
                Custom Notes
            </button>
        </li>
    </ul>
    <div class="card mt-2">
        <div class="card-body py-0">

            <div class="tab-content mt-4" id="orderTabsContent">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <form @submit.prevent="submitKonfigurasi" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Prefix Order</label>
                                <input type="text" class="form-control" v-model.trim="formData.prefix_order"
                                    placeholder="" required>
                                <div class="form-text">Contoh: ORD-, INV-, TRX-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Panjang Random Order</label>
                                <input type="number" class="form-control" v-model.number="formData.length_random_order"
                                    min="4" max="32" required>
                                <div class="form-text">Jumlah karakter acak di belakang prefix.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Character Set</label>
                                <input type="text" class="form-control" v-model.trim="formData.string" required>
                                <div class="form-text">Karakter yang digunakan untuk generate ID, misal:
                                    0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Count Pending</label>
                                <input type="number" class="form-control" v-model.number="formData.count_pending"
                                    min="0">
                                <div class="form-text">Jumlah maksimum order pending yang dihitung (opsional).</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Transaksi Delay (detik)</label>
                                <input type="number" class="form-control" v-model.number="formData.transaksi_delay"
                                    min="0">
                                <div class="form-text">Delay pemrosesan transaksi dalam detik.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Expired Order (menit)</label>
                                <input type="number" class="form-control" v-model.number="formData.exp_order" min="1">
                                <div class="form-text">Durasi kadaluarsa order dalam menit.</div>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">Preview ID Order</h6>
                            </div>
                            <div class="card-body">
                                <code>{{ previewOrder }}</code>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end py-2">
                            <button type="submit" class="btn btn-primary" :disabled="submitting">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"
                                    aria-hidden="true"></span>
                                <i class="fas fa-save"></i>
                                Simpan Konfigurasi
                            </button>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
                    <form @submit.prevent="submitKonfigurasi" novalidate>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Pending</label>
                                <textarea class="form-control" rows="2" v-model.trim="formData.custom_notes.pending"
                                    placeholder="Selesaikan pembayaranmu untuk menghindari pembatalan otomatis"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Failed</label>
                                <textarea class="form-control" rows="2" v-model.trim="formData.custom_notes.failed"
                                    placeholder="Transaksi gagal, mohon buat ulang transaksi anda"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Success</label>
                                <textarea class="form-control" rows="2" v-model.trim="formData.custom_notes.success"
                                    placeholder="Silahkan cek notes seller di bawah"></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end py-2">
                            <button type="submit" class="btn btn-primary" :disabled="submitting">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"
                                    aria-hidden="true"></span>
                                <i class="fas fa-save"></i>
                                Simpan Notes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>