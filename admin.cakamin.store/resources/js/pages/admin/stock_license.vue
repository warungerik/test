<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import utility from '../admin/utility/page-title.vue'
import { ref, computed, watch } from 'vue'
import { route } from 'ziggy-js'
import Swal from 'sweetalert2'
import axios from 'axios'

const props = defineProps({
    app: String,
    stock: Array,
    products: { type: Array, default: () => [] },
    denoms: { type: Array, default: () => [] }
})
const stockList = ref([...(props.stock || [])])


const search = ref('')
const productFilter = ref('all')
const statusFilter = ref('all')
const sortKey = ref('created_at')
const sortDir = ref('desc')

const currentPage = ref(1)
const perPage = ref(10)

const showModal = ref(false)
const isEdit = ref(false)
const isBulkMode = ref(false)

const bulkLicenses = ref('')

const sortBy = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortDir.value = 'asc'
    }
}

const filtered = computed(() => {
    const items = [...stockList.value].filter(item => {
        const searchMatch = !search.value || [
            item.license,
            item?.product?.name,
            item?.denom?.name
        ].some(s => (s || '').toLowerCase().includes(search.value.toLowerCase()))

        const productMatch = productFilter.value === 'all' || String(item.product_id) === String(productFilter.value)

        return searchMatch && productMatch
    })

    items.sort((a, b) => {
        const va = a[sortKey.value]
        const vb = b[sortKey.value]
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

const totalPages = computed(() => Math.ceil(filtered.value.length / perPage.value))
const paginated = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filtered.value.slice(start, start + perPage.value)
})

const availableDenoms = computed(() => {
    if (!form.product_id) return []
    return (props.denoms || []).filter(d => d.product_id === form.product_id)
})

const form = useForm({
    id: null,
    product_id: null,
    denom_id: null,
    license: ''
})

const openCreate = () => {
    isEdit.value = false
    isBulkMode.value = false
    form.reset()
    form.clearErrors()
    bulkLicenses.value = ''
    showModal.value = true
}

const openBulk = () => {
    isEdit.value = false
    isBulkMode.value = true
    form.reset()
    form.clearErrors()
    bulkLicenses.value = ''
    showModal.value = true
}

const openEdit = (item) => {
    isEdit.value = true
    isBulkMode.value = false
    form.reset()
    form.clearErrors()
    Object.assign(form, {
        id: item.id,
        product_id: item.product_id,
        denom_id: item.denom_id,
        license: item.license || ''
    })
    showModal.value = true
}

const submit = async () => {
    try {
        if (isBulkMode.value) {
            const licenses = bulkLicenses.value.split('\n').filter(l => l.trim())
            if (!licenses.length) {
                alert('Masukkan minimal 1 license')
                return
            }

            const payload = {
                product_id: form.product_id,
                denom_id: form.denom_id,
                licenses: licenses
            }

            const res = await axios.post(route('api.stock-licenses.bulk'), payload)
            if (res.data?.status) {
                stockList.value.unshift(...res.data.data)
                showModal.value = false
                toastify?.success?.('Success!', `${licenses.length} license berhasil ditambahkan`)
            } else {
                toastify?.error?.('Error!', res.data?.message || 'Gagal menambahkan')
            }
        } else {
            const routeName = isEdit.value ? 'api.stock-licenses.update' : 'api.stock-licenses.store'
            const payload = { ...form.data() }
            if (isEdit.value) payload._method = 'PUT'

            const res = await axios.post(
                isEdit.value ? route(routeName, form.id) : route(routeName),
                payload
            )

            if (res.data?.status) {
                const newItem = res.data.data
                if (isEdit.value) {
                    const idx = stockList.value.findIndex(s => s.id === newItem.id)
                    if (idx !== -1) stockList.value[idx] = newItem
                } else {
                    stockList.value.unshift(newItem)
                }
                showModal.value = false
                toastify?.success?.('Success!', res.data.message || 'Berhasil disimpan')
            } else {
                toastify?.error?.('Error!', res.data?.message || 'Gagal menyimpan')
            }
        }
    } catch (err) {
        toastify?.error?.('Error!', err.response?.data?.message || err.message || 'Terjadi kesalahan')
    }
}

const deleteItem = (item) => {
    Swal.fire({
        title: 'Konfirmasi',
        text: `Hapus license "${item.license}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('api.stock-licenses.destroy', item.id))
                .then(res => {
                    if (res.data?.status) {
                        const idx = stockList.value.findIndex(s => s.id === item.id)
                        if (idx !== -1) stockList.value.splice(idx, 1)
                        toastify?.success?.('Success!', res.data.message)
                    } else {
                        toastify?.error?.('Error!', res.data?.message)
                    }
                })
                .catch(err => {
                    toastify?.error?.('Error!', err.response?.data?.message || err.message)
                })
        }
    })
}

const currency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount)

watch(() => props.stock, (val) => { stockList.value = [...(val || [])] })
</script>

<template>

    <Head title="Stock License" />
    <utility title="Stock" />

    <!-- Toolbar -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Pencarian</label>
                    <input v-model="search" type="text" class="form-control"
                        placeholder="Cari license atau product..." />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Product</label>
                    <select v-model="productFilter" class="form-select">
                        <option value="all">Semua Product</option>
                        <option v-for="p in (props.products || [])" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select v-model.number="perPage" class="form-select" @change="currentPage = 1">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="col-md text-end">
                    <button class="btn btn-primary me-2" @click="openCreate">
                        <i class="fas fa-plus me-1"></i> Tambah License
                    </button>
                    <button class="btn btn-success" @click="openBulk">
                        <i class="fas fa-layer-group me-1"></i> Bulk Import
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-1">Total License</h6>
                            <h4 class="mb-0">{{ filtered.length }}</h4>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-database fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-1">Products</h6>
                            <h4 class="mb-0">{{ (props.products || []).length }}</h4>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-box fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-1">Packages</h6>
                            <h4 class="mb-0">{{ (props.denoms || []).length }}</h4>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tags fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-1">Page {{ currentPage }}</h6>
                            <h4 class="mb-0">of {{ totalPages }}</h4>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-square fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Stock License</h5>
            <small class="text-muted">Total: {{ filtered.length }} licenses</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th role="button" @click="sortBy('license')">
                                License
                                <i
                                    :class="['ms-1 fas', sortKey === 'license' && sortDir === 'asc' ? 'fa-caret-up' : sortKey === 'license' ? 'fa-caret-doown' : 'fa-filter']"></i>
                            </th>
                            <th>Product</th>
                            <th>Package</th>
                            <th class="text-end">Price</th>
                            <th role="button" @click="sortBy('created_at')">
                                Added
                                <i
                                    :class="['ms-1 fas', sortKey === 'created_at' && sortDir === 'asc' ? 'fa-caret-up' : sortKey === 'created_at' ? 'fa-caret-doown' : 'fa-filter']"></i>
                            </th>
                            <th class="text-center" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in paginated" :key="item.id">
                            <td>{{ (currentPage - 1) * perPage + index + 1 }}</td>
                            <td>
                                <code class="fw-bold">{{ item.license }}</code>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img v-if="item?.product?.thumbnail"
                                        :src="`${app}/assets/images/product/${item.product.thumbnail}`"
                                        class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover;"
                                        :alt="item.product.name" />
                                    <div>
                                        <div class="fw-semibold">{{ item?.product?.name || 'N/A' }}</div>
                                        <small class="text-muted">{{ item?.product?.slug || '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ item?.denom?.name || 'N/A' }}</div>
                                <small class="text-muted">{{ item?.denom?.duration || 0 }} hari</small>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold">{{ currency(item?.denom?.price || 0) }}</span>
                            </td>
                            <td>
                                <small class="text-muted">{{ new Date(item.created_at).toLocaleDateString('id-ID')
                                }}</small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-secondary" @click="openEdit(item)" title="Edit">
                                        <i class="fas fa-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" @click="deleteItem(item)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="paginated.length === 0">
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox display-6 mb-2"></i>
                                <div>
                                    Tidak ada data license
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                Showing {{ paginated.length ? ((currentPage - 1) * perPage + 1) : 0 }}
                to {{ Math.min(currentPage * perPage, filtered.length) }}
                of {{ filtered.length }} entries
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                        <button class="page-link" @click="currentPage > 1 && currentPage--">«</button>
                    </li>
                    <li v-for="n in Math.min(totalPages, 5)" :key="n" class="page-item"
                        :class="{ active: currentPage === n }">
                        <button class="page-link" @click="currentPage = n">{{ n }}</button>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                        <button class="page-link" @click="currentPage < totalPages && currentPage++">»</button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" tabindex="-1" :class="{ show: showModal }" style="display: block;" v-if="showModal"
        @click.self="showModal = false" aria-modal="true" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ isBulkMode ? 'Bulk Import License' : isEdit ? 'Edit License' : 'Tambah License' }}
                    </h5>
                    <button type="button" class="btn-close" @click="showModal = false"></button>
                </div>
                <form @submit.prevent="submit">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Product *</label>
                                <select v-model="form.product_id" class="form-select" required>
                                    <option :value="null">Pilih Product</option>
                                    <option v-for="p in (props.products.filter(p => p.ppob == '0') || [])" :key="p.id"
                                        :value="p.id">
                                        {{ p.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.product_id" class="invalid-feedback d-block">
                                    {{ form.errors.product_id }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Package *</label>
                                <select v-model="form.denom_id" class="form-select" required
                                    :disabled="!form.product_id">
                                    <option :value="null">{{ form.product_id ? 'Pilih Package' : 'Pilih Product dulu' }}
                                    </option>
                                    <option v-for="d in availableDenoms" :key="d.id" :value="d.id">
                                        {{ d.name }} - {{ currency(d.price) }}
                                    </option>
                                </select>
                                <div v-if="form.errors.denom_id" class="invalid-feedback d-block">
                                    {{ form.errors.denom_id }}
                                </div>
                            </div>

                            <div class="col-12" v-if="!isBulkMode">
                                <label class="form-label">License *</label>
                                <input v-model="form.license" type="text" class="form-control"
                                    placeholder="Masukkan license key" required />
                                <div v-if="form.errors.license" class="invalid-feedback d-block">
                                    {{ form.errors.license }}
                                </div>
                            </div>

                            <div class="col-12" v-if="isBulkMode">
                                <label class="form-label">Bulk Licenses *</label>
                                <textarea v-model="bulkLicenses" class="form-control" rows="8"
                                    placeholder="Masukkan license, satu per baris:&#10;LICENSE-001&#10;LICENSE-002&#10;LICENSE-003"
                                    required></textarea>
                                <div class="form-text">
                                    Masukkan satu license per baris. Total: {{bulkLicenses.split('\n').filter(l =>
                                        l.trim()).length}} license
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" @click="showModal = false">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            {{ isBulkMode ? 'Import All' : isEdit ? 'Update' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
