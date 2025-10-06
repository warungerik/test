<script setup>
import { Head } from '@inertiajs/vue3'
import utility from '../admin/utility/page-title.vue'
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js'
import Swal from 'sweetalert2'

const props = defineProps({ history: Array })
const rows = ref([...(props.history || [])])

// toolbar
const q = ref('')
const status = ref('all')
const dateFrom = ref('')
const dateTo = ref('')

// expand
const openId = ref(null)
const toggleOpen = (id) => { openId.value = openId.value === id ? null : id }

// pagination
const currentPage = ref(1)
const perPage = ref(10)
const perPageOptions = [5, 10, 20, 50]

const matchesDate = (isoStr) => {
    if (!dateFrom.value && !dateTo.value) return true
    const ts = new Date(isoStr).getTime()
    const fromOk = !dateFrom.value || ts >= new Date(dateFrom.value).getTime()
    const toOk = !dateTo.value || ts <= new Date(dateTo.value).getTime() + 24 * 60 * 60 * 1000 - 1
    return fromOk && toOk
}

const filtered = computed(() => {
    const key = q.value.trim().toLowerCase()
    const arr = [...rows.value].filter(r => {
        const s = !key || [
            r.name, r.invoice_id, r?.product?.game, r?.product?.name_provider, r?.product?.denom,
            r?.payment?.name, r?.payment?.code, r?.whatsapp, r?.seller_notes?.notes
        ].some(v => (v || '').toString().toLowerCase().includes(key))
        const st = status.value === 'all' || r.status === status.value
        const d = matchesDate(r.created_at)
        return s && st && d
    }).sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    const totalPages = Math.max(1, Math.ceil(arr.length / perPage.value))
    if (currentPage.value > totalPages) currentPage.value = totalPages
    return arr
})
const paginatedRows = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filtered.value.slice(start, start + perPage.value)
})
const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage.value)))
const pages = computed(() => {
    const max = 7
    const t = totalPages.value
    const cur = currentPage.value
    if (t <= max) return Array.from({ length: t }, (_, i) => i + 1)
    const arr = new Set([1, 2, t - 1, t, cur - 1, cur, cur + 1].filter(n => n >= 1 && n <= t))
    const sorted = [...arr].sort((a, b) => a - b)
    return sorted
})

// sync props
watch(() => props.history, (val) => { rows.value = [...(val || [])]; currentPage.value = 1 })

const currency = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(n || 0))
const showEdit = ref(false)
const editing = ref(null)
const editForm = ref({
    id: null,
    name: '',
    whatsapp: '',
    status: 'pending',
    seller_notes: ''
})
const openEdit = (h) => {
    editing.value = h
    editForm.value = {
        id: h.id,
        name: h.name || '',
        whatsapp: h.whatsapp || '',
        status: h.status || 'pending',
        seller_notes: typeof h?.seller_notes?.notes === 'string' ? h.seller_notes.notes : (h?.seller_notes ?? '') // fallback
    }
    showEdit.value = true
}
const saveEdit = async () => {
    try {
        const url = route('api.history.update', editForm.value.id)
        const payload = {
            name: editForm.value.name,
            whatsapp: editForm.value.whatsapp,
            status: editForm.value.status,
            seller_notes: typeof editForm.value.seller_notes === 'string'
                ? { notes: editForm.value.seller_notes }
                : editForm.value.seller_notes
        }
        const res = await axios.post(url, { ...payload, _method: 'PUT' })
        if (res.data?.status) {
            const updated = res.data.data
            const idx = rows.value.findIndex(r => r.id === updated.id)
            if (idx !== -1) rows.value[idx] = updated
            showEdit.value = false
            toastify['success']?.('Berhasil', 'Data berhasil disimpan')
        } else {
            toastify['error']?.('Whoops!', res.data.message)
        }
    } catch (e) {
        const msg = e.response?.data?.message || e.message || 'Terjadi kesalahan'
        toastify['error']?.('Whoops!', msg)
    }
}

const onDelete = async (h) => {
    const res = await Swal.fire({
        title: 'Yakin ingin menghapus transaksi ini?',
        text: "Jika transaksi ini sudah digunakan, maka transaksi ini akan dibatalkan",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!',
    })
    if (res.isConfirmed) {
        try {
            const url = route('api.history.destroy', h.id)
            const res = await axios.post(url)
            if (res.data?.status) {
                const idx = rows.value.findIndex(r => r.id === h.id)
                if (idx !== -1) rows.value.splice(idx, 1)
            } else {
                toastify['error']?.('Whoops!', res.data.message)

            }
        } catch (e) {
            const msg = e.response?.data?.message || e.message || 'Terjadi kesalahan'
            toastify['error']?.('Whoops!', msg)
        }
    }
}

</script>

<template>

    <Head :title="'History Transaksi'" />
    <utility :title="'History'" />

    <!-- Toolbar -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Pencarian</label>
                    <input v-model="q" type="text" class="form-control"
                        placeholder="Nama, invoice, game, provider, denom, payment, WA, catatan..." />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select v-model="status" class="form-select">
                        <option value="all">Semua</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dari</label>
                    <input v-model="dateFrom" type="date" class="form-control" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai</label>
                    <input v-model="dateTo" type="date" class="form-control" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rows/Page</label>
                    <select v-model.number="perPage" class="form-select" @change="currentPage = 1">
                        <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0">History Transaksi</div>
            <div class="small text-muted">Total: {{ filtered.length }}</div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:42px;">#</th>
                            <th>Invoice</th>
                            <th>Nama</th>
                            <th>Game</th>
                            <th>Provider</th>
                            <th>Denom</th>
                            <th class="text-end">Harga</th>
                            <th class="text-center">Status</th>
                            <th>Dibuat</th>
                            <th>Jatuh Tempo</th>
                            <th class="text-center" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(h, i) in paginatedRows" :key="h.id">
                            <tr>
                                <td>{{ i + 1 }}</td>
                                <td class="fw-semibold">
                                    <button class="btn btn-sm btn-link p-0 text-decoration-none"
                                        @click="toggleOpen(h.id)">
                                        <span class="me-1">{{ h.invoice_id }}</span>
                                        <i :class="['fas', openId === h.id ? 'fa-arrow-up' : 'fa-arrow-down']"></i>
                                    </button>
                                </td>
                                <td>{{ h.name }}</td>
                                <td>{{ h?.product?.game || '-' }}</td>
                                <td>{{ h?.product?.name_provider || '-' }}</td>
                                <td>{{ h?.product?.denom || '-' }}</td>
                                <td class="text-end">{{ currency(h.price) }}</td>
                                <td class="text-center">
                                    <span :class="['badge',
                                        h.status === 'success' ? 'bg-success' :
                                            h.status === 'failed' ? 'bg-danger' : 'bg-warning text-dark']">
                                        {{ h.status }}
                                    </span>
                                </td>
                                <td class="small">{{ new Date(h.created_at).toLocaleString() }}</td>
                                <td class="small">{{ h.expired_at || '-' }}</td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" @click="openEdit(h)">
                                            <i class="fas fa-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" @click="onDelete(h)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="openId === h.id" class="bg-light">
                                <td colspan="11">
                                    <div class="p-3">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="card h-100">
                                                    <div class="card-header py-2"><strong>Detail Product</strong></div>
                                                    <div class="card-body">
                                                        <ul class="list-group list-group-flush">
                                                            <li class="list-group-item d-flex justify-content-between">
                                                                <span>Invoice ID</span><span class="fw-semibold">{{
                                                                    h?.invoice_id || '-' }}</span>
                                                            </li>
                                                            <li class="list-group-item d-flex justify-content-between">
                                                                <span>Game</span><span class="fw-semibold">{{
                                                                    h?.product?.game || '-' }}</span>
                                                            </li>
                                                            <li class="list-group-item d-flex justify-content-between">
                                                                <span>Provider</span><span class="fw-semibold">{{
                                                                    h?.product?.name_provider || '-' }}</span>
                                                            </li>
                                                            <li class="list-group-item d-flex justify-content-between">
                                                                <span>Denom</span><span class="fw-semibold">{{
                                                                    h?.product?.denom || '-' }}</span>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="card h-100">
                                                    <div class="card-header py-2"><strong>Detail Pembayaran</strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="mb-2">
                                                            <div class="text-muted small">Metode</div>
                                                            <div class="fw-semibold">{{ h?.payment?.name }} ({{
                                                                h?.payment?.code }})</div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="text-muted small">Biaya</div>
                                                            <div>{{ (h?.payment?.fee_fixed ?? 0).toLocaleString('id-ID')
                                                                }} + {{ Number(h?.payment?.fee_percent ?? 0).toFixed(2)
                                                                }}%</div>
                                                        </div>
                                                        <div class="mb-2"
                                                            v-if="h?.payment?.number || h?.payment?.name_account">
                                                            <div class="text-muted small">Rekening/Tujuan</div>
                                                            <div class="fw-semibold">{{ h?.payment?.number || '-' }}
                                                            </div>
                                                            <div class="small">{{ h?.payment?.name_account || '-' }}
                                                            </div>
                                                        </div>
                                                        <div class="mb-2" v-if="h?.payment?.detail?.url">
                                                            <a class="btn btn-sm btn-primary"
                                                                :href="Array.isArray(h.payment.detail.url) ? h.payment.detail.url : h.payment.detail.url"
                                                                target="_blank" rel="noopener">
                                                                Buka Link Pembayaran
                                                            </a>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-3"
                                                            v-if="h?.payment?.detail?.qr_link">
                                                            <img :src="Array.isArray(h.payment.detail.qr_link) ? h.payment.detail.qr_link : h.payment.detail.qr_link"
                                                                alt="QR"
                                                                style="width:96px;height:96px;object-fit:contain;border:1px solid #e9ecef;border-radius:.25rem;" />
                                                            <div class="small text-muted">Scan QR untuk membayar</div>
                                                        </div>
                                                        <div class="mt-2" v-if="h?.payment?.detail?.payment_code">
                                                            <div class="text-muted small">Payment Code</div>
                                                            <code
                                                                class="fw-semibold">{{ h.payment.detail.payment_code }}</code>
                                                        </div>
                                                        <div class="mt-2" v-if="h?.payment?.detail?.instructions">
                                                            <div class="text-muted small">Instruksi</div>
                                                            <div v-html="h.payment.detail.instructions"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card h-100">
                                                    <div class="card-header py-2"><strong>Ringkasan Harga</strong></div>
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between">
                                                            <span>Harga</span><span class="fw-semibold">{{
                                                                currency(h.price - (h?.other_prices?.tax ?? 0) +
                                                                    (h?.other_prices?.discount ?? 0)) }}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <span>Pajak</span><span>{{ currency(h?.other_prices?.tax ??
                                                                0) }}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <span>Diskon</span><span>-{{
                                                                currency(h?.other_prices?.discount ?? 0) }}</span>
                                                        </div>
                                                        <hr />
                                                        <div class="d-flex justify-content-between">
                                                            <span>Total</span><span class="fw-bold">{{ currency(h.price)
                                                                }}</span>
                                                        </div>
                                                        <div class="mt-3">
                                                            <div class="text-muted small">Kontak</div>
                                                            <div class="fw-semibold">{{ h.whatsapp || '-' }}</div>
                                                        </div>
                                                        <div class="mt-3" v-if="h?.seller_notes?.notes">
                                                            <div class="text-muted small">Catatan Penjual</div>
                                                            <div class="fw-semibold">{{ h.seller_notes.notes }}</div>
                                                            <div class="mt-2"
                                                                v-if="Array.isArray(h?.seller_notes?.download)">
                                                                <div class="text-muted small mb-1">Unduhan</div>
                                                                <ul class="list-group">
                                                                    <li v-for="(d, idx) in h.seller_notes.download"
                                                                        :key="idx" class="list-group-item py-2">
                                                                        <a :href="Array.isArray(d.link) ? d.link : d.link"
                                                                            target="_blank" rel="noopener">{{ d.title
                                                                            }}</a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <div class="mt-3">
                                                            <div class="text-muted small">Diperbarui</div>
                                                            <div>{{ new Date(h.updated_at).toLocaleString() }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                        </template>
                        <tr v-if="paginatedRows.length === 0">
                            <td colspan="11" class="text-center py-4 text-muted">Tidak ada riwayat.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                <div class="small text-muted">
                    Menampilkan
                    <strong>
                        {{ filtered.length ? ((currentPage - 1) * perPage + 1) : 0 }}
                        -
                        {{ Math.min(currentPage * perPage, filtered.length) }}
                    </strong>
                    dari <strong>{{ filtered.length }}</strong> data
                </div>
                <nav aria-label="History pages">
                    <ul class="pagination mb-0">
                        <li class="page-item" :class="{ disabled: currentPage === 1 }">
                            <button class="page-link" @click="currentPage > 1 && (currentPage--)"
                                aria-label="Previous">«</button>
                        </li>

                        <!-- Leading ellipsis -->
                        <li v-if="pages > 1" class="page-item">
                            <button class="page-link" @click="currentPage = 1">1</button>
                        </li>
                        <li v-if="pages > 2" class="page-item disabled">
                            <span class="page-link">…</span>
                        </li>

                        <li v-for="p in pages" :key="`p-${p}`" class="page-item" :class="{ active: currentPage === p }">
                            <button class="page-link" @click="currentPage = p">{{ p }}</button>
                        </li>

                        <!-- Trailing ellipsis -->
                        <li v-if="pages[pages.length - 1] < totalPages - 1" class="page-item disabled">
                            <span class="page-link">…</span>
                        </li>
                        <li v-if="pages[pages.length - 1] < totalPages" class="page-item">
                            <button class="page-link" @click="currentPage = totalPages">{{ totalPages }}</button>
                        </li>

                        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                            <button class="page-link" @click="currentPage < totalPages && (currentPage++)"
                                aria-label="Next">»</button>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" tabindex="-1" :class="{ show: showEdit }" style="display: block;" v-if="showEdit"
        @click.self="showEdit = false" aria-modal="true" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Transaksi</h5>
                    <button type="button" class="btn-close" @click="showEdit = false"></button>
                </div>
                <form @submit.prevent="saveEdit">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama</label>
                                <input v-model="editForm.name" class="form-control" />
                            </div>
                            <div class="col-12">
                                <label class="form-label">WhatsApp</label>
                                <input v-model="editForm.whatsapp" class="form-control" placeholder="08xxxxxxxxxx" />
                            </div>
                            <div class="col-12">
                                <label class="form-label">Status</label>
                                <select v-model="editForm.status" class="form-select">
                                    <option value="success">Success</option>
                                    <option value="failed">Failed</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan Penjual</label>
                                <textarea v-model="editForm.seller_notes" rows="3" class="form-control"
                                    placeholder="Catatan ditampilkan ke pembeli"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-text">Invoice: {{ editing?.invoice_id }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" @click="showEdit = false">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
