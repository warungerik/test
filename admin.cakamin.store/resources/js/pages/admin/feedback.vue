<script setup>
import { Head } from '@inertiajs/vue3';
import utility from '../admin/utility/page-title.vue'
import { ref, reactive, computed, watch } from 'vue'
import Swal from 'sweetalert2';

const props = defineProps({
    feedback: Array
})

const feedback = ref([...props.feedback])

const formData = reactive({
    searchQuery: '',
    filterRating: '',
    filterStatus: 'all',
    sortBy: 'id',
    sortOrder: 'desc',
    selectedItems: [],
    currentPage: 1,
    itemsPerPage: 10
})

const replyModal = ref(false)
const bulkReplyModal = ref(false)
const editingFeedback = ref(null)
const replyText = ref('')
const bulkReplyText = ref('')
const loading = ref(false)

// perhatikan: watch pakai feedback.value
watch(() => feedback.value, (newFeedback) => {
    if (newFeedback && newFeedback.length > 0) {
        formData.selectedItems = []
        formData.currentPage = 1
    }
}, { immediate: true })

// Computed untuk mendapatkan data feedback yang dipilih
const selectedFeedbackData = computed(() => {
    return feedback.value.filter(item => formData.selectedItems.includes(item.id))
})

// Computed untuk mengecek apakah ada item yang dipilih dan belum dibalas
const hasUnrepliedSelected = computed(() => {
    return selectedFeedbackData.value.some(item => !item.reply_admin)
})

const filteredFeedback = computed(() => {
    if (!feedback.value) return []

    let filtered = feedback.value.filter(item => {
        const searchMatch = formData.searchQuery === '' ||
            item.name.toLowerCase().includes(formData.searchQuery.toLowerCase()) ||
            item.message.toLowerCase().includes(formData.searchQuery.toLowerCase()) ||
            item.information.invoice_id.toLowerCase().includes(formData.searchQuery.toLowerCase())

        const ratingMatch = formData.filterRating === '' ||
            item.rating.toString() === formData.filterRating

        const statusMatch = formData.filterStatus === 'all' ||
            (formData.filterStatus === 'replied' && item.reply_admin) ||
            (formData.filterStatus === 'pending' && !item.reply_admin)

        return searchMatch && ratingMatch && statusMatch
    })

    filtered.sort((a, b) => {
        let aVal = a[formData.sortBy]
        let bVal = b[formData.sortBy]

        if (formData.sortBy === 'created_at') {
            aVal = new Date(aVal)
            bVal = new Date(bVal)
        }

        if (formData.sortOrder === 'asc') {
            return aVal > bVal ? 1 : -1
        } else {
            return aVal < bVal ? 1 : -1
        }
    })

    return filtered
})

const totalItems = computed(() => filteredFeedback.value.length)
const totalPages = computed(() => Math.ceil(totalItems.value / formData.itemsPerPage))
const startIndex = computed(() => (formData.currentPage - 1) * formData.itemsPerPage)
const endIndex = computed(() => startIndex.value + formData.itemsPerPage)
const paginatedFeedback = computed(() => filteredFeedback.value.slice(startIndex.value, endIndex.value))

const paginationInfo = computed(() => {
    const start = totalItems.value === 0 ? 0 : startIndex.value + 1
    const end = Math.min(endIndex.value, totalItems.value)
    return {
        start,
        end,
        total: totalItems.value
    }
})

const visiblePages = computed(() => {
    const pages = []
    const maxVisible = 5
    let startPage = Math.max(1, formData.currentPage - Math.floor(maxVisible / 2))
    let endPage = Math.min(totalPages.value, startPage + maxVisible - 1)

    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1)
    }

    for (let i = startPage; i <= endPage; i++) {
        pages.push(i)
    }

    return pages
})

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const renderStars = (rating) => {
    const stars = []
    for (let i = 1; i <= 5; i++) {
        stars.push(i <= rating ? 'fas fa-star text-warning' : 'far fa-star text-muted')
    }
    return stars
}

const getRatingBadge = (rating) => {
    if (rating >= 5) return { class: 'bg-success', text: 'Sangat Baik' }
    if (rating >= 4) return { class: 'bg-primary', text: 'Baik' }
    if (rating >= 3) return { class: 'bg-warning', text: 'Rata - rata' }
    if (rating >= 2) return { class: 'bg-orange', text: 'Not bad' }
    return { class: 'bg-danger', text: 'Bad' }
}

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        formData.currentPage = page
        formData.selectedItems = []
    }
}

const changeItemsPerPage = () => {
    formData.currentPage = 1
    formData.selectedItems = []
}

watch([
    () => formData.searchQuery,
    () => formData.filterRating,
    () => formData.filterStatus,
    () => formData.sortBy,
    () => formData.sortOrder
], () => {
    formData.currentPage = 1
})

const selectAll = () => {
    if (formData.selectedItems.length === paginatedFeedback.value.length) {
        const currentPageIds = paginatedFeedback.value.map(item => item.id)
        formData.selectedItems = formData.selectedItems.filter(id => !currentPageIds.includes(id))
    } else {
        const currentPageIds = paginatedFeedback.value.map(item => item.id)
        const newSelections = currentPageIds.filter(id => !formData.selectedItems.includes(id))
        formData.selectedItems.push(...newSelections)
    }
}

const toggleSelect = (id) => {
    const index = formData.selectedItems.indexOf(id)
    if (index > -1) {
        formData.selectedItems.splice(index, 1)
    } else {
        formData.selectedItems.push(id)
    }
}

const openReplyModal = (fb) => {
    editingFeedback.value = fb
    replyText.value = fb.reply_admin || ''
    replyModal.value = true
}

const closeReplyModal = () => {
    replyModal.value = false
    editingFeedback.value = null
    replyText.value = ''
}

// New function to open bulk reply modal
const openBulkReplyModal = () => {
    if (formData.selectedItems.length === 0) {
        toastify["warning"]?.("Warning", "Pilih feedback yang ingin dibalas terlebih dahulu")
        return
    }
    bulkReplyText.value = ''
    bulkReplyModal.value = true
}

// New function to close bulk reply modal
const closeBulkReplyModal = () => {
    bulkReplyModal.value = false
    bulkReplyText.value = ''
}

const submitReply = async () => {
    loading.value = true
    if (!editingFeedback.value || !replyText.value.trim()) return
    try {
        const res = await axios.post(route('api.feedback-reply'), {
            id: editingFeedback.value.id,
            reply: replyText.value
        })
        if (res.data.status) {
            toastify["success"]?.("Success", res.data.message)
            const updated = res.data.data

            const idx = paginatedFeedback.value.findIndex(r => r.id === updated.id)
            if (idx !== -1) paginatedFeedback.value[idx] = updated

            const idxx = feedback.value.findIndex(r => r.id === updated.id)
            if (idxx !== -1) feedback.value[idxx] = updated
        } else {
            toastify["error"]?.("Whoops!", res.data.message)
        }
    } catch (err) {
        toastify["error"]?.("Whoops!", "Terjadi kesalahan pada server")
    } finally {
        loading.value = false
    }

    closeReplyModal()
}

// New function to submit bulk reply
const submitBulkReply = async () => {
    if (!bulkReplyText.value.trim()) {
        toastify["warning"]?.("Warning", "Silakan tulis balasan terlebih dahulu")
        return
    }

    loading.value = true

    try {
        const selectedIds = formData.selectedItems
        const res = await axios.post(route('api.feedback-bulk-reply'), {
            ids: selectedIds,
            reply: bulkReplyText.value
        })

        if (res.data.status) {
            toastify["success"]?.("Success", `Berhasil mengirim balasan ke ${selectedIds.length} feedback`)
            const updatedFeedbacks = res.data.data

            updatedFeedbacks.forEach(updatedItem => {
                const paginatedIdx = paginatedFeedback.value.findIndex(r => r.id === updatedItem.id)
                if (paginatedIdx !== -1) {
                    paginatedFeedback.value[paginatedIdx] = updatedItem
                }
                const mainIdx = feedback.value.findIndex(r => r.id === updatedItem.id)
                if (mainIdx !== -1) {
                    feedback.value[mainIdx] = updatedItem
                }
            })
            formData.selectedItems = []

        } else {
            toastify["error"]?.("Error", res.data.message)
        }
    } catch (err) {
        console.error('Bulk reply error:', err)
        toastify["error"]?.("Error", "Terjadi kesalahan saat mengirim bulk reply")
    } finally {
        loading.value = false
    }

    closeBulkReplyModal()
}
const deleteSelected = () => {
    if (formData.selectedItems.length === 0) return;

    Swal.fire({
        title: "Are you sure?",
        text: "Apakah anda yakin ingin menghapus data ini?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then((result) => {
        if (result.isConfirmed) {
            const deletedIds = [...formData.selectedItems]; // simpan dulu ID
            axios.post(route('api.feedback.destroy'), {
                id: deletedIds,
            }).then(res => {
                if (res.data.status) {
                    Swal.fire("Deleted!", "Data berhasil dihapus", "success");
                    feedback.value = feedback.value.filter(x => !deletedIds.includes(x.id));
                    formData.selectedItems = [];
                } else {
                    Swal.fire("Failed!", res.data.message || "Data gagal dihapus", "error");
                }
            }).catch(() => {
                Swal.fire("Error!", "Terjadi kesalahan pada server", "error");
            })
        }
    })
}

</script>

<template>

    <Head title="Feedback" />
    <utility :title="'Feedback'" />

    <div class="container-fluid">
        <div class="row ">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="fw-bold mb-1">{{ feedback?.length || 0 }}</h4>
                                <p class="mb-0">Total Feedback</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-comments fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="fw-bold mb-1">{{feedback?.filter(f => f.reply_admin).length || 0}}</h4>
                                <p class="mb-0">Replied</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-reply fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="fw-bold mb-1">{{feedback?.filter(f => !f.reply_admin).length || 0}}</h4>
                                <p class="mb-0">Pending</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="fw-bold mb-1">{{
                                    feedback?.length ? (feedback.reduce((sum, f) => sum + f.rating, 0) /
                                        feedback.length).toFixed(1) : '0.0'
                                }}</h4>
                                <p class="mb-0">Avg. Rating</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-star fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="row ">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center g-1">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input v-model="formData.searchQuery" type="text"
                                        class="form-control border-start-0"
                                        placeholder="Cari feedback, nama, atau invoice...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select v-model="formData.filterRating" class="form-select">
                                    <option value="">Semua Rating</option>
                                    <option value="5">5 Bintang</option>
                                    <option value="4">4 Bintang</option>
                                    <option value="3">3 Bintang</option>
                                    <option value="2">2 Bintang</option>
                                    <option value="1">1 Bintang</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select v-model="formData.filterStatus" class="form-select">
                                    <option value="all">Semua Status</option>
                                    <option value="replied">Sudah Dibalas</option>
                                    <option value="pending">Belum Dibalas</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select v-model="formData.itemsPerPage" @change="changeItemsPerPage"
                                    class="form-select">
                                    <option value="10">10 per halaman</option>
                                    <option value="25">25 per halaman</option>
                                    <option value="50">50 per halaman</option>
                                    <option value="100">100 per halaman</option>
                                </select>
                            </div>
                            <div class="col-md-3 text-end">
                                <!-- Updated button from Export to Bulk Reply -->
                                <button @click="openBulkReplyModal" :disabled="formData.selectedItems.length === 0"
                                    class="btn btn-outline-primary me-2"
                                    :class="{ 'btn-primary text-white': formData.selectedItems.length > 0 }">
                                    <i class="fas fa-reply-all me-1"></i>
                                    Bulk Reply ({{ formData.selectedItems.length }})
                                </button>
                                <button @click="deleteSelected" :disabled="formData.selectedItems.length === 0"
                                    class="btn btn-outline-danger">
                                    <i class="fas fa-trash me-1"></i>
                                    Hapus ({{ formData.selectedItems.length }})
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table and rest of the template remains the same -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    :checked="paginatedFeedback.length > 0 && paginatedFeedback.every(item => formData.selectedItems.includes(item.id))"
                                                    @change="selectAll">
                                            </div>
                                        </th>
                                        <th>Customer & Product</th>
                                        <th>Rating & Feedback</th>
                                        <th>Invoice</th>
                                        <th width="120">Status</th>
                                        <th>Date</th>
                                        <th width="120" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in paginatedFeedback" :key="item.id"
                                        :class="{ 'table-active': formData.selectedItems.includes(item.id) }">
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    :checked="formData.selectedItems.includes(item.id)"
                                                    @change="toggleSelect(item.id)">
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-semibold text-dark">{{ item.name }}</div>
                                                <div class="small text-primary">{{ item.information.product.name_product
                                                }}</div>
                                                <div class="small text-muted">
                                                    {{ item.information.product.game }} - {{
                                                        item.information.product.denom }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="mb-2">
                                                <!-- Star Rating -->
                                                <div class="mb-1">
                                                    <span v-for="(starClass, index) in renderStars(item.rating)"
                                                        :key="index" :class="starClass" style="font-size: 14px;"></span>
                                                    <span class="badge ms-2" :class="getRatingBadge(item.rating).class">
                                                        {{ getRatingBadge(item.rating).text }}
                                                    </span>
                                                </div>
                                                <!-- Feedback Message -->
                                                <div class="text-muted small" style="max-width: 300px;">
                                                    {{ item.message }}
                                                </div>
                                            </div>
                                            <!-- Admin Reply -->
                                            <div v-if="item.reply_admin" class="mt-2 p-2 bg-light rounded small">
                                                <div class="fw-semibold text-success mb-1">
                                                    <i class="fas fa-reply me-1"></i>Admin Reply:
                                                </div>
                                                <div class="text-muted">{{ item.reply_admin }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">{{ item.information.invoice_id
                                            }}</span>
                                        </td>
                                        <td>
                                            <span class="badge" :class="item.reply_admin ? 'bg-success' : 'bg-warning'">
                                                {{ item.reply_admin ? 'Replied' : 'Pending' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div>{{ formatDate(item.created_at) }}</div>
                                                <div class="text-muted" v-if="item.updated_at !== item.created_at">
                                                    Updated: {{ formatDate(item.updated_at) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button @click="openReplyModal(item)" class="btn btn-outline-primary"
                                                    :title="item.reply_admin ? 'Edit Reply' : 'Add Reply'">
                                                    <i class="fas fa-reply"></i>
                                                </button>
                                                <button @click="formData.selectedItems = [item.id]; deleteSelected()"
                                                    class="btn btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="paginatedFeedback.length === 0" class="text-center py-5">
                            <i class="fas fa-comment-slash text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">Tidak ada feedback</h5>
                            <p class="text-muted">
                                {{ formData.searchQuery ? 'Tidak ada hasil pencarian' : 'Belum ada feedback yang masuk'
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Pagination Footer (same as before) -->
                    <div class="card-footer" v-if="totalPages > 1">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    {{ paginationInfo.start }}-{{ paginationInfo.end }} dari {{ paginationInfo.total }}
                                    entries
                                </small>
                            </div>
                            <div class="col-md-6">
                                <nav aria-label="Feedback pagination">
                                    <ul class="pagination pagination-sm justify-content-end mb-0">
                                        <li class="page-item" :class="{ 'disabled': formData.currentPage === 1 }">
                                            <button class="page-link" @click="goToPage(1)"
                                                :disabled="formData.currentPage === 1" title="First Page">
                                                <i class="fas fa-angle-double-left"></i>
                                            </button>
                                        </li>
                                        <li class="page-item" :class="{ 'disabled': formData.currentPage === 1 }">
                                            <button class="page-link" @click="goToPage(formData.currentPage - 1)"
                                                :disabled="formData.currentPage === 1" title="Previous Page">
                                                <i class="fas fa-angle-left"></i>
                                            </button>
                                        </li>
                                        <li v-for="page in visiblePages" :key="page" class="page-item"
                                            :class="{ 'active': page === formData.currentPage }">
                                            <button class="page-link" @click="goToPage(page)">
                                                {{ page }}
                                            </button>
                                        </li>
                                        <li class="page-item"
                                            :class="{ 'disabled': formData.currentPage === totalPages }">
                                            <button class="page-link" @click="goToPage(formData.currentPage + 1)"
                                                :disabled="formData.currentPage === totalPages" title="Next Page">
                                                <i class="fas fa-angle-right"></i>
                                            </button>
                                        </li>
                                        <li class="page-item"
                                            :class="{ 'disabled': formData.currentPage === totalPages }">
                                            <button class="page-link" @click="goToPage(totalPages)"
                                                :disabled="formData.currentPage === totalPages" title="Last Page">
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

    <!-- Single Reply Modal (same as before) -->
    <div class="modal fade" :class="{ show: replyModal }" :style="{ display: replyModal ? 'block' : 'none' }"
        v-if="replyModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-reply me-2"></i>
                        {{ editingFeedback?.reply_admin ? 'Edit Reply' : 'Reply to Feedback' }}
                    </h5>
                    <button type="button" class="btn-close" @click="closeReplyModal"></button>
                </div>
                <div class="modal-body">
                    <div class="bg-light p-3 rounded mb-3" v-if="editingFeedback">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">{{ editingFeedback.name }}</div>
                                <div class="small text-muted">{{ editingFeedback.information.invoice_id }}</div>
                            </div>
                            <div>
                                <span v-for="(starClass, index) in renderStars(editingFeedback.rating)" :key="index"
                                    :class="starClass"></span>
                            </div>
                        </div>
                        <div class="text-muted">{{ editingFeedback.message }}</div>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Admin Reply:</label>
                        <textarea v-model="replyText" class="form-control" rows="4"
                            placeholder="Tulis balasan untuk customer..."></textarea>
                        <div class="form-text">
                            Reply ini akan dikirim ke customer dan ditampilkan di halaman feedback.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeReplyModal">
                        Batal
                    </button>
                    <button type="button" class="btn btn-primary" :disabled="!replyText.trim()" @click="submitReply">
                        <i class="fas fa-paper-plane me-1"></i>
                        {{ loading ? 'Loading...' : (editingFeedback?.reply_admin ? 'Update Reply' : 'Send Reply') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade" :class="{ show: replyModal }" v-if="replyModal"></div>
    <div class="modal fade" :class="{ show: bulkReplyModal }" :style="{ display: bulkReplyModal ? 'block' : 'none' }"
        v-if="bulkReplyModal">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-reply-all me-2"></i>
                        Bulk Reply to {{ formData.selectedItems.length }} Feedback(s)
                    </h5>
                    <button type="button" class="btn-close" @click="closeBulkReplyModal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3">Selected Feedback:</h6>
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive" style="max-height: 300px;">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th width="200">Customer</th>
                                                <th width="150">Invoice</th>
                                                <th width="100">Rating</th>
                                                <th>Message</th>
                                                <th width="100">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in selectedFeedbackData" :key="item.id">
                                                <td>
                                                    <div class="fw-semibold small">{{ item.name }}</div>
                                                    <div class="text-muted" style="font-size: 11px;">
                                                        {{ item.information.product.name_product }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info-subtle text-info small">
                                                        {{ item.information.invoice_id }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-1">{{ item.rating }}</span>
                                                        <i class="fas fa-star text-warning"
                                                            style="font-size: 12px;"></i>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 300px;"
                                                        :title="item.message">
                                                        {{ item.message }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge small"
                                                        :class="item.reply_admin ? 'bg-success' : 'bg-warning'">
                                                        {{ item.reply_admin ? 'Replied' : 'Pending' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Bulk Reply Message:</label>
                        <textarea v-model="bulkReplyText" class="form-control" rows="5"
                            placeholder="Tulis balasan yang akan dikirim ke semua feedback yang dipilih..."></textarea>
                        <div class="form-text">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Reply ini akan dikirim ke {{ formData.selectedItems.length }} customer dan
                                    ditampilkan di halaman feedback.</span>
                                <span class="text-muted small">{{ bulkReplyText.length }} karakter</span>
                            </div>
                        </div>
                        <div v-if="selectedFeedbackData.some(item => item.reply_admin)"
                            class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Peringatan:</strong> Beberapa feedback yang dipilih sudah memiliki balasan.
                            Bulk reply akan menimpa balasan yang sudah ada.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeBulkReplyModal">
                        Batal
                    </button>
                    <button type="button" class="btn btn-primary" :disabled="!bulkReplyText.trim() || loading"
                        @click="submitBulkReply">
                        <i class="fas fa-paper-plane me-1"></i>
                        {{ loading ? 'Mengirim...' : `Send Reply to ${formData.selectedItems.length} Feedback` }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade" :class="{ show: bulkReplyModal }" v-if="bulkReplyModal"></div>
</template>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}

.table th.cursor-pointer:hover {
    background-color: var(--bs-gray-200);
}

.modal.show {
    background: rgba(0, 0, 0, 0.5);
}

.bg-orange {
    background-color: #fd7e14 !important;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 1020;
}
</style>
