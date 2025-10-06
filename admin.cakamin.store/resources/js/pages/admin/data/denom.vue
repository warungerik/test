<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import { route } from 'ziggy-js'
import Swal from 'sweetalert2'
import axios from 'axios'
import utility from '../../admin/utility/page-title.vue'

const props = defineProps({
    app: String,
    denom: Array,
    products: { type: Array, default: () => [] }
})
const denomList = ref([...(props.denom || [])])

// Filter & Search
const search = ref('')
const productFilter = ref('all')
const statusFilter = ref('all')
const sortKey = ref('created_at')
const sortDir = ref('desc')

// Pagination
const currentPage = ref(1)
const perPage = ref(10)

// Modal state
const showModal = ref(false)
const isEdit = ref(false)

const sortBy = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortDir.value = 'asc'
    }
}

const filtered = computed(() => {
    return denomList.value.filter(item => {
        const searchMatch = !search.value || [
            item.name,
            item?.product?.name,
            item.price.toString(),
            item.duration.toString()
        ].some(s => (s || '').toLowerCase().includes(search.value.toLowerCase()))

        const productMatch = productFilter.value === 'all' ||
            String(item.product_id) === String(productFilter.value)

        const statusMatch = statusFilter.value === 'all' ||
            item.status === statusFilter.value

        return searchMatch && productMatch && statusMatch
    }).sort((a, b) => {
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
})

const totalPages = computed(() => Math.ceil(filtered.value.length / perPage.value))
const paginated = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filtered.value.slice(start, start + perPage.value)
})

const form = useForm({
    id: null,
    product_id: null,
    name: '',
    price: '',
    duration: '',
    status: 'active'
})

const openCreate = () => {
    isEdit.value = false
    form.reset()
    form.clearErrors()
    showModal.value = true
}

const openEdit = (item) => {
    isEdit.value = true
    form.reset()
    form.clearErrors()
    Object.assign(form, {
        id: item.id,
        product_id: item.product_id,
        name: item.name,
        price: item.price,
        duration: item.duration,
        status: item.status
    })
    showModal.value = true
}

const submit = async () => {
    try {
        const routeName = isEdit.value ? 'api.denom.update' : 'api.denom.store'
        const payload = { ...form.data() }
        if (isEdit.value) payload._method = 'PUT'

        const res = await axios.post(
            isEdit.value ? route(routeName, form.id) : route(routeName),
            payload
        )

        if (res.data?.status) {
            const newItem = res.data.data
            if (isEdit.value) {
                const idx = denomList.value.findIndex(d => d.id === newItem.id)
                if (idx !== -1) denomList.value[idx] = newItem
            } else {
                denomList.value.unshift(newItem)
            }
            showModal.value = false
            toastify?.success?.('Success!', res.data.message || 'Data berhasil disimpan')
        } else {
            toastify?.error?.('Error!', res.data?.message || 'Gagal menyimpan data')
        }
    } catch (err) {
        toastify?.error?.('Error!', err.response?.data?.message || err.message || 'Terjadi kesalahan')
    }
}

const deleteItem = (item) => {
    Swal.fire({
        title: 'Konfirmasi',
        text: `Hapus paket "${item.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('api.denom.destroy', item.id))
                .then(res => {
                    if (res.data?.status) {
                        const idx = denomList.value.findIndex(d => d.id === item.id)
                        if (idx !== -1) denomList.value.splice(idx, 1)
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

const currency = (amount) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
}).format(amount)

watch(() => props.denom, (val) => { denomList.value = [...(val || [])] })

</script>

<template>

    <Head title="Kelola Denominasi" />
    <utility :title="'Denom'" />
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-1">Total Paket</h6>
                            <h4 class="mb-0">{{ filtered.length }}</h4>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tags fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-1">Paket Aktif</h6>
                            <h4 class="mb-0">{{filtered.filter(d => d.status === 'active').length}}</h4>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-circle-check fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-1">Harga Terendah</h6>
                            <h4 class="mb-0">{{currency(Math.min(...filtered.map(d => d.price)))}}</h4>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-circle-down fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-1">Harga Tertinggi</h6>
                            <h4 class="mb-0">{{currency(Math.max(...filtered.map(d => d.price)))}}</h4>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-circle-up fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Pencarian</label>
                    <input v-model="search" type="text" class="form-control"
                        placeholder="Cari nama paket, produk, harga..." />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Produk</label>
                    <select v-model="productFilter" class="form-select">
                        <option value="all">Semua Produk</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select v-model="statusFilter" class="form-select">
                        <option value="all">Semua Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Halaman</label>
                    <select v-model.number="perPage" class="form-select" @change="currentPage = 1">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Daftar Paket Denom</h5>
            <button class="btn btn-primary" @click="openCreate">
                <i class="fas fa-plus"></i>
                Tambah Paket
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th role="button" @click="sortBy('name')">
                                Nama Paket
                                <i
                                    :class="['ms-1 fas', sortKey === 'name' && sortDir === 'asc' ? 'fa-caret-up' : sortKey === 'name' ? 'fa-caret-down' : 'fa-filter']"></i>
                            </th>
                            <th>Produk</th>
                            <th role="button" @click="sortBy('price')" class="text-end">
                                Harga
                                <i
                                    :class="['ms-1 fas', sortKey === 'price' && sortDir === 'asc' ? 'fa-caret-up' : sortKey === 'price' ? 'fa-caret-down' : 'fa-filter']"></i>
                            </th>
                            <th role="button" @click="sortBy('duration')" class="text-center">
                                Durasi
                                <i
                                    :class="['ms-1 fas', sortKey === 'duration' && sortDir === 'asc' ? 'fa-caret-up' : sortKey === 'duration' ? 'fa-caret-down' : 'fa-filter']"></i>
                            </th>
                            <th class="text-center">Status</th>
                            <th role="button" @click="sortBy('created_at')">
                                Dibuat
                                <i
                                    :class="['ms-1 fas', sortKey === 'created_at' && sortDir === 'asc' ? 'fa-caret-up' : sortKey === 'created_at' ? 'fa-caret-down' : 'fa-filter']"></i>
                            </th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in paginated" :key="item.id">
                            <td>{{ (currentPage - 1) * perPage + index + 1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ item.name }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img v-if="item?.product?.thumbnail"
                                        :src="`${app}/assets/images/product/${item.product.thumbnail}`"
                                        class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover;"
                                        :alt="item.product.name" />
                                    <div>
                                        <div class="fw-semibold">{{ item?.product?.name || 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-success">{{ currency(item.price) }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info text-dark">
                                    {{ item.duration }} {{ item.duration === 1 ? 'hari' : 'hari' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span :class="['badge', item.status === 'active' ? 'bg-success' : 'bg-danger']">
                                    {{ item.status === 'active' ? 'Active' : 'Inactive' }}
                                </span>
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
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox display-6 d-block mb-2"></i>
                                Tidak ada data denominasi
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
                        {{ isEdit ? 'Edit Paket' : 'Tambah Paket' }}
                    </h5>
                    <button type="button" class="btn-close" @click="showModal = false"></button>
                </div>
                <form @submit.prevent="submit">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Produk *</label>
                                <select v-model="form.product_id" class="form-select" required>
                                    <option :value="null">Pilih Produk</option>
                                    <option v-for="product in products" :key="product.id" :value="product.id">
                                        {{ product.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.product_id" class="invalid-feedback d-block">
                                    {{ form.errors.product_id }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Nama Paket *</label>
                                <input v-model="form.name" type="text" class="form-control"
                                    placeholder="Contoh: Paket 3 Hari" required />
                                <div v-if="form.errors.name" class="invalid-feedback d-block">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Harga *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input v-model.number="form.price" type="number" class="form-control"
                                        placeholder="25000" min="0" required />
                                </div>
                                <div v-if="form.errors.price" class="invalid-feedback d-block">
                                    {{ form.errors.price }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Durasi *</label>
                                <div class="input-group">
                                    <input v-model.number="form.duration" type="number" class="form-control"
                                        placeholder="3" min="1" required />
                                    <span class="input-group-text">hari</span>
                                </div>
                                <div v-if="form.errors.duration" class="invalid-feedback d-block">
                                    {{ form.errors.duration }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Status</label>
                                <select v-model="form.status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <div v-if="form.errors.status" class="invalid-feedback d-block">
                                    {{ form.errors.status }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" @click="showModal = false">Batal</button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            {{ isEdit ? 'Update' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
