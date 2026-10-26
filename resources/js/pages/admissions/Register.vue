<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, ChevronDown, Eye, EyeOff, Lock, Mail, User } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AcadexSplitLayout from '@/layouts/AcadexSplitLayout.vue';
import acadexLogo from '@/acadex-logo-512.png';

defineOptions({ layout: AcadexSplitLayout });

const props = defineProps<{
    careers: { id: number; name: string }[];
    turnstile_site_key: string | null;
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    career_id: '',
    captcha_token: '',
});

const showPassword = ref(false);
const showConfirmation = ref(false);

const inputClass =
    'h-[42px] rounded-xl border-[1.5px] border-transparent bg-[#E8EEFB] pl-10 text-sm shadow-none placeholder:text-[#7B8AA3] focus-visible:border-[#1E3A5F] focus-visible:bg-white focus-visible:ring-4 focus-visible:ring-[#1E3A5F]/12';

type TurnstileApi = {
    render: (el: HTMLElement, options: Record<string, unknown>) => string;
    reset: (id?: string) => void;
    remove: (id?: string) => void;
};

const getTurnstile = () => (window as unknown as { turnstile?: TurnstileApi }).turnstile;

const captchaContainer = ref<HTMLElement | null>(null);
let widgetId: string | null = null;

function loadTurnstileScript(): Promise<void> {
    return new Promise((resolve, reject) => {
        if (getTurnstile()) return resolve();

        const existing = document.querySelector('script[data-turnstile]');
        if (existing) {
            existing.addEventListener('load', () => resolve());
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.dataset.turnstile = 'true';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('No se pudo cargar Turnstile'));
        document.head.appendChild(script);
    });
}

onMounted(async () => {
    if (!props.turnstile_site_key || !captchaContainer.value) return;

    await loadTurnstileScript();

    widgetId = getTurnstile()!.render(captchaContainer.value, {
        sitekey: props.turnstile_site_key,
        callback: (token: string) => { form.captcha_token = token; },
        'expired-callback': () => { form.captcha_token = ''; },
        'error-callback': () => { form.captcha_token = ''; },
    });
});

onBeforeUnmount(() => {
    if (widgetId) getTurnstile()?.remove(widgetId);
});

function submit() {
    form.post('/admisiones/registro', {
        onError: () => {
            form.captcha_token = '';
            if (widgetId) getTurnstile()?.reset(widgetId);
        },
    });
}
</script>

<template>
    <div class="flex flex-col px-6 py-6 sm:px-10 lg:px-16 lg:py-9">
        <Head title="Regístrate para postular" />

        <div class="flex items-center justify-between text-sm text-[#5B6B82]">
            <Link href="/" class="flex items-center gap-1.5 font-medium text-[#1E3A5F] hover:text-[#214EA4]">
                <ArrowLeft class="h-4 w-4" />
                Volver al inicio
            </Link>
        </div>

        <div class="mx-auto my-auto w-full max-w-[440px] py-8">
            <img
                :src="acadexLogo"
                alt="Acadex"
                width="48"
                height="48"
                class="mb-4 h-12 w-12 rounded-2xl object-contain lg:hidden"
            />
            <h1 class="acadex-wordmark text-[30px]">Regístrate para postular</h1>
            <p class="mt-2 text-[15px] text-[#5B6B82]">
                Crea tu cuenta para empezar tu proceso de admisión.
            </p>

            <form class="mt-6 grid gap-[15px]" @submit.prevent="submit">
                <div class="grid gap-1.5">
                    <Label for="name" class="text-[13px] text-[#1F2A3D]">Nombre completo</Label>
                    <div class="relative">
                        <User class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[#6B7A90]" />
                        <Input id="name" v-model="form.name" autocomplete="name" :class="inputClass" />
                    </div>
                    <p v-if="form.errors.name" class="text-xs text-[#D85A30]">{{ form.errors.name }}</p>
                </div>

                <div class="grid gap-1.5">
                    <Label for="email" class="text-[13px] text-[#1F2A3D]">Correo electrónico</Label>
                    <div class="relative">
                        <Mail class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[#6B7A90]" />
                        <Input id="email" type="email" v-model="form.email" autocomplete="email" :class="inputClass" />
                    </div>
                    <p v-if="form.errors.email" class="text-xs text-[#D85A30]">{{ form.errors.email }}</p>
                </div>

                <div class="grid gap-1.5">
                    <Label for="career" class="text-[13px] text-[#1F2A3D]">Carrera a la que deseas postular</Label>
                    <div class="relative">
                        <select
                            id="career"
                            v-model="form.career_id"
                            class="h-[42px] w-full appearance-none rounded-xl border-[1.5px] border-[#D9DEE7] bg-white pl-3.5 pr-10 text-sm text-[#1F2A3D] outline-none focus:border-[#1E3A5F] focus:ring-4 focus:ring-[#1E3A5F]/12"
                        >
                            <option value="" disabled>Selecciona una carrera</option>
                            <option v-for="c in careers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 size-4 -translate-y-1/2 text-[#1F2A3D]" />
                    </div>
                    <p v-if="form.errors.career_id" class="text-xs text-[#D85A30]">{{ form.errors.career_id }}</p>
                </div>

                <div class="grid gap-[15px] sm:grid-cols-2 sm:gap-3">
                    <div class="grid content-start gap-1.5">
                        <Label for="password" class="text-[13px] text-[#1F2A3D]">Contraseña</Label>
                        <div class="relative">
                            <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[#6B7A90]" />
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                autocomplete="new-password"
                                placeholder="Contraseña"
                                :class="[inputClass, 'pr-10']"
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#6B7A90] hover:text-[#1E3A5F]"
                                :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-[#D85A30]">{{ form.errors.password }}</p>
                    </div>

                    <div class="grid content-start gap-1.5">
                        <Label for="password_confirmation" class="text-[13px] text-[#1F2A3D]">Confirmar contraseña</Label>
                        <div class="relative">
                            <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[#6B7A90]" />
                            <Input
                                id="password_confirmation"
                                :type="showConfirmation ? 'text' : 'password'"
                                v-model="form.password_confirmation"
                                autocomplete="new-password"
                                placeholder="Repítela"
                                :class="[inputClass, 'pr-10']"
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#6B7A90] hover:text-[#1E3A5F]"
                                :aria-label="showConfirmation ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                @click="showConfirmation = !showConfirmation"
                            >
                                <EyeOff v-if="showConfirmation" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <div v-if="turnstile_site_key" ref="captchaContainer"></div>
                    <p v-if="form.errors.captcha_token" class="text-xs text-[#D85A30]">
                        {{ form.errors.captcha_token }}
                    </p>
                </div>

                <Button
                    type="submit"
                    class="mt-1.5 h-[46px] w-full rounded-xl border-0 bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] font-normal text-white shadow-[0_12px_26px_-12px_rgba(33,78,164,0.75)] hover:brightness-105"
                    :disabled="form.processing || (!!turnstile_site_key && !form.captcha_token)"
                >
                    Crear cuenta y empezar
                    <ArrowRight class="h-4 w-4" />
                </Button>
            </form>
        </div>

        <p class="text-center text-xs text-[#8A97AB]">© Acadex</p>
    </div>
</template>