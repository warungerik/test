<script setup>
import { Head } from "@inertiajs/vue3";
import axios from "axios";
import { ref } from "vue";

const invoice_id = ref("");
const cekstatus = async () => {
    try {
        const res = await axios.post(
            route("api.cek-status", { invoice_id: invoice_id.value })
        );
        if (res.data.status) {
            toastify["success"]?.("Gotcha!", res.data.message);

            setInterval(() => {
                window.location.href = route("home.invoice", invoice_id.value);
            }, 2000);
        } else {
            toastify["error"]?.("Whoops!", res.data.message);
        }
    } catch (err) {
        toastify["error"]?.("Whoops!", err.message);
    }
};
</script>
<template>

    <Head :title="`Lacak Pesanan`" />
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="containerTracking">
                <div class="head">
                    <div class="title">Cek Status Pesanan Kamu</div>
                </div>
                <div class="cardTrack">
                    <form @submit.prevent="cekstatus">
                        <div class="floating-label-search">
                            <input type="text" class="form-control floating-input" v-model="invoice_id"
                                name="invoice_id" placeholder="Invoice ID" />
                            <button class="btnYellowPrimary py-3">Cari</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="h-40"></div>
</template>
