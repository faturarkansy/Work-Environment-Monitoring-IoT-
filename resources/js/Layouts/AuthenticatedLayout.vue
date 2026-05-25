<script setup>
import { ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="border-b border-gray-100 bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between lg:pt-4 lg:pb-2 p-4">
                    <div class="flex items-center">
                        <div class="flex shrink-0 items-center">
                            <Link :href="$page.props.auth.user.role === 'admin' ? route('dashboard') : route('worker.display')">
                                <img src="/images/Logo.png" alt="Logo" class="block h-9 w-auto" />
                            </Link>
                        </div>
                        <div class="ms-4">
                            <h1 class="text-xl font-bold text-gray-800 leading-tight">
                                Work Environment Monitor (Live)
                            </h1>
                        </div>
                    </div>

                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button type="button" class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-md font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none">
                                            {{ $page.props.auth.user.name }}

                                            <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button"> Log Out </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <div v-if="$page.props.auth.user.role === 'admin'" class="-me-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div 
                    v-if="$page.props.auth.user.role === 'admin'" 
                    class="hidden sm:flex h-12 justify-center items-center border-t border-gray-50 bg-white"
                >
                    <div class="flex justify-center w-full">
                        <div class="space-x-6 sm:-my-px sm:flex h-12">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')" class="text-lg font-bold"> Dashboard </NavLink>
                            <NavLink :href="route('all-workers.index')" :active="route().current('all-workers.index')" class="text-lg font-bold"> All Workers </NavLink>
                            <NavLink :href="route('history.index')" :active="route().current('history.index')" class="text-lg font-bold"> History </NavLink>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="$page.props.auth.user.role === 'admin'" :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden border-t border-gray-200">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')"> Dashboard </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('all-workers.index')" :active="route().current('all-workers.index')"> All Workers </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('history.index')" :active="route().current('history.index')"> History </ResponsiveNavLink>
                </div>
                <div class="border-t border-gray-200 pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-gray-800">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user.email }}</div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button"> Log Out </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header class="bg-white shadow-sm" v-if="$slots.header">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>