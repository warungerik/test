<script setup>
import { Link } from '@inertiajs/vue3'
import { head } from 'lodash'
import Swal from 'sweetalert2'
import { computed, onMounted, ref, watch } from 'vue'
import { route } from 'ziggy-js'
const props = defineProps({
    app: String,
    website: Array
})
const darkTheme = ref(true)
watch(darkTheme, (newVal) => {
    if (newVal) {
        document.querySelector('html').setAttribute('data-bs-theme', 'dark')
    } else {
        document.querySelector('html').removeAttribute('data-bs-theme')
    }
})
const toggleTheme = () => {
    darkTheme.value = !darkTheme.value
}
onMounted(() => {
    try {
        var collapsedToggle = document.querySelector(".mobile-menu-btn");
        const h = document.querySelector(".startbar-overlay"),
            changeSidebarSize =
                (collapsedToggle?.addEventListener("click", function () {
                    "collapsed" == document.body.getAttribute("data-sidebar-size")
                        ? document.body.setAttribute("data-sidebar-size", "default")
                        : document.body.setAttribute(
                            "data-sidebar-size",
                            "collapsed"
                        );
                }),
                    h &&
                    h.addEventListener("click", () => {
                        document.body.setAttribute(
                            "data-sidebar-size",
                            "collapsed"
                        );
                    }),
                    () => {
                        310 <= window.innerWidth && window.innerWidth <= 1440
                            ? document.body.setAttribute(
                                "data-sidebar-size",
                                "collapsed"
                            )
                            : document.body.setAttribute(
                                "data-sidebar-size",
                                "default"
                            );
                    });
        window.addEventListener("resize", () => {
            changeSidebarSize();
        }),
            changeSidebarSize();
    } catch (e) { }
})
const confirmLogout = () => {
    Swal.fire({
        title: 'Yakin ingin keluar?',
        text: "Jika anda keluar, maka anda akan keluar dari halaman ini",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Keluar!',
    }).then(result => {
        if (result.isConfirmed) {
            window.location.href = route('user.logout');
        }
    })
}
const currentPath = window.location.pathname;
const loginLayout = computed(() => !currentPath.startsWith("/login"));
</script>
<template>
    <div v-if="loginLayout">
        <div class="topbar d-print-none">
            <div class="container-fluid">
                <nav class="topbar-custom d-flex justify-content-between" id="topbar-custom">
                    <ul class="topbar-item list-unstyled d-inline-flex align-items-center mb-0">
                        <li>
                            <button class="nav-link mobile-menu-btn nav-icon" id="togglemenu">
                                <i class="iconoir-menu"></i>
                            </button>
                        </li>
                    </ul>
                    <ul class="topbar-item list-unstyled d-inline-flex align-items-center mb-0">

                        <li class="topbar-item">
                            <a class="nav-link nav-icon" href="javascript:void(0);" @click="toggleTheme">
                                <i class="iconoir-half-moon dark-mode" v-if="darkTheme == true"></i>
                                <i class="iconoir-sun-light light-mode" v-if="darkTheme == false"></i>
                            </a>
                        </li>

                        <li class="dropdown topbar-item">
                            <a class="nav-link dropdown-toggle arrow-none nav-icon" data-bs-toggle="dropdown" href="#"
                                role="button" aria-haspopup="false" aria-expanded="false" data-bs-offset="0,19">
                                <img :src="`${app}/assets/images/user/avatar.jpg`" alt=""
                                    class="thumb-md rounded-circle">
                            </a>
                            <div class="dropdown-menu dropdown-menu-end py-0">
                                <div class="d-flex align-items-center dropdown-item py-2 bg-secondary-subtle">
                                    <div class="flex-shrink-0">
                                        <img :src="`${app}/assets/images/user/avatar.jpg`" alt=""
                                            class="thumb-md rounded-circle">
                                    </div>
                                    <div class="flex-grow-1 ms-2 text-truncate align-self-center">
                                        <h6 class="my-0 fw-medium text-dark fs-13">Admin Panel</h6>
                                        <small class="text-muted mb-0">Owner panel</small>
                                    </div>
                                </div>
                                <div class="dropdown-divider mt-0"></div>
                                <small class="text-muted px-2 pb-1 d-block">Account</small>
                                <Link class="dropdown-item" :href="route('user.settings')"><i
                                    class="las la-user fs-18 me-1 align-text-bottom"></i> Profile</Link>
                                <div class="dropdown-divider mb-0"></div>
                                <a href="javascript:void(0)" @click="confirmLogout" class="dropdown-item text-danger"><i
                                        class="las la-power-off fs-18 me-1 align-text-bottom"></i> Logout</a>
                            </div>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <div class="startbar d-print-none">
            <div class="brand">
                <Link :href="route('dashboard')" class="logo">
                <span>
                    <img :src="`${app}/assets/images/logo/${website.logo}`" alt="logo-small" class="logo-sm">
                </span>
                <span class="">
                    <img :src="`${app}/assets/images/logo/${website.logo}`" alt="logo-large" class="logo-lg logo-light">
                    <img :src="`${app}/assets/images/logo/${website.logo}`" alt="logo-large" class="logo-lg logo-dark">
                </span>
                </Link>
            </div>
            <div class="startbar-menu">
                <div class="startbar-collapse" id="startbarCollapse" data-simplebar>
                    <div class="d-flex align-items-start flex-column w-100">
                        <ul class="navbar-nav mb-auto w-100">
                            <li class="menu-label mt-2">
                                <span>Main</span>
                            </li>

                            <li class="nav-item" :class="{ active: route().current('dashboard') }">
                                <Link class="nav-link" :class="{ active: route().current('dashboard') }"
                                    :href="route('dashboard')">
                                <i class="iconoir-report-columns menu-icon"></i>
                                <span>Dashboard</span>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="collapse" data-bs-target="#sidebarKonfigurasi"
                                    role="button" :class="{ active: route().current('admin.konfigurasi.*') }"
                                    :aria-expanded="route().current('admin.konfigurasi.*') ? 'true' : 'false'"
                                    aria-controls="sidebarKonfigurasi">
                                    <i class="iconoir-task-list menu-icon"></i>
                                    <span>Konfigurasi</span>
                                </a>
                                <div class="collapse" :class="{ show: route().current('admin.konfigurasi.*') }"
                                    id="sidebarKonfigurasi">
                                    <ul class="nav flex-column ms-3">
                                        <li class="nav-item"
                                            :class="{ active: route().current('admin.konfigurasi.website') }">
                                            <Link class="nav-link" :href="route('admin.konfigurasi.website')"
                                                :class="{ active: route().current('admin.konfigurasi.website') }">
                                            Website
                                            </Link>
                                        </li>

                                        <li class="nav-item"
                                            :class="{ active: route().current('admin.konfigurasi.banner') }">
                                            <Link class="nav-link" :href="route('admin.konfigurasi.banner')"
                                                :class="{ active: route().current('admin.konfigurasi.banner') }">
                                            Banner
                                            </Link>
                                        </li>

                                        <li class="nav-item"
                                            :class="{ active: route().current('admin.konfigurasi.order') }">
                                            <Link class="nav-link" :href="route('admin.konfigurasi.order')"
                                                :class="{ active: route().current('admin.konfigurasi.order') }">
                                            Order
                                            </Link>
                                        </li>

                                        <li class="nav-item"
                                            :class="{ active: route().current('admin.konfigurasi.payment-gateway') }">
                                            <Link class="nav-link" :href="route('admin.konfigurasi.payment-gateway')"
                                                :class="{ active: route().current('admin.konfigurasi.payment-gateway') }">
                                            Payment Gateway
                                            </Link>
                                        </li>
                                        <li class="nav-item"
                                            :class="{ active: route().current('admin.konfigurasi.flash-sale') }">
                                            <Link class="nav-link" :href="route('admin.konfigurasi.flash-sale')"
                                                :class="{ active: route().current('admin.konfigurasi.flash-sale') }">
                                            Flashsale
                                            </Link>
                                        </li>
                                        <li class="nav-item"
                                            :class="{ active: route().current('admin.konfigurasi.digiflazz') }">
                                            <Link class="nav-link" :href="route('admin.konfigurasi.digiflazz')"
                                                :class="{ active: route().current('admin.konfigurasi.digiflazz') }">
                                            Digiflazz
                                            </Link>
                                        </li>

                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item" :class="{ active: route().current('admin.voucher') }">
                                <Link class="nav-link" :class="{ active: route().current('admin.voucher') }"
                                    :href="route('admin.voucher')">
                                <i class="iconoir-gift menu-icon"></i>
                                <span>Voucher</span>
                                </Link>
                            </li>
                            <li class="nav-item" :class="{ active: route().current('admin.categories') }">
                                <Link class="nav-link" :class="{ active: route().current('admin.categories') }"
                                    :href="route('admin.categories')">
                                <i class="iconoir-filter-list menu-icon"></i>
                                <span>Kategori</span>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="collapse" data-bs-target="#sidebarDat" role="button"
                                    :class="{ active: route().current('admin.data.*') }"
                                    :aria-expanded="route().current('admin.data.*') ? 'true' : 'false'"
                                    aria-controls="sidebarDat">
                                    <i class="iconoir-database-tag menu-icon"></i>
                                    <span>Data</span>
                                </a>
                                <div class="collapse" :class="{ show: route().current('admin.data.*') }"
                                    id="sidebarDat">
                                    <ul class="nav flex-column ms-3">
                                        <li class="nav-item"
                                            :class="{ active: route().current('admin.data.providers') }">
                                            <Link class="nav-link" :href="route('admin.data.providers')"
                                                :class="{ active: route().current('admin.data.providers') }">
                                            Provider
                                            </Link>
                                        </li>
                                        <li class="nav-item"
                                            :class="{ active: route().current('admin.data.products') }">
                                            <Link class="nav-link" :href="route('admin.data.products')"
                                                :class="{ active: route().current('admin.data.products') }">Products
                                            </Link>
                                        </li>
                                        <li class="nav-item" :class="{ active: route().current('admin.data.payment') }">
                                            <Link class="nav-link" :href="route('admin.data.payment')"
                                                :class="{ active: route().current('admin.data.payment') }">Payment
                                            </Link>
                                        </li>
                                        <li class="nav-item" :class="{ active: route().current('admin.data.denom') }">
                                            <Link class="nav-link" :href="route('admin.data.denom')"
                                                :class="{ active: route().current('admin.data.denom') }">Denom
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="collapse" data-bs-target="#sidebarDatPPOB"
                                    role="button" :class="{ active: route().current('admin.data-ppob.*') }"
                                    :aria-expanded="route().current('admin.data-ppob.*') ? 'true' : 'false'"
                                    aria-controls="sidebarDatPPOB">
                                    <i class="iconoir-database-tag menu-icon"></i>
                                    <span>Data PPOB</span>
                                </a>
                                <div class="collapse" :class="{ show: route().current('admin.data-ppob.*') }"
                                    id="sidebarDatPPOB">
                                    <ul class="nav flex-column ms-3">
                                        <li class="nav-item"
                                            :class="{ active: route().current('admin.data-ppob.products') }">
                                            <Link class="nav-link" :href="route('admin.data-ppob.products')"
                                                :class="{ active: route().current('admin.data-ppob.products') }">
                                            Products
                                            </Link>
                                        </li>
                                        <li class="nav-item"
                                            :class="{ active: route().current('admin.data-ppob.denom') }">
                                            <Link class="nav-link" :href="route('admin.data-ppob.denom')"
                                                :class="{ active: route().current('admin.data-ppob.denom') }">
                                            Denom
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item" :class="{ active: route().current('admin.feedback') }">
                                <Link class="nav-link" :class="{ active: route().current('admin.feedback') }"
                                    :href="route('admin.feedback')">
                                <i class="iconoir-message-text menu-icon"></i>
                                <span>Feedback</span>
                                </Link>
                            </li>
                            <li class="nav-item" :class="{ active: route().current('admin.history') }">
                                <Link class="nav-link" :class="{ active: route().current('admin.history') }"
                                    :href="route('admin.history')">
                                <i class="iconoir-database-settings menu-icon"></i>
                                <span>History</span>
                                </Link>
                            </li>
                            <li class="nav-item" :class="{ active: route().current('admin.stock-license') }">
                                <Link class="nav-link" :class="{ active: route().current('admin.stock-license') }"
                                    :href="route('admin.stock-license')">
                                <i class="iconoir-database menu-icon"></i>
                                <span>Stock</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="startbar-overlay d-print-none"></div>
        <div class="page-wrapper">
            <div class="page-content">
                <slot />
            </div>
        </div>
    </div>
    <div v-else>
        <div class="container-xxl">
            <slot />
        </div>
    </div>
</template>