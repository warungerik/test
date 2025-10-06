<script setup>
import { ref } from "vue"
import { route } from "ziggy-js"
import axios from "axios"
import { Head, usePage } from "@inertiajs/vue3"

const props = defineProps({
    feedback: Array,
    countFeedback: Number,
    averageRating: String
})
const page = usePage()
const auth = page.props.auth;
const feedbackList = ref([...props.feedback])
const itemShow = ref(3)
const hasMore = ref(true)
const loading = ref(false)

async function loadMore() {
    loading.value = true
    try {
        const res = await axios.post(route("api.load-more-feedback", { count: itemShow.value }))
        if (res.data.status) {
            feedbackList.value.push(...res.data.feedback)
            itemShow.value += 15

            if (feedbackList.value.length >= props.countFeedback) {
                hasMore.value = false
            }
        } else {
            toastify["error"]?.("Whoops!", res.data.message)
        }
    } catch (error) {
        toastify["error"]?.("Whoops!", "Terjadi kesalahan, coba beberapa saat lagi.")
    } finally {
        loading.value = false
    }
}
function toggleReply(f) {
    f.showReply = !f.showReply;
}
async function submitReply(f) {
    if (!f.reply || f.reply.trim() === "") {
        alert("Reply tidak boleh kosong!");
        return;
    }

    try {
        const res = await axios.post(route("api.reply-admin"), {
            feedback_id: f.id,
            reply: f.reply,
        });

        f.reply_admin = f.reply;
        f.reply = "";
        f.showReply = false;
        if (res.data.status) {
            toastify["success"]?.("Gotcha!", "Success reply feedback");
        } else {
            toastify["error"]?.("Whoops!", res.data.message);

        }
    } catch (err) {
        toastify["error"]?.("Whoops!", "Failed send reply feedback");
    }
}


function timeAgo(dateString) {
    const now = new Date()
    const past = new Date(dateString)
    const diff = Math.floor((now - past) / 1000)

    if (diff < 60) return `${diff} seconds ago`
    if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`
    if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`
    if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`

    return past.toLocaleDateString("en-US", {
        day: "2-digit",
        month: "long",
        year: "numeric",
    })
}
</script>

<template>

    <Head :title="'Feedback'" />
    <section class="reviews-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-12 text-center text-md-start">
                    <div class="overall-rating">
                        <div class="reviews-rating-number">{{ averageRating }}</div>
                        <div class="reviews-rating-stars">
                            <i v-for="n in 5" :key="n" class="fas" :class="{
                                'fa-star text-info': n <= Math.floor(averageRating), // full star
                                'fa-star-half-alt text-info':
                                    n === Math.floor(averageRating) + 1
                                    && averageRating % 1 >= 0.3
                                    && averageRating % 1 < 0.8,                     // half star
                                'fa-star text-secondary':
                                    n > Math.floor(averageRating) +
                                    ((averageRating % 1 >= 0.3 && averageRating % 1 < 0.8) ? 1 : 0) // empty star
                            }">
                            </i>
                        </div>
                        <div class="reviews-rating-count">
                            Based on {{ countFeedback }} reviews
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="reviews-content">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="row">
                        <!-- gunakan feedbackList -->
                        <div class="col-md-6 col-lg-4" v-for="f in feedbackList" :key="f.id">
                            <div class="review-card">
                                <div class="review-header-row">
                                    <div class="review-rating">
                                        <i class="fas" v-for="n in 5" :key="n"
                                            :class="n <= f.rating ? 'fa-star text-info' : 'fa-star text-secondary'">
                                        </i>
                                    </div>
                                    <div class="review-date">
                                        <span>{{ timeAgo(f.created_at) }}</span>
                                    </div>
                                </div>
                                <p class="review-content">{{ f.message }}</p>
                                <div class="verified-badge">
                                    <div class="badge-text">
                                        <i class="fas fa-check-circle me-2"></i> Verified Purchase
                                    </div>
                                    <div class="badge-text text-info" style="cursor: pointer"
                                        v-if="auth.user?.role == 'admin'" @click="toggleReply(f)">
                                        {{ f.showReply ? "Close" : "Reply" }}
                                    </div>
                                </div>
                                <div class="seller-reply" v-if="f.reply_admin">
                                    <div class="seller-reply-header">
                                        <i class="fas fa-user-shield me-2"></i> Admin Reply
                                    </div>
                                    <div class="seller-reply-content" v-html="f.reply_admin"></div>
                                </div>
                                <div v-if="auth.user?.role == 'admin' && f.showReply">
                                    <div class="admin-reply-form">
                                        <form @submit.prevent="submitReply(f)">
                                            <textarea v-model="f.reply"
                                                placeholder="Enter your reply here..."></textarea>
                                            <div class="admin-form-actions">
                                                <button class="btn-cancel-reply" type="button"
                                                    @click="f.showReply = false">
                                                    Cancel
                                                </button>
                                                <button class="btn-submit-reply" type="submit">Submit</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mb-4">
                        <button class="load-more text-center mb-4" type="button" v-if="hasMore" @click="loadMore">
                            <span v-if="loading">Loading..</span>
                            <span v-else>Load More Reviews</span>
                            <i class="fas fa-chevron-down ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
