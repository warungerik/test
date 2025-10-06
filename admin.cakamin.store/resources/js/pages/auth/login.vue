<script setup>
import { Head, Link } from "@inertiajs/vue3"
import { ref } from "vue"
import { route } from "ziggy-js"

const props = defineProps({
    app: String,
    website: Object
})

const username = ref('')
const password = ref('')
const rememberMe = ref(false)

const login = async () => {
    try {
        if (!username.value || !password.value) {
            return toastify['error']?.("Whoops!", 'Data harus diisi semua.')
        }
        const res = await axios.post(route('api.login'), {
            username: username.value,
            password: password.value,
            remember: rememberMe.value
        })
        if (res.data.status) {
            toastify["success"]?.("Gotcha!", res.data.message);
            setTimeout(() => {
                window.location.href = route('dashboard')
            }, 2000);
        } else {
            toastify["error"]?.("Oops!", res.data.message);
        }
    } catch (err) {
        toastify["error"]?.("Oops!", 'Terjadi kesalahan, silahkan coba lagi nanti.');
    }
}

function togglePassword() {
    const passwordInput = document.getElementById("userpassword");
    const togglePassword = document.getElementById("toggle-password");
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        togglePassword.classList.remove("fa-eye-slash");
        togglePassword.classList.add("fa-eye");
    } else {
        passwordInput.type = "password";
        togglePassword.classList.remove("fa-eye");
        togglePassword.classList.add("fa-eye-slash");
    }
}
</script>

<template>

    <Head :title="'Login user'" />

    <div class="row vh-100 d-flex justify-content-center">
        <div class="col-12 align-self-center">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-4 mx-auto">
                        <div class="card">
                            <div class="card-body p-0 bg-black auth-header-box rounded-top">
                                <div class="text-center p-3">
                                    <Link :href="route('login')" class="logo logo-admin">
                                    <img :src="`${app}/${website.logo}`" height="50" alt="logo" class="auth-logo" />
                                    </Link>
                                    <h4 class="mt-3 mb-1 fw-semibold text-white fs-18">
                                        Let's Get Started {{ website.name_store }}
                                    </h4>
                                    <p class="text-muted fw-medium mb-0">
                                        Sign in to continue to {{ website.name_store }}.
                                    </p>
                                </div>
                            </div>

                            <div class="card-body pt-0">
                                <form class="my-4" @submit.prevent="login">
                                    <div class="form-group mb-2">
                                        <label class="form-label" for="username">Username</label>
                                        <input v-model="username" type="text" class="form-control" id="username"
                                            placeholder="Enter username" />
                                    </div>

                                    <div class="form-group position-relative">
                                        <label class="form-label" for="password">Password</label>
                                        <div class="input-group">
                                            <input v-model="password" type="password" class="form-control"
                                                id="userpassword" placeholder="Password" required />
                                            <span class="input-group-text" style="cursor:pointer">
                                                <i id="toggle-password" class="fa fa-eye-slash"
                                                    @click="togglePassword"></i>
                                            </span>
                                        </div>
                                    </div>


                                    <!-- Remember Me -->
                                    <div class="form-group row mt-3">
                                        <div class="col-sm-6">
                                            <div class="form-check form-switch form-switch-success">
                                                <input v-model="rememberMe" class="form-check-input" type="checkbox"
                                                    id="customSwitchSuccess" />
                                                <label class="form-check-label" for="customSwitchSuccess">Remember
                                                    me</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Button -->
                                    <div class="form-group mb-0 row">
                                        <div class="col-12">
                                            <div class="d-grid mt-3">
                                                <button class="btn btn-primary" type="submit">
                                                    Log In <i class="fas fa-sign-in-alt ms-1"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
