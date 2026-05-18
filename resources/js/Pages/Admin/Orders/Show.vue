<template>
  <AdminLayout>
    <div class="p-8">
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ order.order_number }}</h1>
          <p class="text-gray-600 mt-1">{{ formatDate(order.created_at) }}</p>
        </div>
        <Link href="/admin/orders" class="text-blue-600 hover:text-blue-900">
          ← Back to Orders
        </Link>
      </div>

      <div class="grid grid-cols-3 gap-6 mb-8">
        <!-- Customer Info -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Customer</h3>
          <p class="text-gray-900 font-medium">{{ order.user?.name }}</p>
          <p class="text-gray-600 text-sm">{{ order.user?.email }}</p>
          <p v-if="order.user?.phone" class="text-gray-600 text-sm">{{ order.user.phone }}</p>
        </div>

        <!-- Order Status -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Order Status</h3>
          <form @submit.prevent="updateStatus" class="space-y-4">
            <select
              v-model="statusForm.status"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="processing">Processing</option>
              <option value="shipped">Shipped</option>
              <option value="delivered">Delivered</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <button
              type="submit"
              :disabled="statusForm.processing"
              class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
            >
              {{ statusForm.processing ? 'Updating...' : 'Update Status' }}
            </button>
          </form>
        </div>

        <!-- Payment Status -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Payment Status</h3>
          <span :class="['px-3 py-1 rounded-full text-sm font-medium', paymentColor(order.payment_status)]">
            {{ order.payment_status }}
          </span>
        </div>
      </div>

      <!-- Order Items -->
      <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-bold text-gray-900">Order Items</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Product</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Price</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Quantity</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="item in order.items" :key="item.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <p class="font-medium text-gray-900">{{ item.product?.name }}</p>
                  <p class="text-sm text-gray-600">{{ item.product?.slug }}</p>
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ formatCurrency(item.price) }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ item.quantity }}</td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ formatCurrency(item.price * item.quantity) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="bg-white rounded-lg shadow p-6 max-w-md ml-auto">
        <div class="space-y-3">
          <div class="flex justify-between text-gray-900">
            <span>Subtotal</span>
            <span>{{ formatCurrency(order.subtotal) }}</span>
          </div>
          <div class="flex justify-between text-gray-900">
            <span>Delivery Fee</span>
            <span>{{ formatCurrency(order.delivery_fee) }}</span>
          </div>
          <div class="border-t border-gray-200 pt-3 flex justify-between text-lg font-bold text-gray-900">
            <span>Total</span>
            <span>{{ formatCurrency(order.total) }}</span>
          </div>
        </div>
      </div>

      <!-- Order Notes -->
      <div v-if="order.notes" class="mt-8 bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Order Notes</h3>
        <p class="text-gray-700">{{ order.notes }}</p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
import type { Order } from '@/types';

interface Props {
  order: Order;
}

const props = defineProps<Props>();

const statusForm = useForm({
  status: props.order.status,
});

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('th-TH', {
    style: 'currency',
    currency: 'THB',
  }).format(value);
};

const formatDate = (date: string) => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(date));
};

const paymentColor = (status: string) => {
  return status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
};

const updateStatus = () => {
  statusForm.put(`/admin/orders/${props.order.order_number}/status`, {
    onSuccess: () => {
      window.location.reload();
    },
  });
};
</script>
