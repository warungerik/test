<script setup>
import { ref, reactive, watch } from "vue";
import axios from "axios";
import { Head } from "@inertiajs/vue3"
import utility from '../../admin/utility/page-title.vue'

// props
const props = defineProps({
    app: String,
    website: Object,
});

// previews & files
const logoPreview = ref("");
const faviconPreview = ref("");
const ogImagePreview = ref("");
const coverImagePreview = ref("");
const logoFile = ref(null);
const faviconFile = ref(null);
const ogImageFile = ref(null);
const coverImageFile = ref(null);

// form state
const formData = reactive({
    name_store: "",
    logo: "",
    header: {
        title: "",
        favicon: "",
        description: "",
        keywords: "",
        author: "",
        theme_color: "#11143A",
        robots: "index, follow",
        device: "desktop",
        coverage: "Worldwide",
        apple_mobile_web_app_title: "",
        apple_mobile_web_app_capable: true,
        apple_touch_fullscreen: true,
        og: {
            type: "website",
            url: "",
            title: "",
            site_name: "",
            description: "",
            image: { url: "", alt: "" },
        },
    },
    footer: {
        section1: { title: "", text: "", text2: "" },
        section2: { title: "FAQ", title2: "Pertanyaan Umum", title3: "", accordion: [] },
        section3: { desc: "", image_cover: "", contact: [] },
    },
});

// sync props
watch(
    () => props.website,
    (newVal) => {
        if (newVal) {
            Object.assign(formData, JSON.parse(JSON.stringify(newVal)));
            if (formData.logo) logoPreview.value = props.app + "/assets/images/logo/" + formData.logo;
            if (formData.header.favicon) faviconPreview.value = props.app + "/assets/images/favicon/" + formData.header.favicon;
            if (formData.header.og.image.url) ogImagePreview.value = props.app + "/assets/images/icon/" + formData.header.og.image.url;
            if (formData.footer.section3.image_cover) coverImagePreview.value = props.app + "/assets/images/banner/" + formData.footer.section3.image_cover;
        }
    },
    { immediate: true }
);
function objectToFormData(obj, formData = new FormData(), parentKey = "") {
    if (obj && typeof obj === "object" && !(obj instanceof File)) {
        Object.keys(obj).forEach((key) => {
            const value = obj[key];
            const newKey = parentKey ? `${parentKey}[${key}]` : key;
            objectToFormData(value, formData, newKey);
        });
    } else {
        formData.append(parentKey, obj ?? "");
    }
    return formData;
}

// file handlers
const previewFile = (file, target) => {
    if (file && file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = (e) => (target.value = e.target.result);
        reader.readAsDataURL(file);
    }
};
const handleLogoUpload = (e) => { logoFile.value = e.target.files[0]; previewFile(logoFile.value, logoPreview); };
const handleFaviconUpload = (e) => { faviconFile.value = e.target.files[0]; previewFile(faviconFile.value, faviconPreview); };
const handleOgImageUpload = (e) => { ogImageFile.value = e.target.files[0]; previewFile(ogImageFile.value, ogImagePreview); };
const handleCoverImageUpload = (e) => { coverImageFile.value = e.target.files[0]; previewFile(coverImageFile.value, coverImagePreview); };

// FAQ & Contact
const addFAQ = () => formData.footer.section2.accordion.push({ title: "", fill: "" });
const removeFAQ = (index) => {
    if (confirm('Are you sure you want to delete this FAQ item?')) {
        formData.footer.section2.accordion.splice(index, 1);
    }
};

const moveFAQ = (index, direction) => {
    const items = formData.footer.section2.accordion;
    if (direction === 'up' && index > 0) {
        [items[index], items[index - 1]] = [items[index - 1], items[index]];
    } else if (direction === 'down' && index < items.length - 1) {
        [items[index], items[index + 1]] = [items[index + 1], items[index]];
    }
};
const addContact = () => formData.footer.section3.contact.push({ icon: "", url: "" });
const removeContact = (i) => formData.footer.section3.contact.splice(i, 1);

// save
const saveAll = async () => {
    let fd = objectToFormData(formData);
    if (logoFile.value) fd.set("logo", logoFile.value);
    if (faviconFile.value) fd.set("header[favicon]", faviconFile.value);
    if (ogImageFile.value) fd.set("header[og][image][url]", ogImageFile.value);
    if (coverImageFile.value) fd.set("footer[section3][image_cover]", coverImageFile.value);

    try {
        const res = await axios.post(route("api.konfigurasi-website"), fd, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        if (res.data.status) {
            toastify["success"]?.("Gotcha!", res.data.message);
        } else {
            toastify["error"]?.("Whoops!", res.data.message);
        }
    } catch (err) {
        toastify["error"]?.("Whoops!", err.message);
    }
};
</script>

<template>

    <Head :title="'Konfigurasi Website'" />
    <utility :title="'Website'"></utility>
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general"
                type="button">General</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#header" type="button">Header
                & SEO</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#footer" type="button">Footer</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#faq" type="button">FAQ</button>
        </li>
    </ul>
    <div class="card mt-2">
        <div class="card-body py-0">

            <form @submit.prevent="saveAll" enctype="multipart/form-data">
                <div class="tab-content mt-4">
                    <div class="tab-pane fade show active" id="general">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Store Name</label>
                                <input type="text" class="form-control" v-model="formData.name_store" />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Store Logo</label>
                                <input type="file" class="form-control" @change="handleLogoUpload" />
                                <div v-if="logoPreview" class="mt-2">
                                    <img :src="logoPreview" class="img-fluid rounded border" style="max-height:80px" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="header">
                        <div class="mb-3">
                            <label class="form-label">Page Title</label>
                            <input class="form-control" v-model="formData.header.title" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Description</label>
                            <textarea class="form-control" rows="3" v-model="formData.header.description"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Keywords</label>
                                <input class="form-control" v-model="formData.header.keywords" />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Author</label>
                                <input class="form-control" v-model="formData.header.author" />
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Favicon</label>
                            <input type="file" class="form-control" @change="handleFaviconUpload" />
                            <div v-if="faviconPreview" class="mt-2">
                                <img :src="faviconPreview" style="max-height:40px" />
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Theme Color</label>
                            <input type="color" class="form-control form-control-color"
                                v-model="formData.header.theme_color" />
                        </div>
                        <hr />
                        <h6>Open Graph</h6>
                        <div class="mb-3">
                            <label>OG URL</label>
                            <input class="form-control" v-model="formData.header.og.url" />
                        </div>
                        <div class="mb-3">
                            <label>OG Title</label>
                            <input class="form-control" v-model="formData.header.og.title" />
                        </div>
                        <div class="mb-3">
                            <label>OG Site name</label>
                            <input class="form-control" v-model="formData.header.og.site_name" />
                        </div>
                        <div class="mb-3">
                            <label>OG Deskripsi</label>
                            <input class="form-control" v-model="formData.header.og.description" />
                        </div>
                        <div class="mb-3">
                            <label>OG Image</label>
                            <input type="file" class="form-control" @change="handleOgImageUpload" />
                            <div v-if="ogImagePreview" class="mt-2">
                                <img :src="ogImagePreview" class="img-fluid rounded border" style="max-height:100px" />
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>OG Image Alt</label>
                            <textarea class="form-control" v-model="formData.header.og.image.alt"></textarea>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="footer">
                        <h5>Section 1</h5>
                        <div class="mb-3">
                            <label>Title</label>
                            <input class="form-control" v-model="formData.footer.section1.title" />
                        </div>
                        <div class="mb-3">
                            <label>Text</label>
                            <input class="form-control" v-model="formData.footer.section1.text" />
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea class="form-control" v-model="formData.footer.section1.text2"></textarea>
                        </div>
                        <hr />
                        <h5>Section 3 (Contact)</h5>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea class="form-control" v-model="formData.footer.section3.desc"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Cover Image</label>
                            <input type="file" class="form-control" @change="handleCoverImageUpload" />
                            <div v-if="coverImagePreview" class="mt-2">
                                <img :src="coverImagePreview" style="max-height:100px" />
                            </div>
                        </div>
                        <div>
                            <label>Contact Links</label>
                            <div v-for="(c, i) in formData.footer.section3.contact" :key="i" class="d-flex gap-2 mb-2">
                                <input class="form-control" v-model="c.icon" placeholder="Icon" />
                                <input class="form-control" v-model="c.url" placeholder="URL" />
                                <button type="button" class="btn btn-danger" @click="removeContact(i)">x</button>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-success" @click="addContact">+ Add
                                Contact</button>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="faq">
                        <h5>FAQ Settings</h5>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <input class="form-control" v-model="formData.footer.section2.title"
                                    placeholder="FAQ Title" />
                            </div>
                            <div class="col-md-4">
                                <input class="form-control" v-model="formData.footer.section2.title2"
                                    placeholder="Secondary Title" />
                            </div>
                            <div class="col-md-4">
                                <input class="form-control" v-model="formData.footer.section2.title3"
                                    placeholder="Subtitle" />
                            </div>
                        </div>

                        <div class="accordion mb-4" id="faqEditAccordion">
                            <div v-for="(faq, index) in formData.footer.section2.accordion" :key="`edit-${index}`"
                                class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        :data-bs-target="`#editCollapse${index}`" :aria-expanded="false"
                                        :aria-controls="`editCollapse${index}`">
                                        <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                            <span>
                                                <strong>FAQ {{ index + 1 }}:</strong>
                                                {{ faq.title || 'Untitled Question' }}
                                            </span>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    @click.stop="moveFAQ(index, 'up')" :disabled="index === 0">
                                                    <i class="fas fa-arrow-up"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    @click.stop="moveFAQ(index, 'down')"
                                                    :disabled="index === formData.footer.section2.accordion.length - 1">
                                                    <i class="fas fa-arrow-down"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    @click.stop="removeFAQ(index)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div :id="`editCollapse${index}`" class="accordion-collapse collapse"
                                    data-bs-parent="#faqEditAccordion">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <label :for="`faq_question_${index}`" class="form-label">
                                                <strong>Question</strong>
                                            </label>
                                            <input type="text" class="form-control" :id="`faq_question_${index}`"
                                                v-model="faq.title" placeholder="Enter your question here...">
                                        </div>
                                        <div class="mb-3">
                                            <label :for="`faq_answer_${index}`" class="form-label">
                                                <strong>Answer</strong>
                                            </label>
                                            <textarea class="form-control" :id="`faq_answer_${index}`" rows="4"
                                                v-model="faq.fill" placeholder="Enter your answer here..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" @click="addFAQ">+ Add
                                FAQ</button>
                        </div>


                        <!-- Preview Section -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">FAQ Preview</h5>
                                <small class="text-muted">Begini tampilan FAQ Anda di situs web</small>
                            </div>
                            <div class="card-body">
                                <!-- Preview Headers -->
                                <div class="text-center mb-4">
                                    <h3>{{ formData.footer.section2.title || 'FAQ' }}</h3>
                                    <h4 class="text-muted">{{ formData.footer.section2.title2 || 'Pertanyaan Umum'
                                    }}</h4>
                                    <p class="text-muted">{{ formData.footer.section2.title3 }}</p>
                                </div>

                                <!-- Preview Accordion -->
                                <div class="accordion" id="faqPreviewAccordion"
                                    v-if="formData.footer.section2.accordion.length > 0">
                                    <div v-for="(faq, index) in formData.footer.section2.accordion.filter(f => f.title && f.fill)"
                                        :key="`preview-${index}`" class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse" :data-bs-target="`#previewCollapse${index}`"
                                                :aria-expanded="false" :aria-controls="`previewCollapse${index}`">
                                                {{ faq.title }}
                                            </button>
                                        </h2>
                                        <div :id="`previewCollapse${index}`" class="accordion-collapse collapse"
                                            data-bs-parent="#faqPreviewAccordion">
                                            <div class="accordion-body">
                                                {{ faq.fill }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State -->
                                <div v-else class="text-center text-muted py-5">
                                    <i class="fas fa-question-circle fa-3x mb-3"></i>
                                    <p>No FAQ items to preview. Add some questions above to see the preview.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save All Configuration
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</template>