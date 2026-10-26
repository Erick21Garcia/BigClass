<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AcadexSplitLayout from '@/layouts/AcadexSplitLayout.vue';
import { home, register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Eye, EyeOff, GraduationCap, Lock, Mail } from 'lucide-vue-next';
import { ref } from 'vue';
import acadexLogo from '@/acadex-logo-512.png';

defineOptions({ layout: AcadexSplitLayout });

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const showPassword = ref(false);

const inputClass =
    'h-11 rounded-xl border-[1.5px] border-transparent bg-[#E8EEFB] pl-10 text-sm shadow-none placeholder:text-[#7B8AA3] focus-visible:border-[#1E3A5F] focus-visible:bg-white focus-visible:ring-4 focus-visible:ring-[#1E3A5F]/12';
</script>

<template>
    <div class="flex flex-col px-6 py-6 sm:px-10 lg:px-16 lg:py-9">
        <Head title="Iniciar sesión" />

        <div class="flex items-center justify-between text-sm text-[#5B6B82]">
            <Link :href="home()" class="flex items-center gap-1.5 font-medium text-[#1E3A5F] hover:text-[#214EA4]">
                <ArrowLeft class="h-4 w-4" />
                Volver al inicio
            </Link>

            <span v-if="canRegister">
                ¿No tienes una cuenta?
                <TextLink :href="register()" :tabindex="5" class="font-medium text-[#214EA4] hover:text-[#1E3A5F]">
                    Regístrate
                </TextLink>
            </span>
        </div>

        <div class="mx-auto my-auto w-full max-w-[400px] py-10">
            <img
                :src="acadexLogo"
                alt="Acadex"
                width="56"
                height="56"
                class="h-14 w-14 rounded-2xl object-contain shadow-[0_14px_30px_-12px_rgba(30,58,95,0.5)]"
            />
            <h1 class="acadex-wordmark mt-5 whitespace-nowrap text-[30px]">Inicia sesión en tu cuenta</h1>
            <p class="mt-2 text-[15px] leading-normal text-[#5B6B82]">
                Ingresa tu correo y contraseña para iniciar sesión
            </p>

            <div v-if="status" class="mt-5 text-sm font-medium text-green-600">
                {{ status }}
            </div>

            <Form
                v-bind="store.form()"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="mt-7 flex flex-col"
            >
                <div class="grid gap-[18px]">
                    <div class="grid gap-1.5">
                        <Label for="email" class="text-[13px] text-[#1F2A3D]">Correo electrónico</Label>
                        <div class="relative">
                            <Mail
                                class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[#1E3A5F]"
                            />
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="email"
                                placeholder="correo@ejemplo.com"
                                :class="inputClass"
                            />
                        </div>
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-[13px] text-[#1F2A3D]">Contraseña</Label>
                            <TextLink
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-[12.5px] font-medium text-[#214EA4] hover:text-[#1E3A5F]"
                                :tabindex="5"
                            >
                                ¿Olvidaste tu contraseña?
                            </TextLink>
                        </div>
                        <div class="relative">
                            <Lock
                                class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[#6B7A90]"
                            />
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                name="password"
                                required
                                :tabindex="2"
                                autocomplete="current-password"
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
                                <EyeOff v-if="showPassword" class="h-[17px] w-[17px]" />
                                <Eye v-else class="h-[17px] w-[17px]" />
                            </button>
                        </div>
                        <InputError :message="errors.password" />
                    </div>

                    <Label for="remember" class="flex items-center space-x-3 text-[13.5px] font-normal text-[#1F2A3D]">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>Recordarme</span>
                    </Label>

                    <Button
                        type="submit"
                        class="mt-1 h-[46px] w-full rounded-xl bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] font-medium text-white shadow-[0_12px_26px_-12px_rgba(33,78,164,0.75)] hover:brightness-105"
                        :tabindex="4"
                        :disabled="processing"
                        data-test="login-button"
                    >
                        <Spinner v-if="processing" />
                        Iniciar sesión
                        <ArrowRight v-if="!processing" class="h-4 w-4" />
                    </Button>
                </div>
            </Form>

            <div class="mt-6 flex items-center gap-3 text-xs text-[#8A97AB]">
                <span class="h-px flex-1 bg-[#E3E8F1]"></span>
                ¿Quieres estudiar con nosotros?
                <span class="h-px flex-1 bg-[#E3E8F1]"></span>
            </div>

            <Link
                href="/admisiones/registro"
                class="group mt-4 flex items-center gap-3.5 rounded-[14px] border border-[#E3E8F1] bg-white px-4 py-3.5 transition hover:border-[#214EA4]/40"
            >
                <div class="grid h-[38px] w-[38px] shrink-0 place-items-center rounded-[10px] bg-[#E8EEFB] text-[#214EA4]">
                    <GraduationCap class="h-[19px] w-[19px]" />
                </div>
                <div>
                    <p class="text-[13.5px] font-medium text-[#1E3A5F]">Postula a una carrera</p>
                    <p class="mt-0.5 text-xs text-[#5B6B82]">Crea tu cuenta de admisiones</p>
                </div>
                <ArrowRight class="ml-auto h-4 w-4 text-[#214EA4] transition-transform group-hover:translate-x-0.5" />
            </Link>
        </div>

        <p class="text-center text-xs text-[#8A97AB]">© Acadex</p>
    </div>
</template>