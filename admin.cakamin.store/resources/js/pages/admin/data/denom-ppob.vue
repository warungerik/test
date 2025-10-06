<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import utility from '../../admin/utility/page-title.vue'
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const props = defineProps({
    app: String,
    config: Object,
    category: Object,
    product: Object,
})

const replace_text = ref([props.config.replace_text])
const selectedItems = ref([])
const showModal = ref(false)
const isEditing = ref(false)
const showModalReplace = ref(false)
const showModalSetProduct = ref(false)
const showModalSetGroup = ref(false)
const loading = ref(false)
const syncing = ref(false)
const saving = ref(false)
const dropdown = ref(false)
const groupCategory = ref("")
const selectedProduct = ref("")
const productSearchQuery = ref("")
const original = ref("")
const replace = ref("")
const dataParameter = ref("")
const showAutoUpdateModal = ref(false)
const currentAutoUpdateItem = ref(null)
const apiParams = reactive({
    page: 1,
    per_page: 10,
    search: '',
    sort_by: 'id',
    sort_order: 'desc',
    status: '',
    category: '',
    provider: ''
})

const form = reactive({
    id: null,
    product_id: 0,
    category_id: 0,
    provider: '',
    code: '',
    name: '',
    category: '',
    brand: '',
    type: 'Umum',
    modal: 0,
    price: 0,
    multi: 1,
    note: '',
    server: 0,
    offline: '00:00 - 00:00',
    status: 'active',
    auto_update: []
})

const emptyCategoryPayload = () => ({
    id: null,
    name: "",
    icon: "",
    type: "ppob",
    status: "active",
})

const isEdit = ref(false)
const dataCategories = ref([...(props.category || [])])
const dataProducts = ref([...(props.product || [])])
const formCategory = useForm(emptyCategoryPayload())
const showModalCategory = ref(false)
const iconSuggestions = [
    { icon: "fas fa-gamepad", label: "Gaming" },
    { icon: "fas fa-mobile-alt", label: "Mobile" },
    { icon: "fas fa-laptop", label: "PC" },
    { icon: "fas fa-star", label: "Popular" },
    { icon: "fas fa-trophy", label: "Tournament" },
    { icon: "fas fa-dice", label: "Casino" },
    { icon: "fas fa-chess", label: "Strategy" },
    { icon: "fas fa-rocket", label: "Action" },
    { icon: "fas fa-puzzle-piece", label: "Puzzle" },
    { icon: "fas fa-car", label: "Racing" },
    { icon: "fas fa-futbol", label: "Sports" },
    { icon: "fas fa-magic", label: "Fantasy" }
]

const autoUpdateForm = reactive({
    item_id: null,
    selected_fields: []
})

const availableAutoUpdateFields = ref([
    { value: 'modal', label: 'Modal', description: 'Harga modal akan diperbarui otomatis' },
    { value: 'price', label: 'Harga Jual', description: 'Harga jual akan diperbarui otomatis' },
    { value: 'note', label: 'Catatan', description: 'Catatan/deskripsi akan diperbarui otomatis' },
    { value: 'status', label: 'Status', description: 'Status aktif/nonaktif akan diperbarui otomatis' },
    { value: 'name', label: 'Nama Produk', description: 'Nama produk akan diperbarui otomatis' },
    { value: 'type', label: 'Tipe Produk', description: 'Tipe produk akan diperbarui otomatis' }
])

const apiResponse = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0
})
const providerOptions = ref([{ value: 'digiflazz', label: 'DigiFlazz' }])
const categoryOptions = ref([])

const categoryPagination = reactive({
    currentPage: 1,
    perPage: 10,
    searchQuery: ''
})

const filteredCategories = computed(() => {
    let filtered = [...dataCategories.value]

    if (categoryPagination.searchQuery.trim()) {
        const query = categoryPagination.searchQuery.toLowerCase()
        filtered = filtered.filter(cat =>
            cat.name?.toLowerCase().includes(query) ||
            cat.icon?.toLowerCase().includes(query)
        )
    }

    return filtered
})

const paginatedCategories = computed(() => {
    const start = (categoryPagination.currentPage - 1) * categoryPagination.perPage
    const end = start + categoryPagination.perPage
    return filteredCategories.value.slice(start, end)
})

const categoryTotalPages = computed(() => {
    return Math.ceil(filteredCategories.value.length / categoryPagination.perPage)
})

const categoryDisplayInfo = computed(() => {
    const total = filteredCategories.value.length
    const from = total > 0 ? (categoryPagination.currentPage - 1) * categoryPagination.perPage + 1 : 0
    const to = Math.min(categoryPagination.currentPage * categoryPagination.perPage, total)
    return `Menampilkan ${from} - ${to} dari ${total} data`
})

const categoryVisiblePages = computed(() => {
    const total = categoryTotalPages.value
    const current = categoryPagination.currentPage
    const delta = 2
    const pages = []
    const start = Math.max(2, current - delta)
    const end = Math.min(total - 1, current + delta)

    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    return pages
})

const filteredProducts = computed(() => {
    let filtered = [...dataProducts.value]

    if (productSearchQuery.value.trim()) {
        const query = productSearchQuery.value.toLowerCase()
        filtered = filtered.filter(product =>
            product.name?.toLowerCase().includes(query) ||
            product.slug?.toLowerCase().includes(query) ||
            product.publisher?.toLowerCase().includes(query) ||
            product.game?.name?.toLowerCase().includes(query) ||
            product.category?.name?.toLowerCase().includes(query) ||
            product.provider?.name?.toLowerCase().includes(query)
        )
    }

    return filtered.filter(p => p.status === 'active')
})

const changeCategoryPage = (page) => {
    if (page >= 1 && page <= categoryTotalPages.value) {
        categoryPagination.currentPage = page
    }
}

const changeCategoryPerPage = (newPerPage) => {
    categoryPagination.perPage = parseInt(newPerPage)
    categoryPagination.currentPage = 1
}

watch(() => categoryPagination.searchQuery, () => {
    categoryPagination.currentPage = 1
})

let searchDebounceTimer = null
const showSuccess = (msg) => {
    Swal.fire({ icon: 'success', title: 'Berhasil', text: msg, timer: 2000, showConfirmButton: false })
}
const showLoading = () => {
    Swal.fire({ title: 'Loading', html: 'Mohon tunggu sebentar', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } })
}
const showError = (msg) => {
    Swal.fire({ icon: 'error', title: 'Oops...', text: msg })
}
const showConfirm = async (msg) => {
    const result = await Swal.fire({ title: 'Konfirmasi', text: msg, icon: 'question', showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal' })
    return result.isConfirmed
}

watch([
    () => apiParams.page,
    () => apiParams.per_page,
    () => apiParams.sort_by,
    () => apiParams.sort_order,
    () => apiParams.status,
    () => apiParams.category,
    () => apiParams.provider
], () => { fetchData() })
watch(() => apiParams.search, (newSearch) => {
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
    searchDebounceTimer = setTimeout(() => {
        apiParams.page = 1
        fetchData()
    }, 500)
})

const fetchData = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams()
        Object.keys(apiParams).forEach(key => {
            if (apiParams[key] !== '' && apiParams[key] !== null) {
                params.append(key, apiParams[key])
            }
        })
        const response = await axios.post(route('api.get-denom-ppob'), params, { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
        if (response.data.status) {
            apiResponse.value = response.data.data
            selectedItems.value = []
            categoryOptions.value = response.data.category
        } else {
            showError('Gagal memuat data: ' + response.data.message)
        }
    } catch (error) {
        showError('Terjadi kesalahan saat memuat data')
    } finally {
        loading.value = false
    }
}

const resetForm = () => {
    Object.keys(form).forEach(key => {
        if (key === 'id') form[key] = null
        else if (['product_id', 'category_id', 'modal', 'price', 'server'].includes(key)) form[key] = 0
        else if (key === 'multi') form[key] = 1
        else if (key === 'type') form[key] = 'Umum'
        else if (key === 'offline') form[key] = '00:00 - 00:00'
        else if (key === 'status') form[key] = 'active'
        else if (key === 'auto_update') form[key] = []
        else form[key] = ''
    })
}

const openModal = (item = null) => {
    if (item) {
        isEditing.value = true
        Object.keys(form).forEach(key => {
            form[key] = item[key] || (typeof form[key] === 'number' ? 0 : '')
        })
    } else {
        isEditing.value = false
        resetForm()
    }
    showModal.value = true
}
const closeModal = () => { showModal.value = false; resetForm() }

const saveItem = async () => {
    saving.value = true
    try {
        const endpoint = isEditing.value ? 'api.update-denom-ppob' : 'api.store-denom-ppob'
        const response = await axios.post(route(endpoint), form)
        if (response.data.status) {
            showSuccess(response.data.message || 'Data berhasil disimpan')
            closeModal()
            fetchData()
        } else {
            showError('Gagal menyimpan: ' + response.data.message)
        }
    } catch (error) {
        showError('Terjadi kesalahan saat menyimpan data')
    } finally {
        saving.value = false
    }
}
const openModalReplace = () => { showModalReplace.value = true }
const deleteReplaceText = async (id) => {
    if (!(await showConfirm('Apakah Anda yakin ingin menghapus data ini?'))) return
    showLoading()
    try {
        const response = await axios.post(route('api.delete-replace-text'), { 'id': id })
        if (response.data.status) {
            showSuccess('Data berhasil dihapus')
            replace_text.value[0] = response.data.data
        } else {
            showError('Gagal menghapus data')
        }
    } catch (err) {
        showError('Terjadi kesalahan saat menghapus data')
    }
}
const updateReplace = async () => {
    try {
        showLoading()
        const res = await axios.post(route('api.update-replace-text'))
        if (res.data.status) {
            showSuccess('Data berhasil dihapus')
            fetchData()
        } else {
            showError('Gagal menghapus: ' + response.data.message)
        }
    } catch (err) {
        showError('Terjadi kesalahan saat menambahkan data')
    }
}
const addReplaceText = async () => {
    try {
        const res = await axios.post(route('api.tambah-replace-text'), { 'original': original.value, 'replace': replace.value, 'data': dataParameter.value })
        if (res.data.status) {
            showSuccess('Data berhasil dihapus')
            replace_text.value[0] = res.data.data
        } else {
            showError('Gagal menghapus: ' + response.data.message)
        }
    } catch (err) {
        showError('Terjadi kesalahan saat menambahkan data')
    }
}
const closeModalReplace = () => { showModalReplace.value = false }
const deleteItem = async (id) => {
    if (!(await showConfirm('Apakah Anda yakin ingin menghapus item ini?'))) return
    dropdown.value = true
    try {
        const response = await axios.post(route('api.delete-denom-ppob'), { id })
        if (response.data.status) {
            showSuccess('Data berhasil dihapus')
        } else {
            showError('Gagal menghapus: ' + response.data.message)
        }
    } catch (error) {
        showError('Terjadi kesalahan saat menghapus data')
    } finally {
        dropdown.value = false
    }
}
const toggleSelectAll = () => {
    if (selectedItems.value.length === apiResponse.value.data.length) {
        selectedItems.value = []
    } else {
        selectedItems.value = apiResponse.value.data.map(item => item.id)
    }
}
const syncDigiflazz = async () => {
    if (!(await showConfirm(`Apakah anda yakin ingin sinkronisasi data digiflazz?`))) return
    showLoading()
    try {
        const response = await axios.post(route('api.sinkronisasi-digiflazz'))
        if (response.data.status) {
            showSuccess(`Berhasil sinkronisasi data`)
            fetchData()
        } else {
            showError('Gagal sinkronisasi data digiflazz')
        }
    } catch (err) {
        showError('Terjadi kesalahan saat sinkronisasi data')
    }
}
const setProduct = async () => {
    if (selectedItems.value.length === 0) {
        showError('Pilih item yang ingin di-set terlebih dahulu')
        return
    }
    showModalSetProduct.value = true
}
const setGroup = async () => {
    if (selectedItems.value.length === 0) {
        showError('Pilih item yang ingin dihapus terlebih dahulu')
        return
    }
    showModalSetGroup.value = true
}
const saveGroup = async () => {
    const res = await axios.post(route('api.set-group-ppob'), {
        ids: selectedItems.value,
        category: groupCategory.value,
    })
    if (res.data.status) {
        showSuccess(res.data.message)
        selectedItems.value = []
        showModalSetGroup.value = false
        fetchData()
    } else {
        showError('Gagal mengupdate: ' + res.data.message)
    }
}
const saveProduct = async () => {
    const res = await axios.post(route('api.set-product-ppob'), {
        ids: selectedItems.value,
        product_id: selectedProduct.value,
    })
    if (res.data.status) {
        showSuccess(res.data.message)
        selectedItems.value = []
        showModalSetProduct.value = false
        fetchData()
    } else {
        showError('Gagal mengupdate: ' + res.data.message)
    }
}
const bulkDelete = async () => {
    if (selectedItems.value.length === 0) {
        showError('Pilih item yang ingin dihapus terlebih dahulu')
        return
    }
    if (!(await showConfirm(`Apakah Anda yakin ingin menghapus ${selectedItems.value.length} item yang dipilih?`))) return
    dropdown.value = true
    try {
        const response = await axios.post(route('api.bulk-delete-denom-ppob'), { ids: selectedItems.value })
        if (response.data.status) {
            showSuccess(`${selectedItems.value.length} data berhasil dihapus`)
            selectedItems.value = []
            fetchData()
        } else {
            showError('Gagal menghapus: ' + response.data.message)
        }
    } catch (error) {
        showError('Terjadi kesalahan saat menghapus data')
    } finally {
        dropdown.value = false
    }
}

const openAutoUpdateModal = (item) => {
    currentAutoUpdateItem.value = item
    autoUpdateForm.item_id = item.id
    autoUpdateForm.selected_fields = Array.isArray(item.auto_update) ? [...item.auto_update] : []
    showAutoUpdateModal.value = true
}
const closeAutoUpdateModal = () => {
    showAutoUpdateModal.value = false
    currentAutoUpdateItem.value = null
    autoUpdateForm.item_id = null
    autoUpdateForm.selected_fields = []
}
const closeSetGroupModal = () => { showModalSetGroup.value = false }
const closeSetProductModal = () => { showModalSetProduct.value = false }

const saveAutoUpdateSettings = async () => {
    try {
        saving.value = true
        const response = await axios.post(route('api.update-auto-update-settings'), {
            id: autoUpdateForm.item_id,
            auto_update: autoUpdateForm.selected_fields
        })
        if (response.data.status) {
            const item = apiResponse.value.data.find(item => item.id === autoUpdateForm.item_id)
            if (item) {
                item.auto_update = [...autoUpdateForm.selected_fields]
            }
            showSuccess('Pengaturan auto update berhasil disimpan')
            closeAutoUpdateModal()
        } else {
            showError('Gagal menyimpan pengaturan: ' + response.data.message)
        }
    } catch (error) {
        showError('Terjadi kesalahan saat menyimpan pengaturan')
    } finally {
        saving.value = false
    }
}

const updateStatus = async (id, newStatus) => {
    try {
        const response = await axios.post(route('api.update-status-denom-ppob'), { id, status: newStatus })
        if (response.data.status) {
            const item = apiResponse.value.data.find(item => item.id === id)
            if (item) {
                item.status = newStatus
                item.updated_at = new Date().toISOString()
            }
            showSuccess('Status berhasil diperbarui')
        } else {
            showError('Gagal update status: ' + response.data.message)
        }
    } catch (error) {
        showError('Terjadi kesalahan saat update status')
    }
}
const changePage = (page) => { if (page >= 1 && page <= apiResponse.value.last_page) { apiParams.page = page } }
const changePerPage = (newPerPage) => { apiParams.per_page = newPerPage; apiParams.page = 1 }
const sortTable = (column) => {
    if (apiParams.sort_by === column) {
        apiParams.sort_order = apiParams.sort_order === 'asc' ? 'desc' : 'asc'
    } else {
        apiParams.sort_by = column
        apiParams.sort_order = 'asc'
    }
    apiParams.page = 1
}
const clearFilters = () => {
    apiParams.search = ''
    apiParams.status = ''
    apiParams.category = ''
    apiParams.provider = ''
    apiParams.page = 1
    fetchData()
}

const isAllSelected = computed(() => {
    return apiResponse.value.data.length > 0 && selectedItems.value.length === apiResponse.value.data.length
})
const displayInfo = computed(() => {
    const { from, to, total } = apiResponse.value
    return `Menampilkan ${from || 0} - ${to || 0} dari ${total || 0} data`
})
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount)
}
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
const visiblePages = computed(() => {
    const total = apiResponse.value.last_page
    const current = apiResponse.value.current_page
    const delta = 2
    const pages = []
    const start = Math.max(2, current - delta)
    const end = Math.min(total - 1, current + delta)
    for (let i = start; i <= end; i++) { pages.push(i) }
    return pages
})

onMounted(() => { fetchData() })

const openCategoryModal = (category = null) => {
    isEdit.value = !!category
    formCategory.reset()
    Object.assign(formCategory, category || emptyCategoryPayload())
    showModalCategory.value = true
}
const submitCategory = async () => {
    try {
        const url = route(isEdit.value ? "api.category.update" : "api.category.store")
        const res = await axios.post(url, formCategory.data())
        if (res.data?.status) {
            const newCategory = res.data.data
            toastify["success"]?.("Gotcha!", res.data.message)
            if (isEdit.value) {
                const idx = dataCategories.value.findIndex(c => c.id === newCategory.id)
                if (idx !== -1) dataCategories.value[idx] = newCategory
            } else {
                dataCategories.value.unshift(newCategory)
            }
            showModalCategory.value = false
        } else {
            toastify["error"]?.("Whoops", res.data?.message)
        }
    } catch (err) {
        toastify["error"]?.("Oops", err.response?.data?.message || err.message)
    }
}
const destroyCategory = async (category) => {
    if (!(await showConfirm(`Hapus kategori "${category.name}"?`))) return
    try {
        const res = await axios.post(route("api.category.destroy", category.id))
        if (res.data.status) {
            dataCategories.value = dataCategories.value.filter(c => c.id !== category.id)
            toastify["success"]?.("Gotcha!", res.data.message)
        } else {
            toastify["error"]?.("Whoops!", res.data.message)
        }
    } catch (err) {
        toastify["error"]?.("Oops", err.response?.data?.message || err.message)
    }
}
</script>

<template>

    <Head title="Kelola Denominasi" />
    <utility :title="'Denom PPOB'" />

    <div class="container-fluid">

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">Kelola Group Categories</div>
                <button class="btn btn-primary" @click="openCategoryModal()">Tambah Category</button>
            </div>

            <div class="card-body pb-0">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" placeholder="Cari nama atau icon..."
                                v-model="categoryPagination.searchQuery">
                            <button v-if="categoryPagination.searchQuery" class="btn btn-outline-secondary"
                                type="button" @click="categoryPagination.searchQuery = ''" title="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex align-items-center justify-content-end">
                            <label class="me-2">Show:</label>
                            <select class="form-select" style="width: auto;" :value="categoryPagination.perPage"
                                @change="changeCategoryPerPage($event.target.value)">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>Icon</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="category in paginatedCategories" :key="category.id">
                                <td class="fw-semibold">{{ category.name }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i :class="category.icon" class="me-2"></i>
                                        <code class="small">{{ category.icon }}</code>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge"
                                        :class="category.status == 'active' ? 'bg-success' : 'bg-danger'">
                                        {{ category.status == "active" ? "Aktif" : "Nonaktif" }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary me-2"
                                        @click="openCategoryModal(category)">
                                        <i class="fas fa-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="destroyCategory(category)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="paginatedCategories.length === 0">
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    {{ categoryPagination.searchQuery ? 'Tidak ada data yang cocok' : 'Tidak ada data '
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer" v-if="categoryTotalPages > 1">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">{{ categoryDisplayInfo }}</small>
                    </div>
                    <div class="col-md-6">
                        <nav aria-label="Category pagination">
                            <ul class="pagination pagination-sm justify-content-end mb-0">
                                <li class="page-item" :class="{ disabled: categoryPagination.currentPage === 1 }">
                                    <button class="page-link" @click="changeCategoryPage(1)"
                                        :disabled="categoryPagination.currentPage === 1">
                                        <i class="fas fa-angle-double-left"></i>
                                    </button>
                                </li>

                                <li class="page-item" :class="{ disabled: categoryPagination.currentPage === 1 }">
                                    <button class="page-link"
                                        @click="changeCategoryPage(categoryPagination.currentPage - 1)"
                                        :disabled="categoryPagination.currentPage === 1">
                                        <i class="fas fa-angle-left"></i>
                                    </button>
                                </li>

                                <li class="page-item" :class="{ active: categoryPagination.currentPage === 1 }">
                                    <button class="page-link" @click="changeCategoryPage(1)">1</button>
                                </li>

                                <li class="page-item disabled" v-if="categoryVisiblePages[0] > 2">
                                    <span class="page-link">...</span>
                                </li>

                                <template v-for="page in categoryVisiblePages" :key="page">
                                    <li class="page-item" :class="{ active: categoryPagination.currentPage === page }">
                                        <button class="page-link" @click="changeCategoryPage(page)">{{ page }}</button>
                                    </li>
                                </template>

                                <li class="page-item disabled"
                                    v-if="categoryVisiblePages[categoryVisiblePages.length - 1] < categoryTotalPages - 1">
                                    <span class="page-link">...</span>
                                </li>

                                <li class="page-item" v-if="categoryTotalPages > 1"
                                    :class="{ active: categoryPagination.currentPage === categoryTotalPages }">
                                    <button class="page-link" @click="changeCategoryPage(categoryTotalPages)">
                                        {{ categoryTotalPages }}
                                    </button>
                                </li>

                                <li class="page-item"
                                    :class="{ disabled: categoryPagination.currentPage === categoryTotalPages }">
                                    <button class="page-link"
                                        @click="changeCategoryPage(categoryPagination.currentPage + 1)"
                                        :disabled="categoryPagination.currentPage === categoryTotalPages">
                                        <i class="fas fa-angle-right"></i>
                                    </button>
                                </li>

                                <li class="page-item"
                                    :class="{ disabled: categoryPagination.currentPage === categoryTotalPages }">
                                    <button class="page-link" @click="changeCategoryPage(categoryTotalPages)"
                                        :disabled="categoryPagination.currentPage === categoryTotalPages">
                                        <i class="fas fa-angle-double-right"></i>
                                    </button>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold mb-2">
                                    <i class="fas fa-search me-2"></i>Pencarian
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text border-end-0">
                                        <i class="fas fa-search text-white"></i>
                                    </span>
                                    <input type="text" class="form-control"
                                        placeholder="Cari berdasarkan nama, kategori, brand, kode, atau provider..."
                                        v-model="apiParams.search" :disabled="loading">
                                    <button v-if="apiParams.search" class="btn btn-outline-secondary" type="button"
                                        @click="apiParams.search = ''" title="Clear search">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold mb-2">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </label>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                                <select class="form-select" v-model="apiParams.status">
                                    <option value="">🔘 Semua Status</option>
                                    <option value="active">✅ Aktif</option>
                                    <option value="inactive">⚠️ Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                                <select class="form-select" v-model="apiParams.category">
                                    <option value="">📁 Semua Kategori</option>
                                    <option v-for="cat in categoryOptions" :key="cat.category" :value="cat.category">
                                        {{ cat.category }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                                <select class="form-select" v-model="apiParams.provider">
                                    <option value="">🏢 Semua Provider</option>
                                    <option v-for="prov in providerOptions" :key="prov.value" :value="prov.value">
                                        {{ prov.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <button type="button" class="btn btn-outline-secondary w-100" @click="clearFilters()">
                                    <i class="fas fa-times-circle me-1"></i>
                                    Hapus Filter
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label class="form-label fw-semibold mb-2">
                                    <i class="fas fa-tools me-2"></i>Aksi
                                </label>
                            </div>
                            <div class="col-12">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-primary " @click="openModal()"
                                        :disabled="loading">
                                        <i class="fas fa-plus-circle me-2"></i>
                                        Tambah Denominasi
                                    </button>

                                    <button type="button" class="btn btn-success " @click="syncDigiflazz()"
                                        :disabled="syncing">
                                        <i class="fas fa-sync-alt me-2" :class="{ 'fa-spin': syncing }"></i>
                                        <span v-if="syncing">Sinkronisasi...</span>
                                        <span v-else>Sinkron DigiFlazz</span>
                                    </button>

                                    <button type="button" class="btn btn-dark " @click="openModalReplace()"
                                        :disabled="loading">
                                        <i class="fas fa-edit me-2"></i>
                                        Replace Text
                                    </button>
                                    <button type="button" class="btn btn-primary dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <span v-if="dropdown">Menghapus...</span>
                                        <span v-else><i class="fas fa-edit"></i> Aksi</span>
                                        <i class="las la-angle-down ms-1"></i></button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="#" @click="setProduct()">Set Product <span
                                                class="badge bg-primary ms-1">{{
                                                    selectedItems.length }}</span></a>
                                        <a class="dropdown-item" href="#" @click="setGroup()">Set Group <span
                                                class="badge bg-primary ms-1">{{
                                                    selectedItems.length }}</span></a>
                                        <a class="dropdown-item" @click="bulkDelete()" href="#">Hapus data <span
                                                class="badge bg-white text-danger ms-1">{{
                                                    selectedItems.length }}</span></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    Daftar Denominasi PPOB
                                    <span v-if="loading" class="spinner-border spinner-border-sm ms-2"></span>
                                </h5>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <label class="me-2">Show:</label>
                                    <select class="form-select me-1" style="width: auto;" :value="apiParams.per_page"
                                        @change="changePerPage($event.target.value)">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="50">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" :checked="isAllSelected"
                                                    @change="toggleSelectAll()">
                                            </div>
                                        </th>
                                        <th>
                                            <button class="btn btn-link text-white text-nowrap p-0 border-0"
                                                @click="sortTable('code')">
                                                Kode

                                            </button>
                                        </th>
                                        <th>
                                            <button class="btn btn-link text-white p-0 border-0"
                                                @click="sortTable('name')">
                                                Nama
                                            </button>
                                        </th>
                                        <th>Kategori</th>
                                        <th>Brand</th>
                                        <th>
                                            <button class="btn btn-link text-white p-0 border-0"
                                                @click="sortTable('price')">
                                                Harga

                                            </button>
                                        </th>
                                        <th>Auto Update</th>
                                        <th>Status</th>
                                        <th>
                                            <button class="btn btn-link text-white p-0 border-0"
                                                @click="sortTable('updated_at')">
                                                Update

                                            </button>
                                        </th>
                                        <th width="150">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="loading">
                                        <td colspan="11" class="text-center py-4">
                                            <div class="spinner-border" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-else-if="apiResponse.data.length === 0">
                                        <td colspan="11" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <p>Tidak ada data yang ditemukan</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-else v-for="item in apiResponse.data" :key="item.id">
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" :value="item.id"
                                                    v-model="selectedItems">
                                            </div>
                                        </td>
                                        <td>
                                            <code>{{ item.code }}</code>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ item.name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ item.note || '-' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ item.category }}</span>
                                        </td>
                                        <td>{{ item.brand || '-' }}</td>
                                        <td>{{ formatCurrency(item.price) }}
                                            <div class="text-muted" :style="{ 'font-size': '10px' }">Modal: {{
                                                formatCurrency(item.modal) }}</div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                    @click="openAutoUpdateModal(item)" title="Konfigurasi Auto Update">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm dropdown-toggle"
                                                    :class="item.status === 'active' ? 'btn-success' : 'btn-warning'"
                                                    type="button" :id="'status-' + item.id" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="fas  text-white"
                                                        :class="item.status === 'active' ? 'fa-check-circle' : 'fa-times-circle'"></i>
                                                    {{ item.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                                </button>
                                                <ul class="dropdown-menu" :aria-labelledby="'status-' + item.id">
                                                    <li>
                                                        <a class="dropdown-item" href="#"
                                                            @click.prevent="updateStatus(item.id, 'active')">
                                                            <i class="fas fa-check-circle text-success"></i>
                                                            Aktif
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#"
                                                            @click.prevent="updateStatus(item.id, 'inactive')">
                                                            <i class="fas fa-times-circle text-warning"></i>
                                                            Nonaktif
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ formatDate(item.updated_at) }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    @click="openModal(item)" title="Edit" :disabled="loading">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    @click="deleteItem(item.id)" title="Hapus" :disabled="deleting">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer" v-if="apiResponse.last_page > 1">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <small class="text-muted">{{ displayInfo }}</small>
                            </div>
                            <div class="col-md-6">
                                <nav aria-label="Table pagination">
                                    <ul class="pagination pagination-sm justify-content-end mb-0">

                                        <li class="page-item" :class="{ disabled: apiResponse.current_page === 1 }">
                                            <button class="page-link" @click="changePage(1)"
                                                :disabled="apiResponse.current_page === 1">
                                                <i class="fas fa-angle-double-left"></i>
                                            </button>
                                        </li>

                                        <li class="page-item" :class="{ disabled: apiResponse.current_page === 1 }">
                                            <button class="page-link" @click="changePage(apiResponse.current_page - 1)"
                                                :disabled="apiResponse.current_page === 1">
                                                <i class="fas fa-angle-left"></i>
                                            </button>
                                        </li>


                                        <li class="page-item" :class="{ active: apiResponse.current_page === 1 }">
                                            <button class="page-link" @click="changePage(1)">1</button>
                                        </li>


                                        <li class="page-item disabled" v-if="visiblePages[0] > 2">
                                            <span class="page-link">...</span>
                                        </li>


                                        <template v-for="page in visiblePages" :key="page">
                                            <li class="page-item"
                                                :class="{ active: apiResponse.current_page === page }">
                                                <button class="page-link" @click="changePage(page)">{{ page }}</button>
                                            </li>
                                        </template>


                                        <li class="page-item disabled"
                                            v-if="visiblePages[visiblePages.length - 1] < apiResponse.last_page - 1">
                                            <span class="page-link">...</span>
                                        </li>


                                        <li class="page-item" v-if="apiResponse.last_page > 1"
                                            :class="{ active: apiResponse.current_page === apiResponse.last_page }">
                                            <button class="page-link" @click="changePage(apiResponse.last_page)">{{
                                                apiResponse.last_page }}</button>
                                        </li>


                                        <li class="page-item"
                                            :class="{ disabled: apiResponse.current_page === apiResponse.last_page }">
                                            <button class="page-link" @click="changePage(apiResponse.current_page + 1)"
                                                :disabled="apiResponse.current_page === apiResponse.last_page">
                                                <i class="fas fa-angle-right"></i>
                                            </button>
                                        </li>

                                        <li class="page-item"
                                            :class="{ disabled: apiResponse.current_page === apiResponse.last_page }">
                                            <button class="page-link" @click="changePage(apiResponse.last_page)"
                                                :disabled="apiResponse.current_page === apiResponse.last_page">
                                                <i class="fas fa-angle-double-right"></i>
                                            </button>
                                        </li>
                                    </ul>

                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" :class="{ show: showModal, 'd-block': showModal }"
        :style="{ display: showModal ? 'block' : 'none' }" tabindex="-1" role="dialog" aria-labelledby="modalTitle"
        :aria-hidden="!showModal">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-edit me-2"></i>
                        {{ isEditing ? 'Edit Denominasi' : 'Tambah Denominasi' }}
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal()" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveItem()">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kode <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" v-model="form.code" required
                                        placeholder="Masukkan kode produk" :disabled="saving">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Provider <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.provider" required :disabled="saving">
                                        <option value="">Pilih Provider</option>
                                        <option v-for="prov in providerOptions" :key="prov.value" :value="prov.value">
                                            {{ prov.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.name" required
                                placeholder="Masukkan nama produk" :disabled="saving">
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.category" required :disabled="saving">
                                        <option value="">Pilih Kategori</option>
                                        <option v-for="cat in categoryOptions" :key="cat.category"
                                            :value="cat.category">
                                            {{ cat.category }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control" v-model="form.brand"
                                        placeholder="Masukkan brand" :disabled="saving">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tipe</label>
                                    <input type="text" class="form-control" v-model="form.type"
                                        placeholder="Masukkan Type" :disabled="saving">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Modal <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" v-model="form.modal" required min="0"
                                            step="0.01" placeholder="0" :disabled="saving">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" v-model="form.price" required min="0"
                                            step="0.01" placeholder="0" :disabled="saving">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Multi</label>
                                    <select class="form-select" v-model="form.multi" :disabled="saving">
                                        <option :value="0">Tidak (Single)</option>
                                        <option :value="1">Ya (Multiple)</option>
                                    </select>
                                    <div class="form-text">Apakah produk bisa dibeli dalam jumlah banyak</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" v-model="form.status" :disabled="saving">
                                        <option value="active">Aktif</option>
                                        <option value="inactive">Nonaktif</option>
                                    </select>
                                    <div class="form-text">Status ketersediaan produk</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Waktu Offline</label>
                            <input type="text" class="form-control" v-model="form.offline" placeholder="23:30 - 00:30"
                                :disabled="saving">
                            <div class="form-text">Rentang waktu ketika produk tidak tersedia (format: HH:MM - HH:MM)
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" v-model="form.note" rows="3"
                                placeholder="Masukkan catatan tambahan..." :disabled="saving"></textarea>
                            <div class="form-text">Informasi tambahan mengenai produk atau cara penggunaan</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal()" :disabled="saving">
                        <i class="fas fa-times me-1"></i>
                        Batal
                    </button>
                    <button type="button" class="btn btn-primary" @click="saveItem()" :disabled="saving">
                        <i class="fas fa-save me-1"></i>
                        <span v-if="saving">Menyimpan...</span>
                        <span v-else>{{ isEditing ? 'Update Data' : 'Simpan Data' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" :class="{ show: showModalReplace, 'd-block': showModalReplace }"
        :style="{ display: showModalReplace ? 'block' : 'none' }" tabindex="-1" role="dialog"
        aria-labelledby="autoUpdateModalTitle" :aria-hidden="!showModalReplace">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="autoUpdateModalTitle">
                        <i class="fas fa-sync-alt me-2"></i>
                        Replace Text Layanan
                    </h5>
                    <button type="button" class="btn-close" @click="closeModalReplace()" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle text-center">
                            <thead>
                                <tr>
                                    <th class="text-center">No.</th>
                                    <th>Original Text</th>
                                    <th>Replace Text</th>
                                    <th>Data</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(val, key) in replace_text[0]">
                                    <td class="text-center">{{ key + 1 }}</td>
                                    <td>{{ val.text }}</td>
                                    <td>{{ val.replace_text }}</td>
                                    <td>{{ val.data }}</td>
                                    <td class="text-center">
                                        <i class="fa-solid fa-xmark text-danger" title="Delete"
                                            @click="deleteReplaceText(key)" style="cursor: pointer"></i>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="row">
                        <div class="col-md">
                            <label for="" class="form-label">Original Text</label>
                            <input type="text" name="original" placeholder="Masukkan Original Text" v-model="original"
                                class="form-control">
                        </div>
                        <div class="col-md">
                            <label for="" class="form-label">Replace Text</label>
                            <input type="text" name="replace" placeholder="Masukkan Replace Text" v-model="replace"
                                class="form-control">
                        </div>
                        <div class="col-md">
                            <label for="" class="form-label">Data Parameter</label>
                            <select name="data" id="data" v-model="dataParameter" class="form-control">
                                <option value="">Pilih salah satu</option>
                                <option value="name">Name</option>
                                <option value="category">Category</option>
                                <option value="brand">Brand</option>
                                <option value="type">Type</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <button class="btn btn-success" type="button" @click="updateReplace"><i
                                class="fas fa-refresh me-1"></i>Update</button>

                        <div class="text-end">
                            <button class="btn btn-primary" type="submit" @click="addReplaceText">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" :class="{ show: showAutoUpdateModal, 'd-block': showAutoUpdateModal }"
        :style="{ display: showAutoUpdateModal ? 'block' : 'none' }" tabindex="-1" role="dialog"
        aria-labelledby="autoUpdateModalTitle" :aria-hidden="!showAutoUpdateModal">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="autoUpdateModalTitle">
                        <i class="fas fa-sync-alt me-2"></i>
                        Konfigurasi Auto Update
                    </h5>
                    <button type="button" class="btn-close" @click="closeAutoUpdateModal()" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div v-if="currentAutoUpdateItem" class="mb-3">
                        <h6 class="text-primary">
                            <i class="fas fa-info-circle me-1"></i>
                            {{ currentAutoUpdateItem.name }}
                        </h6>
                        <small class="text-muted">Kode: {{ currentAutoUpdateItem.code }}</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pilih Field yang Akan Auto Update:</label>
                        <div class="form-text mb-3">
                            Field yang dipilih akan diperbarui secara otomatis dari provider saat sinkronisasi data
                        </div>

                        <div class="list-group">
                            <div v-for="field in availableAutoUpdateFields" :key="field.value" class="list-group-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" :id="'modal_auto_' + field.value"
                                        :value="field.value" v-model="autoUpdateForm.selected_fields"
                                        :disabled="saving">
                                    <label class="form-check-label w-100" :for="'modal_auto_' + field.value">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="text-dark">{{ field.label }}</strong>
                                                <br>
                                                <small class="text-muted">{{ field.description }}</small>
                                            </div>
                                            <span v-if="autoUpdateForm.selected_fields.includes(field.value)"
                                                class="badge bg-success">
                                                <i class="fas fa-check"></i>
                                            </span>
                                        </div>

                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Tips:</strong> Pilih field yang sering berubah dari provider untuk memastikan data
                            selalu up-to-date.
                            Field yang tidak dipilih akan tetap menggunakan nilai yang Anda set manual.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeAutoUpdateModal()" :disabled="saving">
                        <i class="fas fa-times me-1"></i>
                        Batal
                    </button>
                    <button type="button" class="btn btn-success" @click="saveAutoUpdateSettings()" :disabled="saving">
                        <i class="fas fa-check me-1"></i>
                        <span v-if="saving">Menyimpan...</span>
                        <span v-else>Simpan Pengaturan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" :class="{ show: showModalSetGroup, 'd-block': showModalSetGroup }"
        :style="{ display: showModalSetGroup ? 'block' : 'none' }" tabindex="-1" role="dialog"
        aria-labelledby="setGroupModalTitle" :aria-hidden="!showModalSetGroup">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="setGroupModalTitle">
                        <i class="fas fa-layer-group me-2"></i>
                        Set Group Denom
                    </h5>
                    <button type="button" class="btn-close" @click="closeSetGroupModal()" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Group <span class="text-danger">*</span></label>
                    <select v-model="groupCategory" class="form-control">
                        <option value="">Pilih salah satu</option>
                        <option :value="c.id" v-for="c in dataCategories">{{ c.name }}</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="saveGroup()" :disabled="saving">
                        <i class="fas fa-save me-1"></i>
                        <span v-if="saving">Menyimpan...</span>
                        <span v-else>Simpan Data</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" :class="{ show: showModalSetProduct, 'd-block': showModalSetProduct }"
        :style="{ display: showModalSetProduct ? 'block' : 'none' }" tabindex="-1" role="dialog"
        aria-labelledby="setProductModalTitle" :aria-hidden="!showModalSetProduct">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="setProductModalTitle">
                        <i class="fas fa-box me-2"></i>
                        Set Product untuk Denom Terpilih
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeSetProductModal()"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>{{ selectedItems.length }}</strong> item denominasi akan dikaitkan dengan product yang
                        dipilih.
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-search me-2"></i>Cari Product
                        </label>
                        <input type="text" class="form-control mb-3" placeholder="Ketik untuk mencari product..."
                            v-model="productSearchQuery">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-box-open me-2"></i>Pilih Product <span class="text-danger">*</span>
                        </label>

                        <div class="product-selection-container" style="max-height: 400px; overflow-y: auto;">
                            <div v-for="product in filteredProducts" :key="product.id"
                                class="product-card mb-2 p-3 border rounded cursor-pointer"
                                :class="{ 'border-primary bg-light': selectedProduct === product.id }"
                                @click="selectedProduct = product.id" style="cursor: pointer; transition: all 0.2s;">

                                <div class="d-flex align-items-center">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" :id="'product-' + product.id"
                                            :value="product.id" v-model="selectedProduct"
                                            style="width: 20px; height: 20px;">
                                    </div>

                                    <div class="product-thumbnail me-3" v-if="product.thumbnail">
                                        <img :src="`${app}/assets/images/product/${product.thumbnail}`"
                                            :alt="product.name" class="rounded"
                                            style="width: 60px; height: 60px; object-fit: cover;">
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <h6 class="mb-0 me-2">{{ product.name }}</h6>
                                            <span class="badge"
                                                :class="product.status === 'active' ? 'bg-success' : 'bg-secondary'">
                                                {{ product.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                            <span v-if="product.popular == '1'" class="badge bg-warning text-dark ms-1">
                                                <i class="fas fa-star"></i> Popular
                                            </span>
                                        </div>

                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-gamepad me-1"></i>
                                            <strong>Game:</strong> {{ product.game?.name || '-' }}

                                            <i class="fas fa-layer-group me-1"></i>
                                            <strong>Category:</strong> {{ product.category?.name || '-' }}
                                            <i class="fas fa-building me-1"></i>
                                            <strong>Publisher:</strong> {{ product.publisher }}
                                        </div>
                                    </div>

                                    <div class="ms-3" v-if="selectedProduct === product.id">
                                        <i class="fas fa-check-circle text-primary fa-2x"></i>
                                    </div>
                                </div>
                            </div>

                            <div v-if="filteredProducts.length === 0" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Tidak ada product yang ditemukan</p>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3" v-if="!selectedProduct">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Silakan pilih product terlebih dahulu sebelum menyimpan
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeSetProductModal()" :disabled="saving">
                        <i class="fas fa-times me-1"></i>
                        Batal
                    </button>
                    <button type="button" class="btn btn-primary" @click="saveProduct()"
                        :disabled="saving || !selectedProduct">
                        <i class="fas fa-save me-1"></i>
                        <span v-if="saving">Menyimpan...</span>
                        <span v-else>Simpan Product</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" tabindex="-1" :class="{ show: showModalCategory }" style="display: block"
        v-if="showModalCategory" @click.self="showModalCategory = false" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ isEdit ? "Edit Category" : "Tambah Category" }}</h5>
                    <button type="button" class="btn-close" @click="showModalCategory = false"></button>
                </div>
                <form @submit.prevent="submitCategory">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama Category</label>
                                <input v-model="formCategory.name" type="text" class="form-control"
                                    placeholder="Nama kategori" required />
                                <div v-if="formCategory.errors.name" class="invalid-feedback d-block">
                                    {{ formCategory.errors.name }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Icon Class</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i :class="formCategory.icon || 'bi bi-question'"></i>
                                    </span>
                                    <input v-model="formCategory.icon" type="text" class="form-control"
                                        placeholder="fas fa-gamepad" />
                                </div>
                                <div class="form-text">
                                    Gunakan Bootstrap Icons atau Font Awesome. Contoh:
                                    <code>fas fa-gamepad</code>
                                </div>
                                <div v-if="formCategory.errors.icon" class="invalid-feedback d-block">
                                    {{ formCategory.errors.icon }}
                                </div>
                            </div>
                            <div class="col-12" v-if="formCategory.icon">
                                <label class="form-label">Preview Icon</label>
                                <div class="border rounded p-3 text-center">
                                    <i :class="formCategory.icon" style="font-size: 2rem"></i>
                                    <div class="mt-2 small text-muted">{{ formCategory.icon }}</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Status</label>
                                <select v-model="formCategory.status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <div v-if="formCategory.errors.status" class="invalid-feedback d-block">
                                    {{ formCategory.errors.status }}
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Icon Suggestions</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="formCategory.icon = 'fas fa-gamepad'">
                                        <i class="fas fa-gamepad me-1"></i> Gaming
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="formCategory.icon = 'fas fa-mobile-alt'">
                                        <i class="fas fa-mobile-alt me-1"></i> Mobile
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="formCategory.icon = 'fas fa-laptop'">
                                        <i class="fas fa-laptop me-1"></i> PC
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="formCategory.icon = 'fas fa-star'">
                                        <i class="fas fa-star me-1"></i> Popular
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="formCategory.icon = 'fas fa-trophy'">
                                        <i class="fas fa-trophy me-1"></i> Tournament
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="formCategory.icon = 'fas fa-dice'">
                                        <i class="fas fa-dice me-1"></i> Casino
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="formCategory.icon = 'fas fa-chess'">
                                        <i class="fas fa-chess me-1"></i> Strategy
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="formCategory.icon = 'fas fa-rocket'">
                                        <i class="fas fa-rocket me-1"></i> Action
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="formCategory.icon = 'fas fa-puzzle-piece'">
                                        <i class="fas fa-puzzle-piece me-1"></i> Puzzle
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="formCategory.icon = 'fas fa-car'">
                                        <i class="fas fa-car me-1"></i> Racing
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="formCategory.icon = 'fas fa-futbol'">
                                        <i class="fas fa-futbol me-1"></i> Sports
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click="formCategory.icon = 'fas fa-magic'">
                                        <i class="fas fa-magic me-1"></i> Fantasy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" @click="showModalCategory = false">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="formCategory.processing">
                            {{ isEdit ? "Simpan Perubahan" : "Simpan" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade" :class="{ show: showModal }" v-if="showModal"></div>
    <div class="modal-backdrop fade" :class="{ show: showModalReplace }" v-if="showModalReplace"></div>
    <div class="modal-backdrop fade" :class="{ show: showModalSetProduct }" v-if="showModalSetProduct"></div>
    <div class="modal-backdrop fade" :class="{ show: showModalSetGroup }" v-if="showModalSetGroup"></div>
    <div class="modal-backdrop fade" :class="{ show: showModalCategory }" v-if="showModalCategory"></div>
    <div class="modal-backdrop fade" :class="{ show: showAutoUpdateModal }" v-if="showAutoUpdateModal"></div>
</template>

<style scoped>
.modal.show {
    display: block !important;
}

.modal-backdrop.show {
    opacity: 0.5;
}

.btn-link {
    text-decoration: none;
}

.btn-link:hover {
    text-decoration: underline;
}

.list-group-item .form-check {
    margin: 0;
    display: flex;
    align-items: center;
}

.list-group-item .form-check-label {
    cursor: pointer;
    padding: 0.5rem 0;
    width: 100%;
    margin-left: 20px;
}

.list-group-item .form-check-label .d-flex {
    align-items: center;
}

.product-card:hover {
    border-color: #0d6efd !important;
    background-color: #f8f9fa !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.product-selection-container::-webkit-scrollbar {
    width: 8px;
}

.product-selection-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.product-selection-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.product-selection-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
