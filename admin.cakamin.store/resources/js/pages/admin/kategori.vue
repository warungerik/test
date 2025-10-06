<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import utility from "../admin/utility/page-title.vue";
import { ref } from 'vue';

const props = defineProps({
    category: Object,
})
const emptyCategoryPayload = () => ({
    id: null,
    name: "",
    icon: "",
    status: "active",
});

const isEdit = ref(false);
const dataCategories = ref([...(props.category || [])]);
const formCategory = useForm(emptyCategoryPayload());
const imagePreviewIcon = ref(null);
const showModalCategory = ref(false);
const openCategory = () => {
    isEdit.value = false;
    formCategory.reset();
    Object.assign(formCategory, emptyCategoryPayload());
    imagePreviewIcon.value = null;
    showModalCategory.value = true;
};

const categoryEdit = (category) => {
    isEdit.value = true;
    formCategory.reset();
    Object.assign(formCategory, {
        id: category.id,
        name: category.name || "",
        icon: category.icon || "",
        status: category.status || "active",
    });
    showModalCategory.value = true;
};

const submitCategory = async () => {
    try {
        const url = route(isEdit.value ? "api.category.update" : "api.category.store");
        const payload = formCategory.data();

        const res = await axios.post(url, payload);
        if (res.data?.status) {
            const newCategory = res.data.data;
            toastify["success"]?.("Gotcha!", res.data.message);
            if (isEdit.value) {
                const idx = dataCategories.value.findIndex((p) => p.id === newCategory.id);
                if (idx !== -1) {
                    dataCategories.value[idx] = newCategory;
                }
            } else {
                dataCategories.value.unshift(newCategory);
            }
        } else {
            toastify["error"]?.("Whoops", res.data?.message);
        }
        showModalCategory.value = false;
    } catch (err) {
        const msg =
            err.response?.data?.message || err.message || "Terjadi kesalahan tak terduga.";
        toastify["error"]?.("Whoops", msg);
    }
};

const destroyCategory = (category) => {
    Swal.fire({
        title: "Konfirmasi",
        text: `Hapus kategori "${category.name}"?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal",
    }).then((result) => {
        if (result.isConfirmed) {
            axios
                .post(route("api.category.destroy", category.id))
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
                    const idx = dataCategories.value.findIndex((p) => p.id === category.id);
                    if (idx !== -1) {
                        dataCategories.value.splice(idx, 1);
                    }
                });
        }
    });
};

</script>
<template>

    <Head :title="'Kelola Products'" />
    <utility title="Products" />
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0">Kelola Categories</div>
            <button class="btn btn-primary" @click="openCategory">Tambah Category</button>
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
                        <tr v-for="category in dataCategories" :key="category.id">
                            <td class="fw-semibold">{{ category.name }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i :class="category.icon" class="me-2"></i>
                                    <code class="small">{{ category.icon }}</code>
                                </div>
                            </td>
                            <td>
                                <span class="badge" :class="category.status == 'active' ? 'bg-success' : 'bg-danger'">
                                    {{ category.status == "active" ? "Aktif" : "Nonaktif" }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary me-2" @click="categoryEdit(category)">
                                    <i class="fas fa-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" @click="destroyCategory(category)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="dataCategories.length === 0">
                            <td colspan="4" class="text-center py-4 text-muted">
                                Tidak ada data kategori.
                            </td>
                        </tr>
                    </tbody>
                </table>
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