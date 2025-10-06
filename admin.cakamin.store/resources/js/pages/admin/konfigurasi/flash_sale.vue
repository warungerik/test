<script setup>
import { Head, router } from "@inertiajs/vue3";
import utility from "../../admin/utility/page-title.vue";
import { ref, onMounted } from "vue";
import dayjs from "dayjs";
import Swal from "sweetalert2";

const props = defineProps({
    flash_sale: Object,
    flashsale: Array,
    game: Array,
    product: Array,
    denom: Array,
});


const emptyForm = () => ({
    id: null,
    game_id: null,
    product_id: null,
    denom_id: null,
    type_discount: "fixed",
    discount: null,
    limit: null,
    expired_at: "",
    status: "inactive",
});

const form_flashSale = ref({
    id: props.flash_sale?.id ?? null,
    expired_at: props.flash_sale?.expired_at
        ? dayjs(props.flash_sale.expired_at).format("YYYY-MM-DDTHH:mm")
        : "",
    status: props.flash_sale?.status ?? "inactive",
});

const flashsales = ref([...props.flashsale]);

const isCreate = ref(true);
const modalForm = ref(emptyForm());

const formatCurrency = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(n ?? 0);

const effectivePrice = (item) => {
    const price = item?.denom?.price ?? 0;
    const discount = Number(item?.discount ?? 0);
    if (item?.type_discount === "percent") {
        return Math.max(0, Math.round(price * (1 - discount / 100)));
    }
    return Math.max(0, price - discount);
}
const updateData = async () => {
    const res = await axios.post(route("api.konfigurasi-flashsale"), form_flashSale.value);
    if (res.data.status) {
        toastify["success"]?.("Gotcha!", res.data.message);
    } else {
        toastify["error"]?.("Whoops!", res.data.message);
    }
};

let bsModal = ref(false);

const openCreate = () => {
    isCreate.value = true;
    modalForm.value = emptyForm();
    bsModal.value = true;
};

const openEdit = (item) => {
    isCreate.value = false;
    modalForm.value = {
        id: item.id,
        game_id: item.game_id ?? item?.game?.id ?? null,
        product_id: item.product_id ?? item?.provider?.id ?? null,
        denom_id: item.denom_id ?? item?.denom?.id ?? null,
        type_discount: item.type_discount ?? "fixed",
        discount: item.discount ?? null,
        limit: item.limit ?? null,
        expired_at: item.expired_at ?? "",
        status: item.status ?? "inactive",
    };
    bsModal.value = true;
};
const submitModal = async () => {
    const f = modalForm.value;
    if (!f.game_id || !f.product_id || !f.denom_id || !f.limit || !f.type_discount) {
        const formEl = document.getElementById("modalForm");
        if (formEl) formEl.classList.add("was-validated");
        return;
    }

    try {
        const res = await axios.post(
            route("api.flash-sale", { create: isCreate.value, ...modalForm.value })
        );

        if (res.data.status) {
            toastify["success"]?.("Gotcha!", res.data.message);
            const newData = res.data.data
            if (isCreate.value) {
                flashsales.value.unshift(res.data.data);
            } else {
                const idx = flashsales.value.findIndex(p => p.id === newData.id)
                if (idx !== -1) {
                    flashsales.value[idx] = newData
                }
            }

            bsModal.value = false;
            modalForm.value = emptyForm();
        } else {
            toastify["error"]?.("Whoops!", res.data.message);
        }
    } catch (err) {
        toastify["error"]?.("Whoops!", "Terjadi kesalahan server.");
    }
};

const removeItem = async (item) => {
    try {
        const result = await Swal.fire({
            title: "Yakin ingin menghapus item ini?",
            text: "Jika item ini sudah digunakan, maka item ini akan dibatalkan",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, Hapus!"
        });

        if (result.isConfirmed) {
            const res = await axios.post(route("api.delete-flash-sale", { id: item.id }));
            if (res.data.status) {
                toastify["success"]?.("Gotcha!", res.data.message);
                const idx = flashsales.value.findIndex(i => i.id === item.id);
                if (idx !== -1) flashsales.value.splice(idx, 1);
            } else {
                toastify["error"]?.("Whoops!", res.data.message);
            }
        }
    } catch (err) {
        console.error(err);
        toastify["error"]?.("Whoops!", "Terjadi kesalahan server.");
    }
};

</script>

<template>

    <Head :title="'Flash Sale'" />
    <utility title="Flashsale" />

    <div class="container-fluid py-3">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">Konfigurasi Flash sale</div>
            </div>
            <div class="card-body">
                <form @submit.prevent="updateData" class="needs-validation" novalidate>
                    <div class="row g-3 align-items-center mb-3">
                        <label for="expired_at" class="col-lg-3 col-form-label">Berakhir pada</label>
                        <div class="col-lg-9">
                            <input type="datetime-local" v-model="form_flashSale.expired_at" class="form-control"
                                name="expired" id="expired_at" placeholder="YYYY-MM-DDTHH:mm" required />
                            <div class="invalid-feedback">Tanggal berakhir wajib diisi.</div>
                        </div>
                    </div>

                    <div class="row g-3 align-items-center mb-3">
                        <label for="status" class="col-lg-3 col-form-label">Status</label>
                        <div class="col-lg-9">
                            <select name="status" v-model="form_flashSale.status" id="status" class="form-select"
                                required>
                                <option value="active">Aktif</option>
                                <option value="inactive">Tidak</option>
                            </select>
                            <div class="invalid-feedback">Pilih status.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">Data Flash Sale</div>
                <button type="button" class="btn btn-primary" @click="openCreate">
                    Tambah Flash Sale
                </button>
            </div>
            <div class="card-body">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                    <div class="col" v-for="item in flashsales" :key="item.id">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body d-flex flex-column border" :style="{ borderRadius: '0.5rem' }">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="btn btn-sm"
                                        :class="item.status == 'active' ? 'btn-success' : 'btn-danger'">
                                        {{ item.status == "active" ? "Aktif" : "Nonaktif" }}
                                    </span>
                                    <small class="text-muted">Limit tersisa:
                                        {{ Math.max(0, (item?.limit ?? 0) - (item?.used_limit ?? 0)) }}</small>
                                </div>

                                <h5 class="card-title mb-1">{{ item?.game?.name || "-" }}</h5>
                                <p class="card-subtitle text-muted mb-2">
                                    {{ item?.product?.name || "-" }}
                                </p>

                                <div class="mt-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-decoration-line-through text-muted" v-if="item?.denom?.price">
                                            {{ formatCurrency(item.denom.price) }}
                                        </span>
                                        <span class="fw-bold text-primary">{{
                                            formatCurrency(effectivePrice(item))
                                            }}</span>
                                    </div>
                                    <small class="text-muted">
                                        {{ item?.denom?.name || "-" }}
                                        • Diskon:
                                        <strong>
                                            <template v-if="item.type_discount === 'percent'">
                                                {{ item.discount }}%
                                            </template>
                                            <template v-else>
                                                {{ formatCurrency(item.discount) }}
                                            </template>
                                        </strong>
                                    </small>
                                </div>

                                <div class="mt-auto pt-3 d-flex justify-content-between">
                                    <button class="btn btn-sm btn-outline-primary" @click="openEdit(item)">
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="removeItem(item)">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div v-if="!flashsales || flashsales.length === 0" class="col">
                    <div class="alert alert-outline-light border text-center mb-0">
                        Belum ada data flash sale.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="flashSaleModal" :class="{ show: bsModal }" style="display: block;" v-if="bsModal"
        @click.self="bsModal = false" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="flashSaleModalLabel">
                        {{ isCreate ? "Tambah" : "Edit" }} Flash Sale
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form id="modalForm" class="needs-validation" novalidate @submit.prevent="submitModal">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="game_id">Game</label>
                                <select id="game_id" v-model="modalForm.game_id" class="form-select" required>
                                    <option :value="null" disabled>Pilih game</option>
                                    <option v-for="g in props.game || []" :key="g.id" :value="g.id">
                                        {{ g.name }}
                                    </option>
                                </select>
                                <div class="invalid-feedback">Game wajib dipilih.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="product_id">Product</label>
                                <select id="denom_id" v-model="modalForm.product_id" class="form-select" required>
                                    <option :value="null" disabled>Pilih product</option>

                                    <template
                                        v-if="props.product.filter((p) => p.game_id == modalForm.game_id).length > 0">
                                        <option v-for="d in props.product.filter((p) => p.game_id == modalForm.game_id)"
                                            :key="d.id" :value="d.id">
                                            {{ d.name }}
                                        </option>
                                    </template>
                                </select>
                                <div class="invalid-feedback">Provider wajib dipilih.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="denom_id">Denom</label>
                                <select id="denom_id" v-model="modalForm.denom_id" class="form-select" required>
                                    <option :value="null" disabled>Pilih denom</option>

                                    <template
                                        v-if="props.denom.filter((p) => p.product_id == modalForm.product_id).length > 0">
                                        <option
                                            v-for="d in props.denom.filter((p) => p.product_id == modalForm.product_id)"
                                            :key="d.id" :value="d.id">
                                            {{ d.name }} — {{ formatCurrency(d.price) }}
                                        </option>
                                    </template>
                                </select>

                                <div class="invalid-feedback">Denom wajib dipilih.</div>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label" for="limit">Limit</label>
                                <input id="limit" type="number" min="1" step="1" v-model.number="modalForm.limit"
                                    class="form-control" placeholder="Jumlah kuota" required />
                                <div class="invalid-feedback">Limit minimal 1.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="type_discount">Tipe Diskon</label>
                                <select id="type_discount" v-model="modalForm.type_discount" class="form-select"
                                    required>
                                    <option value="fixed">Potongan (Rp)</option>
                                    <option value="percent">Persen (%)</option>
                                </select>
                                <div class="invalid-feedback">Pilih tipe diskon.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="discount">Nilai Diskon</label>
                                <div class="input-group">
                                    <span v-if="modalForm.type_discount === 'fixed'" class="input-group-text">Rp</span>
                                    <input id="discount" type="number" min="0"
                                        :max="modalForm.type_discount === 'percent' ? 100 : undefined" step="1"
                                        v-model.number="modalForm.discount" class="form-control"
                                        placeholder="Nilai diskon" required />
                                    <span v-if="modalForm.type_discount === 'percent'" class="input-group-text">%</span>
                                    <div class="invalid-feedback">
                                        <template v-if="modalForm.type_discount === 'percent'">Diskon % harus
                                            0–100.</template>
                                        <template v-else>Diskon tidak boleh negatif.</template>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label" for="status_modal">Status</label>
                                <select id="status_modal" v-model="modalForm.status" class="form-select" required>
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Tidak</option>
                                </select>
                                <div class="invalid-feedback">Pilih status.</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="bsModal = false">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            {{ isCreate ? "Tambah" : "Simpan" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
