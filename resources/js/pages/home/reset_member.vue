<script setup>

import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const product_id = ref(null)
const loading = ref(false)
const props = defineProps({
    product: Array,
})

const submit = async () => {
    loading.value = true
    if (!product.value) {
        toastify["error"]?.("Whoops!", "Pilih Product terlebih dahulu");
        return
    }
    if (!license.value) {
        toastify["error"]?.("Whoops!", "Masukkan license terlebih dahulu");
        return
    }
    try {
        const res = await axios.post(route('api.reset-license'), { product_id: product_id.value, license: license.value })
        if (res.data.status) {
            toastify["success"]?.("Gotcha!", res.data.message);
            license.value = ''
        } else {
            toastify["error"]?.("Whoops!", res.data.message);
        }
    } catch (e) {
        toastify['error']?.("Whoops!", "Terjadi Kesalahan, silahkan coba beberapa saat lagi")
    } finally {
        loading.value = false
    }
}
</script>
<template>

    <Head :title="`Reset License / Key`" />
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="title-head text-left mb-3 mt-3">
                Reset License
            </div>
            <div class="cards shadow mb-2">
                <form @submit.prevent="submit">
                    <div class="nowrap-left floating-label-content mb-2">
                        <select class="form-select form-control floating-select" id="product" v-model="product_id">
                            <option selected :value="null">Pilih Product</option>
                            <option v-for="p in product" :value="p.id" v-html="p.name"></option>
                        </select>
                    </div>
                    <div class="floating-label-content">
                        <input type="text" class="form-control games-input floating-input" v-model="license"
                            name="license" id="license" placeholder="Masukkan license" required="">
                    </div>
                    <div class="text-end">
                        <button class="btn btn-primary mt-3">
                            <span v-if="loading" class="spinner-border spinner-border-sm" role="status"
                                aria-hidden="true"></span>
                            <span v-else>Reset</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>