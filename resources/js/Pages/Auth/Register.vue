<script setup>
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import LottiePlayer from "@/Components/LottiePlayer.vue"; // Impor LottiePlayer agar serasi dengan Login
import { Head, Link, useForm } from "@inertiajs/vue3";

const form = useForm({
    name: "",
    email: "",
    role: "worker", // Default role diatur ke worker
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Register" />

    <div
        class="h-screen flex justify-center items-center bg-[linear-gradient(180deg,#ffffff_20%,#b1b2f8_95%)] font-sans p-6 overflow-hidden max-[950px]:h-auto max-[950px]:overflow-auto max-[950px]:p-5"
    >
        <div
            class="flex w-full max-w-[1100px] h-[95vh] max-h-[760px] max-[950px]:flex-col max-[950px]:max-w-[600px] max-[950px]:h-auto"
        >
            <div
                class="flex-[1.1] flex flex-col justify-start gap-y-4 items-start p-6 max-[950px]:p-[30px] max-[640px]:!p-2 max-[950px]:items-center"
            >
                <div
                    class="flex items-start ml-5 max-[950px]:ml-0 max-[950px]:items-center max-[950px]:justify-center max-[950px]:w-full"
                >
                    <img
                        src="/images/Logo1.png"
                        alt="EnviMatework Logo"
                        class="h-[40px] mt-2 mr-3.5 transition-all duration-200 max-[950px]:h-[30px] max-[950px]:mt-0 max-[950px]:mr-2"
                    />

                    <div class="flex flex-col">
                        <span
                            class="text-[20px] mt-0.5 font-semibold text-[#333] font-sans"
                        >
                            Envi<span
                                class="text-indigo-500 font-bungee font-normal"
                                >M</span
                            >atework
                        </span>

                        <div
                            class="flex items-start gap-x-3 mt-1 max-[950px]:hidden"
                        >
                            <div
                                class="w-[95px] h-[3px] bg-[#6a53f2] rounded-full shrink-1 mt-[5px]"
                            ></div>
                            <span
                                class="text-[12px] text-black font-semibold tracking-[1px] leading-[1.3] max-w-[145px]"
                            >
                                Work Climate Environment Monitor
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="flex-1 w-full flex justify-center items-center mr-8 max-[950px]:hidden"
                >
                    <LottiePlayer
                        src="/lottie/monitor_animation.json"
                        className="w-full max-w-[500px] max-[950px]:max-w-[340px]"
                    />
                </div>
            </div>

            <div
                class="flex-[0.9] flex justify-center items-center p-6 max-[950px]:p-5"
            >
                <div
                    class="bg-white w-full max-w-[400px] py-12 px-[35px] min-h-[350px] flex flex-col justify-center rounded-[35px] border border-gray-200 shadow-[0_0_4px_#00000040] max-[950px]:py-8 max-[950px]:px-7 max-[950px]:min-h-0"
                >
                    <h2 class="text-[28px] font-bold text-[#333] text-center">
                        Registration
                    </h2>
                    <p
                        class="text-[14px] text-[#6c757d] text-center mt-2 mb-[20px]"
                    >
                        Register your account to get started
                    </p>

                    <form @submit.prevent="submit" class="flex flex-col">
                        <div>
                            <div class="relative mb-4">
                                <input
                                    id="name"
                                    type="text"
                                    class="w-full border border-[#ccd3dd] rounded-3xl px-[25px] h-[40px] transition-colors duration-200 focus:border-[#6a53f2] focus:ring-0 focus:outline-none text-[14px] text-[#333]"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                    placeholder="Name"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.name"
                                />
                            </div>
                        </div>

                        <div>
                            <div class="relative mb-4">
                                <input
                                    id="email"
                                    type="email"
                                    class="w-full border border-[#ccd3dd] rounded-3xl px-[25px] h-[40px] transition-colors duration-200 focus:border-[#6a53f2] focus:ring-0 focus:outline-none text-[14px] text-[#333]"
                                    v-model="form.email"
                                    required
                                    autocomplete="username"
                                    placeholder="Email"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.email"
                                />
                            </div>
                        </div>

                        <div>
                            <div class="relative mb-4">
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="w-full border border-[#ccd3dd] rounded-3xl px-[25px] h-[40px] transition-colors duration-200 focus:border-[#6a53f2] focus:ring-0 focus:outline-none bg-white text-[14px] text-[#333] appearance-none cursor-pointer"
                                    required
                                >
                                    <option value="" disabled hidden>
                                        Register as...
                                    </option>
                                    <option value="worker">Worker</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.role"
                                />
                            </div>
                        </div>

                        <div>
                            <div class="relative mb-4">
                                <input
                                    id="password"
                                    type="password"
                                    class="w-full border border-[#ccd3dd] rounded-3xl px-[25px] h-[40px] transition-colors duration-200 focus:border-[#6a53f2] focus:ring-0 focus:outline-none text-[14px] text-[#333]"
                                    v-model="form.password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Password"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.password"
                                />
                            </div>
                        </div>

                        <div>
                            <div class="relative mb-5">
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    class="w-full border border-[#ccd3dd] rounded-3xl px-[25px] h-[40px] transition-colors duration-200 focus:border-[#6a53f2] focus:ring-0 focus:outline-none text-[14px] text-[#333]"
                                    v-model="form.password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Confirm Password"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.password_confirmation"
                                />
                            </div>
                        </div>

                        <div class="w-full">
                            <button
                                type="submit"
                                class="w-1/2 mx-auto flex items-center justify-center bg-[#6a53f2] text-white text-[15px] font-bold h-[40px] rounded-3xl border-none transition-colors duration-200 cursor-pointer hover:bg-[#583fe4]"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Register
                            </button>
                        </div>
                    </form>

                    <div class="mt-5 text-center text-sm text-gray-600">
                        Already registered?
                        <Link
                            :href="route('login')"
                            class="text-[#6a53f2] no-underline font-bold hover:underline"
                        >
                            Login
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
