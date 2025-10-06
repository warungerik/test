<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import utility from '../../admin/utility/page-title.vue'
import { ref, computed, watch } from 'vue'
import Swal from 'sweetalert2'
import { route } from 'ziggy-js'
import axios from 'axios'

const props = defineProps({
    app: String,
    categories: Array,
    payments: Array,
    kPayment: Array,
})

/* ===== CATEGORIES STATE ===== */
const catList = ref([...(props.categories || [])])
const catQ = ref('')
const catSortKey = ref('name')
const catSortDir = ref('asc')

const catFiltered = computed(() => {
    const items = [...catList.value].filter(c => {
        const q = catQ.value.trim().toLowerCase()
        return !q || [c.name, c.status].some(s => (s || '').toLowerCase().includes(q))
    })
    items.sort((a, b) => {
        const va = a[catSortKey.value]; const vb = b[catSortKey.value]
        if (typeof va === 'string' && typeof vb === 'string') {
            return catSortDir.value === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va)
        }
        return catSortDir.value === 'asc' ? (va > vb ? 1 : va < vb ? -1 : 0) : (vb > va ? 1 : vb < va ? -1 : 0)
    })
    return items
})
const catSortBy = (k) => {
    if (catSortKey.value === k) catSortDir.value = catSortDir.value === 'asc' ? 'desc' : 'asc'
    else { catSortKey.value = k; catSortDir.value = 'asc' }
}

const showCatModal = ref(false)
const isEditCat = ref(false)
const catForm = useForm({ id: null, name: '', status: 'active' })

const openCreateCat = () => {
    isEditCat.value = false
    catForm.reset()
    Object.assign(catForm, { id: null, name: '', status: 'active' })
    showCatModal.value = true
}
const openEditCat = (c) => {
    isEditCat.value = true
    catForm.reset()
    Object.assign(catForm, { id: c.id, name: c.name || '', status: c.status || 'active' })
    showCatModal.value = true
}
const submitCat = async () => {
    try {
        const routeName = isEditCat.value
            ? 'api.payment-categories.update'
            : 'api.payment-categories.store'

        const payload = { ...catForm.data() }
        if (isEditCat.value) payload._method = 'PUT'

        const res = await axios.post(
            route(routeName, isEditCat.value ? catForm.id : undefined),
            payload
        )

        if (res.data?.status) {
            if (isEditCat.value) {
                const idx = catList.value.findIndex(c => c.id === catForm.id)
                if (idx !== -1) {
                    catList.value[idx] = res.data.data
                }
            } else {
                catList.value.unshift(res.data.data)
            }

            showCatModal.value = false
            toastify?.success?.("Gotcha!", res.data.message || "Berhasil disimpan!")
        } else {
            toastify?.error?.("Whoops!", res.data.message || "Gagal menyimpan data.")
        }
    } catch (err) {
        const msg = err.response?.data?.message || err.message || "Terjadi kesalahan tak terduga."
        toastify?.error?.("Oops!", msg)
    }
}
const removeCat = (c) => {
    Swal.fire({
        title: 'Konfirmasi',
        text: `Hapus kategori "${c.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(route('api.payment-categories.destroy', c.id))
                .then(res => {
                    if (res.data.status) {
                        toastify?.success?.("Gotcha!", res.data.message)
                    } else {
                        toastify?.error?.("Whoops!", res.data.message)
                    }
                })
                .catch(err => {
                    toastify?.error?.("Oops!", err.response?.data?.message || err.message)
                })
                .finally(() => {
                    const idx = catList.value.findIndex(p => p.id === c.id)
                    if (idx !== -1) {
                        catList.value.splice(idx, 1)
                    }
                })
        }
    })
}
watch(() => props.categories, (val) => { catList.value = [...(val || [])] })

/* ===== PAYMENTS (NO sub_payment) ===== */
const list = ref([...(props.payments || [])])
const q = ref('')
const categoryFilter = ref('all')
const statusFilter = ref('all')
const sortKey = ref('name')
const sortDir = ref('asc')

const filtered = computed(() => {
    const items = [...list.value].filter(p => {
        const matchQ = !q.value || [p.name, p.code_payment, p.provider, p?.category?.name]
            .some(s => (s || '').toLowerCase().includes(q.value.toLowerCase()))
        const matchCat = categoryFilter.value === 'all' || String(p.category_id) === String(categoryFilter.value)
        const matchStatus = statusFilter.value === 'all' || p.status === statusFilter.value
        return matchQ && matchCat && matchStatus
    })
    items.sort((a, b) => {
        const va = a[sortKey.value]; const vb = b[sortKey.value]
        if (va == null && vb == null) return 0
        if (va == null) return sortDir.value === 'asc' ? -1 : 1
        if (vb == null) return sortDir.value === 'asc' ? 1 : -1
        if (typeof va === 'string' && typeof vb === 'string') {
            return sortDir.value === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va)
        }
        return sortDir.value === 'asc' ? (va > vb ? 1 : va < vb ? -1 : 0) : (vb > va ? 1 : vb < va ? -1 : 0)
    })
    return items
})
const sortBy = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortDir.value = 'asc'
    }
}

const showModal = ref(false)
const isEdit = ref(false)
const imagePreview = ref(null)

const empty = () => ({
    id: null,
    category_id: props.categories?.id || null, // fix default
    provider: null,
    code_payment: '',
    name: '',
    minimum: 0,
    maximum: 0,
    fee_fixed: 0,
    fee_percent: 0,
    number: '',
    name_account: '',
    image: null,     // file object
    image_url: '',   // fallback URL string
    instructions: '',
    status: 'active',
})
const form = useForm(empty())

const openCreate = () => {
    isEdit.value = false
    form.reset()
    Object.assign(form, empty())
    imagePreview.value = null
    showModal.value = true
}
const openEdit = (p) => {
    isEdit.value = true
    form.reset()
    Object.assign(form, {
        id: p.id,
        category_id: p.category_id || null,
        provider: p.provider || '',
        code_payment: p.code_payment || '',
        name: p.name || '',
        minimum: p.minimum ?? 0,
        maximum: p.maximum ?? 0,
        fee_fixed: p.fee_fixed ?? 0,
        fee_percent: Number(p.fee_percent ?? 0),
        number: p.number || '',
        name_account: p.name_account || '',
        image: null,
        image_url: p.image.startsWith('http')
            ? p.image
            : `${app}/assets/images/payment/${p.image}`,
        instructions: p.instructions || '',
        status: p.status || 'inactive',
    })
    imagePreview.value = form.image_url || null
    showModal.value = true
}
const onImageFile = (e) => {
    const f = e.target.files?.[0] || null
    form.image = f
    if (f) {
        const reader = new FileReader()
        reader.onload = () => (imagePreview.value = reader.result)
        reader.readAsDataURL(f)
    } else {
        imagePreview.value = form.image_url || null
    }
}
const submit = async () => {
    try {
        const routeName = isEdit.value ? 'api.payment.update' : 'api.payment.store'
        const url = isEdit.value ? route(routeName, form.id) : route(routeName)

        const fd = new FormData()
        const data = form.data()
        Object.entries(data).forEach(([k, v]) => {
            if (v !== undefined && v !== null) {
                fd.append(k, v)
            }
        })
        if (isEdit.value) fd.set('_method', 'PUT')
        if (form.image instanceof File) fd.set('image', form.image)

        const res = await axios.post(url, fd)

        if (res.data?.status) {
            const newPayment = res.data.data
            const idx = list.value.findIndex(p => p.id === newPayment.id)
            if (idx !== -1) list.value[idx] = newPayment
            else list.value.unshift(newPayment)

            showModal.value = false
            imagePreview.value = null
            toastify?.success?.("Gotcha!", res.data.message || "Berhasil disimpan!")
        } else {
            toastify?.error?.("Oops!", res.data.message || "Gagal menyimpan data.")
        }
    } catch (err) {
        const msg = err.response?.data?.message || err.message || "Terjadi kesalahan tak terduga."
        toastify?.error?.("Oops!", msg)
    }
}

const removeItem = (p) => {
    Swal.fire({
        title: 'Konfirmasi',
        text: `Hapus payment "${p.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(route('api.payment.destroy', p.id))
                .then(res => {
                    if (res.data?.status) {
                        toastify?.success?.("Gotcha!", res.data.message)
                        const idx = list.value.findIndex(x => x.id === p.id)
                        if (idx !== -1) list.value.splice(idx, 1)
                    } else {
                        toastify?.error?.("Whoops!", res.data.message)
                    }
                })
                .catch(err => {
                    toastify?.error?.("Oops!", err.response?.data?.message || err.message)
                })
        }
    })
}
watch(() => props.payments, (val) => { list.value = [...(val || [])] })
</script>

<template>

    <Head :title="'Konfigurasi Payment'" />
    <utility title="Payment" />

    <!-- Categories Manager -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0">Kategori Pembayaran</div>
            <div class="d-flex gap-2">
                <input v-model="catQ" type="text" class="form-control form-control-sm" placeholder="Cari kategori..." />
                <button class="btn btn-sm btn-primary text-nowrap" @click="openCreateCat">
                    <i class="fas fa-plus"></i> Tambah
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:42px;">#</th>
                            <th role="button" @click="catSortBy('name')">
                                Nama
                                <i
                                    :class="['ms-1', catSortKey === 'name' && catSortDir === 'asc' ? 'fas fa-caret-up' : catSortKey === 'name' ? 'fas fa-caret-down' : 'fas fa-filter']"></i>
                            </th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th>Diubah</th>
                            <th class="text-center" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(c, i) in catFiltered" :key="c.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ c.name }}</td>
                            <td>
                                <span :class="['badge', c.status === 'active' ? 'bg-success' : 'bg-danger']">
                                    {{ c.status === 'active' ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="small">{{ c.created_at ? new Date(c.created_at).toLocaleString() : '-' }}</td>
                            <td class="small">{{ c.updated_at ? new Date(c.updated_at).toLocaleString() : '-' }}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary me-2" @click="openEditCat(c)">
                                    <i class="fas fa-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" @click="removeCat(c)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="catFiltered.length === 0">
                            <td colspan="6" class="text-center py-4 text-muted">Tidak ada kategori.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payments Toolbar -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <input v-model="q" type="text" class="form-control"
                        placeholder="Cari nama/kode/provider/kategori..." />
                </div>
                <div class="col-md-4">
                    <select v-model="categoryFilter" class="form-select">
                        <option value="all">Semua Kategori</option>
                        <option v-for="c in (props.categories || [])" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select v-model="statusFilter" class="form-select">
                        <option value="all">Semua Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 text-md-end">
                    <button class="btn btn-primary w-100" @click="openCreate">
                        <i class="fas fa-plus"></i> Tambah Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0">Channels</div>
            <div class="small text-muted">Total: {{ filtered.length }}</div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:42px;">#</th>
                            <th role="button" @click="sortBy('name')">Nama
                                <i
                                    :class="['ms-1', sortKey === 'name' && sortDir === 'asc' ? 'fas fa-caret-up' : sortKey === 'name' ? 'fas fa-caret-down' : 'fas fa-filter']"></i>
                            </th>
                            <th>Kode</th>
                            <th>Provider</th>
                            <th>Kategori</th>
                            <th class="text-end">Min</th>
                            <th class="text-end">Max</th>
                            <th class="text-end">Fee</th>
                            <th>Logo</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(p, i) in filtered" :key="p.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ p.name }}</td>
                            <td><code class="small">{{ p.code_payment }}</code></td>
                            <td>{{ p.provider }}</td>
                            <td>{{ p?.category?.name || '-' }}</td>
                            <td class="text-end">{{ new Intl.NumberFormat().format(p.minimum ?? 0) }}</td>
                            <td class="text-end">{{ new Intl.NumberFormat().format(p.maximum ?? 0) }}</td>
                            <td class="text-end">
                                {{ (p.fee_fixed ?? 0).toLocaleString() }} + {{ Number(p.fee_percent ?? 0).toFixed(2) }}%
                            </td>
                            <td>
                                <img v-if="p.image" :src="p.image.startsWith('http')
                                    ? p.image
                                    : `${app}/assets/images/payment/${p.image}`" alt="logo"
                                    style="width:36px;height:36px;object-fit:contain" />
                            </td>

                            <td class="text-center">
                                <span :class="['badge', p.status === 'active' ? 'bg-success' : 'bg-danger']">
                                    {{ p.status === 'active' ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary me-2" @click="openEdit(p)">
                                    <i class="fas fa-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" @click="removeItem(p)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="11" class="text-center py-4 text-muted">Tidak ada data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" tabindex="-1" :class="{ show: showCatModal }" style="display: block;" v-if="showCatModal"
        @click.self="showCatModal = false" aria-modal="true" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ isEditCat ? 'Edit Kategori' : 'Tambah Kategori' }}</h5>
                    <button type="button" class="btn-close" @click="showCatModal = false"></button>
                </div>
                <form @submit.prevent="submitCat">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama</label>
                                <input v-model="catForm.name" class="form-control" placeholder="Nama kategori"
                                    required />
                                <div v-if="catForm.errors.name" class="invalid-feedback d-block">{{ catForm.errors.name
                                    }}</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Status</label>
                                <select v-model="catForm.status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <div v-if="catForm.errors.status" class="invalid-feedback d-block">{{
                                    catForm.errors.status }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" @click="showCatModal = false">Batal</button>
                        <button type="submit" class="btn btn-primary" :disabled="catForm.processing">
                            {{ isEditCat ? 'Simpan Perubahan' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" tabindex="-1" :class="{ show: showModal }" style="display: block;" v-if="showModal"
        @click.self="showModal = false" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ isEdit ? 'Edit Payment' : 'Tambah Payment' }}</h5>
                    <button type="button" class="btn-close" @click="showModal = false"></button>
                </div>
                <form @submit.prevent="submit" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Kategori</label>
                                <select v-model="form.category_id" class="form-select" required>
                                    <option :value="null">Pilih kategori</option>
                                    <option v-for="c in (props.categories || [])" :key="c.id" :value="c.id">{{ c.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Provider</label>
                                <select v-model="form.provider" class="form-select">
                                    <option :value="null">Pilih provider</option>
                                    <option v-for="(val, key) in kPayment" :key="key" :value="key">{{
                                        key.charAt(0).toUpperCase() + key.slice(1) }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode Payment</label>
                                <input v-model="form.code_payment" class="form-control"
                                    placeholder="QRIS / VA / E-Wallet..." required />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nama</label>
                                <input v-model="form.name" class="form-control" placeholder="QRIS / QRISS ..."
                                    required />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Minimum</label>
                                <input v-model.number="form.minimum" type="number" class="form-control" min="0" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Maximum</label>
                                <input v-model.number="form.maximum" type="number" class="form-control" min="0" />
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Fee Fixed</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input v-model.number="form.fee_fixed" type="number" class="form-control" min="0"
                                        step="1" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fee Percent</label>
                                <div class="input-group">
                                    <input v-model.number="form.fee_percent" type="number" class="form-control" min="0"
                                        step="0.01" />
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nomor</label>
                                <input v-model="form.number" class="form-control" placeholder="No. VA / tag / label" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nama Akun</label>
                                <input v-model="form.name_account" class="form-control" placeholder="Nama pemilik" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Logo (URL)</label>
                                <input v-model="form.image_url" class="form-control" placeholder="https://..."
                                    @input="e => imagePreview = form.image_url" />
                                <div class="form-text">Boleh kosong jika unggah file di bawah.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Logo (File)</label>
                                <input type="file" class="form-control" accept="image/*" @change="onImageFile" />
                            </div>
                            <div class="col-12">
                                <img v-if="imagePreview" :src="imagePreview" alt="preview" class="border rounded mt-2"
                                    style="width:72px;height:72px;object-fit:contain;" />
                            </div>

                            <div class="col-12">
                                <label class="form-label">Instruksi</label>
                                <textarea v-model="form.instructions" class="form-control" rows="3"
                                    placeholder="Langkah-langkah pembayaran"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Status</label>
                                <select v-model="form.status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="col-md-8 d-flex align-items-end">
                                <div v-if="form.progress" class="w-100">
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar"
                                            :style="{ width: form.progress.percentage + '%' }">
                                            {{ form.progress.percentage }}%
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" @click="showModal = false">Batal</button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
