<template>
  <AdminLayout>
    <div class="p-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-8">Orders</h1>

      <!-- Filters -->
      <div class="mb-6 bg-white rounded-lg shadow p-4 flex space-x-4">
        <div class="flex-1">
          <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
          <select
            :value="statusFilter"
            @change="(e) => filterByStatus((e.target as HTMLSelectElement).value)"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      <!-- Orders Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Order ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Payment</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm text-blue-600 font-medium">
                  <Link :href="`/admin/orders/${order.order_number}`">
                    {{ order.order_number }}
                  </Link>
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ order.user?.name || 'N/A' }}</td>
                <td class="px-6 py-4 text-sm">
                  <span :class="['px-2 py-1 rounded-full text-xs font-medium', statusColor(order.status)]">
                    {{ order.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm">
                  <span :class="['px-2 py-1 rounded text-xs font-medium', paymentColor(order.payment_status)]">
                    {{ order.payment_status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ formatCurrency(order.total) }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(order.created_at) }}</td>
                <td class="px-6 py-4 text-sm">
                  <Link
                    :href="`/admin/orders/${order.order_number}`"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    View
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="orders.meta.last_page > 1" class="mt-6 flex justify-center space-x-2">
        <Link
          v-if="orders.links.prev"
          :href="orders.links.prev"
          class="px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50"
        >
          Previous
        </Link>
        <span class="px-3 py-2">
          Page {{ orders.meta.current_page }} of {{ orders.meta.last_page }}
        </span>
        <Link
          v-if="orders.links.next"
          :href="orders.links.next"
          class="px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50"
        >
          Next
        </Link>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import type { PaginatedResponse, Order } from '@/types';

interface Props {
  orders: PaginatedResponse<Order>;
  statusFilter?: string;
}

const props = defineProps<Props>();

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

const paymentColor = (status: string) => {
  return status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
};

const filterByStatus = (status: string) => {
  if (status) {
    router.get('/admin/orders', { status });
  } else {
    router.get('/admin/orders');
  }
};
</script>
