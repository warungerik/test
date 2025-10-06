<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import utility from "../admin/utility/page-title.vue"
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'
import Swal from 'sweetalert2'
import axios from 'axios'

const props = defineProps({
    app: String,
    user: Object
})

const activeTab = ref('profile')

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const profileForm = useForm({
    name: props.user?.name || '',
    username: props.user?.username || '',
    level: props.user?.level || 'admin'
})

const passwordForm = useForm({
    current_password: '',
    new_password: '',
    confirm_password: ''
})

const passwordStrength = computed(() => {
    const password = passwordForm.new_password
    if (!password) return { level: 0, text: '', color: '' }

    let score = 0
    const checks = {
        length: password.length >= 8,
        lowercase: /[a-z]/.test(password),
        uppercase: /[A-Z]/.test(password),
        number: /\d/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    }

    score = Object.values(checks).filter(Boolean).length

    if (score <= 2) return { level: score, text: 'Lemah', color: 'danger' }
    if (score <= 3) return { level: score, text: 'Sedang', color: 'warning' }
    if (score <= 4) return { level: score, text: 'Kuat', color: 'info' }
    return { level: score, text: 'Sangat Kuat', color: 'success' }
})

const passwordMatch = computed(() => {
    if (!passwordForm.confirm_password) return null
    return passwordForm.new_password === passwordForm.confirm_password
})

const updateProfile = async () => {
    try {
        const res = await axios.put(route('api.user.update-profile'), profileForm.data())
        if (res.data?.status) {
            toastify?.success?.('Success!', 'Profil berhasil diperbarui')
        } else {
            toastify?.error?.('Error!', res.data?.message || 'Gagal memperbarui profil')
        }
    } catch (err) {
        toastify?.error?.('Error!', err.response?.data?.message || err.message)
    }
}

const changePassword = async () => {
    if (!passwordMatch.value) {
        toastify?.error?.('Error!', 'Konfirmasi password tidak cocok')
        return
    }

    if (passwordStrength.value.level < 3) {
        toastify?.error?.('Error!', 'Password terlalu lemah. Gunakan kombinasi huruf besar, kecil, angka, dan simbol')
        return
    }

    try {
        const res = await axios.post(route('api.user.change-password'), passwordForm.data())
        if (res.data?.status) {
            passwordForm.reset()
            toastify?.success?.('Success!', 'Password berhasil diubah')
        } else {
            toastify?.error?.('Error!', res.data?.message || 'Gagal mengubah password')
        }
    } catch (err) {
        toastify?.error?.('Error!', err.response?.data?.message || err.message)
    }
}

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<template>

    <Head :title="'Settings'" />
    <utility :title="'Settings'" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">
                        <i class="fas fa-user-cog me-2"></i>
                        Pengaturan Akun
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="border-bottom">
                        <ul class="nav nav-tabs nav-tabs-line" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link" :class="{ active: activeTab === 'profile' }"
                                    @click="activeTab = 'profile'">
                                    <i class="fas fa-user-circle"></i>
                                    Profil
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" :class="{ active: activeTab === 'security' }"
                                    @click="activeTab = 'security'">
                                    <i class="fas fa-lock"></i>
                                    Keamanan
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" :class="{ active: activeTab === 'activity' }"
                                    @click="activeTab = 'activity'">
                                    <i class="fas fa-history"></i>
                                    Aktivitas
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="p-4">
                        <div v-show="activeTab === 'profile'">
                            <form @submit.prevent="updateProfile">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Nama Lengkap</label>
                                        <input v-model="profileForm.name" type="text" class="form-control"
                                            :class="{ 'is-invalid': profileForm.errors.name }"
                                            placeholder="Masukkan nama lengkap" required>
                                        <div v-if="profileForm.errors.name" class="invalid-feedback">
                                            {{ profileForm.errors.name }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text">@</span>
                                            <input v-model="profileForm.username" type="text" class="form-control"
                                                :class="{ 'is-invalid': profileForm.errors.username }"
                                                placeholder="username" required>
                                        </div>
                                        <div v-if="profileForm.errors.username" class="invalid-feedback">
                                            {{ profileForm.errors.username }}
                                        </div>
                                        <div class="form-text">Username harus unik dan tidak dapat diubah
                                            setelah dibuat.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Level Akses</label>
                                        <select v-model="profileForm.level" class="form-select"
                                            :class="{ 'is-invalid': profileForm.errors.level }" disabled>
                                            <option value="admin">Administrator</option>
                                            <option value="user">User</option>
                                        </select>
                                        <div class="form-text">Level akses tidak dapat diubah sendiri.</div>
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary me-2"
                                            :disabled="profileForm.processing">
                                            <span v-if="profileForm.processing"
                                                class="spinner-border spinner-border-sm me-2"></span>
                                            <i class="fas fa-save me-1"></i>
                                            Simpan Perubahan
                                        </button>
                                        <button type="button" class="btn btn-light" @click="profileForm.reset()">
                                            <i class="fas fa-history me-1"></i>
                                            Reset
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div v-show="activeTab === 'security'">
                            <div class="row">
                                <div class="col-md-8">
                                    <form @submit.prevent="changePassword">
                                        <h5 class="mb-3">
                                            <i class="fas fa-key me-2"></i>
                                            Ubah Password
                                        </h5>

                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Password Saat Ini</label>
                                                <div class="input-group">
                                                    <input v-model="passwordForm.current_password"
                                                        :type="showCurrentPassword ? 'text' : 'password'"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': passwordForm.errors.current_password }"
                                                        placeholder="Masukkan password saat ini" required>
                                                    <button class="btn btn-outline-secondary" type="button"
                                                        @click="showCurrentPassword = !showCurrentPassword">
                                                        <i
                                                            :class="showCurrentPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                                    </button>
                                                </div>
                                                <div v-if="passwordForm.errors.current_password"
                                                    class="invalid-feedback">
                                                    {{ passwordForm.errors.current_password }}
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Password Baru</label>
                                                <div class="input-group">
                                                    <input v-model="passwordForm.new_password"
                                                        :type="showNewPassword ? 'text' : 'password'"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': passwordForm.errors.new_password }"
                                                        placeholder="Masukkan password baru" required>
                                                    <button class="btn btn-outline-secondary" type="button"
                                                        @click="showNewPassword = !showNewPassword">
                                                        <i
                                                            :class="showNewPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                                    </button>
                                                </div>
                                                <div v-if="passwordForm.errors.new_password" class="invalid-feedback">
                                                    {{ passwordForm.errors.new_password }}
                                                </div>

                                                <!-- Password Strength -->
                                                <div v-if="passwordForm.new_password" class="mt-2">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small class="text-muted">Kekuatan Password:</small>
                                                        <small :class="`text-${passwordStrength.color}`"
                                                            class="fw-semibold">
                                                            {{ passwordStrength.text }}
                                                        </small>
                                                    </div>
                                                    <div class="progress" style="height: 4px;">
                                                        <div class="progress-bar"
                                                            :class="`bg-${passwordStrength.color}`"
                                                            :style="{ width: (passwordStrength.level / 5) * 100 + '%' }">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                                                <div class="input-group">
                                                    <input v-model="passwordForm.confirm_password"
                                                        :type="showConfirmPassword ? 'text' : 'password'"
                                                        class="form-control" :class="{
                                                            'is-invalid': passwordForm.errors.confirm_password || (passwordForm.confirm_password && !passwordMatch),
                                                            'is-valid': passwordForm.confirm_password && passwordMatch
                                                        }" placeholder="Ulangi password baru" required>
                                                    <button class="btn btn-outline-secondary" type="button"
                                                        @click="showConfirmPassword = !showConfirmPassword">
                                                        <i
                                                            :class="showConfirmPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                                    </button>
                                                </div>
                                                <div v-if="passwordForm.errors.confirm_password"
                                                    class="invalid-feedback">
                                                    {{ passwordForm.errors.confirm_password }}
                                                </div>
                                                <div v-else-if="passwordForm.confirm_password && !passwordMatch"
                                                    class="invalid-feedback">
                                                    Password tidak cocok
                                                </div>
                                                <div v-else-if="passwordMatch" class="valid-feedback">
                                                    Password cocok
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary me-2"
                                                    :disabled="passwordForm.processing || !passwordMatch || passwordStrength.level < 3">
                                                    <span v-if="passwordForm.processing"
                                                        class="spinner-border spinner-border-sm me-2"></span>
                                                    <i class="fas fa-save me-1"></i>
                                                    Ubah Password
                                                </button>
                                                <button type="button" class="btn btn-light"
                                                    @click="passwordForm.reset()">
                                                    <i class="fas fa-history me-1"></i>
                                                    Reset
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Tips Keamanan
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex align-items-start mb-3">
                                                <i class="fas fa-check2-circle text-success me-2 mt-1"></i>
                                                <small>Minimal 8 karakter</small>
                                            </div>
                                            <div class="d-flex align-items-start mb-3">
                                                <i class="fas fa-check2-circle text-success me-2 mt-1"></i>
                                                <small>Kombinasi huruf besar & kecil</small>
                                            </div>
                                            <div class="d-flex align-items-start mb-3">
                                                <i class="fas fa-check2-circle text-success me-2 mt-1"></i>
                                                <small>Mengandung angka</small>
                                            </div>
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-check2-circle text-success me-2 mt-1"></i>
                                                <small>Karakter khusus (!@#$%^&*)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-show="activeTab === 'activity'">
                            <h5 class="mb-3">
                                <i class="fas fa-history me-2"></i>
                                Informasi Akun
                            </h5>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="thumb-xl justify-content-center d-flex align-items-center bg-info-subtle text-info rounded-circle me-2">
                                                        <i class="fas fa-calendar-plus fs-4"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1">Akun Dibuat</h6>
                                                    <p class="text-muted mb-0">{{ formatDate(props.user?.created_at) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="thumb-xl justify-content-center d-flex align-items-center bg-success-subtle text-success rounded-circle me-2">
                                                        <i class="fas fa-user-clock fs-4"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1">Terakhir Diubah</h6>
                                                    <p class="text-muted mb-0">{{ formatDate(props.user?.updated_at) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="thumb-xl justify-content-center d-flex align-items-center bg-success-subtle text-success rounded-circle me-2">
                                                        <i class="fas fa-user-tie fs-4"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1">ID Pengguna</h6>
                                                    <p class="text-muted mb-0">#{{ props.user?.id }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="thumb-xl justify-content-center d-flex align-items-center bg-warning-subtle text-warning rounded-circle me-2">
                                                        <i class="fas fa-user-shield fs-4"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1">Status</h6>
                                                    <span class="badge bg-success">Aktif</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>