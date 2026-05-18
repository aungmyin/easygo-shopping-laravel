<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <div class="w-64 bg-gray-900 text-white shadow-lg">
      <div class="p-6 border-b border-gray-800">
        <h1 class="text-2xl font-bold">Easy Go</h1>
        <p class="text-gray-400 text-sm">Admin Panel</p>
      </div>

      <nav class="mt-6">
        <Link
          href="/admin"
          :class="[
            'flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 transition',
            route().current('admin.dashboard') && 'bg-blue-600 text-white',
          ]"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l4-4m0 0l4 4m-4-4v4" />
          </svg>
          Dashboard
        </Link>

        <div class="px-6 py-4 mt-6">
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Catalog</h3>
        </div>

        <Link
          href="/admin/products"
          :class="[
            'flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 transition',
            route().current('admin.products.*') && 'bg-blue-600 text-white',
          ]"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4m0 0L4 7m16 0l-8 4m8-4v10l-8 4m0-10L4 7v10l8 4" />
          </svg>
          Products
        </Link>

        <Link
          href="/admin/categories"
          :class="[
            'flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 transition',
            route().current('admin.categories.*') && 'bg-blue-600 text-white',
          ]"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.5a2 2 0 00-1 .25M21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2v-4a2 2 0 00-2-2h-2.5a2 2 0 00-1 .25" />
          </svg>
          Categories
        </Link>

        <div class="px-6 py-4 mt-6">
          <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Business</h3>
        </div>

        <Link
          href="/admin/orders"
          :class="[
            'flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 transition',
            route().current('admin.orders.*') && 'bg-blue-600 text-white',
          ]"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
          Orders
        </Link>

        <Link
          href="/admin/users"
          :class="[
            'flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 transition',
            route().current('admin.users.*') && 'bg-blue-600 text-white',
          ]"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM18 20a6 6 0 00-12 0v2h12v-2z" />
          </svg>
          Customers
        </Link>
      </nav>

      <div class="absolute bottom-0 w-64 p-6 border-t border-gray-800">
        <button
          @click="logout"
          class="w-full flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          Logout
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-auto">
      <!-- Flash Messages -->
      <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-800 px-4 py-4 rounded-md m-4">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-4 rounded-md m-4">
        {{ $page.props.flash.error }}
      </div>

      <!-- Page Content -->
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const logout = () => {
  router.post('/admin/logout');
};
</script>
