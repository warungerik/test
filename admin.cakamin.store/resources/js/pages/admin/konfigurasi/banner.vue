<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import utility from '../../admin/utility/page-title.vue'
import Swal from 'sweetalert2'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    app: String,
    banner: { type: Array, default: () => [] }
})

const items = ref([])
const uploading = ref(false)

watch(
    () => props.banner,
    (val) => {
        items.value = Array.isArray(val) ? JSON.parse(JSON.stringify(val)) : []
    },
    { immediate: true }
)

async function handleUpload(e) {
    const file = e.target.files[0]
    uploading.value = true
    if (!file) return

    const fd = new FormData()
    fd.append('banner', file)

    try {
        const { data } = await axios.post(route('api.tambah-banner'), fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        if (data?.status && data?.path) {
            items.value.push(data.path)
            toastify["success"]?.("Sukses!", "Banner berhasil diunggah.")
        } else {
            toastify["error"]?.("Gagal!", data?.message || "Gagal menambahkan banner.")
        }
    } catch (err) {
        console.error(err)
        toastify["error"]?.("Error!", "Terjadi kesalahan saat upload.")
    } finally {
        uploading.value = false
        e.target.value = ''
    }
}
function preview(url) {
    window.open(props.app + '/' + url, '_blank', 'noopener,noreferrer')
}

async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text)
        toastify["success"]?.("Yeay!", "Path gambar disalin.")
    } catch {
        const ta = document.createElement('textarea')
        ta.value = text
        document.body.appendChild(ta)
        ta.select()
        document.execCommand('copy')
        document.body.removeChild(ta)
    }
}

function removeAt(idx) {
    Swal.fire({
        title: 'Yakin ingin menghapus banner ini?',
        text: "Jika banner ini sudah digunakan, maka banner ini akan dibatalkan",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!',
    }).then(result => {
        if (result.isConfirmed) {
            axios.post(route('api.hapus-banner'), { id: idx }).then(res => {
                if (res.data.status) {
                    toastify["success"]?.("Gotcha!", res.data.message);
                    items.value.splice(idx, 1)
                } else {
                    toastify["error"]?.("Whoops!", res.data.message)
                }
            })
        }
    })
}
</script>


<template>

    <Head :title="'Konfigurasi Banner'" />
    <utility :title="'Banner'"></utility>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Daftar Banner</h5>
                <div class="d-flex align-items-center gap-3">
                    <div v-if="uploading" class="d-flex align-items-center gap-2">
                        <div class="progress" style="width: 180px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                :style="{ width: progress + '%' }" :aria-valuenow="progress" aria-valuemin="0"
                                aria-valuemax="100">
                                {{ progress }}%
                            </div>
                        </div>
                    </div>
                    <label class="btn btn-primary mb-0">
                        <i class="fas fa-plus me-1"></i> Tambah Banner
                        <input type="file" accept="image/*" class="d-none" @change="handleUpload" :disabled="uploading">
                    </label>
                </div>
            </div>

            <div v-if="errorMsg" class="alert alert-danger py-2 mb-3">{{ errorMsg }}</div>
            <div v-if="successMsg" class="alert alert-success py-2 mb-3">{{ successMsg }}</div>

            <div class="row g-3">
                <div v-for="(src, idx) in items" :key="idx" class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm">
                        <div class="ratio ratio-16x9 bg-light">
                            <img :src="`${app}/assets/images/banner/${src}`" :alt="`Banner ${idx + 1}`"
                                class="img-fluid object-fit-cover" loading="lazy" />
                        </div>
                        <div class="card-body">
                            <h6 class="card-title text-truncate mb-1">Banner {{ idx + 1 }}</h6>
                            <p class="card-text small text-muted text-truncate mb-2">{{ src }}</p>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    @click="preview(src)">Lihat</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    @click="copyToClipboard(src)">Salin Path</button>
                                <button type="button" class="btn btn-outline-danger btn-sm ms-auto"
                                    @click="removeAt(idx)">Hapus</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!items.length" class="col-12">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-image fs-1 d-block mb-2"></i>
                        <p class="mb-0">Belum ada banner. Unggah banner menggunakan tombol “Tambah Banner”.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.object-fit-cover {
    object-fit: cover;
}
</style>
