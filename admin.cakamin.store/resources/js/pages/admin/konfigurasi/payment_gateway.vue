<script setup>
import { Head } from '@inertiajs/vue3';
import utility from '../../admin/utility/page-title.vue'
import { ref } from 'vue';

const metode = ref("")

const props = defineProps({
    payment_gateway: Array,
})

const form = ref(props.payment_gateway || {})

const submitForm = async () => {
    try {
        const res = await axios.post(
            route('api.payment-gateway'),
            {
                id: metode.value,
                ...form.value[metode.value]
            }
        )
        if (res.data.status) {
            toastify["success"]?.("Berhasil!", res.data.message);
        } else {
            toastify["error"]?.("Whoops!", res.data.message);
        }

    } catch (e) {
        toastify["error"]?.("Whoops!", "Terjadi kesalahan pada server");
    }
}
</script>

<template>

    <Head :title="'Konfigurasi Payment Gateway'" />
    <utility title="Payment" />

    <div class="card">
        <div class="card-body">
            <div class="mb-4">
                <label for="metode">Pilih Metode Pembayaran</label>
                <select name="metode" id="metode" class="form-control form-select" v-model="metode">
                    <option value="">Pilih metode</option>
                    <option v-for="(val, key) in payment_gateway" :key="key" :value="key">
                        {{ key.charAt(0).toUpperCase() + key.slice(1) }}
                    </option>
                </select>

                <form class="mt-2" @submit.prevent="submitForm">
                    <div v-for="(value, keys) in form" :key="keys" :class="[
                        'paymentgateway',
                        keys,
                        'mb-3',
                        metode !== keys ? 'd-none' : ''
                    ]">
                        <div class="row mb-2">
                            <template v-for="(values, key) in value" :key="key">
                                <label class="col-lg-3 col-form-label fw-semibold">
                                    {{key.replace(/_/g, " ").replace(/\b\w/g, l => l.toUpperCase())}}
                                </label>

                                <div class="col-lg-9 mb-2">
                                    <select v-if="key === 'status' || key === 'cancel_support'"
                                        v-model="form[keys][key]" class="form-control form-select">
                                        <option value="active">Aktif</option>
                                        <option value="inactive">Nonaktif</option>
                                    </select>
                                    <input v-else type="text" class="form-control" v-model="form[keys][key]"
                                        :id="key" />
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-shadow btn-primary" type="submit">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
