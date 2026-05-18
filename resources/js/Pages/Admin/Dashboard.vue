<template>
  <AdminLayout>
    <div class="p-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-8">Dashboard</h1>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm">Total Orders</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.total_orders }}</p>
            </div>
            <svg class="w-12 h-12 text-blue-500 opacity-20" fill="currentColor" viewBox="0 0 20 20">
              <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1h7.586a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM5 16a2 2 0 11-4 0 2 2 0 014 0zm12 0a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm">Pending Orders</p>
              <p class="text-3xl font-bold text-yellow-600">{{ stats.pending_orders }}</p>
            </div>
            <svg class="w-12 h-12 text-yellow-500 opacity-20" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.5a1 1 0 002 0V7z" clip-rule="evenodd" />
            </svg>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm">Total Revenue</p>
              <p class="text-3xl font-bold text-green-600">{{ formatCurrency(stats.revenue) }}</p>
            </div>
            <svg class="w-12 h-12 text-green-500 opacity-20" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
            </svg>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm">Total Products</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.total_products }}</p>
            </div>
            <svg class="w-12 h-12 text-purple-500 opacity-20" fill="currentColor" viewBox="0 0 20 20">
              <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1h7.586a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM5 16a2 2 0 11-4 0 2 2 0 014 0zm12 0a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm">Low Stock</p>
              <p class="text-3xl font-bold text-red-600">{{ stats.low_stock }}</p>
            </div>
            <svg class="w-12 h-12 text-red-500 opacity-20" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 2.526a6 6 0 008.367 12.364z" clip-rule="evenodd" />
            </svg>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm">Total Customers</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.total_customers }}</p>
            </div>
            <svg class="w-12 h-12 text-indigo-500 opacity-20" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM9 11a6 6 0 11-12 0 6 6 0 0112 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-bold text-gray-900">Recent Orders</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Order ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm text-blue-600 font-medium">
                  <Link :href="`/admin/orders/${order.order_number}`">
                    {{ order.order_number }}
                  </Link>
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ order.user?.name || 'N/A' }}</td>
                <td class="px-6 py-4 text-sm">
                  <span
                    :class="[
                      'px-2 py-1 rounded-full text-xs font-medium',
                      statusColor(order.status),
                    ]"
                  >
                    {{ order.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ formatCurrency(order.total) }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(order.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
import type { DashboardStats, Order } from '@/types';

interface Props {
  stats: DashboardStats;
  recent_orders: Order[];
}

const props = defineProps<Props>();
const recentOrders = props.recent_orders;

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('th-TH', {
    style: 'currency',
    currency: 'THB',
  }).format(value);
};

const formatDate = (date: string) => {
  return new Intl.DateTimeFormat('en-US').format(new Date(date));
};

const statusColor = (status: string) => {
  const colors: { [key: string]: string } = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    processing: 'bg-indigo-100 text-indigo-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  };
  return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>
