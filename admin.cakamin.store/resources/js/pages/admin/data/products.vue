<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import utility from '../../admin/utility/page-title.vue'
import { ref, computed, watch } from 'vue'
import { route } from 'ziggy-js'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import Swal from 'sweetalert2'
import axios from 'axios'

const props = defineProps({
    app: String,
    game: Array,
    product: Array,
    categories: Array,
    providers: Array,
})

const dataProducts = ref([...(props.product || [])])
const dataGame = ref([...(props.game || [])])
const dataCategories = ref([...(props.categories || [])])
const search = ref('')
const statusFilter = ref('all')
const popularFilter = ref('all')
const sortKey = ref('created_at')
const sortDir = ref('desc')

const currentPage = ref(1)
const perPage = ref(10)

// Download repeater array
const downloadsArr = ref([])

const sortBy = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortDir.value = 'asc'
    }
}

const sorted = computed(() => {
    const copy = [...dataProducts.value]
    copy.sort((a, b) => {
        const va = a[sortKey.value]
        const vb = b[sortKey.value]
        if (va == null && vb == null) return 0
        if (va == null) return sortDir.value === 'asc' ? -1 : 1
        if (vb == null) return sortDir.value === 'asc' ? 1 : -1
        if (typeof va === 'string' && typeof vb === 'string') {
            return sortDir.value === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va)
        }
        return sortDir.value === 'asc'
            ? (va > vb ? 1 : va < vb ? -1 : 0)
            : (vb > va ? 1 : vb < va ? -1 : 0)
    })
    return copy
})

const filtered = computed(() => {
    return sorted.value.filter(p => {
        const q = search.value.trim().toLowerCase()
        const matchesQ = !q || [p.name, p.slug, p?.category?.name, p?.game?.name].some(s => (s || '').toLowerCase().includes(q))
        const matchesStatus = statusFilter.value === 'all' || p.status === statusFilter.value
        const matchesPopular = popularFilter.value === 'all' || String(p.popular) === popularFilter.value
        return matchesQ && matchesStatus && matchesPopular
    })
})

const totalPages = computed(() => Math.ceil(filtered.value.length / perPage.value))
const paginated = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    const end = start + perPage.value
    return filtered.value.slice(start, end)
})

const showModal = ref(false)
const showModalGame = ref(false)
const isEdit = ref(false)
const imagePreviewThumb = ref(null)
const imagePreviewBanner = ref(null)
const editorRef = ref(null)

const emptyPayload = () => ({
    id: null,
    name: '',
    slug: '',
    game_id: props.game?.id || null,
    cgame_id: '',
    category_id: props.categories?.id || null,
    provider_id: null,
    description: '',
    download: '',
    popular: false,
    status: 'active',
    thumbnail: null,
    banner: null,
})

const emptyGamePayload = () => ({
    id: null,
    name: '',
    status: 'active',
})

const imagePreviewIcon = ref(null)

const form = useForm(emptyPayload())
const formGame = useForm(emptyGamePayload())
const type_api = ref(0)
const addDownload = () => {
    downloadsArr.value.push({ title: '', link: '' })
}

const removeDownload = (index) => {
    downloadsArr.value.splice(index, 1)
}

const serializeDownloads = () => {
    const filtered = downloadsArr.value.filter(d => d.title.trim() && d.link.trim())
    return filtered.length ? JSON.stringify(filtered) : ''
}

const hydrateDownloads = (downloadStr) => {
    if (!downloadStr) {
        downloadsArr.value = []
        return
    }
    try {
        const parsed = JSON.parse(downloadStr)
        if (Array.isArray(parsed)) {
            downloadsArr.value = parsed.map(d => ({ title: d.title || '', link: d.link || '' }))
        } else {
            downloadsArr.value = []
        }
    } catch {
        downloadsArr.value = []
    }
}

const openCreate = () => {
    isEdit.value = false
    form.reset()
    Object.assign(form, emptyPayload())
    imagePreviewThumb.value = null
    imagePreviewBanner.value = null
    downloadsArr.value = []
    showModal.value = true
}

const openEdit = (product) => {
    isEdit.value = true
    form.reset()
    Object.assign(form, {
        id: product.id,
        name: product.name || '',
        slug: product.slug || '',
        game_id: product.game_id || null,
        cgame_id: product.cgame_id,
        category_id: product.category_id || null,
        provider_id: product.provider_id || null,
        description: product.description || '',
        download: (() => {
            if (!product.download) return ''
            if (typeof product.download === 'string') return product.download
            if (Array.isArray(product.download)) return JSON.stringify(product.download)
            return ''
        })(),
        popular: String(product.popular) === '1',
        status: product.status || 'inactive',
        thumbnail: null,
        banner: null,
    })
    type_api.value = product.provider?.type_api
    imagePreviewThumb.value = product.thumbnail ? `${app}/assets/images/product/${product.thumbnail}` : null
    imagePreviewBanner.value = product.banner ? `${app}/assets/images/banner/${product.banner}` : null
    hydrateDownloads(form.download)
    showModal.value = true
}

const gameEdit = (game) => {
    isEdit.value = true
    formGame.reset()
    Object.assign(formGame, {
        id: game.id,
        code_game: game.code_game || '',
        name: game.name || '',
        status: game.status || '',
    })
    showModalGame.value = true
}

const openGame = () => {
    isEdit.value = false
    form.reset()
    Object.assign(form, emptyPayload())
    showModalGame.value = true
}

const destroyGame = (game) => {
    Swal.fire({
        title: 'Konfirmasi',
        text: `Hapus game "${game.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(route('api.game.destroy', game.id))
                .then(res => {
                    if (res.data.status) {
                        toastify["success"]?.("Gotcha!", res.data.message)
                    } else {
                        toastify["error"]?.("Whoops!", res.data.message)
                    }
                })
                .catch(err => {
                    toastify["error"]?.("Oops!", err.response?.data?.message || err.message)
                })
                .finally(() => {
                    const idx = dataGame.value.findIndex(p => p.id === game.id)
                    if (idx !== -1) {
                        dataGame.value.splice(idx, 1)
                    }
                })
        }
    })
}

const onThumbChange = (e) => {
    const f = e.target.files?.[0] || null
    form.thumbnail = f
    if (f) {
        const reader = new FileReader()
        reader.onload = () => (imagePreviewThumb.value = reader.result)
        reader.readAsDataURL(f)
    } else {
        imagePreviewThumb.value = null
    }
}

const onBannerChange = (e) => {
    const f = e.target.files?.[0] || null
    form.banner = f
    if (f) {
        const reader = new FileReader()
        reader.onload = () => (imagePreviewBanner.value = reader.result)
        reader.readAsDataURL(f)
    } else {
        imagePreviewBanner.value = null
    }
}

const submit = async () => {
    try {
        const routeName = isEdit.value ? 'api.products.update' : 'api.products.store'
        const url = isEdit.value
            ? route(routeName, form.id)
            : route(routeName)

        const formData = new FormData()

        Object.entries(form.data()).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                formData.append(key, value)
            }
        })
        formData.set('popular', form.popular ? 1 : 0)

        formData.set('download', serializeDownloads())

        if (isEdit.value) {
            formData.set('_method', 'PUT')
        }

        const res = await axios.post(url, formData)

        if (res.data.status) {
            toastify["success"]?.("Gotcha!", res.data.message)
            const newProduct = res.data.data

            if (isEdit.value) {
                const idx = dataProducts.value.findIndex(p => p.id === newProduct.id)
                if (idx !== -1) {
                    dataProducts.value[idx] = newProduct
                }
            } else {
                dataProducts.value.unshift(newProduct)
            }

            showModal.value = false
            imagePreviewThumb.value = null
            imagePreviewBanner.value = null
        } else {
            toastify["error"]?.("Oops!", res.data.message)
        }
    } catch (err) {
        toastify["error"]?.("Oops!", err.response?.data?.message || err.message)
    }
}

const submitGame = async () => {
    try {
        const url = route(isEdit.value ? 'api.game.update' : 'api.game.store')
        const payload = formGame.data()

        const res = await axios.post(url, payload)
        if (res.data?.status) {
            const newGame = res.data.data
            toastify['success']?.('Gotcha!', res.data.message)
            if (isEdit.value) {
                const idx = dataGame.value.findIndex(p => p.id === newGame.id)
                if (idx !== -1) {
                    dataGame.value[idx] = newGame
                }
            } else {
                dataGame.value.unshift(newGame)
            }
        } else {
            toastify['error']?.('Whoops', res.data?.message)
        }
        showModalGame.value = false
    } catch (err) {
        const msg = err.response?.data?.message || err.message || "Terjadi kesalahan tak terduga."
        toastify['error']?.('Whoops', msg)
    }
}

const destroyProduct = (product) => {
    Swal.fire({
        title: 'Konfirmasi',
        text: `Hapus product "${product.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(route('api.products.destroy', product.id))
                .then(res => {
                    if (res.data.status) {
                        toastify["success"]?.("Gotcha!", res.data.message)
                    } else {
                        toastify["error"]?.("Whoops!", res.data.message)
                    }
                })
                .catch(err => {
                    toastify["error"]?.("Oops!", err.response?.data?.message || err.message)
                })
                .finally(() => {
                    const idx = dataProducts.value.findIndex(p => p.id === product.id)
                    if (idx !== -1) {
                        dataProducts.value.splice(idx, 1)
                    }
                })
        }
    })
}

watch(() => props.product, (val) => {
    dataProducts.value = [...(val || [])]
})
</script>

<template>

    <Head :title="'Kelola Products'" />
    <utility title="Products" />

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0">Kelola Games</div>
            <button class="btn btn-primary" @click="openGame">Tambah Game</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="game in dataGame" :key="game.id">
                            <td>{{ game.name }}</td>
                            <td>
                                <span class="badge " :class="game.status == 'active' ? 'bg-success' : 'bg-danger'">
                                    {{ game.status == 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary me-2" @click="gameEdit(game)">
                                    <i class="fas fa-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" @click="destroyGame(game)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input v-model="search" type="text" class="form-control"
                        placeholder="Cari nama/slug/kategori/game..." />
                </div>
                <div class="col-md-3">
                    <select v-model="statusFilter" class="form-select">
                        <option value="all">Semua Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select v-model="popularFilter" class="form-select">
                        <option value="all">Semua Popular</option>
                        <option value="1">Popular</option>
                        <option value="0">Tidak Popular</option>
                    </select>
                </div>
                <div class="col-md-2 text-md-end">
                    <button class="btn btn-primary w-100" @click="openCreate">
                        <i class="fas fa-plus me-1"></i> Tambah Product
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0">Kelola Products</div>
            <div class="small text-muted">Total: {{ filtered.length }}</div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:42px;">#</th>
                            <th role="button" @click="sortBy('name')">
                                Nama
                                <i
                                    :class="['ms-1', sortKey === 'name' && sortDir === 'asc' ? 'fas fa-caret-up' : sortKey === 'name' ? 'fas fa-caret-down' : 'fas fa-filter']"></i>
                            </th>
                            <th>Slug</th>
                            <th>Game</th>
                            <th>Kategori</th>
                            <th class="text-center">Popular</th>
                            <th class="text-center">Status</th>
                            <th role="button" @click="sortBy('updated_at')">
                                Diubah
                                <i
                                    :class="['ms-1', sortKey === 'updated_at' && sortDir === 'asc' ? 'fas fa-caret-up' : sortKey === 'updated_at' ? 'fas fa-caret-down' : 'fas fa-filter']"></i>
                            </th>
                            <th class="text-center">Download</th>
                            <th class="text-center" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(p, idx) in paginated" :key="p.id">
                            <td>{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                            <td class="fw-semibold">
                                <div class="d-flex align-items-center">
                                    <img v-if="p.thumbnail" :src="`${app}/assets/images/product/${p.thumbnail}`"
                                        class="rounded me-2" style="width:28px;height:28px;object-fit:cover;" />
                                    <span>{{ p.name }}</span>
                                </div>
                            </td>
                            <td><code class="small">{{ p.slug }}</code></td>
                            <td>{{ p?.game?.name || '-' }}</td>
                            <td>{{ p?.category?.name || '-' }}</td>
                            <td class="text-center">
                                <span
                                    :class="['badge', String(p.popular) === '1' ? 'bg-warning text-dark' : 'bg-secondary']">
                                    {{ String(p.popular) === '1' ? 'Popular' : 'Normal' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span :class="['badge', p.status === 'active' ? 'bg-success' : 'bg-danger']">
                                    {{ p.status === 'active' ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="small">{{ new Date(p.updated_at).toLocaleString() }}</td>
                            <td class="text-center">
                                <template v-if="p.download">
                                    <span class="badge bg-info text-dark">Ada</span>
                                </template>
                                <template v-else>
                                    <span class="badge bg-light text-dark">-</span>
                                </template>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary me-2" @click="openEdit(p)">
                                    <i class="fas fa-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" @click="destroyProduct(p)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="paginated.length === 0">
                            <td colspan="11" class="text-center py-4 text-muted">Tidak ada data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div>Halaman {{ currentPage }} dari {{ totalPages }}</div>
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                        <button class="page-link" @click="currentPage--" :disabled="currentPage === 1">«</button>
                    </li>
                    <li v-for="n in totalPages" :key="n" class="page-item" :class="{ active: currentPage === n }">
                        <button class="page-link" @click="currentPage = n">{{ n }}</button>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                        <button class="page-link" @click="currentPage++"
                            :disabled="currentPage === totalPages">»</button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal fade" tabindex="-1" :class="{ show: showModal }" style="display: block;" v-if="showModal"
        @click.self="showModal = false" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ isEdit ? 'Edit Product' : 'Tambah Product' }}</h5>
                    <button type="button" class="btn-close" @click="showModal = false"></button>
                </div>
                <form @submit.prevent="submit" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nama</label>
                                <input v-model="form.name" type="text" class="form-control" placeholder="Nama product"
                                    required />
                                <div v-if="form.errors.name" class="invalid-feedback d-block">{{ form.errors.name }}
                                </div>
                            </div>
                            <div class="col-md-4" v-if="type_api == 2">
                                <label class="form-label">Custom Game ID</label>
                                <input type="number" v-model="form.cgame_id" class="form-control"
                                    placeholder="ID game" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Slug</label>
                                <input v-model="form.slug" type="text" class="form-control" placeholder="slug-unik"
                                    required />
                                <div v-if="form.errors.slug" class="invalid-feedback d-block">{{ form.errors.slug }}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Game</label>
                                <select v-model="form.game_id" class="form-select" required>
                                    <option :value="null">Pilih game</option>
                                    <option v-for="g in (props.game || [])" :key="g.id" :value="g.id">{{ g.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.game_id" class="invalid-feedback d-block">{{ form.errors.game_id
                                }}
                                </div>
                            </div>
                            <div :class="type_api == 2 ? 'col-md-4' : 'col-md-6'">
                                <label class="form-label">Kategori</label>
                                <select v-model="form.category_id" class="form-select" required>
                                    <option :value="null">Pilih kategori</option>
                                    <option v-for="c in props.categories" :key="c.id" :value="c.id">{{ c.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.category_id" class="invalid-feedback d-block">{{
                                    form.errors.category_id }}</div>
                            </div>
                            <div :class="type_api == 2 ? 'col-md-4' : 'col-md-6'">
                                <label class="form-label">Provider (opsional)</label>
                                <select v-model="form.provider_id" class="form-select">
                                    <option :value="null">Pilih provider</option>
                                    <option v-for="pr in (props.providers || [])" :key="pr.id" :value="pr.id">{{ pr.name
                                    }}
                                    </option>
                                </select>
                                <div v-if="form.errors.provider_id" class="invalid-feedback d-block">{{
                                    form.errors.provider_id }}</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <QuillEditor ref="editorRef" v-model:content="form.description" content-type="html"
                                    theme="snow" placeholder="Tulis deskripsi produk..." style="height:300px;" />
                                <div v-if="form.errors.description" class="invalid-feedback d-block">{{
                                    form.errors.description }}</div>
                            </div>

                            <!-- Download Repeater -->
                            <div class="col-12">
                                <label class="form-label">Download Links</label>
                                <div class="mb-3">
                                    <!-- Existing downloads -->
                                    <div v-for="(download, index) in downloadsArr" :key="index" class="row g-2 mb-2">
                                        <div class="col-md-5">
                                            <input v-model="download.title" type="text" class="form-control"
                                                placeholder="Nama file (contoh: APK MOD V1)" />
                                        </div>
                                        <div class="col-md-6">
                                            <input v-model="download.link" type="url" class="form-control"
                                                placeholder="Link download (https://...)" />
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-danger"
                                                @click="removeDownload(index)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Add button -->
                                    <button type="button" class="btn btn-outline-primary btn-sm" @click="addDownload">
                                        <i class="fas fa-plus me-1"></i> Add Download
                                    </button>
                                </div>
                                <div class="form-text">Tambahkan link download untuk produk ini. Biarkan kosong jika
                                    tidak
                                    diperlukan.</div>
                                <div v-if="form.errors.download" class="invalid-feedback d-block">{{
                                    form.errors.download }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Thumbnail</label>
                                <input type="file" class="form-control" accept="image/*" @change="onThumbChange" />
                                <div class="form-text">PNG/JPG disarankan 1:1, ukuran kecil.</div>
                                <div class="mt-2">
                                    <img v-if="imagePreviewThumb" :src="imagePreviewThumb" class="border rounded"
                                        style="width:100px;height:100px;object-fit:cover;" alt="preview" />
                                </div>
                                <div v-if="form.errors.thumbnail" class="invalid-feedback d-block">{{
                                    form.errors.thumbnail
                                }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Banner</label>
                                <input type="file" class="form-control" accept="image/*" @change="onBannerChange" />
                                <div class="form-text">Rasio 16:9 disarankan.</div>
                                <div class="mt-2">
                                    <img v-if="imagePreviewBanner" :src="imagePreviewBanner" class="border rounded"
                                        style="width:100%;max-width:220px;aspect-ratio:16/9;object-fit:cover;"
                                        alt="preview" />
                                </div>
                                <div v-if="form.errors.banner" class="invalid-feedback d-block">{{ form.errors.banner }}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label d-block">Popular</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="popularSwitch"
                                        v-model="form.popular">
                                    <label class="form-check-label" for="popularSwitch">{{ form.popular ? 'Popular' :
                                        'Normal' }}</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select v-model="form.status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
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

    <!-- Game Modal -->
    <div class="modal fade" tabindex="-1" :class="{ show: showModalGame }" style="display: block;" v-if="showModalGame"
        @click.self="showModalGame = false" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ isEdit ? 'Edit Game' : 'Tambah Game' }}</h5>
                    <button type="button" class="btn-close" @click="showModalGame = false"></button>
                </div>
                <form @submit.prevent="submitGame">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Code Game</label>
                                <input v-model="formGame.code_game" type="text" class="form-control"
                                    placeholder="Code Game" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama</label>
                                <input v-model="formGame.name" type="text" class="form-control"
                                    placeholder="Nama product" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select v-model="formGame.status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" @click="showModalGame = false">Batal</button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</template>
