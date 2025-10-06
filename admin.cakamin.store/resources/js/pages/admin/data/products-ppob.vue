<script setup>
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import utility from "../../admin/utility/page-title.vue";
import { ref, computed, watch } from "vue";
import { route } from "ziggy-js";
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";
import Swal from "sweetalert2";
import axios from "axios";

const props = defineProps({
    app: String,
    game: Array,
    product: Array,
    categories: Array,
    providers: Array,
});

const dataProducts = ref([...(props.product || [])]);
const dataGame = ref([...(props.game || [])]);
const dataCategories = ref([...(props.categories || [])]);
const search = ref("");
const statusFilter = ref("all");
const popularFilter = ref("all");
const sortKey = ref("created_at");
const sortDir = ref("desc");

const currentPage = ref(1);
const perPage = ref(10);

const sortBy = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === "asc" ? "desc" : "asc";
    } else {
        sortKey.value = key;
        sortDir.value = "asc";
    }
};

const sorted = computed(() => {
    const copy = [...dataProducts.value];
    copy.sort((a, b) => {
        const va = a[sortKey.value];
        const vb = b[sortKey.value];
        if (va == null && vb == null) return 0;
        if (va == null) return sortDir.value === "asc" ? -1 : 1;
        if (vb == null) return sortDir.value === "asc" ? 1 : -1;
        if (typeof va === "string" && typeof vb === "string") {
            return sortDir.value === "asc" ? va.localeCompare(vb) : vb.localeCompare(va);
        }
        return sortDir.value === "asc"
            ? va > vb
                ? 1
                : va < vb
                    ? -1
                    : 0
            : vb > va
                ? 1
                : vb < va
                    ? -1
                    : 0;
    });
    return copy;
});

const filtered = computed(() => {
    return sorted.value.filter((p) => {
        const q = search.value.trim().toLowerCase();
        const matchesQ =
            !q ||
            [p.name, p.slug, p?.category?.name, p?.game?.name].some((s) =>
                (s || "").toLowerCase().includes(q)
            );
        const matchesStatus = statusFilter.value === "all" || p.status === statusFilter.value;
        const matchesPopular =
            popularFilter.value === "all" || String(p.popular) === popularFilter.value;
        return matchesQ && matchesStatus && matchesPopular;
    });
});

const totalPages = computed(() => Math.ceil(filtered.value.length / perPage.value));
const paginated = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filtered.value.slice(start, end);
});

const showModal = ref(false);
const showModalGame = ref(false);
const isEdit = ref(false);
const imagePreviewThumb = ref(null);
const imagePreviewBanner = ref(null);
const editorRef = ref(null);

const emptyPayload = () => ({
    id: null,
    name: "",
    slug: "",
    game_id: props.game?.id || null,
    cgame_id: "",
    category_id: props.categories?.id || null,
    publisher: '',
    provider_id: null,
    description: "",
    popular: false,
    status: "active",
    thumbnail: null,
    banner: null,
});
const emptyCategoryPayload = () => ({
    id: null,
    name: "",
    icon: "",
    type: "ppob",
    status: "active",
});

const emptyGamePayload = () => ({
    id: null,
    name: "",
    status: "active",
});

const formCategory = useForm(emptyCategoryPayload());
const showModalCategory = ref(false);
const imagePreviewIcon = ref(null);

const form = useForm(emptyPayload());
const formGame = useForm(emptyGamePayload());
const type_api = ref(0);
const openCreate = () => {
    isEdit.value = false;
    form.reset();
    Object.assign(form, emptyPayload());
    imagePreviewThumb.value = null;
    imagePreviewBanner.value = null;
    showModal.value = true;
};

const openEdit = (product) => {
    isEdit.value = true;
    form.reset();
    Object.assign(form, {
        id: product.id,
        name: product.name || "",
        slug: product.slug || "",
        game_id: product.game_id || null,
        cgame_id: product.cgame_id,
        publisher: product.publisher,
        category_id: product.category_id || null,
        provider_id: product.provider_id || null,
        description: product.description || "",
        popular: String(product.popular) === "1",
        status: product.status || "inactive",
        thumbnail: null,
        banner: null,
    });
    imagePreviewThumb.value = product.thumbnail
        ? `${app}/assets/images/product/${product.thumbnail}`
        : null;
    imagePreviewBanner.value = product.banner
        ? `${app}/assets/images/banner/${product.banner}`
        : null;
    showModal.value = true;
};

const gameEdit = (game) => {
    isEdit.value = true;
    formGame.reset();
    Object.assign(formGame, {
        id: game.id,
        code_game: game.code_game || "",
        name: game.name || "",
        status: game.status || "",
    });
    showModalGame.value = true;
};
const onThumbChange = (e) => {
    const f = e.target.files?.[0] || null;
    form.thumbnail = f;
    if (f) {
        const reader = new FileReader();
        reader.onload = () => (imagePreviewThumb.value = reader.result);
        reader.readAsDataURL(f);
    } else {
        imagePreviewThumb.value = null;
    }
};

const onBannerChange = (e) => {
    const f = e.target.files?.[0] || null;
    form.banner = f;
    if (f) {
        const reader = new FileReader();
        reader.onload = () => (imagePreviewBanner.value = reader.result);
        reader.readAsDataURL(f);
    } else {
        imagePreviewBanner.value = null;
    }
};

const submit = async () => {
    try {
        const routeName = isEdit.value ? "api.products.update" : "api.products.store";
        const url = isEdit.value ? route(routeName, form.id) : route(routeName);

        const formData = new FormData();

        Object.entries(form.data()).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });
        formData.set('type', 'ppob');
        formData.set("popular", form.popular ? 1 : 0);

        if (isEdit.value) {
            formData.set("_method", "PUT");
        }

        const res = await axios.post(url, formData);

        if (res.data.status) {
            toastify["success"]?.("Gotcha!", res.data.message);
            const newProduct = res.data.data;

            if (isEdit.value) {
                const idx = dataProducts.value.findIndex((p) => p.id === newProduct.id);
                if (idx !== -1) {
                    dataProducts.value[idx] = newProduct;
                }
            } else {
                dataProducts.value.unshift(newProduct);
            }

            showModal.value = false;
            imagePreviewThumb.value = null;
            imagePreviewBanner.value = null;
        } else {
            toastify["error"]?.("Oops!", res.data.message);
        }
    } catch (err) {
        toastify["error"]?.("Oops!", err.response?.data?.message || err.message);
    }
};

const submitGame = async () => {
    try {
        const url = route(isEdit.value ? "api.game.update" : "api.game.store");
        const payload = formGame.data();

        const res = await axios.post(url, payload);
        if (res.data?.status) {
            const newGame = res.data.data;
            toastify["success"]?.("Gotcha!", res.data.message);
            if (isEdit.value) {
                const idx = dataGame.value.findIndex((p) => p.id === newGame.id);
                if (idx !== -1) {
                    dataGame.value[idx] = newGame;
                }
            } else {
                dataGame.value.unshift(newGame);
            }
        } else {
            toastify["error"]?.("Whoops", res.data?.message);
        }
        showModalGame.value = false;
    } catch (err) {
        const msg =
            err.response?.data?.message || err.message || "Terjadi kesalahan tak terduga.";
        toastify["error"]?.("Whoops", msg);
    }
};

const destroyProduct = (product) => {
    Swal.fire({
        title: "Konfirmasi",
        text: `Hapus product "${product.name}"?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal",
    }).then((result) => {
        if (result.isConfirmed) {
            axios
                .post(route("api.products.destroy", product.id))
                .then((res) => {
                    if (res.data.status) {
                        toastify["success"]?.("Gotcha!", res.data.message);
                    } else {
                        toastify["error"]?.("Whoops!", res.data.message);
                    }
                })
                .catch((err) => {
                    toastify["error"]?.("Oops!", err.response?.data?.message || err.message);
                })
                .finally(() => {
                    const idx = dataProducts.value.findIndex((p) => p.id === product.id);
                    if (idx !== -1) {
                        dataProducts.value.splice(idx, 1);
                    }
                });
        }
    });
};

watch(
    () => props.product,
    (val) => {
        dataProducts.value = [...(val || [])];
    }
);
</script>

<template>

    <Head :title="'Kelola Products'" />
    <utility title="Products" />

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
                            <th style="width: 42px">#</th>
                            <th role="button" @click="sortBy('name')">
                                Nama
                                <i :class="[
                                    'ms-1',
                                    sortKey === 'name' && sortDir === 'asc'
                                        ? 'fas fa-caret-up'
                                        : sortKey === 'name'
                                            ? 'fas fa-caret-down'
                                            : 'fas fa-filter',
                                ]"></i>
                            </th>
                            <th>Slug</th>
                            <th>Game</th>
                            <th>Kategori</th>
                            <th class="text-center">Popular</th>
                            <th class="text-center">Status</th>
                            <th role="button" @click="sortBy('updated_at')">
                                Diubah
                                <i :class="[
                                    'ms-1',
                                    sortKey === 'updated_at' && sortDir === 'asc'
                                        ? 'fas fa-caret-up'
                                        : sortKey === 'updated_at'
                                            ? 'fas fa-caret-down'
                                            : 'fas fa-filter',
                                ]"></i>
                            </th>
                            <th class="text-center" style="width: 120px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(p, idx) in paginated" :key="p.id">
                            <td>{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                            <td class="fw-semibold">
                                <div class="d-flex align-items-center">
                                    <img v-if="p.thumbnail" :src="`${app}/assets/images/product/${p.thumbnail}`"
                                        class="rounded me-2" style="width: 28px; height: 28px; object-fit: cover" />
                                    <span>{{ p.name }}
                                        <div class="text-muted fs-10">{{ p.publisher }}</div>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <code class="small">{{ p.slug }}</code>
                            </td>
                            <td>{{ p?.game?.name || "-" }}</td>
                            <td>{{ p?.category?.name || "-" }}</td>
                            <td class="text-center">
                                <span :class="[
                                    'badge',
                                    String(p.popular) === '1' ? 'bg-warning text-dark' : 'bg-secondary',
                                ]">
                                    {{ String(p.popular) === "1" ? "Popular" : "Normal" }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span :class="['badge', p.status === 'active' ? 'bg-success' : 'bg-danger']">
                                    {{ p.status === "active" ? "Active" : "Inactive" }}
                                </span>
                            </td>
                            <td class="small">{{ new Date(p.updated_at).toLocaleString() }}</td>
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
                        <button class="page-link" @click="currentPage--" :disabled="currentPage === 1">
                            «
                        </button>
                    </li>
                    <li v-for="n in totalPages" :key="n" class="page-item" :class="{ active: currentPage === n }">
                        <button class="page-link" @click="currentPage = n">{{ n }}</button>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                        <button class="page-link" @click="currentPage++" :disabled="currentPage === totalPages">
                            »
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <div class="modal fade" tabindex="-1" :class="{ show: showModal }" style="display: block" v-if="showModal"
        @click.self="showModal = false" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ isEdit ? "Edit Product" : "Tambah Product" }}</h5>
                    <button type="button" class="btn-close" @click="showModal = false"></button>
                </div>
                <form @submit.prevent="submit" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama</label>
                                <input v-model="form.name" type="text" class="form-control" placeholder="Nama product"
                                    required />
                                <div v-if="form.errors.name" class="invalid-feedback d-block">
                                    {{ form.errors.name }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slug</label>
                                <input v-model="form.slug" type="text" class="form-control" placeholder="slug-unik"
                                    required />
                                <div v-if="form.errors.slug" class="invalid-feedback d-block">
                                    {{ form.errors.slug }}
                                </div>
                            </div>
                            <div :class="'col-md-6'">
                                <label class="form-label">Kategori</label>
                                <select v-model="form.category_id" class="form-select" required>
                                    <option :value="null">Pilih kategori</option>
                                    <option v-for="c in props.categories" :key="c.id" :value="c.id">
                                        {{ c.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.category_id" class="invalid-feedback d-block">
                                    {{ form.errors.category_id }}
                                </div>
                            </div>
                            <div :class="'col-md-6'">
                                <label class="form-label">Publisher</label>
                                <input v-model="form.publisher" type="text" class="form-control"
                                    placeholder="Publisher / Developer game" required />
                                <div v-if="form.errors.publisher" class="invalid-feedback d-block">
                                    {{ form.errors.publisher }}
                                </div>

                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <QuillEditor ref="editorRef" v-model:content="form.description" content-type="html"
                                    theme="snow" placeholder="Tulis deskripsi produk..." style="height: 300px" />
                                <div v-if="form.errors.description" class="invalid-feedback d-block">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Thumbnail</label>
                                <input type="file" class="form-control" accept="image/*" @change="onThumbChange" />
                                <div class="form-text">PNG/JPG disarankan 1:1, ukuran kecil.</div>
                                <div class="mt-2">
                                    <img v-if="imagePreviewThumb" :src="imagePreviewThumb" class="border rounded"
                                        style="width: 100px; height: 100px; object-fit: cover" alt="preview" />
                                </div>
                                <div v-if="form.errors.thumbnail" class="invalid-feedback d-block">
                                    {{ form.errors.thumbnail }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Banner</label>
                                <input type="file" class="form-control" accept="image/*" @change="onBannerChange" />
                                <div class="form-text">Rasio 16:9 disarankan.</div>
                                <div class="mt-2">
                                    <img v-if="imagePreviewBanner" :src="imagePreviewBanner" class="border rounded"
                                        style="
                      width: 100%;
                      max-width: 220px;
                      aspect-ratio: 16/9;
                      object-fit: cover;
                    " alt="preview" />
                                </div>
                                <div v-if="form.errors.banner" class="invalid-feedback d-block">
                                    {{ form.errors.banner }}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label d-block">Popular</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="popularSwitch"
                                        v-model="form.popular" />
                                    <label class="form-check-label" for="popularSwitch">{{
                                        form.popular ? "Popular" : "Normal"
                                    }}</label>
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
                        <button type="button" class="btn btn-light" @click="showModal = false">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ isEdit ? "Simpan Perubahan" : "Simpan" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Game Modal -->
    <div class="modal fade" tabindex="-1" :class="{ show: showModalGame }" style="display: block" v-if="showModalGame"
        @click.self="showModalGame = false" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ isEdit ? "Edit Game" : "Tambah Game" }}</h5>
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
                        <button type="button" class="btn btn-light" @click="showModalGame = false">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ isEdit ? "Simpan Perubahan" : "Simpan" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Category Modal -->
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
                                <div class="border rounded p-3 text-center bg-light">
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
                            <!-- Icon Suggestions -->
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
</template>
